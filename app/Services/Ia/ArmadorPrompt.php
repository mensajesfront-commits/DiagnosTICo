<?php

namespace App\Services\Ia;

use App\Models\Medicion;
use App\Models\Prompt;
use App\Services\Resultados\CalculadoraPuntajes;

/**
 * Arma el prompt de una categoría por capas (RN-022, HU-081):
 *
 * 1. Instrucciones generales (tabla `prompts`, alcance general; si no hay
 *    fila, los textos de GENERAL).
 * 2. Ajuste del sector del diagnóstico y 3. ajuste de la empresa: en cada
 *    elemento (contexto, tarea, detalles, ejemplos) "usar" deja el de la capa
 *    anterior, "agregar" suma su texto al final y "reemplazar" lo sustituye.
 *    Así se usa siempre lo más específico.
 * 4. Preguntas con respuestas: va como mensaje aparte, en JSON. El "Ten en
 *    cuenta" solo aparece si esa opción fue elegida (RN-013).
 * 5. Formato fijo (FORMATO), igual para todos.
 *
 * Las preguntas opcionales sin responder no se envían.
 */
class ArmadorPrompt
{
    public const string ETAPA = 'analizar_categoria';

    public const array ELEMENTOS = ['contexto', 'tarea', 'detalles', 'ejemplos'];

    /**
     * Instrucciones generales por defecto, mientras el Administrador no las
     * cambie en A4 (Configuración de la IA).
     *
     * @var array<string, string>
     */
    public const array GENERAL = [
        'contexto' => <<<'TXT'
        Eres consultor de marketing digital de NuevasTIC. Analizas una categoría del diagnóstico de marketing digital de una pequeña o mediana empresa colombiana. Recibes las preguntas de la categoría con la respuesta de la empresa, el puntaje que ya calculó el sistema para las preguntas cerradas y, cuando existe, una nota "ten_en_cuenta" del equipo de NuevasTIC sobre la opción que eligió la empresa.
        TXT,
        'tarea' => <<<'TXT'
        1. Escribe una observación breve y concreta para cada pregunta, basada en lo que respondió la empresa.
        2. Califica de 0 a 100 solo las preguntas de tipo "abierta", según su "criterio". En las demás devuelve puntaje null: su puntaje ya está calculado y no lo cambias.
        3. Escribe una observación de la categoría que resuma la situación de la empresa en ese tema.
        4. Escribe una recomendación accionable: un título corto en imperativo y un detalle de una o dos frases que se pueda hacer en 1 a 3 meses con pocos recursos.
        TXT,
        'detalles' => <<<'TXT'
        - Apóyate en estas herramientas según el tema de la categoría, sin nombrarlas en el texto: PESTEL para el entorno (mercado, normas, tecnología), las 5 Fuerzas de Porter para la competencia, VRIO para los recursos y la marca, DOFA para cruzar fortalezas y debilidades con oportunidades y amenazas, y CAME (corregir, afrontar, mantener, explotar) para convertir el análisis en una acción.
        - La recomendación debe atacar la debilidad más importante de la categoría.
        - Usa la nota "ten_en_cuenta" cuando exista.
        - Escribe en español de Colombia, en segunda persona (tú), con frases cortas y sin palabras técnicas ni siglas.
        - No inventes datos que la empresa no dio. No compares con otras empresas ni con promedios.
        TXT,
        'ejemplos' => <<<'TXT'
        Observación de una pregunta: "Los pedidos dependen de WhatsApp personal y llamadas: no hay un canal organizado."
        Recomendación: título "Activa pedidos y respuestas automáticas"; detalle "Un menú digital con pedidos y respuestas rápidas en WhatsApp Business para bajar las demoras del fin de semana."
        TXT,
    ];

    /** Capa 5: formato fijo; la salida estructurada lo exige igual (AnalizadorDeCategoria::esquema). */
    public const string FORMATO = <<<'TXT'
    Responde solo con un objeto JSON con: "preguntas" (una entrada por cada pregunta recibida, con su "id", "puntaje" —0 a 100 en las abiertas, null en las demás— y "observacion"), "observacion_categoria" y "recomendacion" (con "titulo" y "detalle").
    TXT;

