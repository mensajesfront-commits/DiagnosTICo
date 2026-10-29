<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Support\HistorialContrasenas;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        // Un enlace anterior no sirve: al colaborador se la cambia su empresa (E12.3).
        if ($user->hasRole('Colaborador')) {
            throw ValidationException::withMessages(['email' => 'Tu contraseña la cambia la cuenta principal de tu empresa.']);
        }

        Validator::make($input, [
            'password' => $this->passwordRules($user->email, $user),
        ])->validate();

        // También completa una invitación (A5): la cuenta queda activa con su
        // primera contraseña.
        // El enlace llegó a su correo: eso también lo verifica.
        $user->forceFill([
            'password' => $input['password'],
            'contrasena_actualizada_en' => now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        HistorialContrasenas::registrar($user, $input['password']);
    }
}
