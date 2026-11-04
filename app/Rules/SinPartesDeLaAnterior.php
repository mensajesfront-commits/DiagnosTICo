<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * La contraseña nueva no puede repetir partes de la actual (pedido del
 * equipo, 22 de octubre de 2026): ningún trozo de 4 caracteres seguidos o más,
 * sin importar mayúsculas ni tildes. "Mesa-47-Sol" → "Mesa-99-Luna" no pasa
 * ("mesa").
 *
 * Solo se puede revisar donde la persona escribe la contraseña actual (Mi
 * perfil): el sistema guarda las contraseñas cifradas y no puede leer sus
 * partes. Al recuperarla (L4) o cuando la empresa cambia la de un
 * colaborador, solo se revisa que no sea idéntica (DistintaDeLaAnterior).
 *
 * El frontend revisa lo mismo mientras se escribe (`parteDeLaAnterior` en
 * resources/js/lib/contrasena.ts).
 */
class SinPartesDeLaAnterior implements ValidationRule
{
    public const int LARGO = 4;

    public function __construct(private readonly ?string $anterior) {}

    public static function repite(string $nueva, ?string $anterior): bool
    {
        $nueva = self::normalizar($nueva);
        $anterior = self::normalizar((string) $anterior);

        for ($i = 0; $i + self::LARGO <= mb_strlen($anterior); $i++) {
            if (str_contains($nueva, mb_substr($anterior, $i, self::LARGO))) {
                return true;
            }
        }

        return false;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && self::repite($value, $this->anterior)) {
            $fail('La contraseña nueva no puede repetir partes de la actual (4 caracteres seguidos o más).');
        }
    }

    private static function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii($texto));
    }
}
