<?php

namespace App\Http\Responses;

use App\Http\Controllers\VerificacionCorreoController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse;

/**
 * L2 · Después de "Crear cuenta" no se entra al sistema: la cuenta espera a
 * que la persona confirme su correo con el código (L2b). Fortify inicia la
 * sesión al registrar; aquí se cierra y se vuelve al registro, que abre la
 * ventana "Verifica tu correo".
 */
class RegistroResponse implements RegisterResponse
{
    /** @param  Request  $request */
    public function toResponse($request): RedirectResponse
    {
        $correo = (string) $request->user()?->email;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put(VerificacionCorreoController::LLAVE, $correo);

        return to_route('register');
    }
}
