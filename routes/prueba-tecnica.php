<?php

use App\Http\Controllers\PruebaTecnicaController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

        // Vistas previas de las pantallas con los datos de ejemplo del
        // wireframe (resources/datos-ejemplo/*.json), para revisar el frontend
        // mientras no existen las rutas reales. Cada JSON dice qué página
        // abrir y con qué props, que son las mismas que debe entregar el
        // controlador (docs/14_FRONTEND.md).
        Route::get('vistas/{vista}', function (string $vista) {
            $archivo = resource_path("datos-ejemplo/{$vista}.json");
            abort_unless(File::exists($archivo), 404);

            /** @var array{componente: string, props: array<string, mixed>} $datos */
            $datos = File::json($archivo);

            return Inertia::render($datos['componente'], $datos['props']);
        })->where('vista', '[a-z0-9-]+')->name('vista');
    });
