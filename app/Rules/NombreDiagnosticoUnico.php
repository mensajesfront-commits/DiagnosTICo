<?php

namespace App\Rules;

use App\Models\Diagnostico;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Dentro de un mismo sector, dos diagnósticos no pueden llamarse igual, en
 * ningún estado (también cuenta un archivado). En sectores distintos sí se
 * puede. Sin mirar mayúsculas ni espacios de más:
 * "Diagnóstico general" = " diagnóstico  GENERAL ".
 */
class NombreDiagnosticoUnico implements ValidationRule
{
    /**
     * @param  mixed  $sectorId  El sector elegido en el formulario.
     * @param  int|null  $ignorar  Al renombrar, el propio diagnóstico.
     */
    public function __construct(private readonly mixed $sectorId, private readonly ?int $ignorar = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! is_numeric($this->sectorId)) {
            return;
        }

        $existe = Diagnostico::query()
            ->where('sector_id', (int) $this->sectorId)
            ->whereRaw("lower(regexp_replace(trim(nombre), '\\s+', ' ', 'g')) = ?", [self::normalizar($value)])
            ->when($this->ignorar, fn ($q) => $q->whereKeyNot($this->ignorar))
            ->exists();

        if ($existe) {
            $fail('Este sector ya tiene un diagnóstico con ese nombre. Elige otro.');
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
