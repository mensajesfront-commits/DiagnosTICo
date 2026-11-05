<?php

namespace App\Services\Resultados;

use App\Models\Resultado;
use App\Support\DatosResultado;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * E10 · Los dos PDF del resultado (HU-066, RN-024). Salen solo de lo
 * guardado en el resultado: nunca vuelven a llamar a la IA.
 *
 * - Completo (`pdf.informe`): portada, metodología, resultados, diagnóstico
 *   por categoría, DOFA, plan, cronograma, KPI y anexos. Para el
 *   Administrador y el Consultor.
 * - Resumen (`pdf.resumen`): lo que la IA dijo de cada categoría, ordenado
 *   y sin detalle técnico. Es el que recibe la empresa (decisión del 4 de
 *   noviembre: un empresario no lee 36 páginas).
 *
 * Se guardan en el disco local (storage/app/private/informes); las rutas
 * quedan en `resultados.pdf_ruta` y `resultados.pdf_resumen_ruta`.
 */
class InformePdf
{
    public const string COMPLETO = 'completo';

    public const string RESUMEN = 'resumen';

    /** @var list<string> */
    public const array TIPOS = [self::COMPLETO, self::RESUMEN];

    /** Columna del resultado donde se guarda la ruta de cada PDF. */
    public static function columna(string $tipo): string
    {
        return $tipo === self::RESUMEN ? 'pdf_resumen_ruta' : 'pdf_ruta';
    }

    /** informe_restaurante_la_esquina_m3.pdf · resumen_restaurante_la_esquina_m3.pdf */
    public static function nombreArchivo(Resultado $resultado, string $tipo = self::COMPLETO): string
    {
        $resultado->loadMissing('medicion.empresa');
        $prefijo = $tipo === self::RESUMEN ? 'resumen' : 'informe';

        return $prefijo.'_'.Str::slug($resultado->medicion->empresa->nombre, '_').'_m'.$resultado->medicion->numero.'.pdf';
    }

    public static function construir(Resultado $resultado, string $tipo = self::COMPLETO): PdfBuilder
    {
        return self::desde(DatosResultado::informe($resultado), $tipo);
    }

    /**
     * El PDF a partir de los datos ya armados (también el resultado de
     * ejemplo de HU-030, que no se guarda).
     *
     * @param  array<string, mixed>  $informe
     */
    public static function desde(array $informe, string $tipo = self::COMPLETO): PdfBuilder
    {
        $resumen = $tipo === self::RESUMEN;
        $titulo = "{$informe['empresa']['nombre']} · Medición {$informe['numero']}";

        return Pdf::view($resumen ? 'pdf.resumen' : 'pdf.informe', ['informe' => $informe])
            // Encabezado y número de página en cada hoja: el largo depende de las categorías.
            ->headerView('pdf.encabezado', [
                'titulo' => $titulo,
                'nombre' => $resumen ? 'Resumen del diagnóstico de marketing digital' : 'Informe de Diagnóstico y Estrategia de Marketing Digital',
            ])
            ->footerView('pdf.pie')
            ->format(Format::Letter)
            ->margins(20, 18, 18, 18)
            // La plantilla marca window.radarListo cuando terminó de dibujar.
            ->waitUntilReady('window.radarListo === true');
    }

    /** Genera y guarda el PDF; devuelve la ruta dentro del disco local. */
    public static function generar(Resultado $resultado, string $tipo = self::COMPLETO): string
    {
        $ruta = 'informes/'.$resultado->medicion->empresa_id.'/'.self::nombreArchivo($resultado, $tipo);

        self::construir($resultado, $tipo)->disk('local')->save($ruta);

        $resultado->forceFill([self::columna($tipo) => $ruta])->save();

        return $ruta;
    }
}
