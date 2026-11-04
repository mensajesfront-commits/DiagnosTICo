<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Nombre único en una tabla sin mirar mayúsculas ni espacios de más
 * (auditoría QA, ERR-04): "Comidas" = " comidas ". Lo usan los sectores y el
 * catálogo de categorías, con la misma normalización que los diagnósticos
 * (NombreDiagnosticoUnico).
 */
class NombreUnico implements ValidationRule
{
    /**
     * @param  string  $tabla  "sectores" o "categorias".
     * @param  int|null  $ignorar  Al renombrar, la propia fila.
     */
    public function __construct(
        private readonly string $tabla,
        private readonly ?int $ignorar,
        private readonly string $mensaje,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $existe = DB::table($this->tabla)
            ->whereRaw("lower(regexp_replace(trim(nombre), '\\s+', ' ', 'g')) = ?", [NombreDiagnosticoUnico::normalizar($value)])
            ->when($this->ignorar, fn ($q) => $q->where('id', '<>', $this->ignorar))
            ->exists();

        if ($existe) {
            $fail($this->mensaje);
        }
    }
}
