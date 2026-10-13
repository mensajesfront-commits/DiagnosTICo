<?php

namespace App\Console\Commands;

use App\Http\Controllers\PruebaTecnicaController;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Prueba técnica T-025: genera un PDF con el radar de Chart.js usando
 * spatie/laravel-pdf (Browsershot + Chromium).
 */
#[Signature('prueba:pdf {--ruta= : Dónde guardar el PDF}')]
#[Description('Prueba técnica: genera un PDF con el radar de Chart.js')]
class PruebaPdf extends Command
{
    public function handle(): int
    {
        $ruta = $this->option('ruta') ?: storage_path('app/private/pruebas/prueba-radar.pdf');

        File::ensureDirectoryExists(dirname($ruta));

        PruebaTecnicaController::construirPdf()->save($ruta);

        $this->info("PDF generado en {$ruta}");

        return self::SUCCESS;
    }
}
