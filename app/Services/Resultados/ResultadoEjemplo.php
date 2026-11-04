<?php

namespace App\Services\Resultados;

use App\Support\MetodosAnalisis;
use App\Support\Niveles;

/**
 * HU-030 · Resultado de ejemplo de un diagnóstico (A2.6): respuestas de
 * muestra para el mejor caso, el caso medio y el peor caso, calculadas con el
 * mismo `PublicarResultado::calcular` que un resultado real (RN-018).
 *
 * No llama a la IA ni guarda nada: las abiertas reciben un puntaje fijo por
 * caso y los textos de la IA son de muestra.
 *
 * - Mejor: la opción de mayor puntaje; en selección múltiple, todas las que
 *   no excluyen a las demás; abiertas con 90.
 * - Medio: la opción más cercana a 50; en múltiple, las de menor puntaje
 *   hasta pasar 40; abiertas con 50.
 * - Peor: la opción de menor puntaje (o "Ninguna"); abiertas con 10.
 */
class ResultadoEjemplo
{
    public const array CASOS = ['mejor', 'medio', 'peor'];

    /** Puntaje de las abiertas y su texto de muestra en cada caso. */
    private const array ABIERTAS = [
        'mejor' => [90, 'Tenemos un proceso definido, lo medimos cada mes y lo ajustamos con los datos.'],
        'medio' => [50, 'Lo hacemos, pero sin un plan fijo ni medición.'],
        'peor' => [10, 'Por ahora no lo hacemos.'],
    ];

    /**
     * @param  array<string, mixed>  $contenido  contenido congelado (versión o borrador)
     * @return array{puntaje_total: int, nivel: string, variacion: int|null, por_categoria: list<array<string, mixed>>}
     */
    public static function calcular(array $contenido, string $caso): array
    {
        $caso = in_array($caso, self::CASOS, true) ? $caso : 'medio';
        /** @var list<array<string, mixed>> $categorias */
        $categorias = $contenido['categorias'] ?? [];
        [$puntajeAbierta, $textoAbierta] = self::ABIERTAS[$caso];
        $respuestas = [];
        $analisis = [];

        foreach ($categorias as $c) {
            /** @var list<array<string, mixed>> $preguntas */
            $preguntas = $c['preguntas'] ?? [];
            $iaPreguntas = [];
            $puntajes = [];

            foreach ($preguntas as $p) {
                if ($p['tipo'] === 'abierta') {
                    $respuestas[$p['ref']] = ['opciones' => [], 'texto' => $textoAbierta];
                    $iaPreguntas[] = ['id' => $p['ref'], 'puntaje' => $puntajeAbierta, 'observacion' => "Ejemplo: la IA la calificaría con {$puntajeAbierta} según su criterio."];
                    $puntajes[] = $puntajeAbierta;

                    continue;
                }

                $elegidas = self::elegir($p, $caso);
                $respuestas[$p['ref']] = ['opciones' => $elegidas, 'texto' => ''];
                $puntaje = CalculadoraPuntajes::cerrada($p, $elegidas) ?? 0;
                $iaPreguntas[] = ['id' => $p['ref'], 'puntaje' => null, 'observacion' => "Ejemplo: con esta respuesta la pregunta suma {$puntaje} puntos."];
                $puntajes[] = $puntaje;
            }

            $promedio = CalculadoraPuntajes::categoria($puntajes) ?? 0;
            $nombre = (string) $c['nombre'];

            $analisis[$c['ref']] = [
                'preguntas' => $iaPreguntas,
                'metodo' => MetodosAnalisis::porCategoria($nombre, $promedio),
                'metodo_motivo' => 'Ejemplo: con la IA real, el método se elige según lo que responda la empresa.',
                'observacion_categoria' => "Ejemplo: con estas respuestas, {$nombre} queda en nivel ".Niveles::nombre(Niveles::de($promedio)).'. Con la IA real, aquí va lo que muestran las respuestas de la empresa.',
                'recomendacion' => [
                    'titulo' => "Recomendación de ejemplo para {$nombre}",
                    'detalle' => 'Con la IA real, aquí va una acción concreta para los próximos 1 a 3 meses, según las respuestas de la empresa.',
                ],
            ];
        }

        return PublicarResultado::calcular($categorias, $respuestas, $analisis);
    }

    /**
     * Opciones elegidas de una pregunta cerrada en un caso.
     *
     * @param  array<string, mixed>  $pregunta
     * @return list<string>
     */
    public static function elegir(array $pregunta, string $caso): array
    {
        /** @var list<array{ref: string, puntaje: int|float|string, exclusiva?: bool}> $opciones */
        $opciones = $pregunta['opciones'] ?? [];

        if ($opciones === []) {
            return [];
        }

        $puntaje = fn (array $o) => (int) $o['puntaje'];
        $porPuntaje = $opciones;
        usort($porPuntaje, fn (array $a, array $b) => $puntaje($a) <=> $puntaje($b));

        if ($pregunta['tipo'] === 'opcion_unica') {
            $elegida = match ($caso) {
                'mejor' => end($porPuntaje),
                'peor' => $porPuntaje[0],
                default => collect($porPuntaje)->sortBy(fn (array $o) => [abs($puntaje($o) - 50), -$puntaje($o)])->first(),
            };

            return [(string) $elegida['ref']];
        }

        // Selección múltiple.
        $normales = array_values(array_filter($porPuntaje, fn (array $o) => ! ($o['exclusiva'] ?? false)));
        $exclusiva = collect($porPuntaje)->first(fn (array $o) => (bool) ($o['exclusiva'] ?? false));

        if ($caso === 'mejor') {
            return array_map(fn (array $o) => (string) $o['ref'], $normales ?: $porPuntaje);
        }

        if ($caso === 'peor') {
            return [(string) ($exclusiva ?? $porPuntaje[0])['ref']];
        }

        $elegidas = [];
        $suma = 0;

        foreach ($normales as $o) {
            $elegidas[] = (string) $o['ref'];
            $suma += $puntaje($o);

            if ($suma >= 40) {
                break;
            }
        }

        return $elegidas ?: [(string) $porPuntaje[0]['ref']];
    }
}
