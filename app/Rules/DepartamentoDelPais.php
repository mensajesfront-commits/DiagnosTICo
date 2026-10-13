<?php

namespace App\Rules;

use App\Support\Ubicaciones;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * El departamento (estado, provincia o región) debe ser del país elegido.
 */
class DepartamentoDelPais implements ValidationRule
{
    public function __construct(private readonly ?string $pais) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! in_array($value, Ubicaciones::nombresDeDepartamentos((string) $this->pais), true)) {
            $fail('Elige un :attribute de la lista del país.');
        }
    }
}
