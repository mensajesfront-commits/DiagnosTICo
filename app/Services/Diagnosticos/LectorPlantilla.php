<?php

namespace App\Services\Diagnosticos;

use App\Support\EstadoDiagnostico;
use Normalizer;

/**
 * A2.5 · «Pegar el contenido» (HU-020 CA-005): lee un diagnóstico escrito en la
 * plantilla de texto y lo convierte en el contenido que usa
 * `ContenidoDiagnostico::aplicar`, el mismo arreglo que «Copiar uno publicado».
 *
 * La plantilla (EJEMPLO tiene una completa):
 *
 *   CATEGORÍA: Presencia en línea
 *   IMPORTANCIA: 25
 *
 *   PREGUNTA: ¿Tu negocio tiene página web propia?
 *   TIPO: Opción única
 *   OBLIGATORIA: Sí
 *   INDICACIÓN: Cuenta solo una página con dominio propio.
 *   OPCIONES:
 *   - 100 | Sí, y la actualizamos seguido
 *   - 0 | No tenemos | TEN EN CUENTA: Prioriza el perfil de Google.
 *
 * - Las etiquetas no distinguen mayúsculas ni tildes («Categoria», «indicación»).
 * - Una línea que no empieza con etiqueta ni con «-» continúa el campo
 *   anterior: así un criterio o una indicación pueden ocupar varias líneas.
 * - Opción: `- puntaje | texto`, y después, en cualquier orden, `OTRO` (pide
 *   escribir cuál), `NINGUNA` (excluye a las demás, solo en selección
 *   múltiple) y `TEN EN CUENTA: …`.
 * - La importancia es opcional: si ninguna categoría la trae, se reparte en
 *   partes iguales (RN-011).
 *
 * Devuelve `errores` (impiden crear: hay que corregir el texto) y `avisos`
 * (lo que falta para publicar, RN-012: se corrige también en el editor).
 * Cada uno con el número de línea del texto pegado.
 */
class LectorPlantilla
{
    /** Máximo de caracteres que se aceptan pegados. */
    public const int MAXIMO = 100000;

    /** Etiquetas de la plantilla, ya normalizadas → campo. */
    private const array ETIQUETAS = [
        'categoria' => 'categoria',
        'importancia' => 'importancia',
        'pregunta' => 'pregunta',
        'tipo' => 'tipo',
        'obligatoria' => 'obligatoria',
        'indicacion' => 'indicacion',
        'criterio' => 'criterio',
        'criterio de calificacion' => 'criterio',
        'criterio ia' => 'criterio',
        'opciones' => 'opciones',
    ];

    /** Formas de escribir cada tipo, ya normalizadas. */
    private const array TIPOS = [
        'abierta' => 'abierta',
        'pregunta abierta' => 'abierta',
        'opcion unica' => 'opcion_unica',
        'unica' => 'opcion_unica',
        'seleccion unica' => 'opcion_unica',
        'seleccion multiple' => 'seleccion_multiple',
        'opcion multiple' => 'seleccion_multiple',
        'multiple' => 'seleccion_multiple',
    ];

    /** Límites de RN-013, los mismos del editor (`EditorDiagnosticoController::validarPregunta`). */
    private const int MAX_PREGUNTA = 500;

    private const int MAX_INDICACION = 500;

    private const int MAX_CRITERIO = 1000;

    private const int MAX_OPCION = 255;

    private const int MAX_TEN_EN_CUENTA = 200;

    private const int MAX_OPCIONES = 10;

    /** Plantilla de ejemplo que se copia desde A2.5. Debe leerse sin errores ni avisos. */
    public const string EJEMPLO = <<<'TXT'
    CATEGORÍA: Presencia en línea
    IMPORTANCIA: 60

    PREGUNTA: ¿Tu negocio tiene página web propia?
    TIPO: Opción única
    OBLIGATORIA: Sí
    INDICACIÓN: Cuenta solo una página con dominio propio, no un perfil de red social.
    OPCIONES:
    - 100 | Sí, y la actualizamos seguido
    - 50 | Sí, pero está desactualizada
    - 0 | No tenemos | TEN EN CUENTA: Si no hay web, recomienda primero el perfil de Google.

