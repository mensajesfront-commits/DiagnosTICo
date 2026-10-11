<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Services\Mediciones\AsignarMedicion;
use App\Support\DatosEmpresas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A3.1b · Asignar medición (HU-036), A3.1c · reemplazar o cancelar la
 * pendiente y "Cambiar fecha" (HU-009). Permiso `mediciones.asignar`.
 */
class MedicionesController extends Controller
{
    public function __construct(private readonly AsignarMedicion $asignar) {}

    /** A3.1b · Con `?empresa=` llega con la empresa elegida. */
    public function crear(Request $request): Response
    {
        $empresas = Empresa::query()->where('activa', true)->with('sector:id,nombre')->orderBy('nombre')->get();
        $pendientes = Medicion::query()->whereIn('estado', DatosEmpresas::PENDIENTES)
            ->with(['version:id,diagnostico_id,numero', 'version.diagnostico:id,nombre'])
            ->withExists('respuestas')
            ->whereIn('empresa_id', $empresas->modelKeys())->get()->keyBy('empresa_id');

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
                    'diagnostico' => $p->version?->diagnostico?->nombre,
                    'version' => $p->version?->numero,
                    'tiene_respuestas' => (bool) $p->getAttribute('respuestas_exists'),
                ] : null,
                'siguiente' => ((int) $e->mediciones()->where('estado', '!=', 'cancelada')->max('numero')) + 1,
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
        ]);
    }

    /** Asigna; si ya hay una pendiente, solo con `reemplazar` (A3.1c). */
    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
            'diagnostico_id' => ['required', 'integer', 'exists:diagnosticos,id'],
            'fecha_limite' => ['nullable', 'date', 'after_or_equal:today'],
            'mensaje' => ['nullable', 'string', 'max:500'],
            'aviso' => ['boolean'],
            'reemplazar' => ['boolean'],
        ], [
            'fecha_limite.after_or_equal' => 'La fecha límite no puede ser anterior a hoy.',
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
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "Medición {$medicion->numero} asignada a «{$empresa->nombre}»."
            .(($datos['aviso'] ?? false) ? ' Enviamos el aviso por correo.' : '')]);

        return to_route('empresas.ver', $empresa);
    }

    /** A3.1c · Cancelar la medición pendiente sin reemplazarla. */
    public function cancelar(Medicion $medicion): RedirectResponse
    {
        $this->soloPendiente($medicion);
        $medicion->forceFill(['estado' => 'cancelada', 'cancelada_en' => now()])->save();

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
            'fecha_limite' => ['nullable', 'date', 'after_or_equal:today'],
        ], ['fecha_limite.after_or_equal' => 'La fecha límite no puede ser anterior a hoy.'], ['fecha_limite' => 'fecha límite']);

        $estado = $medicion->estado === 'vencida'
            ? ($medicion->respuestas()->exists() ? 'en_curso' : 'no_iniciada')
            : $medicion->estado;

        $medicion->forceFill(['fecha_limite' => $datos['fecha_limite'] ?? null, 'estado' => $estado])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Nueva fecha para la medición {$medicion->numero}."]);

        return back();
    }

    private function soloPendiente(Medicion $medicion): void
    {
        if (! in_array($medicion->estado, ['no_iniciada', 'en_curso', 'vencida'], true)) {
            throw ValidationException::withMessages(['medicion' => 'Solo se cambia una medición pendiente que no se ha enviado.']);
        }
    }
}
