<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Datos del informe PDF (E10, RN-024), según la propuesta de reestructuración
 * del 2 de noviembre de 2026 (fase 1: solo lo que sale de las respuestas y del
 * análisis de la IA).
 *
 * Todo se calcula aquí; la plantilla solo lo muestra. Lo que el sistema no
 * recoge (capturas de canales, competidores, línea base de los KPI) no se
 * inventa: el informe lo marca como información no disponible y dice cómo
 * obtenerla.
 */
final class DatosInforme
{
    /**
     * @param  array<string, mixed>  $cabecera
     * @param  array<string, mixed>  $datos
     * @param  array<string, string>|null  $empresa
     * @return array<string, mixed>
     */
    public static function armar(array $cabecera, array $datos, ?CarbonInterface $enviada, CarbonInterface $generado, ?array $empresa): array
    {
        /** @var list<array<string, mixed>> $categorias */
        $categorias = array_values($datos['categorias']);
        $orden = DatosResultado::ordenadas($categorias);
        $prioridad = array_flip(array_column($orden, 'ref'));
        $pesos = array_sum(array_column($categorias, 'importancia')) ?: 1;

        $detalladas = array_map(fn (array $c) => self::categoria($c, $prioridad[$c['ref']] + 1, $pesos, $categorias), $categorias);
        $porPrioridad = $detalladas;
        usort($porPrioridad, fn (array $a, array $b) => $a['prioridad'] <=> $b['prioridad']);

        $fichas = self::fichas($porPrioridad);
        $exacto = array_sum(array_map(fn (array $c) => $c['puntaje'] * $c['importancia'], $categorias)) / $pesos;

        return [
            ...$cabecera,
            ...$datos,
            'empresa_ficha' => $empresa,
            'nivel_nombre' => Niveles::nombreInforme((string) $datos['nivel']),
            'niveles' => collect(Niveles::NIVELES)->map(fn (array $n, string $clave) => [
                'clave' => $clave,
                'nombre' => Niveles::nombreInforme($clave),
                'nombre_pantalla' => $n[0],
                'rango' => "{$n[1]}–{$n[2]}",
                'interpretacion' => Niveles::enCategoria($clave),
            ])->values()->all(),
            'fecha_respuesta' => $enviada ? CorreoAviso::fecha($enviada) : null,
            'fecha_generado' => CorreoAviso::fecha($generado),
            'total_exacto' => round($exacto, 1),
            'categorias' => $detalladas,
            'por_prioridad' => $porPrioridad,
            'preguntas_total' => array_sum(array_map(fn (array $c) => count($c['preguntas']), $detalladas)),
            'preguntas_respondidas' => array_sum(array_column($detalladas, 'respondidas')),
            'por_nivel' => collect(Niveles::NIVELES)->map(fn (array $n, string $clave) => [
                'clave' => $clave,
                'nombre' => Niveles::nombreInforme($clave),
                'cantidad' => count(array_filter($categorias, fn (array $c) => $c['nivel'] === $clave)),
            ])->values()->all(),
            'ejemplo_calculo' => collect($detalladas)->sortByDesc('importancia')->first(),
            'metodos' => array_map(fn (string $clave) => [
                ...(array) MetodosAnalisis::explicado($clave),
                'usado_en' => array_values(array_map(fn (array $c) => $c['nombre'], array_filter($detalladas, fn (array $c) => ($c['metodo']['clave'] ?? null) === $clave))),
            ], MetodosAnalisis::claves()),
            'dofa' => self::dofa($porPrioridad),
            'faltante' => self::conCategoria($porPrioridad, 'faltante'),
            'problemas' => array_values(array_filter($porPrioridad, fn (array $c) => in_array($c['nivel'], ['critico', 'mejorar'], true))),
            'fichas' => $fichas,
            'cronograma' => self::cronograma($fichas),
            'radar' => [
                'etiquetas' => array_column($categorias, 'nombre'),
                'actual' => array_column($categorias, 'puntaje'),
                'anterior' => $datos['variacion'] === null ? null : array_map(fn (array $c) => $c['anterior'], $categorias),
            ],
            'soporte' => (string) config('diagnostico.soporte_email'),
        ];
    }