    PREGUNTA: ¿En qué redes sociales tiene perfil tu negocio?
    TIPO: Selección múltiple
    OBLIGATORIA: Sí
    INDICACIÓN: Marca solo las que usan para el negocio.
    OPCIONES:
    - 40 | Instagram
    - 30 | Facebook
    - 20 | WhatsApp Business
    - 10 | Otra | OTRO
    - 0 | Ninguna | NINGUNA

    PREGUNTA: ¿Qué te gustaría mejorar de tu presencia en internet?
    TIPO: Abierta
    OBLIGATORIA: No
    INDICACIÓN: Escribe con tus palabras; no hay respuestas buenas o malas.
    CRITERIO: Pregunta opcional: no se califica. Úsala para orientar las recomendaciones.

    CATEGORÍA: Análisis del cliente
    IMPORTANCIA: 40

    PREGUNTA: Describe a tu cliente ideal: quién es y qué busca.
    TIPO: Abierta
    OBLIGATORIA: Sí
    INDICACIÓN: Por ejemplo: edad, dónde vive y por qué te compra.
    CRITERIO: 100: describe un grupo concreto y por qué lo elige.
    60: describe al cliente, pero sin la razón para elegirlo.
    30: respuesta general. 0: no responde.
    TXT;

    /** @var list<array{linea: int, mensaje: string}> */
    private array $errores = [];

    /** @var array<string, array{id: int, nombre: string}> nombre normalizado → categoría */
    private array $catalogo = [];

