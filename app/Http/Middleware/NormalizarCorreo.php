<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Los correos no distinguen mayúsculas (auditoría QA, ERR-02): el campo
 * `email` llega en minúsculas y sin espacios a todas las validaciones. Así
 * "Ese correo ya es de otra cuenta" detecta SOFIA@ElTrigal.co cuando ya
 * existe sofia@eltrigal.co, en el registro, las invitaciones, los
 * colaboradores, las empresas y Mi perfil.
 */
class NormalizarCorreo
{
    public function handle(Request $request, Closure $next): Response
    {
        $correo = $request->input('email');

        if (is_string($correo)) {
            $request->merge(['email' => Str::lower(trim($correo))]);
        }

        return $next($request);
    }
}
