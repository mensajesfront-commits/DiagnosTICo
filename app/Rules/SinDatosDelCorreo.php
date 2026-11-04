<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * La contraseña no puede llevar partes del correo (pedido del equipo, 17 de
 * octubre de 2026). De "juan.perez1102@empresa.co" salen "juanperez1102",
 * "juan", "perez", "1102" y "empresa": ninguna puede aparecer en la
 * contraseña, sin importar mayúsculas ni tildes. Las partes de menos de 3
 * caracteres no cuentan ("04", "co").
 *
 * El frontend revisa lo mismo mientras se escribe (`partesDelCorreo` en
 * resources/js/lib/contrasena.ts).
 */
class SinDatosDelCorreo implements ValidationRule
{
    public const int MINIMO = 3;

    public function __construct(private readonly ?string $correo) {}

    /**
     * @return list<string>
     */
    public static function partes(?string $correo): array
    {
        $correo = self::normalizar((string) $correo);

        if (! str_contains($correo, '@')) {
            return [];
        }

        [$usuario, $dominio] = explode('@', $correo, 2);
        $partes = [preg_replace('/[^a-z0-9]/', '', $usuario)];

        foreach (preg_split('/[^a-z0-9]+/', $usuario) ?: [] as $trozo) {
            $partes[] = $trozo;
            // "maria20lopez04" → "maria", "20", "lopez", "04".
            preg_match_all('/[a-z]+|[0-9]+/', $trozo, $grupos);
            array_push($partes, ...$grupos[0]);
        }

        // El nombre del dominio, sin la extensión ("gmail", "empresa").
        $partes[] = explode('.', $dominio)[0];

        return array_values(array_unique(array_filter(
            $partes,
            fn (?string $p) => $p !== null && mb_strlen($p) >= self::MINIMO,
        )));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $contrasena = self::normalizar($value);

        foreach (self::partes($this->correo) as $parte) {
            if (str_contains($contrasena, $parte)) {
                $fail("La contraseña no puede tener partes de tu correo («{$parte}»).");

                return;
            }
        }
    }

    private static function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii(trim($texto)));
    }
}
