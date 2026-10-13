<?php

namespace App\Support;

use App\Models\ActividadEconomica;
use App\Models\Sector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Catálogo CIIU Rev. 5 A.C. del DANE (DEC-018, `resources/ciiu/`).
 *
 * Cada división y clase tiene `nombre` (corto, el que se muestra) y
 * `nombre_oficial` (el título del DANE).
 *
 * Un sector se asocia a una división (dos dígitos) y todas las clases de esa
 * división (cuatro dígitos) pasan a ser sus subsectores, es decir, sus
 * actividades económicas para el registro (L2) y Mi perfil (E11).
 */
class CatalogoCiiu
{
    public const string ARCHIVO = 'ciiu/ciiu-rev5-ac.json';

    /**
     * Contenido del JSON del DANE.
     *
     * @return array{secciones: list<array{codigo: string, nombre: string}>, divisiones: list<array{codigo: string, nombre: string, nombre_oficial: string, seccion: string}>, clases: list<array{codigo: string, nombre: string, nombre_oficial: string, division: string}>}
     */
    public static function archivo(): array
    {
        /** @var array{secciones: list<array{codigo: string, nombre: string}>, divisiones: list<array{codigo: string, nombre: string, nombre_oficial: string, seccion: string}>, clases: list<array{codigo: string, nombre: string, nombre_oficial: string, division: string}>} $datos */
        $datos = json_decode((string) file_get_contents(resource_path(self::ARCHIVO)), true, flags: JSON_THROW_ON_ERROR);

        return $datos;
    }

    /**
     * Las 87 divisiones con cuántas clases tiene cada una, para elegirlas al
     * crear o editar un sector (A2.2a, A2.2).
     *
     * @return list<array{codigo: string, nombre: string, clases: int}>
     */
    public static function divisiones(): array
    {
        $lista = [];

        $filas = DB::table('ciiu_divisiones as d')
            ->leftJoin('ciiu_clases as c', 'c.division_codigo', '=', 'd.codigo')
            ->groupBy('d.codigo', 'd.nombre')
            ->orderBy('d.codigo')
            ->get(['d.codigo', 'd.nombre', DB::raw('count(c.codigo) as clases')]);

        foreach ($filas as $fila) {
            $lista[] = ['codigo' => (string) $fila->codigo, 'nombre' => (string) $fila->nombre, 'clases' => (int) $fila->clases];
        }

        return $lista;
    }

    /**
     * La división cuyo nombre (corto u oficial) es exactamente el escrito,
     * sin mirar tildes, mayúsculas ni espacios de más. Null si no hay.
     */
    public static function divisionPorNombre(string $nombre): ?string
    {
        $buscado = self::normalizar($nombre);

        foreach (DB::table('ciiu_divisiones')->get(['codigo', 'nombre', 'nombre_oficial']) as $division) {
            if (self::normalizar((string) $division->nombre) === $buscado
                || self::normalizar((string) $division->nombre_oficial) === $buscado) {
                return (string) $division->codigo;
            }
        }

        return null;
    }

    /**
     * Asocia el sector a la división y le deja como subsectores activos todas
     * sus clases. Las actividades que tenía y no son de la división se
     * desactivan (no se borran: puede haber empresas que las usen).
     */
    public static function asignarDivision(Sector $sector, string $division): int
    {
        $clases = DB::table('ciiu_clases')->where('division_codigo', $division)->orderBy('codigo')->get(['codigo', 'nombre']);

        DB::transaction(function () use ($sector, $division, $clases): void {
            $sector->update(['ciiu_division' => $division]);

            foreach ($clases as $clase) {
                ActividadEconomica::updateOrCreate(
                    ['sector_id' => $sector->id, 'codigo' => (string) $clase->codigo],
                    ['nombre' => (string) $clase->nombre, 'activo' => true],
                );
            }

            ActividadEconomica::where('sector_id', $sector->id)
                ->whereNotIn('codigo', $clases->pluck('codigo')->map(fn ($c) => (string) $c)->all())
                ->update(['activo' => false]);
        });

        return $clases->count();
    }

    /**
     * Deja como subsectores activos solo las clases dadas (para sectores que
     * no usan una división entera, como Abogados con 6910).
     *
     * @param  list<string>  $codigos
     */
    public static function asignarClases(Sector $sector, array $codigos): int
    {
        $clases = DB::table('ciiu_clases')->whereIn('codigo', $codigos)->orderBy('codigo')->get(['codigo', 'nombre']);

        DB::transaction(function () use ($sector, $clases): void {
            foreach ($clases as $clase) {
                ActividadEconomica::updateOrCreate(
                    ['sector_id' => $sector->id, 'codigo' => (string) $clase->codigo],
                    ['nombre' => (string) $clase->nombre, 'activo' => true],
                );
            }

            ActividadEconomica::where('sector_id', $sector->id)
                ->whereNotIn('codigo', $clases->pluck('codigo')->map(fn ($c) => (string) $c)->all())
                ->update(['activo' => false]);
        });

        return $clases->count();
    }

    private static function normalizar(string $texto): string
    {
        return Str::of(Str::ascii($texto))->lower()->squish()->toString();
    }
}