    /**
     * La categoría con lo que el informe explica.
     *
     * @param  array<string, mixed>  $c
     * @param  list<array<string, mixed>>  $todas
     * @return array<string, mixed>
     */
    private static function categoria(array $c, int $prioridad, float|int $pesos, array $todas): array
    {
        $preguntas = array_values((array) ($c['preguntas'] ?? []));
        $metodo = isset($c['metodo']['clave']) ? MetodosAnalisis::explicado((string) $c['metodo']['clave']) : null;

        // Todos los grupos del método, también los que la IA dejó vacíos.
        $porGrupo = collect((array) ($c['hallazgos'] ?? []))->keyBy('grupo');
        $grupos = $metodo === null ? [] : array_map(fn (array $g) => [
            ...$g,
            'puntos' => (array) ($porGrupo[$g['nombre']]['puntos'] ?? []),
        ], $metodo['grupos']);

        // Empate de puntaje con otra categoría: cómo se ordenó (propuesta, p. 14).
        $empates = array_values(array_filter($todas, fn (array $o) => $o['ref'] !== $c['ref'] && $o['puntaje'] === $c['puntaje']));
        $desempate = '';

        if ($empates !== []) {
            $nombres = implode(' y ', array_column($empates, 'nombre'));
            $mismaImportancia = array_filter($empates, fn (array $o) => (float) $o['importancia'] === (float) $c['importancia']) !== [];
            $desempate = $mismaImportancia
                ? "Empata con {$nombres} en puntaje e importancia; se ordenan como aparecen en el diagnóstico."
                : "Empata en puntaje con {$nombres}; va primero la de mayor importancia.";
        }

        $recomendaciones = ($c['recomendaciones'] ?? []) !== [] ? $c['recomendaciones'] : [$c['recomendacion']];

        return [
            ...$c,
            'nivel_nombre' => Niveles::nombreInforme((string) $c['nivel']),
            'prioridad' => $prioridad,
            'desempate' => $desempate,
            'aporte' => round($c['puntaje'] * $c['importancia'] / $pesos, 1),
            'significado' => Niveles::enCategoria((string) $c['nivel']),
            'metodo_explicado' => $metodo,
            'grupos' => $grupos,
            'impacto' => (string) ($c['impacto'] ?? ''),
            'fortalezas' => (array) ($c['fortalezas'] ?? []),
            'debilidades' => (array) ($c['debilidades'] ?? []),
            'oportunidades' => (array) ($c['oportunidades'] ?? []),
            'amenazas' => (array) ($c['amenazas'] ?? []),
            'faltante' => (array) ($c['faltante'] ?? []),
            'recomendaciones' => array_map(fn ($r) => self::recomendacion((array) $r), array_values((array) $recomendaciones)),
            'preguntas' => $preguntas,
            'respondidas' => count(array_filter($preguntas, fn ($q) => is_array($q) && $q['respuesta'] !== null)),
        ];
    }

    /**
     * La recomendación con todos los campos de la ficha de acción (los
     * resultados viejos no los traen: quedan vacíos).
     *
     * @param  array<mixed>  $r
     * @return array<string, mixed>
     */
    private static function recomendacion(array $r): array
    {
        $texto = fn (array $d, string $k) => is_scalar($d[$k] ?? null) ? trim((string) $d[$k]) : '';
        $kpi = is_array($r['kpi'] ?? null) ? $r['kpi'] : [];

        return [
            'titulo' => $texto($r, 'titulo'),
            'detalle' => $texto($r, 'detalle'),
            'problema' => $texto($r, 'problema'),
            'objetivo' => $texto($r, 'objetivo'),
            'pasos' => array_values(array_filter((array) ($r['pasos'] ?? []), 'is_string')),
            'responsable' => $texto($r, 'responsable'),
            'recursos' => $texto($r, 'recursos'),
            'plazo' => $texto($r, 'plazo'),
            'semanas' => is_numeric($r['semanas'] ?? null) ? max(1, min(12, (int) $r['semanas'])) : null,
            'indicador' => $texto($r, 'indicador'),
            'kpi' => [
                'formula' => $texto($kpi, 'formula'),
                'linea_base' => $texto($kpi, 'linea_base'),
                'meta' => $texto($kpi, 'meta'),
                'frecuencia' => $texto($kpi, 'frecuencia'),
                'fuente' => $texto($kpi, 'fuente'),
            ],
            'evidencia' => $texto($r, 'evidencia'),
        ];
    }

