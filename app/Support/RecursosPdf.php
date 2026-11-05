<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Recursos que las plantillas PDF incrustan en el HTML.
 *
 * Chromium genera el PDF a partir de HTML sin conexión a internet, así que
 * Chart.js y las fuentes IBM Plex se copian dentro del propio HTML en lugar de
 * cargarse desde un CDN.
 */
class RecursosPdf
{
    public static function chartJs(): string
    {
        return File::get(base_path('node_modules/chart.js/dist/chart.umd.min.js'));
    }

    /**
     * Fuentes del informe PDF (propuesta de reestructuración): Montserrat
     * para títulos (600 y 700) e Inter para el texto (400, 500 y 600).
     */
    public static function fuentesInforme(): string
    {
        return self::caras([
            ['Montserrat', 600, 'montserrat/files/montserrat-latin-600-normal.woff2'],
            ['Montserrat', 700, 'montserrat/files/montserrat-latin-700-normal.woff2'],
            ['Inter', 400, 'inter/files/inter-latin-400-normal.woff2'],
            ['Inter', 500, 'inter/files/inter-latin-500-normal.woff2'],
            ['Inter', 600, 'inter/files/inter-latin-600-normal.woff2'],
        ]);
    }

    /**
     * Reglas @font-face de IBM Plex Sans (400 y 600) e IBM Plex Mono (400)
     * con las fuentes en base64.
     */
    public static function fuentes(): string
    {
        $fuentes = [
            ['IBM Plex Sans', 400, 'ibm-plex-sans/files/ibm-plex-sans-latin-400-normal.woff2'],
            ['IBM Plex Sans', 600, 'ibm-plex-sans/files/ibm-plex-sans-latin-600-normal.woff2'],
            ['IBM Plex Mono', 400, 'ibm-plex-mono/files/ibm-plex-mono-latin-400-normal.woff2'],
        ];

        return self::caras($fuentes);
    }

    /**
     * @param  list<array{0: string, 1: int, 2: string}>  $fuentes  familia, peso y archivo dentro de @fontsource
     */
    private static function caras(array $fuentes): string
    {
        return collect($fuentes)->map(function (array $fuente): string {
            [$familia, $peso, $archivo] = $fuente;
            $datos = base64_encode(File::get(base_path('node_modules/@fontsource/'.$archivo)));

            return "@font-face { font-family: '{$familia}'; font-weight: {$peso}; font-style: normal; "
                ."src: url(data:font/woff2;base64,{$datos}) format('woff2'); }";
        })->implode("\n");
    }
}
