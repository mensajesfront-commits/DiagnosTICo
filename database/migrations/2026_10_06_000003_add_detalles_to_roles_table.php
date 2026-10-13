<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de los roles que muestran A5.1 y A5.2: descripción, si está activo y
 * si es un rol del sistema (no se edita ni se elimina, RN-027).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('descripcion')->nullable()->after('name');
            // Un rol inactivo no se puede asignar a ninguna cuenta.
            $table->boolean('activo')->default(true)->after('descripcion');
            $table->boolean('del_sistema')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'activo', 'del_sistema']);
        });
    }
};
