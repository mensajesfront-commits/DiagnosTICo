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

    Route::post('mi-perfil/foto', [PerfilController::class, 'foto'])->name('perfil.foto');

    Route::get('imagenes/{tipo}/{id}', [PerfilController::class, 'imagen'])
        ->whereIn('tipo', ['usuario', 'empresa'])
        ->whereNumber('id')
        ->name('perfil.imagen');
});
