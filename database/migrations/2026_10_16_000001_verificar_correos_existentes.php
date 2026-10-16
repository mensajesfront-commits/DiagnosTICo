<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La verificación de correo al registrarse empieza aquí. Las cuentas que ya
 * tienen contraseña se crearon antes y se dan por verificadas, para que nadie
 * quede por fuera. Las invitaciones sin contraseña se verifican al crearla.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->whereNotNull('password')
            ->update(['email_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // No se puede saber cuáles se verificaron aquí.
    }
};
