<?php

/*
| Prueba técnica T-024 y T-025: la pantalla con el radar y el PDF con el
| mismo radar. Aquí se simula el PDF (Pdf::fake); la generación real con
| Chromium se prueba con `php artisan prueba:pdf`.
*/

use App\Http\Controllers\PruebaTecnicaController;
use App\Models\User;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('Administrador');
    $this->actingAs(User::factory()->create()->assignRole('Administrador'));
});

it('muestra el radar con la medición actual y la anterior', function () {
    $this->get(route('prueba-tecnica.graficas'))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('prueba-tecnica/Graficas')
            ->has('radar.etiquetas', 10)
            ->has('radar.actual.puntajes', 10)
            ->has('radar.anterior.puntajes', 10));
});

it('descarga el PDF con la plantilla del radar', function () {
    Pdf::fake();

    $this->get(route('prueba-tecnica.pdf'))->assertOk();

    Pdf::assertRespondedWithPdf(function ($pdf) {
        return $pdf->viewName === 'pdf.prueba-radar'
            && $pdf->downloadName === 'prueba-radar.pdf'
            && $pdf->isDownload();
    });
});

it('incrusta Chart.js y las fuentes en el HTML del PDF', function () {
    $html = view('pdf.prueba-radar', [
        'radar' => PruebaTecnicaController::radarDeEjemplo(),
    ])->render();

    expect($html)
        ->toContain('Restaurante La Esquina')
        ->toContain("font-family: 'IBM Plex Sans'")
        ->toContain('new Chart(')
        ->toContain('window.radarListo = true')
        ->not->toContain('cdn.');
});

it('guarda el PDF con el comando prueba:pdf', function () {
    Pdf::fake();

    $this->artisan('prueba:pdf', ['--ruta' => storage_path('app/private/pruebas/test.pdf')])
        ->assertSuccessful();

    Pdf::assertSaved(storage_path('app/private/pruebas/test.pdf'));
});
