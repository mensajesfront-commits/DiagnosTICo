<?php

namespace App\Support;

/**
 * Niveles del resultado (RN-019). El backend decide el nivel; la pantalla
 * solo lo muestra con sus colores (resources/js/lib/niveles.ts, mismas
 * claves).
 */
class Niveles
{
    /**
     * Clave → [nombre, desde, hasta], de menor a mayor.
     *
     * @var array<string, array{0: string, 1: int, 2: int}>
     */
    public const array NIVELES = [
        'critico' => ['Crítico', 0, 29],
        'mejorar' => ['Se puede mejorar', 30, 59],
        'camino' => ['Vas en buen camino', 60, 79],
        'sigue' => ['Sigue así', 80, 100],
    ];

    /** Clave del nivel de un puntaje de 0 a 100. */
    public static function de(int|float $puntaje): string
    {
        $puntaje = max(0, min(100, (int) round($puntaje)));

        foreach (self::NIVELES as $clave => [, $desde, $hasta]) {
            if ($puntaje >= $desde && $puntaje <= $hasta) {
                return $clave;
            }
        }

        return 'critico';
    }

    public static function nombre(string $clave): string
    {
        return self::NIVELES[$clave][0] ?? $clave;
    }
}
