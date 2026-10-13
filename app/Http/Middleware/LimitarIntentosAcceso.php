<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Límite de intentos en registrarse (L2), pedir el enlace de recuperación
 * (L3) y crear la contraseña nueva (L4): 5 por minuto desde la misma
 * dirección. El inicio de sesión ya tiene su propio límite (FortifyServiceProvider).
 */
class LimitarIntentosAcceso
{
    public const int INTENTOS = 5;

    /** Rutas de Fortify a las que se aplica, con el campo donde se muestra el aviso. */
    private const array RUTAS = [
        'register.store' => 'email',
        'password.email' => 'email',
        'password.update' => 'email',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $ruta = $request->route()?->getName();

        if (! $request->isMethod('post') || ! isset(self::RUTAS[$ruta])) {
            return $next($request);
        }

        $clave = "acceso:{$ruta}:".$request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::INTENTOS)) {
            $segundos = RateLimiter::availableIn($clave);

            return back()->withErrors([
                self::RUTAS[$ruta] => trans('auth.throttle', ['seconds' => $segundos, 'minutes' => ceil($segundos / 60)]),
            ])->setStatusCode(302);
        }

        RateLimiter::hit($clave, 60);

        return $next($request);
    }
}
