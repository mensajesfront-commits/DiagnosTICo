<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\SolicitudMedicion;
use App\Models\User;
use App\Notifications\AvisoMedicion;
use App\Services\Mediciones\AsignarMedicion;
use App\Services\Mediciones\EnviarAviso;
use App\Support\CorreoAviso;
use App\Support\DatosEmpresas;
use App\Support\Plazos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A3.1b · Asignar medición (HU-036), A3.1c · reemplazar o cancelar la
 * pendiente, "Cambiar fecha" (HU-009) y A3.1e · el correo de aviso:
 * editarlo, probarlo y enviarlo como recordatorio (HU-037, HU-008).
 * Permiso `mediciones.asignar`.
 */
class MedicionesController extends Controller
{
    public function __construct(
        private readonly AsignarMedicion $asignar,
        private readonly EnviarAviso $enviarAviso,
    ) {}

    /** A3.1b · Con `?empresa=` llega con la empresa elegida. */
    public function crear(Request $request): Response
    {
        $empresas = Empresa::query()->where('activa', true)->with('sector:id,nombre')->orderBy('nombre')->get();
        $principales = User::query()->whereIn('empresa_id', $empresas->modelKeys())->role('Empresa')
            ->orderBy('id')->get(['id', 'empresa_id', 'name', 'email'])->unique('empresa_id')->keyBy('empresa_id');
        $pendientes = Medicion::query()->whereIn('estado', DatosEmpresas::PENDIENTES)
            ->with(['version:id,diagnostico_id,numero', 'version.diagnostico:id,nombre'])
            ->withExists('respuestas')
            ->whereIn('empresa_id', $empresas->modelKeys())->get()->keyBy('empresa_id');
        $solicitudes = SolicitudMedicion::abiertasDe($empresas->modelKeys());
        Plazos::precargar($empresas->modelKeys());

        return Inertia::render('mediciones/Asignar', [
            'empresas' => $empresas->map(fn (Empresa $e) => [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'sector_id' => $e->sector_id,
                'sector' => $e->sector?->nombre,
                'pendiente' => ($p = $pendientes->get($e->id)) ? [
                    'id' => $p->id,
                    'numero' => $p->numero,
                    'estado' => DatosEmpresas::estadoVisible($p),
                    'vence' => $p->fecha_limite?->toDateString(),
                    'fecha_minima' => Plazos::fechaMinima($e->id),
                    'diagnostico' => $p->version?->diagnostico?->nombre,
                    'version' => $p->version?->numero,
                    'tiene_respuestas' => (bool) $p->getAttribute('respuestas_exists'),
                ] : null,
                // HU-083: la empresa pidió una medición nueva (fecha ISO).
                'solicitud' => $solicitudes[$e->id] ?? null,
                // La fecha límite más cercana que se acepta, en la hora de la empresa.
                'fecha_minima' => Plazos::fechaMinima($e->id),
                'siguiente' => ((int) $e->mediciones()->where('estado', '!=', 'cancelada')->max('numero')) + 1,
                // Para "Para:" y {nombre_usuario} del correo (A3.1e).
                'principal' => ($u = $principales->get($e->id)) ? ['name' => $u->name, 'email' => $u->email] : null,
            ])->values(),
            'diagnosticos' => Diagnostico::query()
                ->whereIn('estado', [Diagnostico::BORRADOR, Diagnostico::PUBLICADO])
                ->get(['sector_id'])->pluck('sector_id')->unique()
                ->mapWithKeys(fn (int $sector) => [$sector => [
                    'publicados' => DatosEmpresas::diagnosticosPublicados($sector),
                    'borradores' => Diagnostico::where('sector_id', $sector)->where('estado', Diagnostico::BORRADOR)
                        ->orderBy('nombre')->get(['id', 'nombre'])->toArray(),
                ]]),
            'empresaInicial' => $request->integer('empresa') ?: null,
            // Antes de elegir la empresa: la fecha mínima en la zona del sistema.
            'fechaMinima' => Plazos::fechaMinima(null),
            'plantilla' => CorreoAviso::plantilla(),
        ]);
    }

