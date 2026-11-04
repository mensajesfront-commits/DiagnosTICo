<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La opción "Otro" que pide escribir cuál ("¿Cuál otro canal?", E2): si la
 * empresa la elige, escribe el detalle y la IA lo lee. Una por pregunta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opciones', function (Blueprint $table) {
            $table->boolean('con_texto')->default(false)->after('exclusiva');
        });
    }

    public function down(): void
    {
        Schema::table('opciones', function (Blueprint $table) {
            $table->dropColumn('con_texto');
        });
    }
};
