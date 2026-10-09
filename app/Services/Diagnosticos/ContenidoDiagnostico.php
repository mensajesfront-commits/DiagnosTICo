<?php

namespace App\Services\Diagnosticos;

use App\Models\Diagnostico;
use App\Models\DiagnosticoCategoria;
use App\Models\Opcion;
use App\Models\Pregunta;
use Illuminate\Support\Facades\DB;

/**
 * El contenido de un diagnóstico (categorías con importancia, preguntas y
 * opciones) como un arreglo, para:
 *
 * - congelarlo al publicar una versión (RN-010, `versiones_diagnostico.contenido`);
 * - copiarlo a otro diagnóstico (crear copiando, A2.5; duplicar, A2.7);
 * - volver a la última versión publicada al descartar un borrador.
 *
 * Forma del arreglo:
 * { nombre, descripcion, categorias: [{ ref, categoria_id, nombre, importancia, orden,
 *   preguntas: [{ ref, texto, tipo, indicacion, criterio_ia, obligatoria, orden,
 *   opciones: [{ ref, texto, puntaje, ten_en_cuenta, orden }] }] }] }
 *
 * `ref` es el identificador estable que usan las respuestas y los análisis
 * (`pregunta_ref`, `categoria_ref`).
 */
class ContenidoDiagnostico
{
    /**
     * @return array{nombre: string, descripcion: string|null, categorias: list<array<string, mixed>>}
     */
    public function congelar(Diagnostico $diagnostico): array
    {
        $diagnostico->load(['partes.categoria', 'partes.preguntas.opciones']);
        $categorias = [];

        foreach ($diagnostico->partes as $parte) {
            $preguntas = [];

            foreach ($parte->preguntas as $pregunta) {
                $opciones = [];

                foreach ($pregunta->opciones as $opcion) {
                    $opciones[] = [
                        'ref' => 'o'.$opcion->id,
                        'texto' => $opcion->texto,
                        'puntaje' => $opcion->puntaje,
                        'ten_en_cuenta' => $opcion->ten_en_cuenta,
                        'orden' => $opcion->orden,
                    ];
                }

                $preguntas[] = [
                    'ref' => 'p'.$pregunta->id,
                    'texto' => $pregunta->texto,
                    'tipo' => $pregunta->tipo,
                    'indicacion' => $pregunta->indicacion,
                    'criterio_ia' => $pregunta->criterio_ia,
                    'obligatoria' => $pregunta->obligatoria,
                    'orden' => $pregunta->orden,
                    'opciones' => $opciones,
                ];
            }

            $categorias[] = [
                'ref' => 'c'.$parte->categoria_id,
                'categoria_id' => $parte->categoria_id,
                'nombre' => $parte->categoria?->nombre,
                'importancia' => (float) $parte->importancia,
                'orden' => $parte->orden,
                'preguntas' => $preguntas,
            ];
        }

        return [
            'nombre' => $diagnostico->nombre,
            'descripcion' => $diagnostico->descripcion,
            'categorias' => $categorias,
        ];
    }

    /**
     * Reemplaza las categorías, preguntas y opciones de `$destino` por las del
     * contenido. Las categorías archivadas o borradas del catálogo se omiten.
     *
     * @param  array<string, mixed>  $contenido
     */
    public function aplicar(Diagnostico $destino, array $contenido): void
    {
        DB::transaction(function () use ($destino, $contenido): void {
            $destino->partes()->delete();

            /** @var list<array<string, mixed>> $categorias */
            $categorias = $contenido['categorias'] ?? [];
            $vigentes = DB::table('categorias')->whereNull('archivado_en')->pluck('id')->all();

            foreach ($categorias as $categoria) {
                if (! in_array($categoria['categoria_id'] ?? null, $vigentes, true)) {
                    continue;
                }

                $parte = DiagnosticoCategoria::create([
                    'diagnostico_id' => $destino->id,
                    'categoria_id' => $categoria['categoria_id'],
                    'importancia' => $categoria['importancia'] ?? 0,
                    'orden' => $categoria['orden'] ?? 0,
                ]);

                /** @var list<array<string, mixed>> $preguntas */
                $preguntas = $categoria['preguntas'] ?? [];

                foreach ($preguntas as $p) {
                    $pregunta = Pregunta::create([
                        'diagnostico_categoria_id' => $parte->id,
                        'texto' => $p['texto'],
                        'tipo' => $p['tipo'],
                        'indicacion' => $p['indicacion'],
                        'criterio_ia' => $p['criterio_ia'] ?? null,
                        'obligatoria' => $p['obligatoria'] ?? true,
                        'orden' => $p['orden'] ?? 0,
                    ]);

                    /** @var list<array<string, mixed>> $opciones */
                    $opciones = $p['opciones'] ?? [];

                    foreach ($opciones as $o) {
                        Opcion::create([
                            'pregunta_id' => $pregunta->id,
                            'texto' => $o['texto'],
                            'puntaje' => $o['puntaje'],
                            'ten_en_cuenta' => $o['ten_en_cuenta'] ?? null,
                            'orden' => $o['orden'] ?? 0,
                        ]);
                    }
                }
            }
        });
    }

    /** Copia el contenido actual de un diagnóstico a otro. */
    public function copiar(Diagnostico $origen, Diagnostico $destino): void
    {
        $this->aplicar($destino, $this->congelar($origen));
    }

    /**
     * Importancias iguales que suman 100 (RN-011): la última se lleva el
     * redondeo. 3 categorías → 33.33, 33.33, 33.34.
     *
     * @return list<float>
     */
    public static function repartir(int $cuantas): array
    {
        if ($cuantas <= 0) {
            return [];
        }

        $parte = floor(10000 / $cuantas) / 100;
        $lista = array_fill(0, $cuantas, $parte);
        $lista[$cuantas - 1] = round(100 - $parte * ($cuantas - 1), 2);

        return array_values($lista);
    }
}
