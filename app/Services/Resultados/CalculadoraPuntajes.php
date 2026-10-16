<?php

namespace App\Services\Resultados;

/**
 * Puntajes del resultado (RN-018, HU-068). Funciones puras: la misma entrada
 * da siempre el mismo puntaje.
 *
 * - Opción única: el puntaje de la opción elegida.
 * - Selección múltiple: la suma de las marcadas, con tope 100.
 * - Abierta: el puntaje 0–100 que da la IA.
 * - Categoría: el promedio de sus preguntas respondidas, redondeado.
 * - Total: el promedio de las categorías ponderado por su importancia,
 *   redondeado. Se calcula con los puntajes de categoría ya redondeados, los
 *   mismos que ve la empresa en la tabla.
 *
 * [FUNCIONALIDAD POR DEFINIR] Una pregunta opcional sin responder no entra al
 * promedio de su categoría (no cuenta como 0). Si el equipo decide que cuente
 * como 0, el cambio va solo en categoria().
 */
class CalculadoraPuntajes
{
    /**
     * Puntaje de una pregunta cerrada según las opciones elegidas; null si no
     * eligió ninguna.
     *
     * @param  array<string, mixed>  $pregunta  pregunta de la versión congelada
     * @param  list<int|string>  $elegidas  referencias de las opciones
     */
    public static function cerrada(array $pregunta, array $elegidas): ?int
    {
        /** @var list<array{ref: string, puntaje: int|float|string}> $opciones */
        $opciones = $pregunta['opciones'] ?? [];
        $puntajes = [];

        foreach ($opciones as $opcion) {
            if (in_array($opcion['ref'], array_map('strval', $elegidas), true)) {
                $puntajes[] = (int) $opcion['puntaje'];
            }
        }

        if ($puntajes === []) {
            return null;
        }

        if ($pregunta['tipo'] === 'opcion_unica') {
            return max(0, min(100, $puntajes[0]));
        }

        return max(0, min(100, array_sum($puntajes)));
    }

    /**
     * Promedio redondeado de las preguntas con puntaje; null si ninguna tiene.
     *
     * @param  list<int|null>  $puntajes
     */
    public static function categoria(array $puntajes): ?int
    {
        $con = array_values(array_filter($puntajes, fn (?int $p) => $p !== null));

        return $con === [] ? null : (int) round(array_sum($con) / count($con));
    }

    /**
     * Promedio ponderado por importancia (RN-011: suman 100).
     *
     * @param  list<array{puntaje: int, importancia: float|int}>  $categorias
     */
    public static function total(array $categorias): int
    {
        $pesos = array_sum(array_column($categorias, 'importancia'));

        if ($pesos <= 0) {
            return $categorias === [] ? 0 : (int) round(array_sum(array_column($categorias, 'puntaje')) / count($categorias));
        }

        $suma = 0.0;

        foreach ($categorias as $c) {
            $suma += $c['puntaje'] * $c['importancia'];
        }

        return (int) round($suma / $pesos);
    }

    /**
     * La respuesta como texto para la IA y para la pantalla: las opciones
     * elegidas separadas por " · " o el texto de la abierta. Null si no
     * respondió.
     *
     * @param  array<string, mixed>  $pregunta
     * @param  list<int|string>|null  $elegidas
     */
    public static function textoRespuesta(array $pregunta, ?array $elegidas, ?string $texto): ?string
    {
        if ($pregunta['tipo'] === 'abierta') {
            $texto = trim((string) $texto);

            return $texto === '' ? null : $texto;
        }

        $elegidas = array_map('strval', $elegidas ?? []);
        /** @var list<array{ref: string, texto: string, con_texto?: bool}> $opciones */
        $opciones = $pregunta['opciones'] ?? [];
        $detalle = trim((string) $texto);
        $textos = [];

        foreach ($opciones as $opcion) {
            if (in_array($opcion['ref'], $elegidas, true)) {
                // "Otro" con lo que escribió la empresa: "Otro: TikTok".
                $textos[] = ($opcion['con_texto'] ?? false) && $detalle !== ''
                    ? $opcion['texto'].': '.$detalle
                    : $opcion['texto'];
            }
        }

        return $textos === [] ? null : implode(' · ', $textos);
    }
}
