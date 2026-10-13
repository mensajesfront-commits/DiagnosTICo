<?php

namespace App\Support;

use App\Console\Commands\GenerarUbicaciones;
use App\Models\User;

/**
 * Listas que ofrece "Mi perfil" (A6 y E11).
 */
class OpcionesPerfil
{
    /**
     * Ciudades de las zonas horarias con su nombre en español (la base de
     * zonas las trae en inglés y sin tildes). Las que no están aquí se
     * muestran como vienen, cambiando "_" por espacio.
     *
     * @var array<string, string>
     */
    private const array CIUDADES = [
        'Asuncion' => 'Asunción',
        'Bahia_Banderas' => 'Bahía de Banderas',
        'Bogota' => 'Bogotá',
        'Cancun' => 'Cancún',
        'Ciudad_Juarez' => 'Ciudad Juárez',
        'Cordoba' => 'Córdoba',
        'Costa_Rica' => 'San José',
        'Easter' => 'Isla de Pascua',
        'El_Salvador' => 'San Salvador',
        'Galapagos' => 'Galápagos',
        'Guatemala' => 'Ciudad de Guatemala',
        'Havana' => 'La Habana',
        'Mazatlan' => 'Mazatlán',
        'Merida' => 'Mérida',
        'Mexico_City' => 'Ciudad de México',
        'Panama' => 'Ciudad de Panamá',
        'Rio_Gallegos' => 'Río Gallegos',
        'Tucuman' => 'Tucumán',
    ];

    /**
     * Zonas horarias de los 18 países del sistema (DEC-016), sacadas de la
     * base oficial de zonas (IANA, la que trae PHP): identificador → "País ·
     * Ciudad (UTC−5)", ordenadas por país y ciudad. La diferencia con UTC es
     * la de hoy (en algunos países cambia con el horario de verano).
     *
     * `$incluir` agrega la zona que ya tiene la cuenta si no está en la
     * lista (por ejemplo, Europe/Madrid de antes), para no perderla.
     *
     * @return array<string, string>
     */
    public static function zonasHorarias(?string $incluir = null): array
    {
        $zonas = [];

        foreach (Ubicaciones::PAISES as $iso => [$pais]) {
            foreach (\DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $iso) as $id) {
                $zonas[$id] = [$pais, self::ciudad($id), self::diferencia($id)];
            }
        }

        if ($incluir !== null && ! isset($zonas[$incluir]) && in_array($incluir, \DateTimeZone::listIdentifiers(), true)) {
            $zonas[$incluir] = ['Otra zona', self::ciudad($incluir), self::diferencia($incluir)];
        }

        uasort($zonas, fn (array $a, array $b): int => GenerarUbicaciones::comparar($a[0], $b[0]) ?: GenerarUbicaciones::comparar($a[1], $b[1]));

        return array_map(fn (array $z): string => "{$z[0]} · {$z[1]} ({$z[2]})", $zonas);
    }

    private static function ciudad(string $id): string
    {
        $ultima = substr($id, (int) strrpos($id, '/') + 1);

        return self::CIUDADES[$ultima] ?? str_replace('_', ' ', $ultima);
    }

    /** "UTC−5", "UTC−3", "UTC+1" (con el signo menos tipográfico). */
    private static function diferencia(string $id): string
    {
        $segundos = (new \DateTimeZone($id))->getOffset(new \DateTimeImmutable('now'));
        $horas = intdiv(abs($segundos), 3600);
        $minutos = intdiv(abs($segundos) % 3600, 60);

        return 'UTC'.($segundos < 0 ? '−' : '+').$horas.($minutos > 0 ? ':'.str_pad((string) $minutos, 2, '0', STR_PAD_LEFT) : '');
    }

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
