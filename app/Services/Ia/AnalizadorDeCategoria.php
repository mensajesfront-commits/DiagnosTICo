<?php

namespace App\Services\Ia;

use OpenAI\Laravel\Facades\OpenAI;

/**
 * Pide a OpenAI el análisis de una categoría del diagnóstico (RN-021: una sola
 * etapa por categoría).
 *
 * La IA devuelve, con un formato JSON fijo (salida estructurada):
 * - una observación por pregunta,
 * - el puntaje 0–100 de cada pregunta abierta (las cerradas ya vienen
 *   calculadas por el sistema y aquí llegan con su puntaje),
 * - una observación de la categoría y una recomendación con título y detalle.
 *
 * Las instrucciones las arma ArmadorPrompt por capas (RN-022).
 */
class AnalizadorDeCategoria implements Analizador
{
    /**
     * Esquema JSON que la IA debe respetar. Con "strict" la API rechaza
     * cualquier respuesta que no lo cumpla.
     *
     * @return array<string, mixed>
     */
    public static function esquema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['preguntas', 'observacion_categoria', 'recomendacion'],
            'properties' => [
                'preguntas' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['id', 'puntaje', 'observacion'],
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'puntaje' => [
                                'type' => ['integer', 'null'],
                                'description' => 'Solo para preguntas abiertas: 0 a 100. Null en las cerradas.',
                            ],
                            'observacion' => ['type' => 'string'],
                        ],
                    ],
                ],
                'observacion_categoria' => ['type' => 'string'],
                'recomendacion' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['titulo', 'detalle'],
                    'properties' => [
                        'titulo' => ['type' => 'string', 'description' => 'Acción en imperativo, máximo 8 palabras.'],
                        'detalle' => ['type' => 'string', 'description' => 'Cómo hacerla, en una o dos frases.'],
                    ],
                ],
            ],
        ];
    }

    public function analizar(string $instrucciones, array $categoria): array
    {
        $modelo = config('openai.model');

        if (blank($modelo)) {
            throw new RespuestaIaInvalida('Falta OPENAI_MODEL en el archivo .env.');
        }

        $respuesta = OpenAI::chat()->create([
            'model' => $modelo,
            'messages' => [
                ['role' => 'system', 'content' => $instrucciones],
                ['role' => 'user', 'content' => json_encode($categoria, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'analisis_categoria',
                    'strict' => true,
                    'schema' => self::esquema(),
                ],
            ],
        ]);

        return self::validar($respuesta->choices[0]->message->content ?? '', $categoria);
    }

    /**
     * Revisa que la respuesta tenga una entrada por pregunta y que los puntajes
     * de las abiertas estén entre 0 y 100. Si no, lanza RespuestaIaInvalida para
     * que el trabajo en cola reintente esa categoría (RN-021).
     *
     * @param  array{preguntas: list<array<string, mixed>>}  $categoria
     * @return array{preguntas: list<array{id: string, puntaje: int|null, observacion: string}>, observacion_categoria: string, recomendacion: array{titulo: string, detalle: string}}
     */
    public static function validar(string $contenido, array $categoria): array
    {
        $datos = json_decode($contenido, true);

        if (! is_array($datos) || ! isset($datos['preguntas'], $datos['observacion_categoria'], $datos['recomendacion'])
            || ! is_array($datos['preguntas']) || ! is_array($datos['recomendacion'])
            || ! isset($datos['recomendacion']['titulo'], $datos['recomendacion']['detalle'])) {
            throw new RespuestaIaInvalida('La IA no devolvió el formato esperado.');
        }

        $porId = [];

        foreach ($datos['preguntas'] as $p) {
            if (is_array($p) && isset($p['id'])) {
                $porId[(string) $p['id']] = $p;
            }
        }

        $preguntas = [];

        foreach ($categoria['preguntas'] as $pregunta) {
            $id = (string) $pregunta['id'];
            $analisis = $porId[$id] ?? null;

            if ($analisis === null) {
                throw new RespuestaIaInvalida("Falta el análisis de la pregunta {$id}.");
            }

            $puntaje = null;

            if ($pregunta['tipo'] === 'abierta') {
                $puntaje = $analisis['puntaje'] ?? null;

                if (! is_int($puntaje) || $puntaje < 0 || $puntaje > 100) {
                    throw new RespuestaIaInvalida("Puntaje fuera de rango en la pregunta {$id}.");
                }
            }

            $preguntas[] = ['id' => $id, 'puntaje' => $puntaje, 'observacion' => trim((string) ($analisis['observacion'] ?? ''))];
        }

        return [
            'preguntas' => $preguntas,
            'observacion_categoria' => trim((string) $datos['observacion_categoria']),
            'recomendacion' => [
                'titulo' => trim((string) $datos['recomendacion']['titulo']),
                'detalle' => trim((string) $datos['recomendacion']['detalle']),
            ],
        ];
    }
}
