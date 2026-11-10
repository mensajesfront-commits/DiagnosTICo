<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las mediciones vencidas que se cancelan salen en el historial (E8, A3.4;
 * pedido del equipo del 10 de noviembre de 2026): sirven de prueba de que la
 * empresa recibió el diagnóstico y no lo respondió a tiempo.
 *
 * - `estado_al_cancelar`: en qué estado estaba al cancelarla (vencida,
 *   no_iniciada, en_curso). Las canceladas antes de esta migración quedan en
 *   null y el historial lo deduce de la fecha límite.
 * - `cancelada_por`: quién la canceló o la reemplazó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            $table->string('estado_al_cancelar', 20)->nullable()->after('cancelada_en');
            $table->foreignId('cancelada_por')->nullable()->after('estado_al_cancelar')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelada_por');
            $table->dropColumn('estado_al_cancelar');
        });
    }
};