    /**
     * @param  iterable<array{id: int, nombre: string}|object{id: int, nombre: string}>  $categorias  categorías activas del catálogo
     * @return array{
     *     contenido: array{categorias: list<array<string, mixed>>},
     *     errores: list<array{linea: int, mensaje: string}>,
     *     avisos: list<array{linea: int, mensaje: string}>,
     *     resumen: array{categorias: list<array{nombre: string, importancia: float, preguntas: int}>, preguntas: int, opciones: int},
     * }
     */
    public function leer(string $texto, iterable $categorias): array
    {
        $this->errores = [];
        $this->catalogo = [];

        foreach ($categorias as $c) {
            $c = (array) $c;
            $this->catalogo[self::normalizar((string) $c['nombre'])] = ['id' => (int) $c['id'], 'nombre' => (string) $c['nombre']];
        }

        $lineas = preg_split('/\R/u', $texto) ?: [];
        /** @var list<array<string, mixed>> $categoriasLeidas */
        $categoriasLeidas = [];
        $categoria = null;
        $pregunta = null;
        $campo = null;

        foreach ($lineas as $i => $linea) {
            $n = $i + 1;
            $limpia = trim($linea);

            if ($limpia === '') {
                continue;
            }

            if (str_starts_with($limpia, '-') || str_starts_with($limpia, '*') || str_starts_with($limpia, '•')) {
                if ($pregunta === null || $campo !== 'opciones') {
                    $this->error($n, 'Esta opción no está debajo de «OPCIONES:» de una pregunta.');

                    continue;
                }

                $opcion = $this->opcion(trim(mb_substr($limpia, 1)), $n);

                if ($opcion !== null) {
                    $pregunta['opciones'][] = $opcion;
                }

                continue;
            }

            [$etiqueta, $valor] = $this->etiqueta($limpia);

            if ($etiqueta === null) {
                // Continúa el campo anterior (criterio o indicación en varias líneas).
                if ($pregunta !== null && in_array($campo, ['pregunta', 'indicacion', 'criterio'], true)) {
                    $clave = $campo === 'pregunta' ? 'texto' : ($campo === 'criterio' ? 'criterio_ia' : 'indicacion');
                    $pregunta[$clave] = trim(($pregunta[$clave] ?? '')."\n".$limpia);

                    continue;
                }

                $this->error($n, 'No se entiende esta línea. Debe empezar con una etiqueta como «PREGUNTA:» o con «-» si es una opción.');

                continue;
            }

            switch ($etiqueta) {
                case 'categoria':
                    if ($pregunta !== null) {
                        $categoria['preguntas'][] = $pregunta;
                        $pregunta = null;
                    }

                    if ($categoria !== null) {
                        $categoriasLeidas[] = $categoria;
                    }

                    $categoria = $this->categoria($valor, $n, $categoriasLeidas);
                    $campo = 'categoria';
                    break;

                case 'importancia':
                    if ($categoria === null || $pregunta !== null) {
                        $this->error($n, '«IMPORTANCIA:» va justo debajo de su «CATEGORÍA:», antes de las preguntas.');
                        break;
                    }

                    $valor = rtrim($valor, '% ');
                    $numero = str_replace(',', '.', $valor);

                    if (! is_numeric($numero) || (float) $numero < 0 || (float) $numero > 100) {
                        $this->error($n, "La importancia debe ser un número de 0 a 100 (dice «{$valor}»).");
                        break;
                    }

                    $categoria['importancia'] = round((float) $numero, 2);
                    break;

                case 'pregunta':
                    if ($categoria === null) {
                        // Se sigue leyendo la pregunta (sin guardarla) para no repetir el error en cada campo.
                        $this->error($n, 'Esta pregunta no tiene categoría: escribe antes una línea «CATEGORÍA: …».');
                        $categoria = ['categoria_id' => null, 'nombre' => '', 'importancia' => null, 'preguntas' => [], 'linea' => $n];
                    }

                    if ($pregunta !== null) {
                        $categoria['preguntas'][] = $pregunta;
                    }

                    $pregunta = [
                        'texto' => $valor,
                        'tipo' => null,
                        'indicacion' => '',
                        'criterio_ia' => null,
                        'obligatoria' => true,
                        'opciones' => [],
                        'linea' => $n,
                    ];
                    $campo = 'pregunta';
                    break;

                default:
                    if ($pregunta === null) {
                        $this->error($n, '«'.mb_strtoupper($etiqueta).':» va dentro de una pregunta: escribe antes «PREGUNTA: …».');
                        break;
                    }

                    $campo = $etiqueta;
                    $pregunta = $this->campo($pregunta, $etiqueta, $valor, $n);
            }
        }

        if ($pregunta !== null && $categoria !== null) {
            $categoria['preguntas'][] = $pregunta;
        }

        if ($categoria !== null) {
            $categoriasLeidas[] = $categoria;
        }

        if ($categoriasLeidas === [] && $this->errores === []) {
            $this->error(1, 'No se encontró ninguna categoría. Empieza con una línea «CATEGORÍA: …».');
        }

        $categoriasLeidas = array_values(array_filter($categoriasLeidas, fn (?array $c) => $c !== null && $c['categoria_id'] !== null));
        $contenido = $this->contenido($categoriasLeidas);

        return [
            'contenido' => $contenido,
            'errores' => $this->ordenar($this->errores),
            'avisos' => $this->errores === [] ? $this->avisos($contenido, $categoriasLeidas) : [],
            'resumen' => [
                'categorias' => array_map(fn (array $c) => [
                    'nombre' => (string) $c['nombre'],
                    'importancia' => (float) $c['importancia'],
                    'preguntas' => count($c['preguntas']),
                ], $contenido['categorias']),
                'preguntas' => array_sum(array_map(fn (array $c) => count($c['preguntas']), $contenido['categorias'])),
                'opciones' => array_sum(array_map(
                    fn (array $c) => array_sum(array_map(fn (array $p) => count($p['opciones']), $c['preguntas'])),
                    $contenido['categorias'],
                )),
            ],
        ];
    }

    /**
     * «ETIQUETA: valor» → [campo, valor]; [null, línea] si no empieza con una etiqueta conocida.
     *
     * @return array{0: string|null, 1: string}
     */
    private function etiqueta(string $linea): array
    {
        if (preg_match('/^([^:]{2,40}):(.*)$/u', $linea, $m) !== 1) {
            return [null, $linea];
        }

        $clave = self::normalizar($m[1]);

        return isset(self::ETIQUETAS[$clave]) ? [self::ETIQUETAS[$clave], trim($m[2])] : [null, $linea];
    }

