<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Diagnostico;
use App\Models\DiagnosticoCategoria;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Sector;
use App\Models\VersionDiagnostico;
use App\Services\Diagnosticos\ContenidoDiagnostico;
use Illuminate\Support\Facades\DB;

/**
 * Crea y publica (versión 1) un diagnóstico de prueba a partir de un arreglo
 * de categorías del catálogo. Lo usan DiagnosticoEjemploSeeder y
 * DiagnosticoProgramacionSeeder.
 *
 * Contenido: categoría del catálogo => [importancia, preguntas]. Cada
 * pregunta: [texto, tipo, obligatoria, indicación ("Cómo responder"),
 * opciones, criterio para la IA (opcional, solo abiertas)]. Cada opción:
 * [texto, puntaje, marca, ten en cuenta] con marca 'otro' (pide escribir
 * cuál), 'ninguna' (excluye a las demás) o null.
 */
trait CreaDiagnosticoPublicado
{
    /**
     * @param  array<string, array{0: int, 1: list<array<int, mixed>>}>  $contenido
     */
    protected function crearDiagnostico(Sector $sector, string $nombre, string $descripcion, array $contenido): Diagnostico
    {
        return DB::transaction(function () use ($sector, $nombre, $descripcion, $contenido): Diagnostico {
            $diagnostico = Diagnostico::create([
                'sector_id' => $sector->id,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'estado' => Diagnostico::PUBLICADO,
                'version_borrador' => null,
            ]);

            $orden = 0;

            foreach ($contenido as $categoriaNombre => [$importancia, $preguntas]) {
                $parte = DiagnosticoCategoria::create([
                    'diagnostico_id' => $diagnostico->id,
                    'categoria_id' => Categoria::firstOrCreate(['nombre' => $categoriaNombre])->id,
                    'importancia' => $importancia,
                    'orden' => $orden++,
                ]);

                foreach ($preguntas as $i => $p) {
                    $pregunta = Pregunta::create([
                        'diagnostico_categoria_id' => $parte->id,
                        'texto' => $p[0],
                        'tipo' => $p[1],
                        'obligatoria' => $p[2],
                        'indicacion' => $p[3],
                        'criterio_ia' => $p[5] ?? null,
                        'orden' => $i,
                    ]);

                    foreach ($p[4] as $j => $opcion) {
                        Opcion::create([
                            'pregunta_id' => $pregunta->id,
                            'texto' => $opcion[0],
                            'puntaje' => $opcion[1],
                            'con_texto' => ($opcion[2] ?? null) === 'otro',
                            'exclusiva' => ($opcion[2] ?? null) === 'ninguna',
                            'ten_en_cuenta' => $opcion[3] ?? null,
                            'orden' => $j,
                        ]);
                    }
                }
            }

            VersionDiagnostico::create([
                'diagnostico_id' => $diagnostico->id,
                'numero' => 1,
                'contenido' => app(ContenidoDiagnostico::class)->congelar($diagnostico),
                'publicada_en' => now(),
            ]);

            return $diagnostico;
        });
    }
}
