<?php

namespace Database\Seeders;

use App\Support\CatalogoCiiu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carga el catálogo CIIU Rev. 5 A.C. del DANE (DEC-018): 87 divisiones y 544
 * clases desde `resources/ciiu/ciiu-rev5-ac.json`, con su nombre corto y el
 * oficial. Se puede correr las veces que se quiera: actualiza nombres, agrega
 * lo que falte y pone el nombre corto a los subsectores de todos los
 * sectores.
 */
class CiiuSeeder extends Seeder
{
    public function run(): void
    {
        $datos = CatalogoCiiu::archivo();
        $secciones = array_column($datos['secciones'], 'nombre', 'codigo');

        DB::table('ciiu_divisiones')->upsert(
            array_map(fn (array $d) => [
                'codigo' => $d['codigo'],
                'nombre' => $d['nombre'],
                'nombre_oficial' => $d['nombre_oficial'],
                'seccion' => $d['seccion'],
                'seccion_nombre' => $secciones[$d['seccion']] ?? '',
            ], $datos['divisiones']),
            ['codigo'],
            ['nombre', 'nombre_oficial', 'seccion', 'seccion_nombre'],
        );

        DB::table('ciiu_clases')->upsert(
            array_map(fn (array $c) => [
                'codigo' => $c['codigo'],
                'nombre' => $c['nombre'],
                'nombre_oficial' => $c['nombre_oficial'],
                'division_codigo' => $c['division'],
            ], $datos['clases']),
            ['codigo'],
            ['nombre', 'nombre_oficial', 'division_codigo'],
        );

        // Los subsectores ya creados toman el nombre del catálogo.
        DB::statement('update actividades_economicas a set nombre = c.nombre, updated_at = now()
            from ciiu_clases c where c.codigo = a.codigo and a.nombre <> c.nombre');
    }
}