    /**
     * @param  list<array<string, mixed>>  $leidas
     * @return array<string, mixed>
     */
    private function categoria(string $nombre, int $n, array $leidas): array
    {
        $encontrada = $this->catalogo[self::normalizar($nombre)] ?? null;

        if ($encontrada === null) {
            $nombres = implode(', ', array_column($this->catalogo, 'nombre'));
            $this->error($n, "«{$nombre}» no está en el catálogo de categorías. Usa una de estas: {$nombres}.");
        } elseif (in_array($encontrada['id'], array_column($leidas, 'categoria_id'), true)) {
            $this->error($n, "La categoría «{$encontrada['nombre']}» está dos veces: junta sus preguntas en una sola.");
            $encontrada = null;
        }

        return [
            'categoria_id' => $encontrada['id'] ?? null,
            'nombre' => $encontrada['nombre'] ?? $nombre,
            'importancia' => null,
            'preguntas' => [],
            'linea' => $n,
        ];
    }

    /**
     * @param  array<string, mixed>  $pregunta
     * @return array<string, mixed>
     */
    private function campo(array $pregunta, string $campo, string $valor, int $n): array
    {
        switch ($campo) {
            case 'tipo':
                $tipo = self::TIPOS[self::normalizar($valor)] ?? null;

                if ($tipo === null) {
                    $this->error($n, "Tipo «{$valor}» no reconocido. Usa: Abierta, Opción única o Selección múltiple.");
                }

                $pregunta['tipo'] = $tipo;
                break;

            case 'obligatoria':
                $si = self::normalizar($valor);

                if (! in_array($si, ['si', 'no'], true)) {
                    $this->error($n, "En «OBLIGATORIA:» escribe Sí o No (dice «{$valor}»).");
                }

                $pregunta['obligatoria'] = $si !== 'no';
                break;

            case 'indicacion':
                $pregunta['indicacion'] = $valor;
                break;

            case 'criterio':
                $pregunta['criterio_ia'] = $valor;
                break;

            case 'opciones':
                if ($valor !== '') {
                    $this->error($n, 'Escribe cada opción en su propia línea, debajo de «OPCIONES:», empezando con «-».');
                }
                break;
        }

        return $pregunta;
    }

    /**
     * «100 | Texto | OTRO | NINGUNA | TEN EN CUENTA: …» → opción, o null si no se puede leer.
     *
     * @return array<string, mixed>|null
     */
    private function opcion(string $linea, int $n): ?array
    {
        $partes = array_map('trim', explode('|', $linea));
        $puntaje = rtrim((string) array_shift($partes), ' pts.');

        if (! ctype_digit($puntaje) || (int) $puntaje > 100) {
            $this->error($n, 'La opción debe empezar con su puntaje de 0 a 100 y una barra: «- 100 | Texto de la opción».');

            return null;
        }

        $texto = array_shift($partes) ?? '';

        if ($texto === '') {
            $this->error($n, 'Falta el texto de la opción después del puntaje.');

            return null;
        }

        $opcion = ['texto' => $texto, 'puntaje' => (int) $puntaje, 'ten_en_cuenta' => null, 'exclusiva' => false, 'con_texto' => false, 'linea' => $n];

        foreach ($partes as $parte) {
            $clave = self::normalizar($parte);

            if ($clave === 'otro' || $clave === 'otra') {
                $opcion['con_texto'] = true;
            } elseif ($clave === 'ninguna' || $clave === 'ninguno') {
                $opcion['exclusiva'] = true;
            } elseif (preg_match('/^ten\s+en\s+cuenta\s*:(.*)$/iu', $parte, $m) === 1) {
                $opcion['ten_en_cuenta'] = trim($m[1]) === '' ? null : trim($m[1]);
            } elseif ($parte !== '') {
                $this->error($n, "No se entiende «{$parte}» en la opción. Después del texto solo van OTRO, NINGUNA o TEN EN CUENTA: …");
            }
        }

        if ($opcion['ten_en_cuenta'] !== null && mb_strlen($opcion['ten_en_cuenta']) > self::MAX_TEN_EN_CUENTA) {
            $this->error($n, 'El «Ten en cuenta» tiene '.mb_strlen($opcion['ten_en_cuenta']).' caracteres; el máximo es '.self::MAX_TEN_EN_CUENTA.'.');
        }

        if (mb_strlen($texto) > self::MAX_OPCION) {
            $this->error($n, 'El texto de la opción pasa de '.self::MAX_OPCION.' caracteres.');
        }

        return $opcion;
    }

