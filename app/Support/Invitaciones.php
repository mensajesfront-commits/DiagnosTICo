<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\InvitacionCuenta;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;

/**
 * Envía el correo con el enlace para crear la contraseña (RN-006). Lo usan
 * Invitar usuario (A5) y Registrar empresa (A3.2): el Administrador nunca
 * escribe la contraseña de otra cuenta.
 */
class Invitaciones
{
    public static function enviar(User $usuario, string $rol, ?string $mensaje = null, ?string $invitadaPor = null): void
    {
        /** @var PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($usuario);

        $usuario->notify(new InvitacionCuenta($token, $rol, $mensaje, $invitadaPor));
        $usuario->forceFill(['invitacion_enviada_en' => now()])->save();
    }
}
