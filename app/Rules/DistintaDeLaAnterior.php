<?php

namespace App\Rules;

use App\Models\User;
use App\Support\HistorialContrasenas;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

/**
 * La contraseña nueva no puede ser igual a la que la cuenta tiene ahora
 * (pedido del equipo, 17 de octubre de 2026) ni a ninguna de sus últimas 5,
 * sin importar mayúsculas (HistorialContrasenas, 22 de octubre). Una cuenta
 * invitada que aún no tiene contraseña pasa sin revisar.
 */
class DistintaDeLaAnterior implements ValidationRule
{
    public function __construct(private readonly ?User $usuario) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $actual = $this->usuario?->getAuthPassword();

        if (! is_string($value) || $this->usuario === null) {
            return;
        }

        if (is_string($actual) && $actual !== '' && Hash::check($value, $actual)) {
            $fail('La contraseña nueva debe ser distinta de la anterior.');

            return;
        }

        if (HistorialContrasenas::usada($this->usuario, $value)) {
            $fail('La contraseña nueva no puede ser ninguna de tus últimas '.HistorialContrasenas::CUANTAS.', aunque cambies mayúsculas y minúsculas.');
        }
    }
}
