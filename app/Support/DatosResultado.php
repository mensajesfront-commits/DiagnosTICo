<?php

namespace App\Support;

use App\Models\Medicion;
use App\Models\Resultado;
use App\Services\Resultados\PublicarResultado;
use Carbon\CarbonInterface;

/**
 * El resultado publicado como lo muestran E6 / A3.3 (HU-064), E9 / A3.5
 * (HU-065) y el PDF (HU-066). Todo sale de lo guardado en `resultados`: los
 * puntajes, niveles y variaciones ya vienen calculados (RN-023).
 */
class DatosResultado
{
    /** @return array<string, mixed> */
    public static function cabecera(Medicion $medicion): array
    {
        $medicion->loadMissing(['empresa:id,nombre', 'version.diagnostico.sector:id,nombre']);

        return [
            'id' => $medicion->id,
            'numero' => $medicion->numero,
            'empresa' => ['id' => $medicion->empresa_id, 'nombre' => $medicion->empresa->nombre],
            'sector' => $medicion->version->diagnostico->sector->nombre ?? '',
            'diagnostico' => $medicion->version->diagnostico->nombre ?? '',
            'version' => $medicion->version->numero,
            'enviada_en' => $medicion->enviada_en?->toIso8601String(),
            'terminada_en' => $medicion->terminada_en?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function resultado(Resultado $resultado): array
    {
        $anterior = PublicarResultado::anterior($resultado->medicion);
        $anterior?->load('medicion:id,numero,terminada_en,enviada_en');

        return self::pagina(
            ['puntaje_total' => $resultado->puntaje_total, 'nivel' => $resultado->nivel, 'variacion' => $resultado->variacion, 'por_categoria' => $resultado->por_categoria],
            $anterior === null ? null : [
                'numero' => $anterior->medicion->numero,
                'fecha' => ($anterior->medicion->enviada_en ?? $anterior->publicado_en)->toIso8601String(),
                'puntaje' => $anterior->puntaje_total,
            ],
            $resultado->publicado_en,
            $resultado->pdf_ruta !== null,
        );
    }

    /**
     * El resultado con la forma de E6, a partir de lo calculado (también el
     * resultado de ejemplo de HU-030, que no se guarda).
     *
     * @param  array{puntaje_total: int, nivel: string, variacion: int|null, por_categoria: array<int|string, mixed>}  $datos
     * @param  array{numero: int, fecha: string, puntaje: int}|null  $anterior
     * @return array<string, mixed>
     */
    public static function pagina(array $datos, ?array $anterior, CarbonInterface $publicado, bool $tienePdf): array
    {
        /** @var list<array<string, mixed>> $categorias */
        $categorias = array_values($datos['por_categoria']);

        return [
            'puntaje' => $datos['puntaje_total'],
            'nivel' => $datos['nivel'],
            'frase' => Niveles::frase($datos['nivel']),
            'variacion' => $datos['variacion'],
            'anterior' => $anterior,
            'publicado_en' => $publicado->toIso8601String(),
            'tiene_pdf' => $tienePdf,
            'categorias' => $categorias,
            'prioridades' => self::prioridades($categorias),
            // Resumen de E6: cuántas categorías quedaron en cada nivel y la más fuerte.
            'por_nivel' => array_map(fn (string $clave) => [
                'clave' => $clave,
                'cantidad' => count(array_filter($categorias, fn (array $c) => $c['nivel'] === $clave)),
            ], array_keys(Niveles::NIVELES)),
            'mas_fuerte' => count($categorias) > 1 ? (array_reverse(self::ordenadas($categorias))[0] ?? null) : null,
        ];
    }

    /**
     * Las 3 categorías más débiles, de la más baja a la más alta (HU-064
     * CA-006). Con el mismo puntaje va primero la de más importancia.
     *
     * @param  list<array<string, mixed>>  $categorias
     * @return list<array<string, mixed>>
     */
    public static function prioridades(array $categorias, int $cuantas = 3): array
    {
        return array_slice(self::ordenadas($categorias), 0, $cuantas);
    }

    /**
     * @param  list<array<string, mixed>>  $categorias
     * @return list<array<string, mixed>>
     */
    public static function ordenadas(array $categorias): array
    {
        usort($categorias, fn (array $a, array $b) => [$a['puntaje'], -$a['importancia']] <=> [$b['puntaje'], -$b['importancia']]);

        return array_map(fn (array $c) => [
            'ref' => $c['ref'],
            'nombre' => $c['nombre'],
            'puntaje' => $c['puntaje'],
            'nivel' => $c['nivel'],
            'recomendacion' => $c['recomendacion'],
            // La principal completa (pasos, plazo e indicador), si la hay.
            'principal' => $c['recomendaciones'][0] ?? null,
            'metodo' => $c['metodo'] ?? null,
        ], $categorias);
    }

    /**
     * Datos de la plantilla del PDF (resources/views/pdf/informe.blade.php).
     *
     * @return array<string, mixed>
     */
    public static function informe(Resultado $resultado): array
    {
        $resultado->loadMissing(['medicion.empresa.actividadEconomica']);
        $empresa = $resultado->medicion->empresa;

        return self::informeDe(
            self::cabecera($resultado->medicion),
            self::resultado($resultado),
            $resultado->medicion->enviada_en,
            $resultado->publicado_en,
            [
                'descripcion' => (string) $empresa->descripcion,
                'actividad' => $empresa->actividadEconomica ? "{$empresa->actividadEconomica->codigo} · {$empresa->actividadEconomica->nombre}" : '',
                'ubicacion' => collect([$empresa->ciudad, $empresa->departamento, $empresa->pais])->filter()->join(', '),
                'sitio_web' => (string) $empresa->sitio_web,
                'empleados' => (string) $empresa->numero_empleados,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $cabecera  como `cabecera()`
     * @param  array<string, mixed>  $datos  como `pagina()`
     * @param  array<string, string>|null  $empresa  datos de la ficha técnica; null en el resultado de ejemplo
     * @param  bool  $ejemplo  resultado de ejemplo (HU-030): el PDF lo dice en cada página
     * @return array<string, mixed>
     */
    public static function informeDe(array $cabecera, array $datos, ?CarbonInterface $enviada, CarbonInterface $generado, ?array $empresa = null, bool $ejemplo = false): array
    {
        return [...DatosInforme::armar($cabecera, $datos, $enviada, $generado, $empresa), 'ejemplo' => $ejemplo];
    }
}
