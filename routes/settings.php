<?php

use App\Http\Controllers\PerfilController;
use Illuminate\Support\Facades\Route;

/*
 * Mi perfil (A6 y E11). Los nombres de ruta son los del kit para no romper
 * los enlaces que ya los usan (menú lateral).
 *
 * Verlo: todas las cuentas. Cambiar los datos, la foto o el correo pide
 * "Editar su perfil" y la contraseña, "Cambiar su contraseña". El
 * colaborador no tiene ninguno: eso lo hace la cuenta principal (E12.3).
 */
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/mi-perfil');
    Route::redirect('settings/profile', '/mi-perfil');

    Route::get('mi-perfil', [PerfilController::class, 'edit'])->name('profile.edit');

    Route::middleware('permission:perfil.editar')->group(function () {
        Route::patch('mi-perfil', [PerfilController::class, 'update'])->name('profile.update');

        Route::post('mi-perfil/foto', [PerfilController::class, 'foto'])->name('perfil.foto');

        // Cambiar el correo: se confirma con un código al correo nuevo (DEC-020).
        Route::post('mi-perfil/correo/verificar', [PerfilController::class, 'verificarCorreo'])
            ->middleware('throttle:10,1')
            ->name('perfil.correo.verificar');
        Route::post('mi-perfil/correo/reenviar', [PerfilController::class, 'reenviarCodigoCorreo'])
            ->middleware('throttle:3,1')
            ->name('perfil.correo.reenviar');
        Route::post('mi-perfil/correo/cancelar', [PerfilController::class, 'cancelarCambioCorreo'])
            ->name('perfil.correo.cancelar');
    });

    Route::put('mi-perfil/contrasena', [PerfilController::class, 'contrasena'])
        ->middleware(['permission:contrasena.cambiar', 'throttle:6,1'])
        ->name('user-password.update');

    // A6 · La cuenta principal del Administrador se elimina a sí misma,
    // eligiendo quién queda como principal (nadie más puede eliminarla).
    Route::delete('mi-perfil/cuenta', [PerfilController::class, 'eliminarCuenta'])->name('perfil.eliminar');

    Route::get('imagenes/{tipo}/{id}', [PerfilController::class, 'imagen'])
        ->whereIn('tipo', ['usuario', 'empresa'])
        ->whereNumber('id')
        ->name('perfil.imagen');
});
