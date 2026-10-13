<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * Pantallas de la prueba técnica de la semana 2 (T-024 y T-025): el radar de
 * Chart.js en Vue y el mismo radar dentro de un PDF. Solo existen en local y
 * en pruebas (routes/prueba-tecnica.php).
 */
class PruebaTecnicaController extends Controller
{
    public function graficas(): Response
    {
        return Inertia::render('prueba-tecnica/Graficas', [
            'radar' => self::radarDeEjemplo(),
        ]);
    }

    public function pdf(): PdfBuilder
    {
        return self::construirPdf()->download('prueba-radar.pdf');
    }

    public static function construirPdf(): PdfBuilder
    {
        return Pdf::view('pdf.prueba-radar', ['radar' => self::radarDeEjemplo()])
            ->format(Format::Letter)
            ->margins(16, 16, 16, 16)
            // La plantilla marca window.radarListo cuando Chart.js terminó de dibujar.
            ->waitUntilReady('window.radarListo === true');
    }

    /**
     * Puntajes de ejemplo del wireframe E6 (Restaurante La Esquina, medición 3
     * frente a la medición 2).
     *
     * @return array{empresa: string, etiquetas: list<string>, actual: array{nombre: string, puntajes: list<int>}, anterior: array{nombre: string, puntajes: list<int>}}
     */
    public static function radarDeEjemplo(): array
    {
        return [
            'empresa' => 'Restaurante La Esquina',
            'etiquetas' => [
                'Mercado', 'Competencia', 'Cliente', 'Marca', 'Presencia en línea',
                'Contenido', 'Automatización', 'Inversión', 'KPIs', 'RR. HH.',
            ],
            'actual' => [
                'nombre' => 'Medición 3',
                'puntajes' => [72, 48, 81, 66, 55, 38, 22, 60, 45, 70],
            ],
            'anterior' => [
                'nombre' => 'Medición 2',
                'puntajes' => [62, 50, 75, 62, 43, 33, 22, 52, 36, 67],
            ],
        ];
    }
}
