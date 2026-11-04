<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * A5.5 · Cambiar o asignar el rol de una cuenta (Cambiar rol, Asignar a una
 * cuenta y Crear rol con cuentas).
 *
 * - La cuenta del Administrador principal no cambia de rol.
 * - Un colaborador no cambia de rol: solo lo desactivan o lo eliminan el
 *   Administrador o la cuenta principal de su empresa (RN-025).
 * - Una cuenta de empresa que pasa a un rol interno (Administrador o uno
 *   creado) deja la empresa: los roles internos no tienen empresa. Se
 *   borra su contraseña y le llega la invitación para crear una nueva, como
 *   a cualquier cuenta interna (RN-006). Si era la cuenta principal, la
 *   empresa queda desactivada: nadie más entra por ella.
 */
final class CambioDeRol
{
    public const string MOTIVO_EMPRESA = 'Su cuenta principal pasó al equipo de NuevasTIC.';

    /** @throws ValidationException */
    public static function permitido(User $cuenta, string $campo): void
    {
        if ($cuenta->administrador_principal) {
            throw ValidationException::withMessages([$campo => 'Es la cuenta principal del Administrador: nadie le puede quitar el rol.']);
        }

        if ($cuenta->hasRole('Colaborador')) {
            throw ValidationException::withMessages([$campo => 'Los colaboradores no cambian de rol: solo se desactivan o se eliminan.']);
        }
    }

    /**
     * Aplica el rol. Devuelve true si la cuenta salió de su empresa (recibe
     * la invitación para crear su contraseña).
     */
    public static function aplicar(User $cuenta, Role $rol, ?User $por = null): bool
    {
        $saleDeLaEmpresa = $cuenta->empresa_id !== null && ! in_array($rol->name, DatosUsuarios::ROLES_DE_EMPRESA, true);
        $empresa = $saleDeLaEmpresa && $cuenta->esPrincipal() ? $cuenta->empresa : null;

        DB::transaction(function () use ($cuenta, $rol, $saleDeLaEmpresa, $empresa): void {
            $cuenta->syncRoles([$rol]);
            // Las páginas que le dio su empresa como colaborador ya no aplican.
            $cuenta->syncPermissions([]);

            if (! $saleDeLaEmpresa) {
                return;
            }

            if ($empresa !== null && $empresa->activa) {
                $empresa->update(['activa' => false, 'desactivada_en' => now(), 'motivo_desactivacion' => self::MOTIVO_EMPRESA]);
                DB::table('sessions')->whereIn('user_id', $empresa->usuarios()->pluck('id'))->delete();
            }

            // Sus datos de acceso de empresa se borran: entra como cuenta interna.
            $cuenta->forceFill([
                'empresa_id' => null,
                'cargo' => null,
                'avisos' => null,
                'password' => null,
                'remember_token' => Str::random(60),
                'email_pendiente' => null,
                'telefono_pendiente' => null,
            ])->save();
            DB::table('sessions')->where('user_id', $cuenta->id)->delete();
        });

        if ($saleDeLaEmpresa) {
            Invitaciones::enviar($cuenta, $rol->name, null, $por?->name);
        }

        return $saleDeLaEmpresa;
    }
}
