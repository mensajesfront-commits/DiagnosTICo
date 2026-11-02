<?php

namespace App\Support;

use App\Models\Medicion;
use App\Models\Resultado;
use App\Services\Resultados\PublicarResultado;
use Carbon\CarbonInterface;

/**
 * El resultado publicado como lo muestran E6 / A3.3 (HU-064), E9 / A3.5
 * (HU-065) y el PDF (HU-066). Todo sale de lo guardado en `resultados`: los
 * puntajes, niveles y variaciones ya vienen calculados (RN-023).
 */
class DatosResultado
{
    /** @return array<string, mixed> */
    public static function cabecera(Medicion $medicion): array
    {
        $medicion->loadMissing(['empresa:id,nombre', 'version.diagnostico.sector:id,nombre']);

        return [
            'id' => $medicion->id,
            'numero' => $medicion->numero,
            'empresa' => ['id' => $medicion->empresa_id, 'nombre' => $medicion->empresa->nombre],
            'sector' => $medicion->version->diagnostico->sector->nombre ?? '',
            'diagnostico' => $medicion->version->diagnostico->nombre ?? '',
            'version' => $medicion->version->numero,
            'enviada_en' => $medicion->enviada_en?->toIso8601String(),
            'terminada_en' => $medicion->terminada_en?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function resultado(Resultado $resultado): array
    {
        $anterior = PublicarResultado::anterior($resultado->medicion);
        $anterior?->load('medicion:id,numero,terminada_en,enviada_en');

        return self::pagina(
            ['puntaje_total' => $resultado->puntaje_total, 'nivel' => $resultado->nivel, 'variacion' => $resultado->variacion, 'por_categoria' => $resultado->por_categoria],
            $anterior === null ? null : [
                'numero' => $anterior->medicion->numero,
                'fecha' => ($anterior->medicion->enviada_en ?? $anterior->publicado_en)->toIso8601String(),
                'puntaje' => $anterior->puntaje_total,
            ],
            $resultado->publicado_en,
            $resultado->pdf_ruta !== null,
        );
    }

    /**
     * El resultado con la forma de E6, a partir de lo calculado (también el
     * resultado de ejemplo de HU-030, que no se guarda).
     *
     * @param  array{puntaje_total: int, nivel: string, variacion: int|null, por_categoria: array<int|string, mixed>}  $datos
     * @param  array{numero: int, fecha: string, puntaje: int}|null  $anterior
     * @return array<string, mixed>
     */
    public static function pagina(array $datos, ?array $anterior, CarbonInterface $publicado, bool $tienePdf): array
    {
        /** @var list<array<string, mixed>> $categorias */
        $categorias = array_values($datos['por_categoria']);

        return [
            'puntaje' => $datos['puntaje_total'],
            'nivel' => $datos['nivel'],
            'frase' => Niveles::frase($datos['nivel']),
            'variacion' => $datos['variacion'],
            'anterior' => $anterior,
            'publicado_en' => $publicado->toIso8601String(),
            'tiene_pdf' => $tienePdf,
            'categorias' => $categorias,
            'prioridades' => self::prioridades($categorias),
        ];
    }

    /**
     * Las 3 categorías más débiles, de la más baja a la más alta (HU-064
     * CA-006). Con el mismo puntaje va primero la de más importancia.
     *
     * @param  list<array<string, mixed>>  $categorias
     * @return list<array<string, mixed>>
     */
    public static function prioridades(array $categorias, int $cuantas = 3): array
    {
        return array_slice(self::ordenadas($categorias), 0, $cuantas);
    }

    /**
     * @param  list<array<string, mixed>>  $categorias
     * @return list<array<string, mixed>>
     */
    public static function ordenadas(array $categorias): array
    {
        usort($categorias, fn (array $a, array $b) => [$a['puntaje'], -$a['importancia']] <=> [$b['puntaje'], -$b['importancia']]);

        return array_map(fn (array $c) => [
            'ref' => $c['ref'],
            'nombre' => $c['nombre'],
            'puntaje' => $c['puntaje'],
            'nivel' => $c['nivel'],
            'recomendacion' => $c['recomendacion'],
            // La principal completa (pasos, plazo e indicador), si la hay.
            'principal' => $c['recomendaciones'][0] ?? null,
            'metodo' => $c['metodo'] ?? null,
        ], $categorias);
    }

    /**
     * Datos de la plantilla del PDF (resources/views/pdf/informe.blade.php).
     *
     * @return array<string, mixed>
     */
    public static function informe(Resultado $resultado): array
    {
        $resultado->loadMissing('medicion');

        return self::informeDe(
            self::cabecera($resultado->medicion),
            self::resultado($resultado),
            $resultado->medicion->enviada_en,
            $resultado->publicado_en,
        );
    }

    /**
     * @param  array<string, mixed>  $cabecera  como `cabecera()`
     * @param  array<string, mixed>  $datos  como `pagina()`
     * @return array<string, mixed>
     */
    public static function informeDe(array $cabecera, array $datos, ?CarbonInterface $enviada, CarbonInterface $generado): array
    {
        /** @var list<array<string, mixed>> $categorias */
        $categorias = $datos['categorias'];
        $orden = self::ordenadas($categorias);
        $prioridad = array_flip(array_column($orden, 'ref'));
        $pesos = array_sum(array_column($categorias, 'importancia')) ?: 1;

        // Cada categoría con lo que el informe explica: prioridad, cuánto
        // aporta al total, qué significa su nivel y el método explicado.
        $detalladas = array_map(function (array $c) use ($prioridad, $pesos) {
            $preguntas = (array) ($c['preguntas'] ?? []);

            return [
                ...$c,
                'prioridad' => $prioridad[$c['ref']] + 1,
                'aporte' => round($c['puntaje'] * $c['importancia'] / $pesos, 1),
                'significado' => Niveles::enCategoria((string) $c['nivel']),
                'metodo_explicado' => isset($c['metodo']['clave']) ? MetodosAnalisis::explicado((string) $c['metodo']['clave']) : null,
                'hallazgos' => (array) ($c['hallazgos'] ?? []),
                'fortalezas' => (array) ($c['fortalezas'] ?? []),
                // Los resultados viejos solo traen una recomendación sin pasos.
                'recomendaciones' => ($c['recomendaciones'] ?? []) !== [] ? $c['recomendaciones'] : [$c['recomendacion']],
                'respondidas' => count(array_filter($preguntas, fn ($q) => is_array($q) && $q['respuesta'] !== null)),
            ];
        }, $categorias);

        $porPrioridad = $detalladas;
        usort($porPrioridad, fn (array $a, array $b) => $a['prioridad'] <=> $b['prioridad']);

        return [
            ...$cabecera,
            ...$datos,
            'categorias' => $detalladas,
            'preguntas_total' => array_sum(array_map(fn (array $c) => count((array) ($c['preguntas'] ?? [])), $detalladas)),
            'preguntas_respondidas' => array_sum(array_column($detalladas, 'respondidas')),
            // Ejemplo del cálculo con la categoría que más pesa.
            'ejemplo_calculo' => collect($detalladas)->sortByDesc('importancia')->first(),
            'metodos' => array_map(fn (string $clave) => [
                ...(array) MetodosAnalisis::explicado($clave),
                'usado_en' => array_values(array_map(fn (array $c) => $c['nombre'], array_filter($detalladas, fn (array $c) => ($c['metodo']['clave'] ?? null) === $clave))),
            ], MetodosAnalisis::claves()),
            'plan' => self::plan($porPrioridad),
            'soporte' => (string) config('diagnostico.soporte_email'),
            'nivel_nombre' => Niveles::nombre((string) $datos['nivel']),
            'niveles' => collect(Niveles::NIVELES)->map(fn (array $n, string $clave) => ['clave' => $clave, 'nombre' => $n[0], 'rango' => "{$n[1]}–{$n[2]}"])->values()->all(),
            'fecha_respuesta' => $enviada ? CorreoAviso::fecha($enviada) : null,
            'fecha_generado' => CorreoAviso::fecha($generado),
            // Hasta 2 y 2, sin repetir una categoría en las dos listas.
            'fuertes' => array_slice(array_reverse($orden), 0, min(2, intdiv(count($orden), 2))),
            'debiles' => array_slice($orden, 0, min(2, intdiv(count($orden), 2))),
            // Página 1: cuántas categorías quedaron en cada nivel.
            'por_nivel' => collect(Niveles::NIVELES)->map(fn (array $n, string $clave) => [
                'clave' => $clave,
                'nombre' => $n[0],
                'cantidad' => count(array_filter($categorias, fn (array $c) => $c['nivel'] === $clave)),
            ])->values()->all(),
            // Página 3: después de las 3 prioridades, el resto de la más débil a la más fuerte.
            'otras' => array_slice($orden, 3),
            'radar' => [
                'etiquetas' => array_column($categorias, 'nombre'),
                'actual' => array_column($categorias, 'puntaje'),
                'anterior' => $datos['variacion'] === null ? null : array_map(fn (array $c) => $c['anterior'], $categorias),
            ],
        ];
    }

    /**
     * Plan de acción del PDF en tres partes: primero la recomendación
     * principal de las 3 categorías más débiles, después la principal de las
     * demás y más adelante las otras recomendaciones de cada categoría.
     *
     * @param  list<array<string, mixed>>  $categorias  de la más débil a la más fuerte
     * @return array{primero: list<array<string, mixed>>, despues: list<array<string, mixed>>, luego: list<array<string, mixed>>}
     */
    private static function plan(array $categorias): array
    {
        $plan = ['primero' => [], 'despues' => [], 'luego' => []];

        foreach ($categorias as $n => $c) {
            foreach (array_values((array) $c['recomendaciones']) as $i => $r) {
                $fila = [
                    'categoria' => $c['nombre'],
                    'nivel' => $c['nivel'],
                    'titulo' => (string) ($r['titulo'] ?? ''),
                    'plazo' => (string) ($r['plazo'] ?? ''),
                    'indicador' => (string) ($r['indicador'] ?? ''),
                ];

                $plan[$i > 0 ? 'luego' : ($n < 3 ? 'primero' : 'despues')][] = $fila;
            }
        }

        return $plan;
    }
}
