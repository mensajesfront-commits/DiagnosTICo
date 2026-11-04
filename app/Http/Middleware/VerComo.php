<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SesionVerComo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * A5.4 · "Ver como" (HU-053, HU-054, RN-026).
 *
 * La sesión sigue siendo del Administrador: en cada petición, si "Ver como"
 * está activo, la cuenta vista pasa a ser el usuario de la petición (menú,
 * permisos y datos de su rol). Es solo de lectura: cualquier petición que no
 * sea GET o HEAD se rechaza, salvo salir de la vista o cerrar sesión.
 *
 * Si el Administrador pierde `usuarios.gestionar`, la vista termina sola.
 */
class VerComo
{
    public function handle(Request $request, Closure $next): Response
    {
        $datos = SesionVerComo::datos($request);

        if ($datos === null) {
            return $next($request);
        }

        // Si el guard ya trae la cuenta vista (pruebas, servidores que
        // reutilizan la aplicación), el Administrador es el de la sesión.
        $actual = $request->user();
        $admin = $actual instanceof User && $actual->id === $datos['cuenta_id']
            ? User::find($datos['administrador_id'])
            : $actual;
        $cuenta = User::find($datos['cuenta_id']);

        $valida = $admin instanceof User
            && $admin->id === $datos['administrador_id']
            && $admin->can('usuarios.gestionar')
            && $cuenta !== null
            && ! $cuenta->hasRole('Administrador');

        if ($admin instanceof User) {
            Auth::guard('web')->setUser($admin);
            $request->setUserResolver(fn () => $admin);
        }

        if (! $valida || $request->routeIs('logout')) {
            SesionVerComo::terminar($request);

            return $next($request);
        }

        if ($request->routeIs('ver-como.salir')) {
            return $next($request);
        }

        Auth::guard('web')->setUser($cuenta);
        $request->setUserResolver(fn () => $cuenta);

        if (! $request->isMethodSafe()) {
            $mensaje = 'Estás en "Ver como": es solo de lectura y no se guardó nada.';

            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['message' => $mensaje], 403);
            }

            Inertia::flash('toast', ['type' => 'warning', 'message' => $mensaje]);

            return back();
        }

        return $next($request);
    }
}
