<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * E12.1 · Las páginas de cada colaborador pasan del rol a la cuenta
 * (App\Support\PaginasColaborador). Los colaboradores que ya existen
 * conservan lo que tenían: responder, resultados y PDF. El rol Colaborador
 * se queda sin permisos, también sin "Editar su perfil" ni "Cambiar su
 * contraseña": eso lo hace la cuenta principal.
 */
return new class extends Migration
{
    private const array PAGINAS = ['diagnostico.responder', 'resultados.ver', 'resultados.pdf'];

    public function up(): void
    {
        $rol = DB::table('roles')->where('name', 'Colaborador')->value('id');

        // Instalación nueva: el seeder crea los roles después.
        if ($rol === null) {
            return;
        }

        $permisos = DB::table('permissions')->whereIn('name', self::PAGINAS)->pluck('id');
        $cuentas = DB::table('model_has_roles')
            ->where('role_id', $rol)
            ->where('model_type', 'App\\Models\\User')
            ->pluck('model_id');

        foreach ($cuentas as $cuenta) {
            foreach ($permisos as $permiso) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $permiso,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $cuenta,
                ]);
            }
        }

        DB::table('role_has_permissions')->where('role_id', $rol)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $rol = DB::table('roles')->where('name', 'Colaborador')->value('id');

        if ($rol === null) {
            return;
        }

        $permisos = DB::table('permissions')
            ->whereIn('name', [...self::PAGINAS, 'perfil.editar', 'contrasena.cambiar'])
            ->pluck('id');

        foreach ($permisos as $permiso) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permiso, 'role_id' => $rol]);
        }

        $cuentas = DB::table('model_has_roles')->where('role_id', $rol)->pluck('model_id');
        DB::table('model_has_permissions')
            ->where('model_type', 'App\\Models\\User')
            ->whereIn('model_id', $cuentas)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
