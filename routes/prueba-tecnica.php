<?php

use App\Http\Controllers\PruebaTecnicaController;
use Illuminate\Support\Facades\Route;

/*
| Rutas de la prueba técnica de la semana 2 (T-018, T-024, T-025).
| Solo se cargan en local y en pruebas (ver routes/web.php). Se borran cuando
| las pantallas reales (A5, E6, E10) las reemplacen.
*/

Route::middleware(['auth', 'role:Administrador'])
    ->prefix('prueba-tecnica')
    ->name('prueba-tecnica.')
    ->group(function () {
        // T-018: ruta bloqueada para cualquier rol que no sea Administrador.
        Route::get('solo-administrador', fn () => response()->json([
            'mensaje' => 'Acceso permitido: tienes el rol Administrador.',
        ]))->name('solo-administrador');

        // T-023: muestrario de los componentes base.
        Route::inertia('componentes', 'prueba-tecnica/Componentes')->name('componentes');

        // T-024: radar de Chart.js en Vue.
        Route::get('graficas', [PruebaTecnicaController::class, 'graficas'])->name('graficas');

        // T-025: el mismo radar dentro de un PDF.
        Route::get('pdf', [PruebaTecnicaController::class, 'pdf'])->name('pdf');
    });
