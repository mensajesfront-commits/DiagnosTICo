<?php

namespace App\Support;

/**
 * Qué le falta a un diagnóstico para publicarse (RN-012, HU-023, HU-028).
 *
 * Trabaja sobre el contenido como arreglo (`ContenidoDiagnostico::congelar`
 * o `versiones_diagnostico.contenido`), así sirve igual para el borrador, la
 * vista previa y una versión publicada.
 *
 * Los 5 requisitos:
 * 1. cada categoría tiene al menos 1 pregunta;
 * 2. cada pregunta de opciones tiene al menos 2 opciones, todas con puntaje;
 * 3. la importancia suma 100%;
 * 4. toda pregunta tiene indicación para responder;
 * 5. cada selección múltiple suma exactamente 100 puntos si se marcan todas
 *    (sin contar la opción que excluye a las demás, como «Ninguna»).
 */
class EstadoDiagnostico
{
    /**
     * @param  array<string, mixed>  $contenido
     * @return array{
     *     publicable: bool,
     *     requisitos: list<array{clave: string, texto: string, ok: bool, detalle: string|null}>,
     *     categorias: array<string, list<string>>,
     *     preguntas: array<string, list<string>>,
     *     completas: int,
     *     total: int,
     *     importancia: float,
     * }
     */
    public static function calcular(array $contenido): array
    {
        /** @var list<array<string, mixed>> $categorias */
        $categorias = $contenido['categorias'] ?? [];

        $sinPreguntas = 0;
        $sinOpciones = 0;
        $sinIndicacion = 0;
        /** @var list<int> $sumasMalas */
        $sumasMalas = [];
        $importancia = 0.0;
        /** @var array<string, list<string>> $porCategoria */
        $porCategoria = [];
        /** @var array<string, list<string>> $porPregunta */
        $porPregunta = [];

        foreach ($categorias as $categoria) {
            $ref = (string) $categoria['ref'];
            $importancia += (float) ($categoria['importancia'] ?? 0);
            $porCategoria[$ref] = [];

            /** @var list<array<string, mixed>> $preguntas */
            $preguntas = $categoria['preguntas'] ?? [];

            if ($preguntas === []) {
                $sinPreguntas++;
                $porCategoria[$ref][] = 'No tiene preguntas';
            }

            foreach ($preguntas as $pregunta) {
                $problemas = self::problemasDePregunta($pregunta);

                foreach ($problemas as $clave => $motivo) {
                    match ($clave) {
                        'indicacion' => $sinIndicacion++,
                        'opciones' => $sinOpciones++,
                        default => $sumasMalas[] = self::sumaMaxima($pregunta),
                    };
                }

                if ($problemas !== []) {
                    $porPregunta[(string) $pregunta['ref']] = array_values($problemas);
                    array_push($porCategoria[$ref], ...array_values($problemas));
                }
            }
        }

        $importancia = round($importancia, 2);
        $sumaOk = abs($importancia - 100) < 0.005;

        $requisitos = [
            ['clave' => 'preguntas', 'texto' => 'Cada categoría tiene al menos 1 pregunta', 'ok' => $categorias !== [] && $sinPreguntas === 0,
                'detalle' => $categorias === [] ? 'no hay categorías' : ($sinPreguntas ? self::plural($sinPreguntas, 'categoría sin preguntas', 'categorías sin preguntas') : null)],
            ['clave' => 'opciones', 'texto' => 'Cada opción de las preguntas tiene su puntaje', 'ok' => $sinOpciones === 0,
                'detalle' => $sinOpciones ? self::plural($sinOpciones, 'pregunta con menos de 2 opciones', 'preguntas con menos de 2 opciones') : null],
            ['clave' => 'importancia', 'texto' => 'La importancia de las categorías suma 100%', 'ok' => $sumaOk,
                'detalle' => $sumaOk ? null : 'suma '.self::numero($importancia).'%'],
            ['clave' => 'indicacion', 'texto' => 'Todas las preguntas tienen indicación para responder', 'ok' => $sinIndicacion === 0,
                'detalle' => $sinIndicacion ? 'faltan '.$sinIndicacion : null],
            ['clave' => 'multiple', 'texto' => 'Las selecciones múltiples suman exactamente 100 puntos', 'ok' => $sumasMalas === [],
                'detalle' => match (count($sumasMalas)) {
                    0 => null,
                    1 => $sumasMalas[0] < 100 ? '1 suma solo '.$sumasMalas[0] : '1 suma '.$sumasMalas[0],
                    default => count($sumasMalas).' no suman 100',
                }],
        ];

        $porCorregir = array_filter($porCategoria, fn (array $p) => $p !== []);

        return [
            'publicable' => array_reduce($requisitos, fn (bool $ok, array $r) => $ok && $r['ok'], true),
            'requisitos' => $requisitos,
            'categorias' => $porCorregir,
            'preguntas' => $porPregunta,
            'completas' => count($categorias) - count($porCorregir),
            'total' => count($categorias),
            'importancia' => $importancia,
        ];
    }

    /**
     * Lo que le falta a una pregunta, por clave: indicacion, opciones, multiple.
     *
     * @param  array<string, mixed>  $pregunta
     * @return array<string, string>
     */
    public static function problemasDePregunta(array $pregunta): array
    {
        $problemas = [];

        if (trim((string) ($pregunta['indicacion'] ?? '')) === '') {
            $problemas['indicacion'] = 'Falta la indicación de cómo responder';
        }

        if (($pregunta['tipo'] ?? '') !== 'abierta') {
            /** @var list<array<string, mixed>> $opciones */
            $opciones = $pregunta['opciones'] ?? [];

            if (count($opciones) < 2) {
                $problemas['opciones'] = 'Necesita al menos 2 opciones con puntaje';
            }

            $suma = self::sumaMaxima($pregunta);

            if (($pregunta['tipo'] ?? '') === 'seleccion_multiple' && count($opciones) >= 2 && $suma !== 100) {
                $problemas['multiple'] = $suma < 100
                    ? 'La selección múltiple suma solo '.$suma
                    : 'La selección múltiple suma '.$suma.': se pasa de 100';
            }
        }

        return $problemas;
    }

    /**
     * Lo que suma una selección múltiple con todas marcadas. No cuenta la
     * opción que excluye a las demás («Ninguna»): no se marca junto a otras.
     *
     * @param  array<string, mixed>  $pregunta
     */
    public static function sumaMaxima(array $pregunta): int
    {
        /** @var list<array<string, mixed>> $opciones */
        $opciones = $pregunta['opciones'] ?? [];
        $normales = array_filter($opciones, fn (array $o) => ! ($o['exclusiva'] ?? false));

        return (int) array_sum(array_map(fn (array $o) => (int) ($o['puntaje'] ?? 0), $normales));
    }

    private static function plural(int $n, string $uno, string $varios): string
    {
        return $n.' '.($n === 1 ? $uno : $varios);
    }

    private static function numero(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
}
