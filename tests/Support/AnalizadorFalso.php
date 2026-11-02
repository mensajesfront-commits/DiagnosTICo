<?php

namespace Tests\Support;

use App\Services\Ia\Analizador;
use App\Support\AnalisisSinIa;
use App\Support\MetodosAnalisis;

/**
 * Analizador falso SOLO para las pruebas automáticas: no llama a OpenAI ni
 * gasta saldo. La aplicación usa siempre AnalizadorDeCategoria (OpenAI).
 *
 * Devuelve siempre lo mismo para la misma entrada: la abierta vale según el
 * largo de la respuesta (más detalle, más puntaje) y los textos empiezan por
 * "Prueba:".
 */
class AnalizadorFalso implements Analizador
{
    public function analizar(string $instrucciones, array $categoria): array
    {
        $preguntas = [];

        foreach ($categoria['preguntas'] as $p) {
            $abierta = $p['tipo'] === 'abierta';
            $texto = is_string($p['respuesta'] ?? null) ? $p['respuesta'] : '';

            $preguntas[] = [
                'id' => (string) $p['id'],
                'puntaje' => $abierta ? min(100, 20 + intdiv(mb_strlen($texto), 4)) : null,
                'observacion' => $abierta
                    ? 'Observación de prueba: la respuesta tiene '.mb_strlen($texto).' caracteres.'
                    : 'Observación de prueba sobre tu respuesta.',
            ];
        }

        $metodo = MetodosAnalisis::porCategoria($categoria['nombre']);

        return [
            'preguntas' => $preguntas,
            'metodo' => $metodo,
            'metodo_motivo' => 'Método de prueba elegido por el nombre de la categoría.',
            'observacion_categoria' => "Observación de prueba de {$categoria['nombre']}: analizada sin conexión a la IA.",
            ...AnalisisSinIa::partes($categoria['nombre'], $metodo, 'de prueba'),
        ];
    }
}
