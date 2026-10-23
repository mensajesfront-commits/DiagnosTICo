<?php

namespace App\Support;

/**
 * Métodos con los que la IA analiza cada categoría (pedido del equipo, 14 de
 * octubre): no siempre DOFA; la IA elige el que mejor encaja con lo que
 * respondió la empresa y el resultado muestra cuál usó y por qué.
 *
 * NuevasTIC confirmó (23 de octubre de 2026) que la IA puede usar DOFA,
 * PESTEL y los demás métodos; RN-028 se ajustó. No hay una matriz FODA: solo
 * el nombre del método que guió la observación y la recomendación.
 */
class MetodosAnalisis
{
    /**
     * Clave → [nombre, para qué sirve, cuándo usarlo].
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    public const array METODOS = [
        'PESTEL' => [
            'PESTEL',
            'Factores del entorno: políticos, económicos, sociales, tecnológicos, ecológicos y legales.',
            'cuando las respuestas dependen del entorno: normas, economía, tecnología o cambios en el mercado.',
        ],
        'PORTER' => [
            '5 Fuerzas de Porter',
            'Competidores, poder de clientes y proveedores, sustitutos y nuevos competidores.',
            'cuando las respuestas tratan de la competencia, los precios o el poder de clientes y proveedores.',
        ],
        'VRIO' => [
            'VRIO',
            'Recursos propios: si son valiosos, raros, difíciles de imitar y si la empresa está organizada para aprovecharlos.',
            'cuando las respuestas tratan de lo que la empresa tiene: marca, equipo, contenido, herramientas o datos.',
        ],
        'DOFA' => [
            'DOFA',
            'Debilidades, oportunidades, fortalezas y amenazas.',
            'cuando las respuestas mezclan lo interno (fortalezas y debilidades) con lo externo (oportunidades y amenazas).',
        ],
        'CAME' => [
            'CAME',
            'Corregir debilidades, afrontar amenazas, mantener fortalezas y explotar oportunidades.',
            'cuando el problema ya está claro y hace falta convertirlo en acciones concretas.',
        ],
    ];

    /** @return list<string> */
    public static function claves(): array
    {
        return array_keys(self::METODOS);
    }

    public static function nombre(string $clave): string
    {
        return self::METODOS[$clave][0] ?? $clave;
    }

    /** Texto para las instrucciones de la IA: cada método y cuándo usarlo. */
    public static function guia(): string
    {
        $lineas = [];

        foreach (self::METODOS as $clave => [$nombre, $que, $cuando]) {
            $lineas[] = "  - {$clave} ({$nombre}): {$que} Úsalo {$cuando}";
        }

        return implode("\n", $lineas);
    }

    /**
     * Método por defecto según el nombre de la categoría (analizador de
     * prueba y resultado de ejemplo, sin IA).
     */
    public static function porCategoria(string $nombre, int $puntaje = 50): string
    {
        $n = mb_strtolower($nombre);

        return match (true) {
            str_contains($n, 'mercado') || str_contains($n, 'entorno') => 'PESTEL',
            str_contains($n, 'competencia') => 'PORTER',
            str_contains($n, 'marca') || str_contains($n, 'humanos') || str_contains($n, 'contenido') => 'VRIO',
            $puntaje < 30 => 'CAME',
            default => 'DOFA',
        };
    }
}
