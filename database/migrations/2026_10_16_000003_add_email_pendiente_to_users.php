<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mi perfil (A6, E11): el correo nuevo espera aquí hasta que se confirma
 * con el código que le llega (DEC-020). Mientras tanto se entra con el
 * correo anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_pendiente')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_pendiente');
        });
    }
};
