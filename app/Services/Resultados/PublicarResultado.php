<?php

namespace App\Services\Resultados;

use App\Jobs\GenerarInformePdf;
use App\Models\AnalisisCategoria;
use App\Models\Medicion;
use App\Models\Resultado;
use App\Models\User;
use App\Notifications\ResultadoListo;
use App\Support\MetodosAnalisis;
use App\Support\Niveles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * HU-084 · Publica el resultado cuando todas las categorías están analizadas:
 *
 * 1. Consolida los puntajes (HU-068, RN-018), el nivel (RN-019) y la variación
 *    contra la medición anterior de la empresa (RN-020), por categoría y total.
 * 2. Guarda el resultado, que ya no cambia (RN-023): copia los textos de las
 *    preguntas, las respuestas y el análisis de la IA.
 * 3. Pasa la medición a "Terminada".
 * 4. Avisa a la empresa por correo y manda a generar el PDF (RN-024). Si el
 *    PDF falla, el resultado queda publicado igual.
 *
 * Varias categorías pueden terminar a la vez: la fila de la medición se
 * bloquea para que el resultado se publique una sola vez.
 */
class PublicarResultado
{
    public static function siTermino(Medicion $medicion): ?Resultado
    {
        $resultado = DB::transaction(function () use ($medicion): ?Resultado {
            $medicion = Medicion::with('version')->lockForUpdate()->find($medicion->id);

            if ($medicion === null || $medicion->estado !== 'enviada' || Resultado::where('medicion_id', $medicion->id)->exists()) {
                return null;
            }

            /** @var list<array<string, mixed>> $categorias */
            $categorias = $medicion->version->contenido['categorias'] ?? [];
            $analisis = AnalisisCategoria::where('medicion_id', $medicion->id)->get()->keyBy('categoria_ref');

            foreach ($categorias as $c) {
                if (($analisis[$c['ref']]->estado ?? null) !== 'terminado') {
                    return null;
                }
            }

            $datos = self::consolidar($medicion, $categorias, $analisis->map(fn (AnalisisCategoria $a) => $a->respuesta_ia ?? [])->all());

            $resultado = Resultado::create([...$datos, 'medicion_id' => $medicion->id, 'publicado_en' => now()]);
            $medicion->forceFill(['estado' => 'terminada', 'terminada_en' => now()])->save();

            return $resultado;
        });

        if ($resultado !== null) {
            $resultado->load('medicion.empresa');
            $usuarios = User::where('empresa_id', $resultado->medicion->empresa_id)->where('activo', true)->get();
            Notification::send($usuarios, new ResultadoListo($resultado->medicion));
            GenerarInformePdf::dispatch($resultado->id);
        }

        return $resultado;
    }

    /**
     * @param  list<array<string, mixed>>  $categorias  de la versión congelada
     * @param  array<string, array<string, mixed>>  $analisis  respuesta de la IA por categoría
     * @return array{puntaje_total: int, nivel: string, variacion: int|null, por_categoria: list<array<string, mixed>>}
     */
    public static function consolidar(Medicion $medicion, array $categorias, array $analisis): array
    {
        $respuestas = [];

        foreach ($medicion->respuestas()->get() as $r) {
            $respuestas[$r->pregunta_ref] = ['opciones' => array_map('strval', $r->opciones_elegidas ?? []), 'texto' => (string) $r->texto];
        }

        return self::calcular($categorias, $respuestas, $analisis, self::anterior($medicion));
    }

