<?php

namespace App\Support;

use App\Models\RegistroVerComo;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Estado de "Ver como" en la sesión del Administrador (RN-026):
 * quién mira, a qué cuenta, el registro abierto y a dónde volver.
 */
class SesionVerComo
{
    public const string LLAVE = 'ver_como';

    /** @return array{administrador_id: int, administrador: string, cuenta_id: int, registro_id: int, volver: string}|null */
    public static function datos(Request $request): ?array
    {
        if (! $request->hasSession()) {
            return null;
        }

        /** @var array{administrador_id: int, administrador: string, cuenta_id: int, registro_id: int, volver: string}|null $datos */
        $datos = $request->session()->get(self::LLAVE);

        return $datos;
    }

    public static function iniciar(Request $request, User $admin, User $cuenta, string $volver): void
    {
        self::terminar($request);

        $registro = RegistroVerComo::create(['administrador_id' => $admin->id, 'cuenta_id' => $cuenta->id, 'inicio' => now()]);

        $request->session()->put(self::LLAVE, [
            'administrador_id' => $admin->id,
            'administrador' => $admin->name,
            'cuenta_id' => $cuenta->id,
            'registro_id' => $registro->id,
            'volver' => $volver,
        ]);
    }

    /** Cierra el registro y quita la vista. Devuelve a dónde volver. */
    public static function terminar(Request $request): ?string
    {
        $datos = self::datos($request);

        if ($datos === null) {
            return null;
        }

        RegistroVerComo::whereKey($datos['registro_id'])->whereNull('fin')->update(['fin' => now()]);
        $request->session()->forget(self::LLAVE);

        return $datos['volver'];
    }

    /**
     * Para la franja de aviso (dato compartido `verComo`).
     *
     * @return array{cuenta: string, rol: string|null, empresa: string|null, administrador: string}|null
     */
    public static function compartido(Request $request): ?array
    {
        $datos = self::datos($request);
        $cuenta = $request->user();

        if ($datos === null || ! $cuenta instanceof User || $cuenta->id !== $datos['cuenta_id']) {
            return null;
        }

        return [
            'cuenta' => $cuenta->name,
            'rol' => $cuenta->getRoleNames()->first(),
            'empresa' => $cuenta->empresa?->nombre,
            'administrador' => $datos['administrador'],
        ];
    }
}
