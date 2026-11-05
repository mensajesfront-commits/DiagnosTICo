<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rol Consultor (decisión del 3 de noviembre, docs/05 HU-085, HU-086 y RN-030).
 *
 * - `consultor_empresa`: qué empresas acompaña cada consultor (una o varias).
 *   La vincula el Administrador.
 * - El rol Consultor pasa a ser del sistema y activo, y solo mira: ver
 *   empresas (las vinculadas), resultados, PDF, su perfil y su contraseña.
 *   Pierde "Responder el diagnóstico".
 *
 * En una base nueva el rol lo crea RolesYPermisosSeeder; aquí solo se ajusta
 * si ya existe.
 */
return new class extends Migration
{
    private const array PERMISOS = [
        'empresas.ver',
        'resultados.ver',
        'resultados.pdf',
        'perfil.editar',
        'contrasena.cambiar',
    ];

    public function up(): void
    {
        Schema::create('consultor_empresa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'empresa_id']);
        });

        $rol = DB::table('roles')->where('name', 'Consultor')->where('guard_name', 'web')->first();

        if ($rol === null) {
            return;
        }

        DB::table('roles')->where('id', $rol->id)->update([
            'descripcion' => 'Mira los resultados de sus empresas vinculadas',
            'activo' => true,
            'del_sistema' => true,
        ]);

        $permisos = DB::table('permissions')->whereIn('name', self::PERMISOS)->where('guard_name', 'web')->pluck('id');

        DB::table('role_has_permissions')->where('role_id', $rol->id)->delete();
        DB::table('role_has_permissions')->insert(
            $permisos->map(fn ($id) => ['permission_id' => $id, 'role_id' => $rol->id])->all(),
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('consultor_empresa');
    }
};
