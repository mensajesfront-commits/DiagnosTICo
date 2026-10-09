<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
class EmpresaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->company(),
            'descripcion' => 'Empresa de prueba.',
            'sector_id' => Sector::factory(),
            'ciudad' => 'Cali',
            'departamento' => 'Valle del Cauca',
            'pais' => 'Colombia',
            'activa' => true,
        ];
    }

    public function desactivada(): static
    {
        return $this->state(['activa' => false, 'desactivada_en' => now()]);
    }
}
