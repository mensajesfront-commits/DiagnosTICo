<?php

namespace App\Support;

/**
 * El análisis cuando no hay IA: el modo de prueba (IA_DE_PRUEBA), el
 * resultado de ejemplo (HU-030) y la categoría sin respuestas.
 *
 * Los textos son ilustrativos y empiezan por "Ejemplo:" o "Prueba:", para que
 * nadie los confunda con un análisis real. Llenan todos los grupos del método
 * y todos los campos, para ver cómo queda cada parte del resultado y del PDF.
 */
final class AnalisisSinIa
{
    /**
     * @param  string  $tipo  "de prueba" o "de ejemplo"
     * @return array{impacto: string, hallazgos: list<array{grupo: string, puntos: list<string>}>, fortalezas: list<string>, debilidades: list<string>, oportunidades: list<string>, amenazas: list<string>, faltante: list<string>, recomendaciones: list<array<string, mixed>>, recomendacion: array{titulo: string, detalle: string}}
     */
    public static function partes(string $categoria, string $metodo, string $tipo): array
    {
        $marca = $tipo === 'de prueba' ? 'Prueba:' : 'Ejemplo:';
        $tema = mb_strtolower($categoria);

        $hallazgos = array_map(fn (string $grupo) => [
            'grupo' => $grupo,
            'puntos' => ["{$marca} lo que las respuestas de {$tema} muestran sobre «".mb_strtolower($grupo).'».'],
        ], MetodosAnalisis::grupos($metodo));

        $recomendaciones = [
            [
                'titulo' => "Ordena lo básico de {$tema}",
                'detalle' => "{$marca} es la acción que más sube el puntaje de la categoría.",
                'problema' => "{$marca} la práctica más débil que mostraron las respuestas.",
                'objetivo' => "{$marca} tener esa práctica funcionando cada semana.",
                'pasos' => [
                    'Revisa cómo se hace hoy y anótalo.',
                    'Define una forma sencilla de hacerlo y quién la hace.',
                    'Hazlo cada semana durante un mes.',
                ],
                'responsable' => 'Quien maneja el marketing de la empresa',
                'recursos' => 'Una hoja de cálculo y 2 horas por semana',
                'plazo' => '4 semanas',
                'semanas' => 4,
                'indicador' => 'Semanas en que se cumplió la práctica',
                'kpi' => ['formula' => 'Semanas cumplidas ÷ semanas del plazo × 100', 'linea_base' => '', 'meta' => '', 'frecuencia' => 'Semanal', 'fuente' => 'Registro de la empresa'],
                'evidencia' => 'La hoja con el registro de cada semana',
            ],
            [
                'titulo' => "Mide los resultados de {$tema}",
                'detalle' => "{$marca} sin medir no se sabe qué funciona.",
                'problema' => "{$marca} hoy no se registra ningún dato de esta categoría.",
                'objetivo' => "{$marca} tener un dato mensual para comparar.",
                'pasos' => [
                    'Elige un dato que se pueda contar.',
                    'Anótalo cada semana en la misma hoja.',
                    'Compara cada mes y ajusta.',
                ],
                'responsable' => 'Quien maneja el marketing de la empresa',
                'recursos' => 'Las estadísticas gratuitas de cada canal',
                'plazo' => '8 semanas',
                'semanas' => 8,
                'indicador' => 'Meses con el dato registrado',
                'kpi' => ['formula' => 'Meses con registro completo', 'linea_base' => '', 'meta' => '', 'frecuencia' => 'Mensual', 'fuente' => 'Estadísticas de cada canal'],
                'evidencia' => 'Capturas mensuales de las estadísticas',
            ],
        ];

        return [
            'impacto' => "{$marca} mientras esta categoría siga baja, la empresa pierde clientes que la buscan en internet.",
            'hallazgos' => $hallazgos,
            'fortalezas' => ["{$marca} algo que la empresa ya hace bien en {$tema}."],
            'debilidades' => ["{$marca} la práctica que menos puntaje sacó en {$tema}."],
            'oportunidades' => ["{$marca} algo del mercado que la empresa podría aprovechar."],
            'amenazas' => [],
            'faltante' => ["{$marca} un dato que las respuestas no dan y cómo conseguirlo."],
            'recomendaciones' => $recomendaciones,
            'recomendacion' => ['titulo' => $recomendaciones[0]['titulo'], 'detalle' => $recomendaciones[0]['detalle']],
        ];
    }
}
