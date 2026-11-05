<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RN-006 (cambio del 4 de noviembre, ERR-09): el enlace de "¿Olvidaste tu
 * contraseña?" vence a los 10 minutos; el de la invitación (crear la primera
 * contraseña) sigue sirviendo hasta usarlo. Cada uno va en su tabla para que
 * el vencimiento corto no alcance a las invitaciones.
 *
 * Las invitaciones que ya estaban pendientes (cuentas sin contraseña) pasan
 * a la tabla nueva, para no dejarlas vencidas al migrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitaciones_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        $pendientes = DB::table('users')->whereNull('password')->pluck('email');

        DB::table('password_reset_tokens')->whereIn('email', $pendientes)->orderBy('email')->get()
            ->each(fn (object $fila) => DB::table('invitaciones_tokens')->insert((array) $fila));

        DB::table('password_reset_tokens')->whereIn('email', $pendientes)->delete();
    }

    public function down(): void
    {
        DB::table('invitaciones_tokens')->orderBy('email')->get()
            ->each(fn (object $fila) => DB::table('password_reset_tokens')->upsert((array) $fila, ['email']));

        Schema::dropIfExists('invitaciones_tokens');
    }
};