    /**
     * El cálculo sin la medición: también lo usa el resultado de ejemplo
     * (HU-030), para que dé lo mismo que daría un resultado real.
     *
     * @param  list<array<string, mixed>>  $categorias  de la versión
     * @param  array<string, array{opciones: list<string>, texto: string}>  $respuestas  por referencia de pregunta
     * @param  array<string, array<string, mixed>>  $analisis  respuesta de la IA por categoría
     * @return array{puntaje_total: int, nivel: string, variacion: int|null, por_categoria: list<array<string, mixed>>}
     */
    public static function calcular(array $categorias, array $respuestas, array $analisis, ?Resultado $anterior = null): array
    {
        /** @var list<array<string, mixed>> $porCategoriaAnterior */
        $porCategoriaAnterior = $anterior->por_categoria ?? [];
        $previas = array_column($porCategoriaAnterior, null, 'ref');
        $lista = [];

        foreach ($categorias as $c) {
            $ia = $analisis[$c['ref']] ?? [];
            /** @var list<array{id: string, puntaje: int|null, observacion: string}> $listaIa */
            $listaIa = $ia['preguntas'] ?? [];
            $iaPreguntas = array_column($listaIa, null, 'id');
            /** @var list<array<string, mixed>> $preguntasVersion */
            $preguntasVersion = $c['preguntas'] ?? [];
            $preguntas = [];

            foreach ($preguntasVersion as $p) {
                $r = $respuestas[$p['ref']] ?? null;
                $texto = CalculadoraPuntajes::textoRespuesta($p, $r['opciones'] ?? null, $r['texto'] ?? null);
                $puntaje = null;

                if ($texto !== null) {
                    $puntaje = $p['tipo'] === 'abierta'
                        ? ($iaPreguntas[$p['ref']]['puntaje'] ?? null)
                        : CalculadoraPuntajes::cerrada($p, $r['opciones'] ?? []);
                }

                $preguntas[] = [
                    'ref' => $p['ref'],
                    'texto' => $p['texto'],
                    'tipo' => $p['tipo'],
                    'respuesta' => $texto,
                    'puntaje' => $puntaje,
                    'observacion' => $iaPreguntas[$p['ref']]['observacion'] ?? null,
                ];
            }

            $puntaje = CalculadoraPuntajes::categoria(array_column($preguntas, 'puntaje')) ?? 0;
            $previa = $previas[$c['ref']]['puntaje'] ?? null;

            $lista[] = [
                'ref' => $c['ref'],
                'nombre' => $c['nombre'],
                'importancia' => (float) $c['importancia'],
                'puntaje' => $puntaje,
                'nivel' => Niveles::de($puntaje),
                'anterior' => $previa,
                'variacion' => $previa === null ? null : $puntaje - (int) $previa,
                'observacion' => (string) ($ia['observacion_categoria'] ?? ''),
                // Método con el que la IA analizó la categoría; null en
                // resultados anteriores a este campo.
                'metodo' => isset($ia['metodo']) ? [
                    'clave' => (string) $ia['metodo'],
                    'nombre' => MetodosAnalisis::nombre((string) $ia['metodo']),
                    'motivo' => (string) ($ia['metodo_motivo'] ?? ''),
                ] : null,
                'recomendacion' => [
                    'titulo' => (string) ($ia['recomendacion']['titulo'] ?? ''),
                    'detalle' => (string) ($ia['recomendacion']['detalle'] ?? ''),
                ],
                'preguntas' => $preguntas,
            ];
        }

        $total = CalculadoraPuntajes::total(array_map(fn (array $c) => ['puntaje' => $c['puntaje'], 'importancia' => $c['importancia']], $lista));

        return [
            'puntaje_total' => $total,
            'nivel' => Niveles::de($total),
            'variacion' => $anterior === null ? null : $total - $anterior->puntaje_total,
            'por_categoria' => $lista,
        ];
    }

    /** Resultado de la medición terminada anterior de la misma empresa (RN-020). */
    public static function anterior(Medicion $medicion): ?Resultado
    {
        return Resultado::query()
            ->join('mediciones', 'mediciones.id', '=', 'resultados.medicion_id')
            ->where('mediciones.empresa_id', $medicion->empresa_id)
            ->where('mediciones.numero', '<', $medicion->numero)
            ->orderByDesc('mediciones.numero')
            ->select('resultados.*')
            ->first();
    }
}
