<?php

namespace App\Rules;

use App\Models\Diagnostico;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Dos diagnósticos no pueden llamarse igual, en ningún sector ni estado
 * (también cuenta un archivado). Sin mirar mayúsculas ni espacios de más:
 * "Diagnóstico general" = " diagnóstico  GENERAL ".
 */
class NombreDiagnosticoUnico implements ValidationRule
{
    /** @param int|null $ignorar Al renombrar, el propio diagnóstico. */
    public function __construct(private readonly ?int $ignorar = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $existe = Diagnostico::query()
            ->whereRaw("lower(regexp_replace(trim(nombre), '\\s+', ' ', 'g')) = ?", [self::normalizar($value)])
            ->when($this->ignorar, fn ($q) => $q->whereKeyNot($this->ignorar))
            ->exists();

        if ($existe) {
            $fail('Ya existe un diagnóstico con ese nombre. Elige otro.');
        }
    }

    public static function normalizar(string $nombre): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($nombre)));
    }

    /** El nombre tal como se guarda: sin espacios de más. */
    public static function limpiar(string $nombre): string
    {
        return (string) preg_replace('/\s+/u', ' ', trim($nombre));
    }
}
