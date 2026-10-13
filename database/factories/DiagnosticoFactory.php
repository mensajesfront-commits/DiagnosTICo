<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Diagnostico;
use App\Models\DiagnosticoCategoria;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Sector;
use App\Models\VersionDiagnostico;
use App\Services\Diagnosticos\ContenidoDiagnostico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Diagnósticos de prueba. `conCategorias()` agrega categorías con una
 * pregunta de opción única cada una; `publicado()` además congela la v1.
 *
 * @extends Factory<Diagnostico>
 */
class DiagnosticoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sector_id' => Sector::factory(),
            'nombre' => 'Diagnóstico '.fake()->unique()->word(),
            'descripcion' => null,
            'estado' => Diagnostico::BORRADOR,
            'version_borrador' => 1,
        ];
    }

    public function conCategorias(int $cuantas = 2): static
    {
        return $this->afterCreating(function (Diagnostico $diagnostico) use ($cuantas): void {
            $importancia = round(100 / $cuantas, 2);

            foreach (Categoria::factory()->count($cuantas)->create() as $orden => $categoria) {
                $parte = DiagnosticoCategoria::create([
                    'diagnostico_id' => $diagnostico->id,
                    'categoria_id' => $categoria->id,
                    'importancia' => $importancia,
                    'orden' => $orden,
                ]);

                $pregunta = Pregunta::create([
                    'diagnostico_categoria_id' => $parte->id,
                    'texto' => '¿Tiene página web?',
                    'tipo' => 'opcion_unica',
                    'indicacion' => 'Piensa en tu negocio hoy.',
                    'orden' => 0,
                ]);

                foreach ([['Sí', 100], ['No', 0]] as $i => [$texto, $puntaje]) {
                    Opcion::create(['pregunta_id' => $pregunta->id, 'texto' => $texto, 'puntaje' => $puntaje, 'orden' => $i]);
                }
            }
        });
    }

    /** Con la v1 publicada y sin borrador pendiente. */
    public function publicado(): static
    {
        return $this->conCategorias()->state([
            'estado' => Diagnostico::PUBLICADO,
            'version_borrador' => null,
        ])->afterCreating(function (Diagnostico $diagnostico): void {
            VersionDiagnostico::create([
                'diagnostico_id' => $diagnostico->id,
                'numero' => 1,
                'contenido' => app(ContenidoDiagnostico::class)->congelar($diagnostico),
                'publicada_en' => now(),
            ]);
        });
    }
}
