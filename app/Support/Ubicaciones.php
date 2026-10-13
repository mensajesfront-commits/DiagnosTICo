<?php

namespace App\Support;

use App\Console\Commands\GenerarUbicaciones;
use Illuminate\Support\Facades\File;

/**
 * País → departamento (estado, provincia o región) → ciudad, para el
 * registro (L2) y Mi perfil (A6, E11).
 *
 * Los 18 países de Hispanoamérica (decisión del equipo, 8 de octubre de
 * 2026). Los departamentos y ciudades están en resources/ubicaciones/{ISO}.json,
 * generados con `php artisan ubicaciones:generar` a partir de la base
 * countries-states-cities-database (licencia ODbL; ver
 * resources/ubicaciones/LEEME.md).
 */
class Ubicaciones
{
    /**
     * Código ISO → [nombre en español, cómo se llama su división].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const array PAISES = [
        'AR' => ['Argentina', 'Provincia'],
        'BO' => ['Bolivia', 'Departamento'],
        'CL' => ['Chile', 'Región'],
        'CO' => ['Colombia', 'Departamento'],
        'CR' => ['Costa Rica', 'Provincia'],
        'CU' => ['Cuba', 'Provincia'],
        'EC' => ['Ecuador', 'Provincia'],
        'SV' => ['El Salvador', 'Departamento'],
        'GT' => ['Guatemala', 'Departamento'],
        'HN' => ['Honduras', 'Departamento'],
        'MX' => ['México', 'Estado'],
        'NI' => ['Nicaragua', 'Departamento'],
        'PA' => ['Panamá', 'Provincia'],
        'PY' => ['Paraguay', 'Departamento'],
        'PE' => ['Perú', 'Departamento'],
        'DO' => ['República Dominicana', 'Provincia'],
        'UY' => ['Uruguay', 'Departamento'],
        'VE' => ['Venezuela', 'Estado'],
    ];

    /**
     * Para las listas: [{codigo, nombre, division}], en orden alfabético.
     *
     * @return list<array{codigo: string, nombre: string, division: string}>
     */
    public static function paises(): array
    {
        $lista = [];

        foreach (self::PAISES as $codigo => [$nombre, $division]) {
            $lista[] = ['codigo' => $codigo, 'nombre' => $nombre, 'division' => $division];
        }

        usort($lista, fn (array $a, array $b): int => GenerarUbicaciones::comparar($a['nombre'], $b['nombre']));

        return $lista;
    }

    /** @return list<string> */
    public static function nombresDePaises(): array
    {
        return array_values(array_map(fn (array $p): string => $p[0], self::PAISES));
    }

    public static function codigoDe(string $nombrePais): ?string
    {
        foreach (self::PAISES as $codigo => [$nombre]) {
            if ($nombre === $nombrePais) {
                return $codigo;
            }
        }

        return null;
    }

    /**
     * Departamentos de un país con sus ciudades.
     *
     * @return list<array{nombre: string, ciudades: list<string>}>
     */
    public static function departamentos(string $codigo): array
    {
        $archivo = resource_path('ubicaciones/'.strtoupper($codigo).'.json');

        if (! isset(self::PAISES[strtoupper($codigo)]) || ! File::exists($archivo)) {
            return [];
        }

        /** @var list<array{nombre: string, ciudades: list<string>}> */
        return File::json($archivo);
    }

    /** @return list<string> */
    public static function nombresDeDepartamentos(string $nombrePais): array
    {
        $codigo = self::codigoDe($nombrePais);

        return $codigo === null ? [] : array_column(self::departamentos($codigo), 'nombre');
    }
}
