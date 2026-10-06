<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * RN-001: la contraseña lleva al menos una letra mayúscula.
 * (Password::mixedCase() pide también una minúscula, que RN-001 no exige).
 */
class TieneMayuscula implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/\p{Lu}/u', $value)) {
            $fail('La :attribute debe tener al menos una letra mayúscula.');
        }
    }
}