    /** Asigna; si ya hay una pendiente, solo con `reemplazar` (A3.1c). */
    public function guardar(Request $request): RedirectResponse
    {
        $empresaId = $request->integer('empresa_id') ?: null;
        $datos = $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'diagnostico_id' => ['required', 'integer', 'exists:diagnosticos,id'],
            'fecha_limite' => Plazos::reglas($empresaId),
            'mensaje' => ['nullable', 'string', 'max:500'],
            'aviso' => ['boolean'],
            'reemplazar' => ['boolean'],
            // A3.1e: el correo editado para esta medición (opcional).
            'correo_asunto' => ['nullable', 'required_with:correo_cuerpo', 'string', 'max:255'],
            'correo_cuerpo' => ['nullable', 'required_with:correo_asunto', 'string', 'max:5000'],
            'guardar_plantilla' => ['boolean'],
        ], [
            'fecha_limite.after_or_equal' => Plazos::mensaje($empresaId),
        ], ['empresa_id' => 'empresa', 'diagnostico_id' => 'diagnóstico', 'fecha_limite' => 'fecha límite']);

        $empresa = Empresa::query()->findOrFail((int) $datos['empresa_id']);
        $medicion = $this->asignar->asignar(
            $empresa,
            Diagnostico::query()->findOrFail((int) $datos['diagnostico_id']),
            $datos['fecha_limite'] ?? null,
            $datos['mensaje'] ?? null,
            (bool) ($datos['aviso'] ?? false),
            $request->user(),
            (bool) ($datos['reemplazar'] ?? false),
            isset($datos['correo_asunto'], $datos['correo_cuerpo'])
                ? ['asunto' => $datos['correo_asunto'], 'cuerpo' => $datos['correo_cuerpo']]
                : null,
        );

        if (($datos['guardar_plantilla'] ?? false) && isset($datos['correo_asunto'], $datos['correo_cuerpo'])) {
            CorreoAviso::guardarPlantilla($datos['correo_asunto'], $datos['correo_cuerpo'], $request->user());
        }

        $mensaje = "Medición {$medicion->numero} asignada a «{$empresa->nombre}».";

        if ($datos['aviso'] ?? false) {
            Inertia::flash('toast', $medicion->ultimo_aviso_en
                ? ['type' => 'success', 'message' => $mensaje.' Enviamos el aviso por correo.']
                : ['type' => 'warning', 'message' => $mensaje.' No pudimos enviar el correo: usa "Reenviar aviso".']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => $mensaje]);
        }

        return to_route('empresas.ver', $empresa);
    }

    /** A3.1c · Cancelar la medición pendiente sin reemplazarla. */
    public function cancelar(Medicion $medicion): RedirectResponse
    {
        $this->soloPendiente($medicion);
        $medicion->forceFill(['estado' => 'cancelada', 'cancelada_en' => now()])->save();
        // Cancelar la vencida también responde la solicitud de la empresa (HU-083).
        SolicitudMedicion::atender($medicion->empresa_id);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Medición {$medicion->numero} cancelada."]);

        return back();
    }

    /**
     * "Cambiar fecha" / "Nueva fecha" (HU-009): la medición vuelve a estar
     * pendiente y sale de Vencidas.
     */
    public function cambiarFecha(Request $request, Medicion $medicion): RedirectResponse
    {
        $this->soloPendiente($medicion);
        $datos = $request->validate([
            'fecha_limite' => Plazos::reglas($medicion->empresa_id),
        ], ['fecha_limite.after_or_equal' => Plazos::mensaje($medicion->empresa_id)], ['fecha_limite' => 'fecha límite']);

        $estado = $medicion->estado === 'vencida'
            ? ($medicion->respuestas()->exists() ? 'en_curso' : 'no_iniciada')
            : $medicion->estado;

        $medicion->forceFill(['fecha_limite' => $datos['fecha_limite'] ?? null, 'estado' => $estado])->save();
        SolicitudMedicion::atender($medicion->empresa_id);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Nueva fecha para la medición {$medicion->numero}."]);

        return back();
    }

    /**
     * A3.1e · Datos del correo de una medición pendiente: para quién, el
     * asunto y el cuerpo (los suyos o la plantilla) y el valor real de cada
     * variable, para la vista previa.
     */
    public function correo(Medicion $medicion): JsonResponse
    {
        $this->soloPendiente($medicion);
        $principal = $medicion->empresa ? DatosEmpresas::principal($medicion->empresa) : null;

        return response()->json([
            ...CorreoAviso::deMedicion($medicion),
            'para' => $principal ? ['name' => $principal->name, 'email' => $principal->email] : null,
            'variables' => CorreoAviso::variables($medicion, $principal),
            'ultimo_aviso' => $medicion->ultimo_aviso_en?->toIso8601String(),
        ]);
    }

    /**
     * "Recordatorio" / "Reenviar aviso" (HU-008): guarda el correo editado en
     * la medición (y, si se pide, como plantilla general) y lo envía.
     */
    public function enviarCorreo(Request $request, Medicion $medicion): RedirectResponse
    {
        $this->soloPendiente($medicion);
        $datos = $this->validarCorreo($request);

        $medicion->forceFill(['correo_asunto' => $datos['asunto'], 'correo_cuerpo' => $datos['cuerpo']])->save();

        if ($datos['guardar_plantilla'] ?? false) {
            CorreoAviso::guardarPlantilla($datos['asunto'], $datos['cuerpo'], $request->user());
        }

        $this->enviarAviso->enviar($medicion);

        $para = DatosEmpresas::principal($medicion->empresa)?->email;
        Inertia::flash('toast', ['type' => 'success', 'message' => "Enviamos el aviso de la medición {$medicion->numero} a {$para}."]);

        return back();
    }

    /**
     * "Enviarme un correo de prueba" (HU-037 CA-003): el correo rellenado
     * con las variables de la pantalla, solo al Administrador que lo pide.
     */
    public function probarCorreo(Request $request): RedirectResponse
    {
        $datos = $this->validarCorreo($request, conVariables: true);
        /** @var User $yo */
        $yo = $request->user();
        /** @var array<string, string> $variables */
        $variables = array_map(fn ($v) => (string) $v, $datos['variables'] ?? []);

        $yo->notify(new AvisoMedicion(
            CorreoAviso::rellenar($datos['asunto'], $variables),
            CorreoAviso::rellenar($datos['cuerpo'], $variables),
            prueba: true,
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Te enviamos una prueba a {$yo->email}."]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validarCorreo(Request $request, bool $conVariables = false): array
    {
        $reglas = [
            'asunto' => ['required', 'string', 'max:255'],
            'cuerpo' => ['required', 'string', 'max:5000'],
            'guardar_plantilla' => ['boolean'],
        ];

        if ($conVariables) {
            $reglas['variables'] = ['array'];

            foreach (CorreoAviso::VARIABLES as $variable) {
                $reglas["variables.{$variable}"] = ['nullable', 'string', 'max:500'];
            }
        }

        return $request->validate($reglas, [], ['asunto' => 'asunto', 'cuerpo' => 'mensaje']);
    }

    private function soloPendiente(Medicion $medicion): void
    {
        if (! in_array($medicion->estado, ['no_iniciada', 'en_curso', 'vencida'], true)) {
            throw ValidationException::withMessages(['medicion' => 'Solo se cambia una medición pendiente que no se ha enviado.']);
        }
    }
}
