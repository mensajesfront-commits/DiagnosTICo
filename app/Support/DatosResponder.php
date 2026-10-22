<?php

namespace App\Support;

use App\Models\Medicion;

/**
 * La medición como la responde la empresa (E2, E3; HU-060, HU-061): el
 * cuestionario de la versión congelada SIN puntajes ni indicaciones para la
 * IA (la empresa no los ve, RN-013), sus respuestas guardadas y el avance.
 *
 * Una pregunta está respondida si tiene alguna opción elegida o texto.
 */
class DatosResponder
{
    /** Estados en los que se puede responder. */
    public const array RESPONDIBLES = ['no_iniciada', 'en_curso'];

    /** Se puede responder: no iniciada o en curso y no pasó la fecha límite (RN-016), en la hora de la empresa. */
    public static function respondible(Medicion $medicion): bool
    {
        return in_array($medicion->estado, self::RESPONDIBLES, true)
            && ! Plazos::vencio($medicion);
    }

    /**
     * Las categorías con sus preguntas, sin puntajes.
     *
     * @return list<array<string, mixed>>
     */
    public static function cuestionario(Medicion $medicion): array
    {
        /** @var list<array<string, mixed>> $categorias */
        $categorias = $medicion->version->contenido['categorias'] ?? [];

        return array_map(fn (array $c) => [
            'ref' => $c['ref'],
            'nombre' => $c['nombre'],
            'preguntas' => array_map(fn (array $p) => [
                'ref' => $p['ref'],
                'texto' => $p['texto'],
                'tipo' => $p['tipo'],
                'indicacion' => $p['indicacion'],
                'obligatoria' => (bool) ($p['obligatoria'] ?? true),
                'opciones' => array_map(fn (array $o) => ['ref' => $o['ref'], 'texto' => $o['texto'], 'exclusiva' => (bool) ($o['exclusiva'] ?? false), 'con_texto' => (bool) ($o['con_texto'] ?? false)], $p['opciones'] ?? []),
            ], $c['preguntas'] ?? []),
        ], $categorias);
    }

    /**
     * Respuestas guardadas por referencia de pregunta.
     *
     * @return array<string, array{opciones: list<string>, texto: string}>
     */
    public static function respuestas(Medicion $medicion): array
    {
        $lista = [];

        foreach ($medicion->respuestas()->get() as $r) {
            $lista[$r->pregunta_ref] = [
                'opciones' => array_map('strval', $r->opciones_elegidas ?? []),
                'texto' => (string) $r->texto,
            ];
        }

        return $lista;
    }

    /**
     * Avance por categoría y total; `actual` es la primera categoría con
     * obligatorias sin responder (o la última).
     *
     * @param  list<array<string, mixed>>  $cuestionario
     * @param  array<string, array{opciones: list<string>, texto: string}>  $respuestas
     * @return array{categorias: list<array{ref: string, nombre: string, respondidas: int, total: int, faltan_obligatorias: int}>, respondidas: int, total: int, porcentaje: int, actual: int, completo: bool}
     */
    public static function avance(array $cuestionario, array $respuestas): array
    {
        $categorias = [];
        $respondidas = 0;
        $total = 0;
        $actual = null;

        foreach ($cuestionario as $i => $c) {
            /** @var list<array<string, mixed>> $preguntas */
            $preguntas = $c['preguntas'];
            $hechas = 0;
            $faltan = 0;

            foreach ($preguntas as $p) {
                $lista = self::respondida($respuestas[$p['ref']] ?? null);
                $hechas += $lista ? 1 : 0;
                $faltan += ! $lista && $p['obligatoria'] ? 1 : 0;
            }

            if ($faltan > 0 && $actual === null) {
                $actual = $i;
            }

            $categorias[] = ['ref' => (string) $c['ref'], 'nombre' => (string) $c['nombre'], 'respondidas' => $hechas, 'total' => count($preguntas), 'faltan_obligatorias' => $faltan];
            $respondidas += $hechas;
            $total += count($preguntas);
        }

        return [
            'categorias' => $categorias,
            'respondidas' => $respondidas,
            'total' => $total,
            'porcentaje' => $total ? (int) floor($respondidas * 100 / $total) : 0,
            'actual' => $actual ?? max(0, count($categorias) - 1),
            'completo' => $actual === null,
        ];
    }

    /** @param  array{opciones: list<string>, texto: string}|null  $respuesta */
    public static function respondida(?array $respuesta): bool
    {
        return $respuesta !== null && ($respuesta['opciones'] !== [] || trim($respuesta['texto']) !== '');
    }

    /**
     * Avance de una medición, para el inicio de la empresa y de A1.
     *
     * @return array{categorias: list<array{ref: string, nombre: string, respondidas: int, total: int, faltan_obligatorias: int}>, respondidas: int, total: int, porcentaje: int, actual: int, completo: bool}
     */
    public static function avanceDe(Medicion $medicion): array
    {
        return self::avance(self::cuestionario($medicion), self::respuestas($medicion));
    }
}
