<?php

namespace App\Support;

/**
 * Las partes nuevas del análisis (hallazgos del método, fortalezas y
 * recomendaciones con pasos) cuando no hay IA: el modo de prueba
 * (IA_DE_PRUEBA), el resultado de ejemplo (HU-030) y la categoría sin
 * respuestas. Los textos dicen que son de prueba o de ejemplo.
 */
final class AnalisisSinIa
{
    /**
     * @param  string  $tipo  "de prueba" o "de ejemplo"
     * @return array{hallazgos: list<array{grupo: string, puntos: list<string>}>, fortalezas: list<string>, recomendaciones: list<array{titulo: string, detalle: string, pasos: list<string>, plazo: string, indicador: string}>, recomendacion: array{titulo: string, detalle: string}}
     */
    public static function partes(string $categoria, string $metodo, string $tipo): array
    {
        $hallazgos = array_map(fn (string $grupo) => [
            'grupo' => $grupo,
            'puntos' => ["Hallazgo {$tipo}: con la IA real, aquí va lo que muestran las respuestas en «{$grupo}»."],
        ], array_slice(MetodosAnalisis::grupos($metodo), 0, 2));

        $recomendaciones = [
            [
                'titulo' => "Mejora {$categoria}",
                'detalle' => "Recomendación {$tipo}: con la IA real, aquí va la acción más importante y por qué.",
                'pasos' => [
                    'Primer paso concreto, basado en lo que respondió la empresa.',
                    'Segundo paso, que se apoya en el primero.',
                    'Tercer paso, para dejarlo como costumbre.',
                ],
                'plazo' => '4 semanas',
                'indicador' => 'Algo que se pueda contar o revisar para saber si funcionó.',
            ],
            [
                'titulo' => "Mide tus avances en {$categoria}",
                'detalle' => "Recomendación {$tipo}: la segunda acción en importancia.",
                'pasos' => [
                    'Elige un dato para seguir cada semana.',
                    'Anótalo en una hoja sencilla.',
                    'Revísalo cada mes y ajusta.',
                ],
                'plazo' => '8 semanas',
                'indicador' => 'Cuatro semanas seguidas con el dato anotado.',
            ],
        ];

        return [
            'hallazgos' => $hallazgos,
            'fortalezas' => ["Fortaleza {$tipo}: con la IA real, aquí va algo que la empresa ya hace bien."],
            'recomendaciones' => $recomendaciones,
            'recomendacion' => ['titulo' => $recomendaciones[0]['titulo'], 'detalle' => $recomendaciones[0]['detalle']],
        ];
    }
}
