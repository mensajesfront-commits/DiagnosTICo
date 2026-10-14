<?php

namespace App\Services\Ia;

/**
 * Analiza una categoría del diagnóstico (RN-021: una sola etapa por
 * categoría). Hay dos: la real con OpenAI (AnalizadorDeCategoria) y una de
 * prueba sin conexión (AnalizadorDePrueba, IA_DE_PRUEBA=true en el .env).
 *
 * `$categoria` es la capa "preguntas con respuestas" del prompt (RN-022);
 * cada pregunta trae `id` (su referencia, p. ej. "p12") y `tipo`.
 *
 * Devuelve siempre el formato fijo; si la IA no lo cumple, lanza
 * RespuestaIaInvalida para que se reintente solo esa categoría.
 */
interface Analizador
{
    /**
     * @param  array{nombre: string, preguntas: list<array<string, mixed>>}  $categoria
     * @return array{preguntas: list<array{id: string, puntaje: int|null, observacion: string}>, metodo: string, metodo_motivo: string, observacion_categoria: string, recomendacion: array{titulo: string, detalle: string}}
     */
    public function analizar(string $instrucciones, array $categoria): array;
}
