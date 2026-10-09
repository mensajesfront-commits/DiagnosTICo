<?php

namespace Database\Factories;

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicion>
 */
class MedicionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'version_diagnostico_id' => fn () => Diagnostico::factory()->publicado()->create()->versiones()->firstOrFail()->id,
            'numero' => 1,
            'estado' => 'no_iniciada',
        ];
    }
}
