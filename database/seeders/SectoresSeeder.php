<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

/**
 * Sectores de ejemplo del wireframe.
 * [INFORMACIÓN PENDIENTE] NuevasTIC debe confirmar la lista real de sectores.
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
