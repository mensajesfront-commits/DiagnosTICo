<?php

namespace App\Jobs;

use App\Models\AnalisisCategoria;
use App\Models\Medicion;
use App\Models\Respuesta;
use App\Services\Resultados\CalculadoraPuntajes;
use App\Services\Resultados\PublicarResultado;
use App\Support\DatosResponder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Arranca el análisis de una medición enviada (HU-062 → HU-068, HU-069):
 *
 * 1. Calcula el puntaje de las preguntas de opción única y selección
 *    múltiple y lo guarda en `respuestas.puntaje` (paso 1 de E4).
 * 2. Crea un análisis pendiente por categoría y manda cada uno a la cola
 *    (AnalizarCategoria): una etapa por categoría (RN-021, paso 2 de E4).
 */
class AnalizarMedicion implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $medicionId) {}

    public function handle(): void
    {
        $medicion = Medicion::with('version')->find($this->medicionId);

        if ($medicion === null || $medicion->estado !== 'enviada') {
            return;
        }

        /** @var list<array<string, mixed>> $categorias */
        $categorias = $medicion->version->contenido['categorias'] ?? [];
        $respuestas = DatosResponder::respuestas($medicion);
        $porAnalizar = [];

        DB::transaction(function () use ($medicion, $categorias, $respuestas, &$porAnalizar): void {
            foreach ($categorias as $categoria) {
                /** @var list<array<string, mixed>> $preguntas */
                $preguntas = $categoria['preguntas'] ?? [];

                foreach ($preguntas as $p) {
                    if ($p['tipo'] !== 'abierta' && isset($respuestas[$p['ref']])) {
                        Respuesta::where('medicion_id', $medicion->id)->where('pregunta_ref', $p['ref'])
                            ->update(['puntaje' => CalculadoraPuntajes::cerrada($p, $respuestas[$p['ref']]['opciones'])]);
                    }
                }

                $analisis = AnalisisCategoria::firstOrCreate(
                    ['medicion_id' => $medicion->id, 'categoria_ref' => $categoria['ref']],
                    ['estado' => 'pendiente'],
                );

                if ($analisis->estado !== 'terminado') {
                    $porAnalizar[] = $analisis->id;
                }
            }
        });

        foreach ($porAnalizar as $id) {
            AnalizarCategoria::dispatch($id);
        }

        PublicarResultado::siTermino($medicion);
    }
}
