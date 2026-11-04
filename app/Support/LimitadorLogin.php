<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

/**
 * El contador de intentos fallidos de Fortify, con la llave hecha con un hash
 * del correo (auditoría QA, ERR-01). La llave original lleva el correo tal
 * cual y, con un correo muy largo, pasaba de los 255 caracteres de cache.key:
 * el login daba error 500 en vez de "El correo o la contraseña no son
 * correctos".
 */
class LimitadorLogin extends LoginRateLimiter
{
    protected function throttleKey(Request $request)
    {
        return 'login:'.sha1(Str::lower((string) $request->input(Fortify::username()))).'|'.$request->ip();
    }
}
