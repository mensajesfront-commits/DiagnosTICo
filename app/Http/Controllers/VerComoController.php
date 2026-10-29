<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\SesionVerComo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A5.4 · "Ver como" (HU-053, HU-054). Lo usa quien tiene
 * `usuarios.gestionar`, desde Usuarios (A5) o la ficha de la empresa (A3.1).
 *
 * No se usa sobre cuentas de Administrador ni sobre la propia (respuesta del
 * equipo, 6 de octubre; docs/17_SEGURIDAD.md). Cada uso queda en
 * `registros_ver_como` con su inicio y su fin (RN-026).
 */
class VerComoController extends Controller
{
    public function iniciar(Request $request, User $usuario): RedirectResponse
    {
        /** @var User $yo */
        $yo = $request->user();

        abort_if($usuario->hasRole('Administrador'), 403, '"Ver como" no se usa sobre cuentas de Administrador.');
        abort_if($usuario->id === $yo->id, 403);

        SesionVerComo::iniciar($request, $yo, $usuario, url()->previous(route('usuarios.index')));

        return to_route('dashboard');
    }

    /** "Salir de Ver como": vuelve a donde empezó (A5 o A3.1). */
    public function salir(Request $request): RedirectResponse
    {
        $volver = SesionVerComo::terminar($request);

        return redirect($volver ?? route('usuarios.index'));
    }
}
