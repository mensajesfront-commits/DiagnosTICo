<?php

namespace App\Services\Resultados;

use App\Models\Resultado;
use App\Support\DatosResultado;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * Informe PDF del resultado (E10, HU-066, RN-024): 3 páginas (portada y
 * puntaje; radar y puntaje por categoría; recomendaciones). Sale solo de lo
 * guardado en el resultado: nunca vuelve a llamar a la IA.
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
        return Pdf::view('pdf.informe', ['informe' => DatosResultado::informe($resultado)])
            ->format(Format::Letter)
            ->margins(14, 14, 14, 14)
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