    /**
     * @param  array<string, mixed>  $categoria  categoría de la versión congelada
     * @param  array<string, array{opciones: list<string>, texto: string}>  $respuestas  por referencia de pregunta
     * @return array{instrucciones: string, categoria: array{nombre: string, sector: string, preguntas: list<array<string, mixed>>}}
     */
    public function armar(Medicion $medicion, array $categoria, array $respuestas): array
    {
        $medicion->loadMissing(['version.diagnostico.sector:id,nombre', 'empresa:id,nombre']);
        $diagnostico = $medicion->version->diagnostico;

        return [
            'instrucciones' => self::instrucciones($this->capas($diagnostico?->sector_id, $medicion->empresa_id)),
            'categoria' => [
                'nombre' => (string) $categoria['nombre'],
                'sector' => (string) ($diagnostico->sector->nombre ?? ''),
                'preguntas' => $this->preguntas($categoria, $respuestas),
            ],
        ];
    }

    /**
     * Capas 1 a 3 ya combinadas más la 5 (formato fijo), como texto.
     *
     * @param  array<string, string>  $textos  por elemento
     */
    public static function instrucciones(array $textos = self::GENERAL): string
    {
        $texto = '';

        foreach (self::ELEMENTOS as $elemento) {
            if (trim($textos[$elemento] ?? '') !== '') {
                $texto .= '## '.ucfirst($elemento)."\n".trim($textos[$elemento])."\n\n";
            }
        }

        return $texto."## Formato\n".self::FORMATO;
    }

    /**
     * Texto final de cada elemento tras aplicar general → sector → empresa.
     *
     * @return array<string, string>
     */
    public function capas(?int $sectorId, ?int $empresaId): array
    {
        $general = Prompt::where('etapa', self::ETAPA)->where('alcance', 'general')->first();
        $textos = [];

        foreach (self::ELEMENTOS as $e) {
            $textos[$e] = $general !== null && filled($general->getAttribute($e)) ? (string) $general->getAttribute($e) : self::GENERAL[$e];
        }

        $ajustes = [
            $sectorId ? Prompt::where('etapa', self::ETAPA)->where('alcance', 'sector')->where('sector_id', $sectorId)->first() : null,
            $empresaId ? Prompt::where('etapa', self::ETAPA)->where('alcance', 'empresa')->where('empresa_id', $empresaId)->first() : null,
        ];

        foreach ($ajustes as $ajuste) {
            if ($ajuste === null) {
                continue;
            }

            foreach (self::ELEMENTOS as $e) {
                $modo = (string) $ajuste->getAttribute("modo_{$e}");
                $texto = trim((string) $ajuste->getAttribute($e));

                if ($texto === '') {
                    continue;
                }

                $textos[$e] = match ($modo) {
                    'agregar' => rtrim($textos[$e])."\n".$texto,
                    'reemplazar' => $texto,
                    default => $textos[$e],
                };
            }
        }

        return $textos;
    }

    /**
     * Capa 4: preguntas respondidas con su respuesta.
     *
     * @param  array<string, mixed>  $categoria
     * @param  array<string, array{opciones: list<string>, texto: string}>  $respuestas
     * @return list<array<string, mixed>>
     */
    public function preguntas(array $categoria, array $respuestas): array
    {
        /** @var list<array<string, mixed>> $preguntas */
        $preguntas = $categoria['preguntas'] ?? [];
        $lista = [];

        foreach ($preguntas as $p) {
            $r = $respuestas[$p['ref']] ?? null;
            $texto = CalculadoraPuntajes::textoRespuesta($p, $r['opciones'] ?? null, $r['texto'] ?? null);

            if ($texto === null) {
                continue;
            }

            $item = [
                'id' => (string) $p['ref'],
                'tipo' => (string) $p['tipo'],
                'texto' => (string) $p['texto'],
                'respuesta' => $texto,
                'puntaje' => $p['tipo'] === 'abierta' ? null : CalculadoraPuntajes::cerrada($p, $r['opciones'] ?? []),
            ];

            if ($p['tipo'] === 'abierta') {
                $item['criterio'] = (string) ($p['criterio_ia'] ?? '');
            } else {
                /** @var list<array<string, mixed>> $opciones */
                $opciones = $p['opciones'] ?? [];
                $notas = [];

                foreach ($opciones as $o) {
                    if (in_array($o['ref'], $r['opciones'] ?? [], true) && filled($o['ten_en_cuenta'] ?? null)) {
                        $notas[] = (string) $o['ten_en_cuenta'];
                    }
                }

                if ($notas !== []) {
                    $item['ten_en_cuenta'] = implode(' ', $notas);
                }
            }

            $lista[] = $item;
        }

        return $lista;
    }
}
