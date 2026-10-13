<?php

namespace App\Services\Ia;

/**
 * Analizador sin conexión para programar y probar sin clave de OpenAI
 * (IA_DE_PRUEBA=true en el .env; también lo usan las pruebas automáticas).
 *
 * Devuelve siempre lo mismo para la misma entrada: la abierta vale según el
 * largo de la respuesta (más detalle, más puntaje) y los textos dicen que son
 * de prueba. Nunca se usa en producción.
 */
class AnalizadorDePrueba implements Analizador
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

        return [
            'preguntas' => $preguntas,
            'observacion_categoria' => "Observación de prueba de {$categoria['nombre']}: analizada sin conexión a la IA.",
            'recomendacion' => [
                'titulo' => "Mejora {$categoria['nombre']}",
                'detalle' => 'Recomendación de prueba: con la IA real aquí va una acción concreta para los próximos 1 a 3 meses.',
            ],
        ];
    }
}
