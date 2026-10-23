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
 * Los datos que se insertan solos ({sector}, {contexto_sector}, {categoria},
 * {respuestas_categoria}) se reemplazan al armar. Cada parte guarda de qué
 * capa salió, para que A4.4 la marque con su color.
 *
 * Las preguntas opcionales sin responder no se envían; las respondidas van
 * marcadas como dato extra (no cuentan en el puntaje).
 */
class ArmadorPrompt
{
    public const string ETAPA = 'analizar_categoria';

    public const array ELEMENTOS = ['contexto', 'tarea', 'detalles', 'ejemplos'];

    public const array TITULOS = [
        'contexto' => 'Contexto',
        'tarea' => 'Tarea',
        'detalles' => 'Detalles y restricciones',
        'ejemplos' => 'Ejemplos',
    ];

    /**
     * Datos que el sistema inserta solos (A4): código → [nombre, elemento
     * donde se espera].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const array VARIABLES = [
        'sector' => ['Sector de la empresa', 'contexto'],
        'contexto_sector' => ['Contexto del sector', 'contexto'],
        'categoria' => ['Nombre de la categoría', 'contexto'],
        'respuestas_categoria' => ['Respuestas de la categoría', 'tarea'],
    ];

    /** Lo que queda en el texto en lugar de {respuestas_categoria}. */
    public const string RESPUESTAS_EN_TEXTO = 'las preguntas con sus respuestas, que van en el mensaje siguiente';

    /**
     * Instrucciones generales por defecto, mientras el Administrador no las
     * cambie en A4. "Restaurar texto original" vuelve a estas.
     *
     * @var array<string, string>
     */
    public const array GENERAL = [
        'contexto' => <<<'TXT'
        Eres consultor de marketing digital de NuevasTIC para pequeñas y medianas empresas del sector {sector}. {contexto_sector} Estás revisando el diagnóstico de marketing digital de una empresa, categoría "{categoria}".
        TXT,
        'tarea' => <<<'TXT'
        Analiza todas las respuestas de la empresa en esta categoría: {respuestas_categoria}.
        1. Escribe una observación breve y concreta para cada pregunta, basada en lo que respondió la empresa.
        2. Califica de 0 a 100 solo las preguntas de tipo "abierta", según su "criterio". En las demás devuelve puntaje null: su puntaje ya está calculado y no lo cambias.
        3. Escribe una observación de la categoría que resuma la situación de la empresa en ese tema.
        4. Escribe una recomendación accionable: un título corto en imperativo y un detalle de una o dos frases que se pueda hacer en 1 a 3 meses con pocos recursos.
        TXT,
        'detalles' => <<<'TXT'
        - Criterios para las abiertas: 0–29 la práctica no existe o la respuesta no aplica; 30–59 existe pero es informal; 60–79 está definida y tiene algo de medición; 80–100 está definida, se mide y se mejora con datos.
        - Las preguntas cerradas ya traen su puntaje: úsalo para entender a la empresa, no lo recalifiques. Si una respuesta trae "ten_en_cuenta", tenlo en cuenta al calificar y al redactar.
        - Elige UN método de análisis según lo que respondió la empresa (no siempre el mismo) y úsalo para la observación de la categoría y la recomendación:
          - PESTEL: factores del entorno (políticos, económicos, sociales, tecnológicos, ecológicos y legales). Úsalo cuando las respuestas dependen de normas, economía, tecnología o cambios del mercado.
          - PORTER (5 Fuerzas de Porter): competidores, poder de clientes y proveedores, sustitutos y nuevos competidores. Úsalo cuando las respuestas tratan de la competencia, los precios o el poder de clientes y proveedores.
          - VRIO: si los recursos propios son valiosos, raros, difíciles de imitar y si la empresa está organizada para aprovecharlos. Úsalo cuando las respuestas tratan de la marca, el equipo, el contenido, las herramientas o los datos.
          - DOFA: debilidades, oportunidades, fortalezas y amenazas. Úsalo cuando las respuestas mezclan lo interno con lo externo.
          - CAME: corregir, afrontar, mantener y explotar. Úsalo cuando el problema ya está claro y hace falta convertirlo en acciones.
          Si una respuesta trae "ten_en_cuenta" que pide un método, usa ese.
        - La recomendación ataca la debilidad más importante que muestre el método elegido.
        - Escribe en español de Colombia, en segunda persona (tú), con frases cortas y sin palabras técnicas ni siglas. Basa todo en lo que la empresa respondió, sin suponer; no compares con otras empresas ni con promedios.
        TXT,
        'ejemplos' => <<<'TXT'
        Sí: "Publico en Instagram según lo que se me ocurra ese día" → 35, "Publica con regularidad, pero sin plan ni medición". No: observaciones genéricas como "Debe mejorar sus redes".
        Recomendación — Sí: título "Arma un calendario mensual", detalle "Tres publicaciones por semana; revisa cada mes cuáles funcionan". No: "Mejora tu contenido".
        TXT,
    ];

