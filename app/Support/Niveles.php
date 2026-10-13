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

    /**
     * Frase que explica el nivel en el resultado (HU-064 CA-001) y en el PDF.
     * La de "mejorar" es la del wireframe E6.
     *
     * [INFORMACIÓN PENDIENTE] NuevasTIC aprueba las frases de los otros tres
     * niveles; estas son una propuesta del equipo.
     *
     * @var array<string, string>
     */
    public const array FRASES = [
        'critico' => 'Tu marketing digital está empezando. Elige una categoría en nivel Crítico y trabaja en ella primero: pequeños cambios ya se notan.',
        'mejorar' => 'Tienes bases, pero algunas áreas frenan tu crecimiento. Empieza por las categorías en nivel Crítico y Se puede mejorar.',
        'camino' => 'Tu marketing digital funciona. Refuerza las categorías más bajas para dar el siguiente paso.',
        'sigue' => 'Tu marketing digital está sólido. Mantén lo que haces y mide cada mes para no perder terreno.',
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

    public static function frase(string $clave): string
    {
        return self::FRASES[$clave] ?? '';
    }

    public static function nombre(string $clave): string
    {
        return self::NIVELES[$clave][0] ?? $clave;
    }
}
