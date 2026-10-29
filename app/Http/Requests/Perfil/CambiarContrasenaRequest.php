<?php

namespace App\Http\Requests\Perfil;

use App\Concerns\PasswordValidationRules;
use App\Rules\SinPartesDeLaAnterior;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CambiarContrasenaRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => $this->currentPasswordRules(),
            'password' => [
                ...$this->passwordRules($this->user()?->email, $this->user()),
                // Aquí sí se conoce la actual: no puede repetir ninguna parte.
                new SinPartesDeLaAnterior($this->string('current_password')->value()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'contraseña actual',
            'password' => 'contraseña nueva',
        ];
    }
}
