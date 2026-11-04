<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Invitaciones;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * L4 · "Crea una contraseña nueva" recibe dos clases de enlace (ERR-09):
 *
 * - el de "¿Olvidaste tu contraseña?", que vence a los 10 minutos (broker
 *   `users`, el de Fortify por defecto);
 * - el de una invitación, que sirve hasta usarlo (broker `invitaciones`).
 *
 * Si el token es de una invitación vigente, Fortify valida y guarda con ese
 * broker. Si no, sigue con el de recuperación y su vencimiento corto.
 */
class ElegirEnlaceContrasena
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('password.reset', 'password.update')) {
            $token = $request->route('token') ?? $request->input('token');
            $correo = $request->input('email');

            if (is_string($token) && is_string($correo) && $token !== '') {
                $usuario = User::query()->where('email', $correo)->first();

                if ($usuario !== null && Invitaciones::broker()->tokenExists($usuario, $token)) {
                    config(['fortify.passwords' => Invitaciones::BROKER]);
                }
            }
        }

        return $next($request);
    }
}
