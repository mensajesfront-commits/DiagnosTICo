<?php

namespace Database\Seeders;

use App\Support\CatalogoCiiu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carga el catálogo CIIU Rev. 5 A.C. del DANE (DEC-018): 87 divisiones y 544
 * clases desde `resources/ciiu/ciiu-rev5-ac.json`. Se puede correr las veces
 * que se quiera: actualiza nombres y agrega lo que falte.
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
                'seccion' => $d['seccion'],
                'seccion_nombre' => $secciones[$d['seccion']] ?? '',
            ], $datos['divisiones']),
            ['codigo'],
            ['nombre', 'seccion', 'seccion_nombre'],
        );

        DB::table('ciiu_clases')->upsert(
            array_map(fn (array $c) => [
                'codigo' => $c['codigo'],
                'nombre' => $c['nombre'],
                'division_codigo' => $c['division'],
            ], $datos['clases']),
            ['codigo'],
            ['nombre', 'division_codigo'],
        );
    }
}
