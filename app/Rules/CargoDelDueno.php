<?php

namespace App\Rules;

use App\Models\Empresa;
use App\Models\User;
use App\Support\DatosEmpresas;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * El cargo del dueño de la empresa (su cuenta principal, rol Empresa) es
 * único dentro de la empresa: ningún colaborador puede tenerlo, ni el dueño
 * puede tomar el de un colaborador. Se comparan sin mayúsculas, tildes ni
 * espacios de más ("Gerente" = " gerente ").
 *
 * - `$esPrincipal = false`: el cargo de un colaborador (E12, Mi perfil).
 * - `$esPrincipal = true`: el cargo del dueño (Mi perfil, A3.1d).
 */
class CargoDelDueno implements ValidationRule
{
    public function __construct(
        private readonly ?int $empresaId,
        private readonly bool $esPrincipal = false,
        private readonly ?int $ignorar = null,
    ) {}

    public static function normalizar(?string $cargo): string
    {
        return (string) Str::of((string) $cargo)->ascii()->lower()->squish();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cargo = self::normalizar(is_string($value) ? $value : null);
        $empresa = $this->empresaId ? Empresa::find($this->empresaId) : null;

        if ($cargo === '' || $empresa === null) {
            return;
        }

        if ($this->esPrincipal) {
            $repetido = $empresa->usuarios()
                ->role('Colaborador')
                ->when($this->ignorar, fn ($q) => $q->whereKeyNot($this->ignorar))
                ->get(['id', 'name', 'cargo'])
                ->first(fn (User $u) => self::normalizar($u->cargo) === $cargo);

            if ($repetido) {
                $fail("Ese cargo ya lo tiene {$repetido->name}. El cargo del dueño de la empresa no se puede repetir.");
            }

            return;
        }

        $dueno = DatosEmpresas::principal($empresa);

        if ($dueno && $dueno->id !== $this->ignorar && self::normalizar($dueno->cargo) === $cargo) {
            $fail("«{$dueno->cargo}» es el cargo del dueño de la empresa. Elige otro cargo.");
        }
    }
}