    /**
     * Revisa cada pregunta y arma el contenido final: refs, orden e importancia.
     *
     * @param  list<array<string, mixed>>  $categorias
     * @return array{categorias: list<array<string, mixed>>}
     */
    private function contenido(array $categorias): array
    {
        $conImportancia = array_filter($categorias, fn (array $c) => $c['importancia'] !== null);

        if ($conImportancia !== [] && count($conImportancia) < count($categorias)) {
            foreach ($categorias as $c) {
                if ($c['importancia'] === null) {
                    $this->error((int) $c['linea'], "A «{$c['nombre']}» le falta «IMPORTANCIA:». Ponla en todas las categorías o en ninguna (así se reparte en partes iguales).");
                }
            }
        }

        $repartida = ContenidoDiagnostico::repartir(count($categorias));
        $salida = [];

        foreach ($categorias as $ci => $c) {
            $preguntas = [];

            foreach ($c['preguntas'] as $pi => $p) {
                $preguntas[] = $this->pregunta($p, 'c'.$ci.'p'.$pi, $pi);
            }

            $salida[] = [
                'ref' => 'c'.$ci,
                'categoria_id' => $c['categoria_id'],
                'nombre' => $c['nombre'],
                'importancia' => $conImportancia === [] ? $repartida[$ci] : (float) ($c['importancia'] ?? 0),
                'orden' => $ci,
                'preguntas' => $preguntas,
                'linea' => $c['linea'],
            ];
        }

        return ['categorias' => $salida];
    }

