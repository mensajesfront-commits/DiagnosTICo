<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Captcha del registro (L2) con Cloudflare Turnstile: gratis, sin imágenes
 * que resolver la mayoría de las veces. El navegador muestra el widget con
 * la llave del sitio y el servidor confirma el token con la llave secreta.
 *
 * Sin llaves en el .env, en local y en pruebas se usan las llaves de prueba
 * públicas de Cloudflare, que siempre aprueban. En producción hay que poner
 * las reales (TURNSTILE_SITE_KEY y TURNSTILE_SECRET_KEY).
 */
class Captcha
{
    /** Llaves de prueba de Cloudflare: el widget siempre aprueba. */
    private const string SITIO_PRUEBA = '1x00000000000000000000AA';

    private const string SECRETA_PRUEBA = '1x0000000000000000000000000000000AA';

    public static function activo(): bool
    {
        return (bool) config('services.turnstile.activo');
    }

    /** Llave pública para el widget, o null si el captcha está apagado. */
    public static function llaveSitio(): ?string
    {
        if (! self::activo()) {
            return null;
        }

        $llave = (string) config('services.turnstile.site_key');

        return $llave !== '' ? $llave : (app()->environment(['local', 'testing']) ? self::SITIO_PRUEBA : null);
    }

    /** ¿Cloudflare confirma el token que mandó el navegador? */
    public static function valido(?string $token, ?string $ip = null): bool
    {
        if (! self::activo()) {
            return true;
        }

        if ($token === null || $token === '') {
            return false;
        }

        $secreta = (string) config('services.turnstile.secret_key');

        if ($secreta === '') {
            if (! app()->environment(['local', 'testing'])) {
                return false;
            }
            $secreta = self::SECRETA_PRUEBA;
        }

        try {
            $respuesta = Http::asForm()->timeout(10)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secreta,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) $respuesta->json('success', false);
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
