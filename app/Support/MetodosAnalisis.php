<?php

namespace App\Support;

/**
 * Métodos con los que la IA analiza cada categoría (pedido del equipo, 14 de
 * octubre): no siempre DOFA; la IA elige el que mejor encaja con lo que
 * respondió la empresa y el resultado muestra cuál usó y por qué.
 *
 * NuevasTIC confirmó (23 de octubre de 2026) que la IA puede usar DOFA,
 * PESTEL y los demás métodos; RN-028 se ajustó. No hay una matriz FODA: solo
 * el nombre del método que guió la observación y la recomendación.
 */
class MetodosAnalisis
{
    /**
     * Clave → [nombre, para qué sirve, cuándo usarlo].
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    public const array METODOS = [
        'PESTEL' => [
            'PESTEL',
            'Factores del entorno: políticos, económicos, sociales, tecnológicos, ecológicos y legales.',
            'cuando las respuestas dependen del entorno: normas, economía, tecnología o cambios en el mercado.',
        ],
        'PORTER' => [
            '5 Fuerzas de Porter',
            'Competidores, poder de clientes y proveedores, sustitutos y nuevos competidores.',
            'cuando las respuestas tratan de la competencia, los precios o el poder de clientes y proveedores.',
        ],
        'VRIO' => [
            'VRIO',
            'Recursos propios: si son valiosos, raros, difíciles de imitar y si la empresa está organizada para aprovecharlos.',
            'cuando las respuestas tratan de lo que la empresa tiene: marca, equipo, contenido, herramientas o datos.',
        ],
        'DOFA' => [
            'DOFA',
            'Debilidades, oportunidades, fortalezas y amenazas.',
            'cuando las respuestas mezclan lo interno (fortalezas y debilidades) con lo externo (oportunidades y amenazas).',
        ],
        'CAME' => [
            'CAME',
            'Corregir debilidades, afrontar amenazas, mantener fortalezas y explotar oportunidades.',
            'cuando el problema ya está claro y hace falta convertirlo en acciones concretas.',
        ],
    ];

    /**
     * Grupos en los que cada método ordena lo que encontró (hallazgos del
     * resultado). La IA solo llena los que tienen algo que decir según las
     * respuestas.
     *
     * @var array<string, list<string>>
     */
    public const array GRUPOS = [
        'PESTEL' => ['Político', 'Económico', 'Social', 'Tecnológico', 'Ecológico', 'Legal'],
        'PORTER' => ['Competidores actuales', 'Nuevos competidores', 'Productos sustitutos', 'Poder de los clientes', 'Poder de los proveedores'],
        'VRIO' => ['Valioso', 'Raro', 'Difícil de imitar', 'Organizado para aprovecharlo'],
        'DOFA' => ['Fortalezas', 'Oportunidades', 'Debilidades', 'Amenazas'],
        'CAME' => ['Corregir', 'Afrontar', 'Mantener', 'Explotar'],
    ];

    /**
     * Qué quiere decir cada grupo, en una frase (informe PDF).
     *
     * @var array<string, string>
     */
    public const array EXPLICACION_GRUPOS = [
        'Político' => 'Normas y decisiones del gobierno que afectan al negocio.',
        'Económico' => 'Precios, costos y el poder de compra de los clientes.',
        'Social' => 'Hábitos, gustos y costumbres de los clientes.',
        'Tecnológico' => 'Herramientas y plataformas digitales que hay a la mano.',
        'Ecológico' => 'Exigencias ambientales y de sostenibilidad.',
        'Legal' => 'Leyes que se deben cumplir, como la de protección de datos.',
        'Competidores actuales' => 'Las empresas que hoy venden lo mismo a los mismos clientes.',
        'Nuevos competidores' => 'Qué tan fácil es que llegue alguien nuevo a competir.',
        'Productos sustitutos' => 'Otras formas en que el cliente resuelve la misma necesidad.',
        'Poder de los clientes' => 'Qué tanto pueden exigir los clientes o cambiarse a otro.',
        'Poder de los proveedores' => 'Qué tanto depende la empresa de quienes le venden o le prestan servicios.',
        'Valioso' => '¿El recurso ayuda a ganar clientes o a ahorrar?',
        'Raro' => '¿Pocos competidores lo tienen?',
        'Difícil de imitar' => '¿Costaría tiempo o dinero copiarlo?',
        'Organizado para aprovecharlo' => '¿Hay alguien a cargo y una forma de trabajo para sacarle provecho?',
        'Fortalezas' => 'Lo que la empresa hace bien (depende de ella).',
        'Oportunidades' => 'Lo que el mercado ofrece y se puede aprovechar (viene de afuera).',
        'Debilidades' => 'Lo que la empresa hace mal o todavía no hace (depende de ella).',
        'Amenazas' => 'Lo de afuera que puede hacer daño (viene de afuera).',
        'Corregir' => 'Debilidades que hay que arreglar.',
        'Afrontar' => 'Amenazas para las que hay que prepararse.',
        'Mantener' => 'Fortalezas que no se deben perder.',
        'Explotar' => 'Oportunidades que conviene aprovechar.',
    ];

    /**
     * El método explicado para el informe PDF: nombre, qué es, cuándo se usa
     * y sus grupos con lo que significa cada uno.
     *
     * @return array{clave: string, nombre: string, que: string, cuando: string, grupos: list<array{nombre: string, explicacion: string}>}|null
     */
    public static function explicado(string $clave): ?array
    {
        if (! isset(self::METODOS[$clave])) {
            return null;
        }

        [$nombre, $que, $cuando] = self::METODOS[$clave];

        return [
            'clave' => $clave,
            'nombre' => $nombre,
            'que' => $que,
            'cuando' => ucfirst($cuando),
            'grupos' => array_map(fn (string $g) => ['nombre' => $g, 'explicacion' => self::EXPLICACION_GRUPOS[$g] ?? ''], self::grupos($clave)),
        ];
    }

    /** @return list<string> */
    public static function grupos(string $clave): array
    {
        return self::GRUPOS[$clave] ?? [];
    }

    /**
     * Todos los grupos, sin repetir (para el esquema de la respuesta).
     *
     * @return list<string>
     */
    public static function todosLosGrupos(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::GRUPOS))));
    }

    /** @return list<string> */
    public static function claves(): array
    {
        return array_keys(self::METODOS);
    }

    public static function nombre(string $clave): string
    {
        return self::METODOS[$clave][0] ?? $clave;
    }

    /** Texto para las instrucciones de la IA: cada método y cuándo usarlo. */
    public static function guia(): string
    {
        $lineas = [];

        foreach (self::METODOS as $clave => [$nombre, $que, $cuando]) {
            $lineas[] = "  - {$clave} ({$nombre}): {$que} Úsalo {$cuando} Grupos: ".implode(', ', self::grupos($clave)).'.';
        }

        return implode("\n", $lineas);
    }

    /**
     * Método por defecto según el nombre de la categoría (analizador de
     * prueba y resultado de ejemplo, sin IA).
     */
    public static function porCategoria(string $nombre, int $puntaje = 50): string
    {
        $n = mb_strtolower($nombre);

        return match (true) {
            str_contains($n, 'mercado') || str_contains($n, 'entorno') => 'PESTEL',
            str_contains($n, 'competencia') => 'PORTER',
            str_contains($n, 'marca') || str_contains($n, 'humanos') || str_contains($n, 'contenido') => 'VRIO',
            $puntaje < 30 => 'CAME',
            default => 'DOFA',
        };
    }
}
