<?php

namespace App\Support;

/**
 * Indicativos de los países de la ubicación (`Ubicaciones::PAISES`) y
 * cuántos dígitos lleva el número sin el indicativo. La misma lista está en
 * resources/js/lib/telefonos.ts, que arma el campo.
 *
 * El teléfono se guarda como "+57 3001234567".
 */
class Telefonos
{
    /** @var array<string, array{0: string, 1: string, 2: int}> ISO => [país, indicativo, dígitos] */
    public const array INDICATIVOS = [
        'AR' => ['Argentina', '+54', 11],
        'BO' => ['Bolivia', '+591', 8],
        'CL' => ['Chile', '+56', 9],
        'CO' => ['Colombia', '+57', 10],
        'CR' => ['Costa Rica', '+506', 8],
        'CU' => ['Cuba', '+53', 8],
        'EC' => ['Ecuador', '+593', 9],
        'SV' => ['El Salvador', '+503', 8],
        'GT' => ['Guatemala', '+502', 8],
        'HN' => ['Honduras', '+504', 8],
        'MX' => ['México', '+52', 10],
        'NI' => ['Nicaragua', '+505', 8],
        'PA' => ['Panamá', '+507', 8],
        'PY' => ['Paraguay', '+595', 9],
        'PE' => ['Perú', '+51', 9],
        'DO' => ['República Dominicana', '+1', 10],
        'UY' => ['Uruguay', '+598', 8],
        'VE' => ['Venezuela', '+58', 10],
    ];

    /**
     * País y dígitos esperados de un indicativo ("+57"), o null si no está.
     *
     * @return array{0: string, 1: int}|null
     */
    public static function de(string $indicativo): ?array
    {
        foreach (self::INDICATIVOS as [$pais, $codigo, $digitos]) {
            if ($codigo === $indicativo) {
                return [$pais, $digitos];
            }
        }

        return null;
    }
}
