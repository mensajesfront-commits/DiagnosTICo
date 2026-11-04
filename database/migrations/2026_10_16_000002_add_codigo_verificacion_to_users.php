<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L2b · Código de 6 dígitos para verificar el correo al registrarse (en
 * lugar de un enlace). Se guarda cifrado, vence en 15 minutos y admite 5
 * intentos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('codigo_verificacion')->nullable()->after('email_verified_at');
            $table->timestamp('codigo_vence_en')->nullable()->after('codigo_verificacion');
            $table->unsignedSmallInteger('codigo_intentos')->default(0)->after('codigo_vence_en');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['codigo_verificacion', 'codigo_vence_en', 'codigo_intentos']);
        });
    }
};
