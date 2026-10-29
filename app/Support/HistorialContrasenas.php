<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Las últimas contraseñas de cada cuenta (22 de octubre de 2026), para que al
 * recuperarla, en Mi perfil o al editar un colaborador la nueva no sea
 * ninguna de las últimas 5, sin importar mayúsculas.
 *
 * Se guarda cada contraseña en minúsculas cifrada con bcrypt (tabla
 * `contrasenas_anteriores`), nunca el texto. Las cuentas creadas antes de
 * esta tabla solo tienen su contraseña actual, que revisa
 * DistintaDeLaAnterior.
 */
class HistorialContrasenas
{
    public const int CUANTAS = 5;

    /** Guarda la contraseña recién puesta y deja solo las últimas 5. */
    public static function registrar(User $usuario, string $contrasena): void
    {
        DB::table('contrasenas_anteriores')->insert([
            'user_id' => $usuario->id,
            'hash_minusculas' => Hash::make(self::minusculas($contrasena)),
            'created_at' => now(),
        ]);

        $conservar = DB::table('contrasenas_anteriores')->where('user_id', $usuario->id)
            ->orderByDesc('id')->limit(self::CUANTAS)->pluck('id');

        DB::table('contrasenas_anteriores')->where('user_id', $usuario->id)
            ->whereNotIn('id', $conservar)->delete();
    }

    /** La contraseña es una de las últimas 5 de la cuenta, sin importar mayúsculas. */
    public static function usada(User $usuario, string $contrasena): bool
    {
        $minusculas = self::minusculas($contrasena);

        return DB::table('contrasenas_anteriores')->where('user_id', $usuario->id)
            ->orderByDesc('id')->limit(self::CUANTAS)->pluck('hash_minusculas')
            ->contains(fn (string $hash) => Hash::check($minusculas, $hash));
    }

    private static function minusculas(string $contrasena): string
    {
        return Str::lower($contrasena);
    }
}