    /**
     * Fichas de acción en orden: prioritarias (la principal de las 3
     * categorías más débiles), complementarias (la principal de las demás) y
     * de seguimiento (las otras recomendaciones de cada categoría).
     *
     * @param  list<array<string, mixed>>  $porPrioridad
     * @return list<array<string, mixed>>
     */
    private static function fichas(array $porPrioridad): array
    {
        $fichas = [];

        foreach ($porPrioridad as $n => $c) {
            foreach ($c['recomendaciones'] as $i => $r) {
                $fichas[] = [
                    ...$r,
                    'categoria' => $c['nombre'],
                    'ref' => $c['ref'],
                    'nivel' => $c['nivel'],
                    'orden_en_categoria' => $i,
                    'tipo' => $i > 0 ? 'seguimiento' : ($n < 3 ? 'prioritaria' : 'complementaria'),
                ];
            }
        }

        $peso = ['prioritaria' => 0, 'complementaria' => 1, 'seguimiento' => 2];
        usort($fichas, fn (array $a, array $b) => $peso[$a['tipo']] <=> $peso[$b['tipo']]);

        foreach ($fichas as $k => $f) {
            $fichas[$k]['numero'] = $k + 1;
        }

        return $fichas;
    }

    /**
     * Cronograma por semanas. Las prioritarias empiezan en la semana 1; las
     * complementarias, cuando termina la primera prioritaria; cada acción de
     * seguimiento, cuando termina la anterior de su categoría. Las fichas sin
     * semanas (resultados viejos) quedan fuera y se avisa.
     *
     * @param  list<array<string, mixed>>  $fichas
     * @return array{semanas: int, filas: list<array<string, mixed>>, sin_semanas: int}
     */
    private static function cronograma(array $fichas): array
    {
        $con = array_values(array_filter($fichas, fn (array $f) => $f['semanas'] !== null));
        $finPorCategoria = [];
        $primeraPrioritaria = null;
        $filas = [];

        foreach ($con as $f) {
            $inicio = match ($f['tipo']) {
                'prioritaria' => 1,
                'complementaria' => ($primeraPrioritaria ?? 0) + 1,
                default => ($finPorCategoria[$f['ref']] ?? 0) + 1,
            };
            $fin = $inicio + $f['semanas'] - 1;

            if ($f['tipo'] === 'prioritaria') {
                $primeraPrioritaria = $primeraPrioritaria === null ? $fin : min($primeraPrioritaria, $fin);
            }

            $finPorCategoria[$f['ref']] = max($finPorCategoria[$f['ref']] ?? 0, $fin);
            $filas[] = ['numero' => $f['numero'], 'titulo' => $f['titulo'], 'categoria' => $f['categoria'], 'tipo' => $f['tipo'], 'inicio' => $inicio, 'fin' => $fin];
        }

        return [
            'semanas' => min(16, max([4, ...array_column($filas, 'fin')])),
            'filas' => $filas,
            'sin_semanas' => count($fichas) - count($con),
        ];
    }

    /**
     * DOFA consolidado: lo que la IA clasificó en cada categoría.
     *
     * @param  list<array<string, mixed>>  $categorias
     * @return array<string, list<array{texto: string, categoria: string}>>
     */
    private static function dofa(array $categorias): array
    {
        return [
            'fortalezas' => self::conCategoria(array_reverse($categorias), 'fortalezas'),
            'oportunidades' => self::conCategoria($categorias, 'oportunidades'),
            'debilidades' => self::conCategoria($categorias, 'debilidades'),
            'amenazas' => self::conCategoria($categorias, 'amenazas'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $categorias
     * @return list<array{texto: string, categoria: string}>
     */
    private static function conCategoria(array $categorias, string $campo): array
    {
        $lista = [];

        foreach ($categorias as $c) {
            foreach ((array) $c[$campo] as $texto) {
                if (is_string($texto) && trim($texto) !== '') {
                    $lista[] = ['texto' => trim($texto), 'categoria' => (string) $c['nombre']];
                }
            }
        }

        return $lista;
    }
}
