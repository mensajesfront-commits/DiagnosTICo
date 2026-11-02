<?php

namespace App\Services\Resultados;

use App\Models\Resultado;
use App\Support\DatosResultado;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * Informe PDF del resultado (E10, HU-066, RN-024): portada, cómo leerlo,
 * resumen, una página por categoría, plan de acción y glosario. Sale solo
 * de lo guardado en el resultado: nunca vuelve a llamar a la IA.
 *
 * Se guarda en el disco local (storage/app/private/informes) y la ruta queda
 * en `resultados.pdf_ruta`.
 */
class InformePdf
{
    /** informe_restaurante_la_esquina_m3.pdf */
    public static function nombreArchivo(Resultado $resultado): string
    {
        $resultado->loadMissing('medicion.empresa');

        return 'informe_'.Str::slug($resultado->medicion->empresa->nombre, '_').'_m'.$resultado->medicion->numero.'.pdf';
    }

    public static function construir(Resultado $resultado): PdfBuilder
    {
        return self::desde(DatosResultado::informe($resultado));
    }

    /**
     * El PDF a partir de los datos ya armados (también el resultado de
     * ejemplo de HU-030, que no se guarda).
     *
     * @param  array<string, mixed>  $informe
     */
    public static function desde(array $informe): PdfBuilder
    {
        $titulo = "{$informe['empresa']['nombre']} · Medición {$informe['numero']}";

        return Pdf::view('pdf.informe', ['informe' => $informe])
            // Encabezado y número de página en cada hoja: el largo depende de las categorías.
            ->headerView('pdf.encabezado', ['titulo' => $titulo])
            ->footerView('pdf.pie')
            ->format(Format::Letter)
            ->margins(20, 18, 18, 18)
            // La plantilla marca window.radarListo cuando Chart.js terminó de dibujar.
            ->waitUntilReady('window.radarListo === true');
    }

    /** Genera y guarda el PDF; devuelve la ruta dentro del disco local. */
    public static function generar(Resultado $resultado): string
    {
        $ruta = 'informes/'.$resultado->medicion->empresa_id.'/'.self::nombreArchivo($resultado);

        self::construir($resultado)->disk('local')->save($ruta);

        $resultado->forceFill(['pdf_ruta' => $ruta])->save();

        return $ruta;
    }
}