    /** Capa 5: formato fijo; la salida estructurada lo exige igual (AnalizadorDeCategoria::esquema). */
    public const string FORMATO = <<<'TXT'
    Responde solo con un objeto JSON con: "preguntas" (una entrada por cada pregunta recibida, con su "id", "puntaje" —0 a 100 en las abiertas, null en las demás— y "observacion"), "metodo" (el método de análisis que usaste según las respuestas: PESTEL, PORTER, VRIO, DOFA o CAME), "metodo_motivo" (una frase: por qué ese método encaja con lo que respondió la empresa), "observacion_categoria" y "recomendacion" (con "titulo" y "detalle").
    TXT;

    /**
     * @param  array<string, mixed>  $categoria  categoría de la versión congelada
     * @param  array<string, array{opciones: list<string>, texto: string}>  $respuestas  por referencia de pregunta
     * @param  array<string, mixed>|null  $borrador  cambios sin guardar de una capa (A4.4, A4.7)
     * @return array{instrucciones: string, categoria: array{nombre: string, sector: string, preguntas: list<array<string, mixed>>}, partes: array<string, list<array{texto: string, origen: string, dato: bool}>>}
     */
    public function armar(Medicion $medicion, array $categoria, array $respuestas, ?array $borrador = null): array
    {
        $medicion->loadMissing(['version.diagnostico.sector:id,nombre,descripcion', 'empresa:id,nombre']);
        $sector = $medicion->version->diagnostico->sector ?? null;

        $datos = [
            'sector' => (string) ($sector->nombre ?? ''),
            'contexto_sector' => trim((string) ($sector->descripcion ?? '')),
            'categoria' => (string) $categoria['nombre'],
            'respuestas_categoria' => self::RESPUESTAS_EN_TEXTO,
        ];

        $partes = [];

        foreach ($this->segmentos($sector?->id, $medicion->empresa_id, $borrador) as $elemento => $segmentos) {
            $partes[$elemento] = [];

            foreach ($segmentos as $s) {
                foreach (self::rellenar($s['texto'], $datos) as $pieza) {
                    $partes[$elemento][] = ['texto' => $pieza['texto'], 'origen' => $s['origen'], 'dato' => $pieza['dato']];
                }
            }
        }

        return [
            'instrucciones' => self::unir(array_map(
                fn (array $piezas) => implode('', array_column($piezas, 'texto')),
                $partes,
            )),
            'categoria' => [
                'nombre' => (string) $categoria['nombre'],
                'sector' => $datos['sector'],
                'preguntas' => $this->preguntas($categoria, $respuestas),
            ],
            'partes' => $partes,
        ];
    }

    /**
     * Capas 1 a 3 ya combinadas más la 5 (formato fijo), como texto. Con
     * `$datos`, reemplaza los datos automáticos.
     *
     * @param  array<string, string>  $textos  por elemento
     * @param  array<string, string>  $datos
     */
    public static function instrucciones(array $textos = self::GENERAL, array $datos = []): string
    {
        if ($datos !== []) {
            $textos = array_map(fn (string $t) => implode('', array_column(self::rellenar($t, $datos), 'texto')), $textos);
        }

        return self::unir($textos);
    }

    /** @param  array<string, string>  $textos */
    private static function unir(array $textos): string
    {
        $texto = '';

        foreach (self::ELEMENTOS as $elemento) {
            if (trim($textos[$elemento] ?? '') !== '') {
                $texto .= '## '.self::TITULOS[$elemento]."\n".trim($textos[$elemento])."\n\n";
            }
        }

        return $texto."## Formato\n".self::FORMATO;
    }

    /**
     * Texto final de cada elemento tras aplicar general → sector → empresa.
     *
     * @param  array<string, mixed>|null  $borrador
     * @return array<string, string>
     */
    public function capas(?int $sectorId, ?int $empresaId, ?array $borrador = null): array
    {
        return array_map(
            fn (array $segmentos) => implode("\n", array_column($segmentos, 'texto')),
            $this->segmentos($sectorId, $empresaId, $borrador),
        );
    }

