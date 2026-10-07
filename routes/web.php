<?php

use App\Http\Controllers\ColaboradoresController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UbicacionesController;
use App\Http\Controllers\UsuariosController;
use Illuminate\Support\Facades\Route;

// Al abrir el sistema se entra directo al inicio de sesión; con sesión
// iniciada, al inicio de la cuenta.
Route::get('/', fn () => auth()->check()
    ? to_route('dashboard')
    : to_route('login'))->name('home');

// País → departamento → ciudad (L2, A6, E11). Pública: el registro no tiene sesión.
Route::get('ubicaciones/{pais}', [UbicacionesController::class, 'show'])
    ->where('pais', '[A-Za-z]{2}')
    ->middleware('throttle:60,1')
    ->name('ubicaciones.pais');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // A5 · Usuarios y roles (docs/15_BACKEND.md). Ver: usuarios.ver;
    // cambiar: usuarios.gestionar (docs/17_SEGURIDAD.md).
    Route::middleware('permission:usuarios.ver')->group(function () {
        Route::get('usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
        Route::get('usuarios/roles', [RolesController::class, 'index'])->name('usuarios.roles');
    });

    Route::middleware('permission:usuarios.gestionar')->group(function () {
        Route::post('usuarios/invitar', [UsuariosController::class, 'invitar'])->name('usuarios.invitar');
        Route::post('usuarios/{usuario}/invitacion', [UsuariosController::class, 'reenviarInvitacion'])->name('usuarios.reenviar-invitacion');
        Route::post('usuarios/{usuario}/desactivar', [UsuariosController::class, 'desactivar'])->name('usuarios.desactivar');
        Route::post('usuarios/{usuario}/reactivar', [UsuariosController::class, 'reactivar'])->name('usuarios.reactivar');
        Route::delete('usuarios/{usuario}', [UsuariosController::class, 'eliminar'])->name('usuarios.eliminar');
        Route::put('usuarios/{usuario}/rol', [UsuariosController::class, 'cambiarRol'])->name('usuarios.cambiar-rol');
        Route::post('usuarios/{usuario}/ver-como', [UsuariosController::class, 'verComo'])->name('usuarios.ver-como');

        Route::post('roles', [RolesController::class, 'store'])->name('roles.store');
        Route::put('roles/{rol}', [RolesController::class, 'update'])->name('roles.update');
        Route::delete('roles/{rol}', [RolesController::class, 'destroy'])->name('roles.destroy');
        Route::post('roles/{rol}/asignar', [RolesController::class, 'asignar'])->name('roles.asignar');
    });

    // E12 · Colaboradores: solo la cuenta principal de la empresa (RN-025).
    // El controlador revisa además que tenga empresa y que el colaborador
    // sea de la misma.
    Route::middleware('role:Empresa')->group(function () {
        Route::get('colaboradores', [ColaboradoresController::class, 'index'])->name('colaboradores.index');
        Route::post('colaboradores', [ColaboradoresController::class, 'store'])->name('colaboradores.store');
        Route::put('colaboradores/{colaborador}/contrasena', [ColaboradoresController::class, 'contrasena'])->name('colaboradores.contrasena');
        Route::post('colaboradores/{colaborador}/desactivar', [ColaboradoresController::class, 'desactivar'])->name('colaboradores.desactivar');
        Route::post('colaboradores/{colaborador}/reactivar', [ColaboradoresController::class, 'reactivar'])->name('colaboradores.reactivar');
    });
});

require __DIR__.'/settings.php';

if (app()->environment(['local', 'testing'])) {
    require __DIR__.'/prueba-tecnica.php';
}
