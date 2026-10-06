<?php

namespace Database\Factories;

use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sector>
 */
class SectorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'descripcion' => null,
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }
}
