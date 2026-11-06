<?php

use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\CerrarSesionCuentaInactiva;
use App\Http\Middleware\ElegirEnlaceContrasena;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LimitarIntentosAcceso;
use App\Http\Middleware\NormalizarCorreo;
use App\Http\Middleware\VerComo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // En producción Apache (con el SSL) y Nginx van delante de PHP
        // (docker-compose.prod.yml). Se confía en el proxy inmediato para leer
        // X-Forwarded-Proto/For: así Laravel sabe que la petición llegó por
        // HTTPS (cookies seguras, HSTS, enlaces https) y ve la IP real para
        // el límite de intentos. Nginx solo escucha en 127.0.0.1.
        $middleware->trustProxies(at: '*');

        // ERR-06: cabeceras de seguridad en todas las respuestas.
        $middleware->append(CabecerasSeguridad::class);

        // ERR-02: el correo llega en minúsculas a todas las validaciones.
        $middleware->web(prepend: [
            NormalizarCorreo::class,
        ]);

        $middleware->web(append: [
            CerrarSesionCuentaInactiva::class,
            // ERR-09: L4 valida con el broker de invitación o el de recuperación.
            ElegirEnlaceContrasena::class,
            // Después de revisar la cuenta real y antes de compartir los datos.
            VerComo::class,
            LimitarIntentosAcceso::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Páginas de error con el diseño de Captter (errores/Error.vue) en vez
        // de las de Laravel. El 500 solo se cambia sin APP_DEBUG, para no
        // esconder el detalle mientras se programa. El 419 (la página expiró)
        // no es una pantalla: vuelve atrás con un aviso para intentar de nuevo.
        $exceptions->respond(function (Response $respuesta, Throwable $e, Request $request) {
            $codigo = $respuesta->getStatusCode();

            if ($request->expectsJson()) {
                return $respuesta;
            }

            if ($codigo === 419) {
                Inertia::flash('toast', ['type' => 'error', 'message' => 'La página estuvo abierta mucho tiempo. Inténtalo de nuevo.']);

                return back();
            }

            $propios = config('app.debug') ? [403, 404, 503] : [403, 404, 500, 503];

            if (! in_array($codigo, $propios, true)) {
                return $respuesta;
            }

            return Inertia::render('errores/Error', ['codigo' => $codigo])->toResponse($request)->setStatusCode($codigo);
        });
    })->create();
