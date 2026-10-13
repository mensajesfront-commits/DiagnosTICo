<?php

namespace App\Http\Controllers;

use App\Models\Medicion;
use App\Models\Respuesta;
use App\Models\SolicitudMedicion;
use App\Models\User;
use App\Notifications\NuevaMedicionSolicitada;
use App\Support\DatosResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * E2 · Responder el diagnóstico, E3 · Revisar antes de enviar y E4 ·
 * Enviado (HU-060 a HU-063), y pedir una medición nueva si venció (HU-082).
 *
 * Solo la empresa dueña de la medición (cuenta principal o colaborador,
 * permiso `diagnostico.responder`); de otra empresa da 404 (RN-007).
 * Cada respuesta se guarda al momento (PUT …/respuestas). Una medición
 * vencida o ya enviada no se responde (RN-016, RN-017).
 */
class ResponderController extends Controller
{
    /** E2 · `?categoria=` (índice desde 0) abre esa categoría. */
    public function responder(Request $request, Medicion $medicion): Response|RedirectResponse
    {
        $this->deMiEmpresa($request, $medicion);

        if ($salida = $this->siNoSePuede($medicion)) {
            return $salida;
        }

        $cuestionario = DatosResponder::cuestionario($medicion);
        $respuestas = DatosResponder::respuestas($medicion);
        $avance = DatosResponder::avance($cuestionario, $respuestas);
        $pedida = $request->integer('categoria', -1);

        return Inertia::render('responder/Responder', [
            'medicion' => $this->cabecera($medicion),
            'categorias' => $cuestionario,
            'respuestas' => (object) $respuestas,
            'avance' => $avance,
            'categoriaInicial' => $pedida >= 0 && $pedida < count($cuestionario) ? $pedida : $avance['actual'],
            'guardadoEn' => $medicion->respuestas()->max('updated_at'),
        ]);
    }

    /**
     * Guarda (o borra, si viene vacía) la respuesta de una pregunta. Responde
     * JSON con el avance, para "✓ Guardado 10:42" sin recargar.
     */
    public function guardar(Request $request, Medicion $medicion): JsonResponse
    {
        $this->deMiEmpresa($request, $medicion);

        if (! DatosResponder::respondible($medicion)) {
            return response()->json(['message' => 'Esta medición ya no se puede responder.'], 422);
        }

        $cuestionario = DatosResponder::cuestionario($medicion);
        $pregunta = null;

        foreach ($cuestionario as $c) {
            /** @var list<array<string, mixed>> $preguntas */
            $preguntas = $c['preguntas'];

            foreach ($preguntas as $p) {
                if ($p['ref'] === $request->input('pregunta_ref')) {
                    $pregunta = $p;
                }
            }
        }

        if ($pregunta === null) {
            throw ValidationException::withMessages(['pregunta_ref' => 'La pregunta no es de esta medición.']);
        }

        $refs = array_column($pregunta['opciones'], 'ref');
        $datos = $request->validate([
            'pregunta_ref' => ['required', 'string'],
            'opciones' => ['nullable', 'array', $pregunta['tipo'] === 'opcion_unica' ? 'max:1' : 'max:'.max(1, count($refs))],
            'opciones.*' => ['string', 'in:'.implode(',', $refs ?: ['-'])],
            'texto' => ['nullable', 'string', 'max:1000'],
        ], ['texto.max' => 'La respuesta puede tener hasta 1000 caracteres.']);

        $opciones = $pregunta['tipo'] === 'abierta' ? [] : array_values(array_unique($datos['opciones'] ?? []));
        $texto = $pregunta['tipo'] === 'abierta' ? trim((string) ($datos['texto'] ?? '')) : '';

        DB::transaction(function () use ($medicion, $pregunta, $opciones, $texto, $request): void {
            if ($opciones === [] && $texto === '') {
                Respuesta::where('medicion_id', $medicion->id)->where('pregunta_ref', $pregunta['ref'])->delete();
            } else {
                Respuesta::updateOrCreate(
                    ['medicion_id' => $medicion->id, 'pregunta_ref' => $pregunta['ref']],
                    ['opciones_elegidas' => $opciones ?: null, 'texto' => $texto ?: null, 'respondida_por' => $request->user()?->id],
                );
            }

            if ($medicion->estado === 'no_iniciada') {
                $medicion->forceFill(['estado' => 'en_curso'])->save();
            }
        });

        return response()->json([
            'guardado_en' => now()->toIso8601String(),
            'avance' => DatosResponder::avance($cuestionario, DatosResponder::respuestas($medicion)),
        ]);
    }

