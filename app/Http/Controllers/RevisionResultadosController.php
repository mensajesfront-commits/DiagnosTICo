<?php

namespace App\Http\Controllers;

use App\Jobs\AnalizarCategoria;
use App\Models\Medicion;
use App\Models\User;
use App\Services\Resultados\PublicarResultado;
use App\Support\Ajustes;
use App\Support\DatosResultado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Revisión antes de publicar (supervisión humana, 23 de octubre de 2026).
 *
 * Con la opción encendida en Configuración IA, cuando la IA termina el
 * resultado queda "Por revisar". El Administrador lo ve como lo verá la
 * empresa y elige:
 * - Aprobar y publicar: se publica, la empresa recibe el correo y el
 *   resultado guarda quién lo aprobó.
 * - Rehacer el análisis de una categoría o de todas: la IA lo vuelve a hacer
 *   y la medición vuelve a quedar por revisar al terminar.
 *
 * Permiso `ia.editar`.
 */
class RevisionResultadosController extends Controller
{
    /** Enciende o apaga la revisión (Configuración IA). */
    public function cambiar(Request $request): RedirectResponse
    {
        $datos = $request->validate(['activa' => ['required', 'boolean']]);
        /** @var User $yo */
        $yo = $request->user();

        Ajustes::guardar(Ajustes::REVISION, (bool) $datos['activa'], $yo->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => $datos['activa']
            ? 'Listo: los resultados esperarán tu aprobación antes de publicarse.'
            : 'Listo: los resultados se publican solos. Los que ya esperan revisión siguen esperando.']);

        return back();
    }

    public function ver(Medicion $medicion): Response|RedirectResponse
    {
        if ($medicion->estado === 'terminada') {
            return to_route('resultados.ver', $medicion);
        }

        $datos = $medicion->estado === 'enviada' && $medicion->revision_desde !== null
            ? PublicarResultado::vistaPrevia($medicion)
            : null;

        if ($datos === null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Esta medición no tiene un resultado por revisar.']);

            return to_route('inicio.administrador');
        }

        $anterior = PublicarResultado::anterior($medicion);
        $anterior?->load('medicion:id,numero,terminada_en,enviada_en');

        return Inertia::render('resultados/Resultado', [
            'medicion' => DatosResultado::cabecera($medicion),
            'resultado' => DatosResultado::pagina($datos, $anterior === null ? null : [
                'numero' => $anterior->medicion->numero,
                'fecha' => ($anterior->medicion->enviada_en ?? $anterior->publicado_en)->toIso8601String(),
                'puntaje' => $anterior->puntaje_total,
            ], now(), false),
            'interno' => true,
            'revision' => [
                'desde' => $medicion->revision_desde->toIso8601String(),
            ],
        ]);
    }

    public function aprobar(Request $request, Medicion $medicion): RedirectResponse
    {
        /** @var User $yo */
        $yo = $request->user();

        if ($medicion->estado !== 'enviada' || $medicion->revision_desde === null) {
            throw ValidationException::withMessages(['revision' => 'Esta medición no tiene un resultado por revisar.']);
        }

        if (PublicarResultado::siTermino($medicion, $yo) === null) {
            throw ValidationException::withMessages(['revision' => 'No se pudo publicar: revisa que todas las categorías tengan su análisis.']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Resultado publicado. La empresa ya puede verlo.']);

        return to_route('resultados.ver', $medicion);
    }

    /** Rehace el análisis de una categoría (`categoria`) o de todas. */
    public function rehacer(Request $request, Medicion $medicion): RedirectResponse
    {
        $datos = $request->validate(['categoria' => ['nullable', 'string', 'max:64']]);

        if ($medicion->estado !== 'enviada' || $medicion->revision_desde === null) {
            throw ValidationException::withMessages(['revision' => 'Esta medición no tiene un resultado por revisar.']);
        }

        $analisis = $medicion->analisis()
            ->when($datos['categoria'] ?? null, fn ($q, string $ref) => $q->where('categoria_ref', $ref))
            ->get();

        if ($analisis->isEmpty()) {
            throw ValidationException::withMessages(['categoria' => 'Esa categoría no está en la medición.']);
        }

        DB::transaction(function () use ($medicion, $analisis): void {
            foreach ($analisis as $a) {
                $a->forceFill(['estado' => 'pendiente', 'intentos' => 0])->save();
            }

            $medicion->forceFill(['revision_desde' => null])->save();
        });

        foreach ($analisis as $a) {
            AnalizarCategoria::dispatch($a->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $analisis->count() === 1
            ? 'La IA está rehaciendo esa categoría. Te avisaremos por correo cuando esté lista para revisar.'
            : 'La IA está rehaciendo el análisis. Te avisaremos por correo cuando esté listo para revisar.']);

        return to_route('inicio.administrador');
    }
}
