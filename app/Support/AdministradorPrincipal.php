<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\InvitacionCuenta;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Throwable;

/**
 * La cuenta principal del Administrador (la de ADMIN_EMAIL). Al instalar se
 * crea sin contraseña y le llega la invitación para crearla (RN-006): nadie
 * más la conoce. Lo usan el seeder y `php artisan admin:invitar`.
 */
final class AdministradorPrincipal
{
    /** La cuenta principal o, si todavía no hay, la de ADMIN_EMAIL. */
    public static function cuenta(): ?User
    {
        return User::where('administrador_principal', true)->first()
            ?? User::where('email', (string) config('diagnostico.admin.email'))->first();
    }

    /**
     * Envía el correo para crear la contraseña, al momento (no en la cola).
     * Si el correo falla, devuelve el enlace para dárselo a la persona; si
     * salió bien, null.
     */
    public static function invitar(User $admin): ?string
    {
        /** @var PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($admin);

        try {
            $admin->notifyNow(new InvitacionCuenta($token, 'Administrador'));
            $admin->forceFill(['invitacion_enviada_en' => now()])->save();

            return null;
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar la invitación del Administrador principal', ['error' => $e->getMessage()]);

            return url(route('password.reset', ['token' => $token, 'email' => $admin->getEmailForPasswordReset()], false));
        }
    }
}