    /** E3 · Revisar antes de enviar. */
    public function revisar(Request $request, Medicion $medicion): Response|RedirectResponse
    {
        $this->deMiEmpresa($request, $medicion);

        if ($salida = $this->siNoSePuede($medicion)) {
            return $salida;
        }

        return Inertia::render('responder/Revisar', [
            'medicion' => $this->cabecera($medicion),
            'avance' => DatosResponder::avanceDe($medicion),
            'guardadoEn' => $medicion->respuestas()->max('updated_at'),
        ]);
    }

    /**
     * HU-062 · Envía: todas las obligatorias respondidas; después no se
     * cambian (RN-017). Queda "Enviada" mientras se analiza.
     *
     * [FUNCIONALIDAD POR DEFINIR] El cálculo de puntajes y el análisis de la
     * IA (HU-068, HU-069) arrancan aquí cuando existan (semana 5).
     */
    public function enviar(Request $request, Medicion $medicion): RedirectResponse
    {
        $this->deMiEmpresa($request, $medicion);

        if (! DatosResponder::respondible($medicion)) {
            throw ValidationException::withMessages(['enviar' => 'Esta medición ya no se puede enviar.']);
        }

        $avance = DatosResponder::avanceDe($medicion);

        if (! $avance['completo']) {
            $falta = collect($avance['categorias'])->firstWhere('faltan_obligatorias', '>', 0);
            throw ValidationException::withMessages(['enviar' => "Faltan preguntas obligatorias en {$falta['nombre']}."]);
        }

        $medicion->forceFill(['estado' => 'enviada', 'enviada_en' => now()])->save();

        return to_route('responder.enviado', $medicion);
    }

    /** E4 · Enviado: espera del análisis (HU-063). */
    public function enviado(Request $request, Medicion $medicion): Response|RedirectResponse
    {
        $this->deMiEmpresa($request, $medicion);

        if (in_array($medicion->estado, DatosResponder::RESPONDIBLES, true)) {
            return to_route('responder.mostrar', $medicion);
        }

        return Inertia::render('responder/Enviado', [
            'medicion' => $this->cabecera($medicion),
            'estado' => $medicion->estado,
            'enviadaEn' => $medicion->enviada_en?->toIso8601String(),
        ]);
    }

    /**
     * HU-082 · Pedir una medición nueva porque la pendiente venció. Una
     * solicitud abierta a la vez; le llega por correo a los administradores.
     */
    public function solicitar(Request $request, Medicion $medicion): RedirectResponse
    {
        $this->deMiEmpresa($request, $medicion);

        if (DatosResponder::respondible($medicion) || ! in_array($medicion->estado, ['no_iniciada', 'en_curso', 'vencida'], true)) {
            throw ValidationException::withMessages(['solicitud' => 'Solo se pide una medición nueva cuando la tuya venció.']);
        }

        $abierta = SolicitudMedicion::where('empresa_id', $medicion->empresa_id)->where('estado', 'abierta')->exists();

        if ($abierta) {
            throw ValidationException::withMessages(['solicitud' => 'Ya pediste una medición nueva: el equipo de NuevasTIC la está revisando.']);
        }

        /** @var User $yo */
        $yo = $request->user();
        SolicitudMedicion::create(['empresa_id' => $medicion->empresa_id, 'medicion_vencida_id' => $medicion->id, 'estado' => 'abierta', 'solicitada_por' => $yo->id]);

        $admins = User::role('Administrador')->where('activo', true)->get();
        Notification::send($admins, new NuevaMedicionSolicitada($medicion->empresa()->firstOrFail(), $medicion, $yo->name));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Listo: le avisamos al equipo de NuevasTIC. Te escribirán con la nueva fecha.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function cabecera(Medicion $medicion): array
    {
        $medicion->loadMissing(['version.diagnostico.sector:id,nombre']);

        return [
            'id' => $medicion->id,
            'numero' => $medicion->numero,
            'diagnostico' => $medicion->version->diagnostico->nombre ?? '',
            'sector' => $medicion->version->diagnostico->sector->nombre ?? '',
            'fecha_limite' => $medicion->fecha_limite?->toDateString(),
        ];
    }

    /** RN-007: solo su empresa. De otra, 404 (no se revela que existe). */
    private function deMiEmpresa(Request $request, Medicion $medicion): void
    {
        /** @var User $yo */
        $yo = $request->user();
        abort_unless($yo->empresa_id !== null && $medicion->empresa_id === $yo->empresa_id, 404);
    }

    /** Si ya se envió o venció, al lugar que corresponde. */
    private function siNoSePuede(Medicion $medicion): ?RedirectResponse
    {
        if (DatosResponder::respondible($medicion)) {
            return null;
        }

        if (in_array($medicion->estado, ['enviada', 'terminada'], true)) {
            return to_route('responder.enviado', $medicion);
        }

        Inertia::flash('toast', ['type' => 'warning', 'message' => 'Esta medición venció o fue cancelada: ya no se puede responder.']);

        return to_route('inicio.empresa');
    }
}
