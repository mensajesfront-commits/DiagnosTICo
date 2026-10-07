<?php

namespace App\Support;

use DateTimeInterface;

/**
 * Fechas en español sin depender del idioma del servidor.
 */
class Fechas
{
    private const array MESES = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];

    /** "6 de enero de 2027". */
    public static function larga(DateTimeInterface $fecha): string
    {
        return $fecha->format('j').' de '.self::MESES[(int) $fecha->format('n') - 1].' de '.$fecha->format('Y');
    }
}
