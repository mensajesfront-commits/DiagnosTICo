<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en todas las respuestas (auditoría QA, ERR-06):
 *
 * - Nadie puede mostrar Captter dentro de un iframe de otro sitio
 *   (clickjacking): X-Frame-Options y CSP frame-ancestors.
 * - El navegador no adivina tipos de archivo (nosniff).
 * - Al seguir un enlace externo solo se manda el dominio, no la ruta.
 * - Sin cámara, micrófono ni ubicación: la app no los usa.
 * - Con HTTPS, HSTS para que el navegador no vuelva a pedir por HTTP.
 *
 * No se agrega una CSP completa de scripts: Vite en desarrollo y el
 * `data-page` de Inertia la complican; queda para el despliegue (#46).
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $cabeceras = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];

        if ($request->isSecure()) {
            $cabeceras['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($cabeceras as $nombre => $valor) {
            if (! $respuesta->headers->has($nombre)) {
                $respuesta->headers->set($nombre, $valor);
            }
        }

        return $respuesta;
    }
}
