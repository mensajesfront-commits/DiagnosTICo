<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - `administrador_principal`: la cuenta del Administrador que se creó al
 *   instalar. Ningún otro Administrador le quita el rol, la desactiva ni la
 *   elimina. Se marca la cuenta de Administrador más antigua.
 * - `telefono_pendiente`: el teléfono nuevo de Mi perfil espera el código
 *   que llega al correo, como el correo nuevo (`email_pendiente`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('administrador_principal')->default(false)->after('activo');
            $table->string('telefono_pendiente', 30)->nullable()->after('email_pendiente');
        });

        $rol = DB::table('roles')->where('name', 'Administrador')->value('id');

        if ($rol === null) {
            return;
        }

        $primero = DB::table('users')
            ->join('model_has_roles', fn ($j) => $j->on('model_has_roles.model_id', '=', 'users.id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->where('model_has_roles.role_id', $rol))
            ->whereNull('users.deleted_at')
            ->orderBy('users.id')
            ->value('users.id');

        if ($primero !== null) {
            DB::table('users')->where('id', $primero)->update(['administrador_principal' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['administrador_principal', 'telefono_pendiente']);
        });
    }
};
