<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invitaciones de cuentas internas (A5, "Invitar usuario"): la cuenta se crea
 * sin contraseña y la persona la crea con el enlace del correo (RN-006).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->timestamp('invitacion_enviada_en')->nullable()->after('terminos_aceptados_en');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('invitacion_enviada_en');
            $table->string('password')->nullable(false)->change();
        });
    }
};
