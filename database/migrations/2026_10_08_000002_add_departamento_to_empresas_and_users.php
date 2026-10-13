<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * País → departamento (estado, provincia o región) → ciudad, en la empresa
 * (L2, E11) y en el perfil de las cuentas (A6). Ver App\Support\Ubicaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('departamento')->nullable()->after('ciudad');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('departamento')->nullable()->after('ciudad');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', fn (Blueprint $table) => $table->dropColumn('departamento'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('departamento'));
    }
};
