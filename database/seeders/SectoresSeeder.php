<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

/**
 * Sectores iniciales del wireframe. NuevasTIC confirmó que es la lista real
 * (23 de octubre de 2026).
 */
class SectoresSeeder extends Seeder
{
    public function run(): void
    {
        $sectores = [
            'Abogados' => 'Bufetes, abogados independientes y notarías.',
            'Inmobiliarias' => null,
            'Médicos' => null,
            'Comidas' => null,
            'Alojamientos' => null,
            'Turismo' => null,
        ];

        foreach ($sectores as $nombre => $descripcion) {
            Sector::firstOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }

        $this->call(ActividadesEconomicasSeeder::class);
    }
}
