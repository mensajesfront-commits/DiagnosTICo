<?php

namespace App\Concerns;

use App\Models\User;
use App\Rules\DistintaDeLaAnterior;
use App\Rules\SinDatosDelCorreo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Reglas de la contraseña: RN-001, sin partes del correo y, si la cuenta
     * ya tiene una, distinta de la anterior.
     *
     * @return array<int, Password|ValidationRule|array<mixed>|string>
     */
    protected function passwordRules(?string $correo = null, ?User $usuario = null): array
    {
        return [
            'required',
            'string',
            Password::default(),
            'confirmed',
            new SinDatosDelCorreo($correo),
            new DistintaDeLaAnterior($usuario),
        ];
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return array<int, Password|ValidationRule|array<mixed>|string>
     */
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'current_password'];
    }
}
