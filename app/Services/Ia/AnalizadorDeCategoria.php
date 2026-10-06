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
 *   calculadas por el sistema y aquí llegan con puntaje null),
 * - una observación de la categoría y una recomendación.
 *
 * Prueba técnica T-019. El armado completo del prompt por capas (RN-022) se
 * hace en la semana 5 (T-099); aquí el prompt es fijo.
 */
class AnalizadorDeCategoria
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
                            'id' => ['type' => 'integer'],
                            'puntaje' => [
                                'type' => ['integer', 'null'],
                                'description' => 'Solo para preguntas abiertas: 0 a 100. Null en las cerradas.',
                            ],
                            'observacion' => ['type' => 'string'],
                        ],
                    ],
                ],
                'observacion_categoria' => ['type' => 'string'],
                'recomendacion' => ['type' => 'string'],
            ],
        ];
    }

    /**
     * @param  array{nombre: string, sector: string, preguntas: list<array{id: int, tipo: string, texto: string, respuesta: string, puntaje: int|null, criterio?: string}>}  $categoria
     * @return array{preguntas: list<array{id: int, puntaje: int|null, observacion: string}>, observacion_categoria: string, recomendacion: string}
     */
    public function analizar(array $categoria): array
    {
        $modelo = config('openai.model');

        if (blank($modelo)) {
            throw new RespuestaIaInvalida('Falta OPENAI_MODEL en el archivo .env.');
        }

        $respuesta = OpenAI::chat()->create([
            'model' => $modelo,
            'messages' => [
                ['role' => 'system', 'content' => $this->instrucciones()],
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

        return $this->validar($respuesta->choices[0]->message->content ?? '', $categoria);
    }

    private function instrucciones(): string
    {
        return <<<'TXT'
        Eres consultor de marketing digital de NuevasTIC. Analizas una categoría
        del diagnóstico de una empresa. Recibes sus preguntas con la respuesta
        de la empresa.

        - Para cada pregunta escribe una observación breve y concreta.
        - Califica de 0 a 100 solo las preguntas de tipo "abierta", según su
          criterio de calificación. En las demás devuelve puntaje null.
        - Escribe una observación de la categoría y una recomendación accionable.
        - Escribe en español, en segunda persona, sin palabras técnicas.
        TXT;
    }

    /**
     * Revisa que la respuesta tenga una entrada por pregunta y que los puntajes
     * de las abiertas estén entre 0 y 100. Si no, lanza RespuestaIaInvalida para
     * que el trabajo en cola reintente esa categoría (RN-021).
     *
     * @param  array{preguntas: list<array{id: int, tipo: string}>}  $categoria
     * @return array{preguntas: list<array{id: int, puntaje: int|null, observacion: string}>, observacion_categoria: string, recomendacion: string}
     */
    private function validar(string $contenido, array $categoria): array
    {
        $datos = json_decode($contenido, true);

        if (! is_array($datos) || ! isset($datos['preguntas'], $datos['observacion_categoria'], $datos['recomendacion'])) {
            throw new RespuestaIaInvalida('La IA no devolvió el formato esperado.');
        }

        /** @var array<int, array{id: int, puntaje: int|null, observacion: string}> $porId */
        $porId = array_column($datos['preguntas'], null, 'id');

        foreach ($categoria['preguntas'] as $pregunta) {
            $analisis = $porId[$pregunta['id']] ?? null;

            if ($analisis === null) {
                throw new RespuestaIaInvalida("Falta el análisis de la pregunta {$pregunta['id']}.");
            }

            if ($pregunta['tipo'] === 'abierta') {
                $puntaje = $analisis['puntaje'];

                if (! is_int($puntaje) || $puntaje < 0 || $puntaje > 100) {
                    throw new RespuestaIaInvalida("Puntaje fuera de rango en la pregunta {$pregunta['id']}.");
                }
            }
        }

        return $datos;
    }
}
