<?php

use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\ColaboradoresController;
use App\Http\Controllers\DiagnosticosController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SectoresController;
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
    // T-048: /dashboard es la entrada después del login; cada cuenta va a su inicio.
    Route::get('dashboard', [InicioController::class, 'redirigir'])->name('dashboard');
    Route::get('inicio', [InicioController::class, 'administrador'])->name('inicio.administrador');
    Route::get('mi-inicio', [InicioController::class, 'empresa'])->name('inicio.empresa');

    // A2 · Diagnósticos, sectores y categorías (T-049, T-050). Ver:
    // diagnosticos.ver; cambiar: diagnosticos.editar (docs/17_SEGURIDAD.md).
    Route::middleware('permission:diagnosticos.ver')->group(function () {
        Route::get('diagnosticos', [DiagnosticosController::class, 'index'])->name('diagnosticos.index');
        Route::get('categorias', [CategoriasController::class, 'index'])->name('categorias.index');
    });

    Route::middleware('permission:diagnosticos.editar')->group(function () {
        Route::get('diagnosticos/crear', [DiagnosticosController::class, 'crear'])->name('diagnosticos.crear');
        Route::post('diagnosticos', [DiagnosticosController::class, 'guardar'])->name('diagnosticos.guardar');
        Route::post('diagnosticos/{diagnostico}/duplicar', [DiagnosticosController::class, 'duplicar'])->name('diagnosticos.duplicar');
        Route::post('diagnosticos/{diagnostico}/archivar', [DiagnosticosController::class, 'archivar'])->name('diagnosticos.archivar');
        Route::delete('diagnosticos/{diagnostico}/borrador', [DiagnosticosController::class, 'eliminarBorrador'])->name('diagnosticos.eliminar-borrador');
        Route::delete('diagnosticos/{diagnostico}', [DiagnosticosController::class, 'eliminar'])->name('diagnosticos.eliminar');

        Route::post('sectores', [SectoresController::class, 'store'])->name('sectores.store');
        Route::put('sectores/{sector}', [SectoresController::class, 'update'])->name('sectores.update');
        Route::post('sectores/{sector}/reasignar', [SectoresController::class, 'reasignar'])->name('sectores.reasignar');
        Route::post('sectores/{sector}/desactivar', [SectoresController::class, 'desactivar'])->name('sectores.desactivar');
        Route::post('sectores/{sector}/reactivar', [SectoresController::class, 'reactivar'])->name('sectores.reactivar');
        Route::delete('sectores/{sector}', [SectoresController::class, 'destroy'])->name('sectores.destroy');

        Route::post('categorias', [CategoriasController::class, 'store'])->name('categorias.store');
        Route::put('categorias/{categoria}', [CategoriasController::class, 'update'])->name('categorias.update');
        Route::post('categorias/{categoria}/archivar', [CategoriasController::class, 'archivar'])->name('categorias.archivar');
        Route::post('categorias/{categoria}/restaurar', [CategoriasController::class, 'restaurar'])->name('categorias.restaurar');
        Route::delete('categorias/{categoria}', [CategoriasController::class, 'destroy'])->name('categorias.destroy');
    });

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
        Route::post('usuarios/{usuario}/recuperar', [UsuariosController::class, 'recuperar'])->withTrashed()->name('usuarios.recuperar');
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
        Route::put('colaboradores/{colaborador}', [ColaboradoresController::class, 'actualizar'])->name('colaboradores.actualizar');
        Route::post('colaboradores/{colaborador}/desactivar', [ColaboradoresController::class, 'desactivar'])->name('colaboradores.desactivar');
        Route::post('colaboradores/{colaborador}/reactivar', [ColaboradoresController::class, 'reactivar'])->name('colaboradores.reactivar');
        Route::delete('colaboradores/{colaborador}', [ColaboradoresController::class, 'eliminar'])->name('colaboradores.eliminar');
    });
});

require __DIR__.'/settings.php';

if (app()->environment(['local', 'testing'])) {
    require __DIR__.'/prueba-tecnica.php';
}
