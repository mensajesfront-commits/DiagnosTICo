<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L2 · Cuándo aceptó la cuenta los términos de uso y la política de
 * tratamiento de datos (constancia de la aceptación).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('terminos_aceptados_en')->nullable()->after('contrasena_actualizada_en');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('terminos_aceptados_en');
        });
    }
};
