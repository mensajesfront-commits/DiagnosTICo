<?php

namespace App\Services\Ia;

use App\Support\MetodosAnalisis;
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
            'required' => ['preguntas', 'metodo', 'metodo_motivo', 'observacion_categoria', 'hallazgos', 'fortalezas', 'recomendaciones'],
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
                                'description' => 'Obligatorio en las preguntas de tipo "abierta": un entero de 0 a 100. Null en las demás.',
                            ],
                            'observacion' => ['type' => 'string'],
                        ],
                    ],
                ],
                'metodo' => [
                    'type' => 'string',
                    'enum' => MetodosAnalisis::claves(),
                    'description' => 'Método de análisis elegido según las respuestas de la empresa.',
                ],
                'metodo_motivo' => ['type' => 'string', 'description' => 'Una frase: por qué ese método encaja con lo que respondió la empresa.'],
                'observacion_categoria' => ['type' => 'string', 'description' => 'Diagnóstico de la situación en 2 o 3 frases: qué pasa, por qué y qué le cuesta a la empresa.'],
                'hallazgos' => [
                    'type' => 'array',
                    'description' => 'Lo que encontró el método elegido, por grupo. Solo los grupos del método con algo basado en las respuestas.',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['grupo', 'puntos'],
                        'properties' => [
                            'grupo' => ['type' => 'string', 'enum' => MetodosAnalisis::todosLosGrupos()],
                            'puntos' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => '1 a 3 frases cortas.'],
                        ],
                    ],
                ],
                'fortalezas' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => '1 o 2 cosas que la empresa ya hace bien en este tema, según sus respuestas. Vacía si no hay ninguna.',
                ],
                'recomendaciones' => [
                    'type' => 'array',
                    'description' => '2 o 3 recomendaciones, de la más a la menos importante.',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['titulo', 'detalle', 'pasos', 'plazo', 'indicador'],
                        'properties' => [
                            'titulo' => ['type' => 'string', 'description' => 'Acción en imperativo, máximo 8 palabras.'],
                            'detalle' => ['type' => 'string', 'description' => 'Por qué hacerla y qué logra, en una frase.'],
                            'pasos' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => '3 pasos concretos, en orden.'],
                            'plazo' => ['type' => 'string', 'description' => 'Tiempo para hacerla, por ejemplo "4 semanas".'],
                            'indicador' => ['type' => 'string', 'description' => 'Cómo saber si funcionó: algo que se pueda contar o revisar.'],
                        ],
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
     * @return array{preguntas: list<array{id: string, puntaje: int|null, observacion: string}>, metodo: string, metodo_motivo: string, observacion_categoria: string, hallazgos: list<array{grupo: string, puntos: list<string>}>, fortalezas: list<string>, recomendaciones: list<array{titulo: string, detalle: string, pasos: list<string>, plazo: string, indicador: string}>, recomendacion: array{titulo: string, detalle: string}}
     */
    public static function validar(string $contenido, array $categoria): array
    {
        $datos = json_decode($contenido, true);

        if (! is_array($datos) || ! isset($datos['preguntas'], $datos['observacion_categoria'], $datos['recomendaciones'])
            || ! is_array($datos['preguntas']) || ! is_array($datos['recomendaciones'])) {
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
                $valor = $analisis['puntaje'] ?? null;

                // Algunos modelos devuelven 45.0 o "45": se aceptan si son un número.
                if (! is_numeric($valor)) {
                    throw new RespuestaIaInvalida("La IA no calificó la pregunta abierta {$id} (devolvió ".json_encode($valor).').');
                }

                $puntaje = (int) round((float) $valor);

                if ($puntaje < 0 || $puntaje > 100) {
                    throw new RespuestaIaInvalida("Puntaje fuera de rango en la pregunta {$id}.");
                }
            }

            $preguntas[] = ['id' => $id, 'puntaje' => $puntaje, 'observacion' => trim((string) ($analisis['observacion'] ?? ''))];
        }

        $metodo = strtoupper(trim((string) ($datos['metodo'] ?? '')));

        if (! in_array($metodo, MetodosAnalisis::claves(), true)) {
            throw new RespuestaIaInvalida('La IA no dijo qué método usó (PESTEL, PORTER, VRIO, DOFA o CAME); devolvió '.json_encode($datos['metodo'] ?? null).'.');
        }

        $recomendaciones = self::recomendaciones($datos['recomendaciones']);

        if ($recomendaciones === []) {
            throw new RespuestaIaInvalida('La IA no dio ninguna recomendación con sus pasos.');
        }

        $hallazgos = self::hallazgos(is_array($datos['hallazgos'] ?? null) ? $datos['hallazgos'] : [], $metodo);

        if ($hallazgos === []) {
            throw new RespuestaIaInvalida("La IA no dijo qué encontró con {$metodo}.");
        }

        return [
            'preguntas' => $preguntas,
            'metodo' => $metodo,
            'metodo_motivo' => trim((string) ($datos['metodo_motivo'] ?? '')),
            'observacion_categoria' => trim((string) $datos['observacion_categoria']),
            'hallazgos' => $hallazgos,
            'fortalezas' => array_slice(self::textos(is_array($datos['fortalezas'] ?? null) ? $datos['fortalezas'] : []), 0, 2),
            'recomendaciones' => $recomendaciones,
            // La principal, con la forma de antes (inicio de la empresa, prioridades).
            'recomendacion' => ['titulo' => $recomendaciones[0]['titulo'], 'detalle' => $recomendaciones[0]['detalle']],
        ];
    }

    /**
     * Solo los grupos del método elegido, en su orden, con 1 a 3 puntos.
     *
     * @param  array<mixed>  $lista
     * @return list<array{grupo: string, puntos: list<string>}>
     */
    private static function hallazgos(array $lista, string $metodo): array
    {
        $porGrupo = [];

        foreach ($lista as $h) {
            if (is_array($h) && is_string($h['grupo'] ?? null)) {
                $puntos = self::textos(is_array($h['puntos'] ?? null) ? $h['puntos'] : []);
                $porGrupo[$h['grupo']] = array_merge($porGrupo[$h['grupo']] ?? [], $puntos);
            }
        }

        $hallazgos = [];

        foreach (MetodosAnalisis::grupos($metodo) as $grupo) {
            if (($porGrupo[$grupo] ?? []) !== []) {
                $hallazgos[] = ['grupo' => $grupo, 'puntos' => array_slice($porGrupo[$grupo], 0, 3)];
            }
        }

        return $hallazgos;
    }

    /**
     * Hasta 3 recomendaciones con título y al menos un paso (máximo 3).
     *
     * @param  array<mixed>  $lista
     * @return list<array{titulo: string, detalle: string, pasos: list<string>, plazo: string, indicador: string}>
     */
    private static function recomendaciones(array $lista): array
    {
        $recomendaciones = [];

        foreach ($lista as $r) {
            if (! is_array($r)) {
                continue;
            }

            $titulo = trim((string) ($r['titulo'] ?? ''));
            $pasos = array_slice(self::textos(is_array($r['pasos'] ?? null) ? $r['pasos'] : []), 0, 3);

            if ($titulo === '' || $pasos === []) {
                continue;
            }

            $recomendaciones[] = [
                'titulo' => $titulo,
                'detalle' => trim((string) ($r['detalle'] ?? '')),
                'pasos' => $pasos,
                'plazo' => trim((string) ($r['plazo'] ?? '')),
                'indicador' => trim((string) ($r['indicador'] ?? '')),
            ];
        }

        return array_slice($recomendaciones, 0, 3);
    }

    /**
     * Textos sin espacios de más y sin vacíos.
     *
     * @param  array<mixed>  $lista
     * @return list<string>
     */
    private static function textos(array $lista): array
    {
        return array_values(array_filter(
            array_map(fn ($t) => is_string($t) ? trim($t) : '', $lista),
            fn (string $t) => $t !== '',
        ));
    }
}
