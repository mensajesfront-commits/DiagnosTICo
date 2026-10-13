<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si desactivan una cuenta (RN-004) o su empresa (RN-025) mientras tiene la
 * sesión abierta, en la siguiente petición se cierra la sesión.
 */
class CerrarSesionCuentaInactiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario instanceof User && ! $usuario->puedeEntrar()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // L1 lo muestra en el mismo modal que al intentar entrar.
            return redirect()->route('login')->withErrors(['cuenta_desactivada' => trans('auth.desactivada')]);
        }

        return $next($request);
    }
}