    /**
     * Cada elemento como lista de trozos con su origen: general, sector o
     * empresa. Un borrador (`alcance`, `id`, `modos`, `textos`) toma el lugar
     * de la capa guardada de ese alcance.
     *
     * @param  array<string, mixed>|null  $borrador
     * @return array<string, list<array{texto: string, origen: string}>>
     */
    public function segmentos(?int $sectorId, ?int $empresaId, ?array $borrador = null): array
    {
        $general = self::textosGenerales();

        if (($borrador['alcance'] ?? null) === 'general') {
            /** @var array<string, string|null> $textosBorrador */
            $textosBorrador = $borrador['textos'] ?? [];

            foreach (self::ELEMENTOS as $e) {
                $general[$e] = (string) ($textosBorrador[$e] ?? '');
            }
        }

        $segmentos = [];

        foreach (self::ELEMENTOS as $e) {
            $segmentos[$e] = trim($general[$e]) === '' ? [] : [['texto' => trim($general[$e]), 'origen' => 'general']];
        }

        foreach (['sector' => $sectorId, 'empresa' => $empresaId] as $alcance => $id) {
            $ajuste = self::ajuste($alcance, $id);

            if (($borrador['alcance'] ?? null) === $alcance && (int) ($borrador['id'] ?? 0) === $id) {
                $ajuste = ['modos' => $borrador['modos'] ?? [], 'textos' => $borrador['textos'] ?? []];
            }

            if ($ajuste === null) {
                continue;
            }

            foreach (self::ELEMENTOS as $e) {
                $modo = (string) ($ajuste['modos'][$e] ?? 'usar');
                $texto = trim((string) ($ajuste['textos'][$e] ?? ''));

                if ($texto === '' || $modo === 'usar') {
                    continue;
                }

                $trozo = ['texto' => $texto, 'origen' => $alcance];
                $segmentos[$e] = $modo === 'reemplazar' ? [$trozo] : [...$segmentos[$e], $trozo];
            }
        }

        return $segmentos;
    }

    /**
     * Instrucciones generales guardadas o, si no hay, las de GENERAL.
     *
     * @return array<string, string>
     */
    public static function textosGenerales(): array
    {
        $general = Prompt::where('etapa', self::ETAPA)->where('alcance', 'general')->first();
        $textos = [];

        foreach (self::ELEMENTOS as $e) {
            $textos[$e] = $general !== null ? (string) $general->getAttribute($e) : self::GENERAL[$e];
        }

        return $textos;
    }

    /**
     * Ajuste guardado de un sector o una empresa; null si no tiene.
     *
     * @return array{modos: array<string, string>, textos: array<string, string>}|null
     */
    public static function ajuste(string $alcance, ?int $id): ?array
    {
        if ($id === null) {
            return null;
        }

        $fila = Prompt::where('etapa', self::ETAPA)->where('alcance', $alcance)->where("{$alcance}_id", $id)->first();

        if ($fila === null) {
            return null;
        }

        $modos = [];
        $textos = [];

        foreach (self::ELEMENTOS as $e) {
            $modos[$e] = (string) $fila->getAttribute("modo_{$e}");
            $textos[$e] = (string) $fila->getAttribute($e);
        }

        return ['modos' => $modos, 'textos' => $textos];
    }

    /**
     * Parte un texto en trozos normales y datos automáticos ya reemplazados.
     * Los códigos que no existen quedan como están.
     *
     * @param  array<string, string>  $datos
     * @return list<array{texto: string, dato: bool}>
     */
    public static function rellenar(string $texto, array $datos): array
    {
        $piezas = [];
        $partes = preg_split('/(\{[a-z_]+\})/', $texto, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($partes as $parte) {
            $codigo = trim($parte, '{}');

            if ($parte !== $codigo && array_key_exists($codigo, $datos)) {
                if ($datos[$codigo] !== '') {
                    $piezas[] = ['texto' => $datos[$codigo], 'dato' => true];
                }

                continue;
            }

            $piezas[] = ['texto' => $parte, 'dato' => false];
        }

        // Un dato vacío ({contexto_sector} sin descripción) no deja dobles espacios.
        foreach ($piezas as $i => $p) {
            if ($i > 0 && ! $p['dato'] && str_ends_with($piezas[$i - 1]['texto'], ' ')) {
                $piezas[$i]['texto'] = ltrim($p['texto'], ' ');
            }
        }

        return array_values(array_filter($piezas, fn (array $p) => $p['texto'] !== ''));
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

            // Opcional respondida: dato extra para entender a la empresa (NuevasTIC).
            if (! (bool) ($p['obligatoria'] ?? true)) {
                $item['dato_extra'] = 'Pregunta opcional: úsala para entender mejor a la empresa; no cuenta en el puntaje.';
            }

            if ($p['tipo'] === 'abierta') {
                $item['criterio'] = (string) ($p['criterio_ia'] ?? '');
                // Los modelos pequeños a veces dejan la abierta sin calificar.
                $item['calificar'] = 'Califica esta respuesta con un entero de 0 a 100 según el criterio.';
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
