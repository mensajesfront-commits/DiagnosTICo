<?php

namespace App\Support;

use App\Models\User;

/**
 * Listas que ofrece "Mi perfil" (A6 y E11).
 */
class OpcionesPerfil
{
    /** [INFORMACIÓN PENDIENTE] Lista de países; por ahora solo Colombia. */
    public const array PAISES = ['Colombia'];

    /** @var array<string, string> */
    public const array ZONAS_HORARIAS = [
        'America/Bogota' => 'América/Bogotá (UTC−5)',
        'America/Mexico_City' => 'América/Ciudad de México (UTC−6)',
        'America/Lima' => 'América/Lima (UTC−5)',
        'America/Santiago' => 'América/Santiago (UTC−4/−3)',
        'America/Argentina/Buenos_Aires' => 'América/Buenos Aires (UTC−3)',
        'Europe/Madrid' => 'Europa/Madrid (UTC+1/+2)',
    ];

    /** [INFORMACIÓN PENDIENTE] El sistema solo está en español. */
    public const array IDIOMAS = ['es' => 'Español'];

    /** Rangos de "Número de empleados" (E11). */
    public const array RANGOS_EMPLEADOS = ['1 a 10', '11 a 50', '51 a 200', 'Más de 200'];

    /**
     * Avisos por correo de las cuentas internas (A6), con su valor inicial.
     *
     * @var array<string, array{0: string, 1: bool}>
     */
    public const array AVISOS_INTERNOS = [
        'empresa_envia' => ['Cuando una empresa envía su diagnóstico', true],
        'ia_falla' => ['Cuando falla el análisis de la IA', true],
        'resumen_semanal' => ['Resumen semanal de mediciones pendientes', false],
    ];

    /**
     * Avisos por correo de las cuentas de empresa (E11).
     *
     * @var array<string, array{0: string, 1: bool}>
     */
    public const array AVISOS_EMPRESA = [
        'medicion_asignada' => ['Avisarme por correo cuando me asignen una medición', true],
        'resultado_listo' => ['Avisarme por correo cuando mi resultado esté listo', true],
    ];

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function avisosPara(User $usuario): array
    {
        return $usuario->empresa_id === null ? self::AVISOS_INTERNOS : self::AVISOS_EMPRESA;
    }

    /**
     * Avisos de la cuenta: lo que eligió o, si no ha elegido, el valor inicial.
     *
     * @return array<string, bool>
     */
    public static function avisosDe(User $usuario): array
    {
        $elegidos = $usuario->avisos ?? [];
        $avisos = [];

        foreach (self::avisosPara($usuario) as $clave => [, $inicial]) {
            $avisos[$clave] = (bool) ($elegidos[$clave] ?? $inicial);
        }

        return $avisos;
    }
}
