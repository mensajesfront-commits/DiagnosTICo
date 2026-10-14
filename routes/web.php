<?php

use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\ColaboradoresController;
use App\Http\Controllers\ConfiguracionIaController;
use App\Http\Controllers\DiagnosticosController;
use App\Http\Controllers\EditorDiagnosticoController;
use App\Http\Controllers\EmpresasController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MedicionesController;
use App\Http\Controllers\ResponderController;
use App\Http\Controllers\ResultadoEjemploController;
use App\Http\Controllers\ResultadosController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SectoresController;
use App\Http\Controllers\UbicacionesController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\VerComoController;
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
    // A5.4 · Salir de "Ver como" (el middleware VerComo la deja pasar).
    Route::post('ver-como/salir', [VerComoController::class, 'salir'])->name('ver-como.salir');

    // T-048: /dashboard es la entrada después del login; cada cuenta va a su inicio.
    Route::get('dashboard', [InicioController::class, 'redirigir'])->name('dashboard');
    Route::get('inicio', [InicioController::class, 'administrador'])->name('inicio.administrador');
    Route::get('mi-inicio', [InicioController::class, 'empresa'])->name('inicio.empresa');

    // A2 · Diagnósticos, sectores y categorías (T-049, T-050). Ver:
    // diagnosticos.ver; cambiar: diagnosticos.editar (docs/17_SEGURIDAD.md).
    Route::middleware('permission:diagnosticos.ver')->group(function () {
        Route::get('diagnosticos', [DiagnosticosController::class, 'index'])->name('diagnosticos.index');
        Route::get('categorias', [CategoriasController::class, 'index'])->name('categorias.index');
        // A2.4 · vista previa del borrador o de una versión (?version=).
        Route::get('diagnosticos/{diagnostico}/vista-previa', [EditorDiagnosticoController::class, 'vistaPrevia'])->name('diagnosticos.vista-previa');
        // A2.6 · Resultado de ejemplo (HU-030) y su PDF.
        Route::get('diagnosticos/{diagnostico}/resultado-ejemplo', [ResultadoEjemploController::class, 'ver'])->name('diagnosticos.resultado-ejemplo');
        Route::get('diagnosticos/{diagnostico}/resultado-ejemplo/pdf', [ResultadoEjemploController::class, 'pdf'])
            ->middleware('throttle:20,1')->name('diagnosticos.resultado-ejemplo.pdf');
    });

    Route::middleware('permission:diagnosticos.editar')->group(function () {
        Route::get('diagnosticos/crear', [DiagnosticosController::class, 'crear'])->name('diagnosticos.crear');
        Route::post('diagnosticos', [DiagnosticosController::class, 'guardar'])->name('diagnosticos.guardar');
        Route::post('diagnosticos/{diagnostico}/duplicar', [DiagnosticosController::class, 'duplicar'])->name('diagnosticos.duplicar');
        Route::post('diagnosticos/{diagnostico}/archivar', [DiagnosticosController::class, 'archivar'])->name('diagnosticos.archivar');
        Route::delete('diagnosticos', [DiagnosticosController::class, 'eliminarVarios'])->name('diagnosticos.eliminar-varios');
        Route::delete('diagnosticos/{diagnostico}/borrador', [DiagnosticosController::class, 'eliminarBorrador'])->name('diagnosticos.eliminar-borrador');
        Route::delete('diagnosticos/{diagnostico}', [DiagnosticosController::class, 'eliminar'])->name('diagnosticos.eliminar');

        // A2.1 · Editor (HU-023 a HU-029).
        Route::get('diagnosticos/{diagnostico}/editar', [EditorDiagnosticoController::class, 'editar'])->name('diagnosticos.editar');
        Route::post('diagnosticos/{diagnostico}/preguntas', [EditorDiagnosticoController::class, 'guardarPregunta'])->name('diagnosticos.preguntas.guardar');
        Route::put('diagnosticos/{diagnostico}/preguntas/{pregunta}', [EditorDiagnosticoController::class, 'actualizarPregunta'])->name('diagnosticos.preguntas.actualizar');
        Route::delete('diagnosticos/{diagnostico}/preguntas/{pregunta}', [EditorDiagnosticoController::class, 'eliminarPregunta'])->name('diagnosticos.preguntas.eliminar');
        Route::put('diagnosticos/{diagnostico}/categorias/{parte}/orden', [EditorDiagnosticoController::class, 'ordenarPreguntas'])->name('diagnosticos.preguntas.ordenar');
        Route::post('diagnosticos/{diagnostico}/categorias', [EditorDiagnosticoController::class, 'agregarCategorias'])->name('diagnosticos.categorias.agregar');
        Route::get('diagnosticos/{diagnostico}/importancia', [EditorDiagnosticoController::class, 'importancia'])->name('diagnosticos.importancia');
        Route::put('diagnosticos/{diagnostico}/importancia', [EditorDiagnosticoController::class, 'guardarImportancia'])->name('diagnosticos.importancia.guardar');
        Route::post('diagnosticos/{diagnostico}/publicar', [EditorDiagnosticoController::class, 'publicar'])->name('diagnosticos.publicar');

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

    // A3 · Empresas y mediciones (docs/15_BACKEND.md). Ver: empresas.ver;
    // registrar, editar, desactivar: empresas.registrar; mediciones:
    // mediciones.asignar (docs/17_SEGURIDAD.md).
    Route::middleware('permission:empresas.registrar')->group(function () {
        Route::get('empresas/registrar', [EmpresasController::class, 'crear'])->name('empresas.crear');
        Route::post('empresas', [EmpresasController::class, 'guardar'])->name('empresas.guardar');
        Route::put('empresas/{empresa}', [EmpresasController::class, 'actualizar'])->name('empresas.actualizar');
        Route::post('empresas/{empresa}/desactivar', [EmpresasController::class, 'desactivar'])->name('empresas.desactivar');
        Route::post('empresas/{empresa}/reactivar', [EmpresasController::class, 'reactivar'])->name('empresas.reactivar');
    });

    Route::middleware('permission:empresas.ver')->group(function () {
        Route::get('empresas', [EmpresasController::class, 'index'])->name('empresas.index');
        Route::get('empresas/{empresa}', [EmpresasController::class, 'ver'])->whereNumber('empresa')->name('empresas.ver');
        Route::get('empresas/{empresa}/historial', [HistorialController::class, 'empresa'])->whereNumber('empresa')->name('empresas.historial');
    });

    Route::middleware('permission:mediciones.asignar')->group(function () {
        Route::get('mediciones/asignar', [MedicionesController::class, 'crear'])->name('mediciones.crear');
        Route::post('mediciones', [MedicionesController::class, 'guardar'])->name('mediciones.guardar');
        Route::post('mediciones/{medicion}/cancelar', [MedicionesController::class, 'cancelar'])->name('mediciones.cancelar');
        Route::put('mediciones/{medicion}/fecha', [MedicionesController::class, 'cambiarFecha'])->name('mediciones.fecha');
        // A3.1e · correo de aviso (HU-037) y Recordatorio / Reenviar aviso (HU-008).
        Route::get('mediciones/{medicion}/correo', [MedicionesController::class, 'correo'])->name('mediciones.correo');
        Route::post('mediciones/{medicion}/correo', [MedicionesController::class, 'enviarCorreo'])->name('mediciones.correo.enviar');
        Route::post('mediciones/correo/prueba', [MedicionesController::class, 'probarCorreo'])->middleware('throttle:10,1')->name('mediciones.correo.prueba');
    });

    // A4 · Configuración de la IA (HU-041 a HU-045, HU-080). Ver: ia.ver;
    // guardar, quitar ajustes y probar (gasta una llamada): ia.editar.
    Route::middleware('permission:ia.ver')->group(function () {
        Route::get('configuracion-ia', [ConfiguracionIaController::class, 'index'])->name('ia.index');
        Route::get('configuracion-ia/como-se-arma', [ConfiguracionIaController::class, 'comoSeArma'])->name('ia.como-se-arma');
        Route::post('configuracion-ia/vista-previa', [ConfiguracionIaController::class, 'vistaPrevia'])->middleware('throttle:60,1')->name('ia.vista-previa');
    });

    Route::middleware('permission:ia.editar')->group(function () {
        Route::put('configuracion-ia', [ConfiguracionIaController::class, 'guardar'])->name('ia.guardar');
        Route::delete('configuracion-ia/ajuste', [ConfiguracionIaController::class, 'quitar'])->name('ia.quitar');
        Route::post('configuracion-ia/probar', [ConfiguracionIaController::class, 'probar'])->middleware('throttle:10,1')->name('ia.probar');
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
        Route::post('usuarios/{usuario}/ver-como', [VerComoController::class, 'iniciar'])->name('usuarios.ver-como');

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

    // E2–E4 · Responder el diagnóstico (HU-060 a HU-063) y pedir una
    // medición nueva si venció (HU-082). Solo la empresa dueña (el
    // controlador lo revisa; de otra empresa, 404).
    Route::middleware('permission:diagnostico.responder')->group(function () {
        Route::get('mi-diagnostico/{medicion}', [ResponderController::class, 'responder'])->name('responder.mostrar');
        Route::put('mi-diagnostico/{medicion}/respuestas', [ResponderController::class, 'guardar'])->middleware('throttle:120,1')->name('responder.guardar');
        Route::get('mi-diagnostico/{medicion}/revisar', [ResponderController::class, 'revisar'])->name('responder.revisar');
        Route::post('mi-diagnostico/{medicion}/enviar', [ResponderController::class, 'enviar'])->name('responder.enviar');
        Route::get('mi-diagnostico/{medicion}/enviado', [ResponderController::class, 'enviado'])->name('responder.enviado');
        Route::post('mi-diagnostico/{medicion}/solicitud', [ResponderController::class, 'solicitar'])->middleware('throttle:5,1')->name('responder.solicitar');
        Route::post('mi-diagnostico/{medicion}/reintentar', [ResponderController::class, 'reintentar'])->middleware('throttle:5,1')->name('responder.reintentar');
    });

    // E6 / A3.3 · Resultado, E9 / A3.5 · Mis respuestas y E10 · PDF (HU-064
    // a HU-066). La empresa solo ve la suya (el controlador lo revisa).
    Route::middleware('permission:resultados.ver')->group(function () {
        Route::get('resultados/{medicion}', [ResultadosController::class, 'ver'])->name('resultados.ver');
        Route::get('resultados/{medicion}/respuestas', [ResultadosController::class, 'respuestas'])->name('resultados.respuestas');
        // E8 · Mi historial: solo cuentas de una empresa (el controlador lo revisa).
        Route::get('historial', [HistorialController::class, 'mio'])->name('historial.mio');
    });
    Route::get('resultados/{medicion}/pdf', [ResultadosController::class, 'pdf'])
        ->middleware(['permission:resultados.pdf', 'throttle:20,1'])->name('resultados.pdf');
});

require __DIR__.'/settings.php';

if (app()->environment(['local', 'testing'])) {
    require __DIR__.'/prueba-tecnica.php';
}
