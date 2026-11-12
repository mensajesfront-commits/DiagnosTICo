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
 * - Los scripts solo pueden venir del propio sitio (`script-src 'self'`):
 *   si algún día se colara un XSS, el navegador no ejecutaría scripts
 *   ajenos ni en línea. En producción los scripts los compila Vite y salen
 *   del mismo origen; la plantilla raíz no trae scripts en línea (Inertia
 *   usa el atributo `data-page`, no un `<script>`). En desarrollo esta parte
 *   no se aplica, porque Vite recarga en caliente con scripts en línea (#46).
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        // En desarrollo (Vite con recarga en caliente) solo se limita quién
        // puede enmarcar la página; fuera de desarrollo se restringe también
        // de dónde salen los scripts.
        $csp = ["frame-ancestors 'self'"];

        if (! app()->environment('local')) {
            array_unshift($csp, "script-src 'self'", "object-src 'none'", "base-uri 'self'");
        }

        $cabeceras = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => implode('; ', $csp),
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
