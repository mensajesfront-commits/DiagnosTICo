<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\InvitacionCuenta;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

/**
 * Envía el correo con el enlace para crear la contraseña (RN-006). Lo usan
 * Invitar usuario (A5) y Registrar empresa (A3.2): el Administrador nunca
 * escribe la contraseña de otra cuenta.
 *
 * El enlace de la invitación va en su propio broker (`invitaciones`): sirve
 * hasta usarlo. El de "¿Olvidaste tu contraseña?" vence a los 10 minutos
 * (ERR-09). Los dos llevan a la misma pantalla L4; ElegirEnlaceContrasena
 * decide con cuál se valida.
 */
class Invitaciones
{
    public const string BROKER = 'invitaciones';

    public static function broker(): PasswordBroker
    {
        /** @var PasswordBroker $broker */
        $broker = Password::broker(self::BROKER);

        return $broker;
    }

    /**
     * Borra los enlaces pendientes (invitación y recuperación) de estos correos.
     *
     * @param  array<mixed>|string  $correos
     */
    public static function borrarEnlaces(array|string $correos): void
    {
        $correos = array_map('strval', (array) $correos);

        DB::table('password_reset_tokens')->whereIn('email', $correos)->delete();
        DB::table('invitaciones_tokens')->whereIn('email', $correos)->delete();
    }

    public static function enviar(User $usuario, string $rol, ?string $mensaje = null, ?string $invitadaPor = null): void
    {
        $token = self::broker()->createToken($usuario);

        $usuario->notify(new InvitacionCuenta($token, $rol, $mensaje, $invitadaPor));
        $usuario->forceFill(['invitacion_enviada_en' => now()])->save();
    }
}