    /**
     * @param  array<string, mixed>  $p
     * @return array<string, mixed>
     */
    private function pregunta(array $p, string $ref, int $orden): array
    {
        $n = (int) $p['linea'];
        $texto = (string) $p['texto'];

        if ($texto === '') {
            $this->error($n, 'Falta el texto de la pregunta después de «PREGUNTA:».');
        } elseif (mb_strlen($texto) > self::MAX_PREGUNTA) {
            $this->error($n, 'La pregunta pasa de '.self::MAX_PREGUNTA.' caracteres.');
        }

        if ($p['tipo'] === null) {
            $this->error($n, 'A esta pregunta le falta «TIPO:» (Abierta, Opción única o Selección múltiple).');
        }

        if (mb_strlen((string) $p['indicacion']) > self::MAX_INDICACION) {
            $this->error($n, 'La indicación pasa de '.self::MAX_INDICACION.' caracteres.');
        }

        if ($p['criterio_ia'] !== null && mb_strlen((string) $p['criterio_ia']) > self::MAX_CRITERIO) {
            $this->error($n, 'El criterio pasa de '.self::MAX_CRITERIO.' caracteres.');
        }

        /** @var list<array<string, mixed>> $opciones */
        $opciones = $p['opciones'];

        if ($p['tipo'] === 'abierta' && $opciones !== []) {
            $this->error($n, 'Una pregunta abierta no lleva opciones: quítalas o cambia el tipo.');
        }

        if ($p['tipo'] !== 'abierta' && $p['criterio_ia'] !== null) {
            $this->error($n, '«CRITERIO:» solo va en las preguntas abiertas; en las de opciones usa «TEN EN CUENTA:» en cada opción.');
        }

        if (count($opciones) > self::MAX_OPCIONES) {
            $this->error($n, 'Tiene '.count($opciones).' opciones; el máximo es '.self::MAX_OPCIONES.'.');
        }

        $exclusivas = array_filter($opciones, fn (array $o) => $o['exclusiva']);

        if ($exclusivas !== [] && $p['tipo'] !== 'seleccion_multiple') {
            $this->error((int) reset($exclusivas)['linea'], 'NINGUNA solo se usa en selección múltiple: en opción única ya se elige una sola.');
        } elseif (count($exclusivas) > 1) {
            $this->error((int) array_values($exclusivas)[1]['linea'], 'Solo una opción por pregunta puede ser NINGUNA.');
        }

        $otros = array_filter($opciones, fn (array $o) => $o['con_texto']);

        if (count($otros) > 1) {
            $this->error((int) array_values($otros)[1]['linea'], 'Solo una opción por pregunta puede ser OTRO.');
        }

        return [
            'ref' => $ref,
            'texto' => $texto,
            'tipo' => $p['tipo'] ?? 'abierta',
            'indicacion' => (string) $p['indicacion'],
            'criterio_ia' => $p['tipo'] === 'abierta' ? $p['criterio_ia'] : null,
            'obligatoria' => (bool) $p['obligatoria'],
            'orden' => $orden,
            'opciones' => array_map(fn (array $o, int $oi) => [
                'ref' => $ref.'o'.$oi,
                'texto' => $o['texto'],
                'puntaje' => $o['puntaje'],
                'ten_en_cuenta' => $o['ten_en_cuenta'],
                'exclusiva' => $o['exclusiva'],
                'con_texto' => $o['con_texto'],
                'orden' => $oi,
            ], $p['tipo'] === 'abierta' ? [] : $opciones, $p['tipo'] === 'abierta' ? [] : array_keys($opciones)),
            'linea' => $n,
        ];
    }

    /**
     * Lo que le faltaría para publicar (RN-012), con su línea.
     *
     * @param  array{categorias: list<array<string, mixed>>}  $contenido
     * @param  list<array<string, mixed>>  $categorias
     * @return list<array{linea: int, mensaje: string}>
     */
    private function avisos(array $contenido, array $categorias): array
    {
        $avisos = [];
        $estado = EstadoDiagnostico::calcular($contenido);

        if (abs($estado['importancia'] - 100) >= 0.005) {
            $avisos[] = ['linea' => (int) ($contenido['categorias'][0]['linea'] ?? 1), 'mensaje' => 'La importancia de las categorías suma '.$estado['importancia'].'%; debe sumar 100%.'];
        }

        foreach ($contenido['categorias'] as $c) {
            if ($c['preguntas'] === []) {
                $avisos[] = ['linea' => (int) $c['linea'], 'mensaje' => "«{$c['nombre']}» no tiene preguntas."];
            }

            foreach ($c['preguntas'] as $p) {
                foreach (EstadoDiagnostico::problemasDePregunta($p) as $motivo) {
                    $avisos[] = ['linea' => (int) $p['linea'], 'mensaje' => $motivo.'.'];
                }
            }
        }

        return $this->ordenar($avisos);
    }

    private function error(int $linea, string $mensaje): void
    {
        $this->errores[] = ['linea' => $linea, 'mensaje' => $mensaje];
    }

    /**
     * @param  list<array{linea: int, mensaje: string}>  $lista
     * @return list<array{linea: int, mensaje: string}>
     */
    private function ordenar(array $lista): array
    {
        usort($lista, fn (array $a, array $b) => $a['linea'] <=> $b['linea']);

        return $lista;
    }

    /** Minúsculas, sin tildes ni espacios de más: «Selección  Múltiple» → «seleccion multiple». */
    private static function normalizar(string $texto): string
    {
        $descompuesto = Normalizer::normalize($texto, Normalizer::FORM_D);
        $sinTildes = preg_replace('/\p{Mn}/u', '', $descompuesto === false ? $texto : $descompuesto) ?? $texto;

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $sinTildes) ?? $sinTildes));
    }
}
