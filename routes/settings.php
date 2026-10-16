<?php

use App\Http\Controllers\PerfilController;
use Illuminate\Support\Facades\Route;

/*
 * Mi perfil (A6 y E11). Los nombres de ruta son los del kit para no romper
 * los enlaces que ya los usan (menú lateral).
 */
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/mi-perfil');
    Route::redirect('settings/profile', '/mi-perfil');

    Route::get('mi-perfil', [PerfilController::class, 'edit'])->name('profile.edit');
    Route::patch('mi-perfil', [PerfilController::class, 'update'])->name('profile.update');

    Route::put('mi-perfil/contrasena', [PerfilController::class, 'contrasena'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    // "¿Olvidaste tu contraseña?": código al correo de la cuenta.
    Route::post('mi-perfil/contrasena/codigo', [PerfilController::class, 'codigoContrasena'])
        ->middleware('throttle:3,1')
        ->name('perfil.contrasena.codigo');
    Route::put('mi-perfil/contrasena/con-codigo', [PerfilController::class, 'contrasenaConCodigo'])
        ->middleware('throttle:10,1')
        ->name('perfil.contrasena.con-codigo');

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

    Route::get('imagenes/{tipo}/{id}', [PerfilController::class, 'imagen'])
        ->whereIn('tipo', ['usuario', 'empresa'])
        ->whereNumber('id')
        ->name('perfil.imagen');
});
