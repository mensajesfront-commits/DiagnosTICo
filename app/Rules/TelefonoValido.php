<?php

namespace App\Rules;

use App\Support\Telefonos;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Teléfono "+57 3001234567": un indicativo de la lista y exactamente los
 * dígitos de ese país (Colombia 10, Argentina 11…). Ver `Telefonos`.
 */
class TelefonoValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(\+\d{1,3}) (\d+)$/', $value, $partes)) {
            $fail('Elige el indicativo y escribe solo los números del :attribute.');

            return;
        }

        $pais = Telefonos::de($partes[1]);

        if ($pais === null) {
            $fail('Elige un indicativo de la lista.');

            return;
        }

        [$nombre, $digitos] = $pais;

        if (strlen($partes[2]) !== $digitos) {
            $fail("Un número de {$nombre} tiene {$digitos} dígitos sin el indicativo.");
        }
    }
}
