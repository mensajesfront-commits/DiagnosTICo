<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Categoría '.fake()->unique()->numberBetween(1, 999999),
            'descripcion' => null,
        ];
    }

    public function archivada(): static
    {
        return $this->state(['archivado_en' => now()]);
    }
}
