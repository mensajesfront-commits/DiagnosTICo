<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E10 · Dos PDF por resultado (decisión del 4 de noviembre): el informe
 * completo, para el Administrador y el Consultor (`pdf_ruta`), y el resumen
 * corto, que recibe la empresa (`pdf_resumen_ruta`). Los dos salen de lo que
 * ya guardó la IA; el resumen se genera al publicar o en la primera descarga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultados', function (Blueprint $table) {
            $table->string('pdf_resumen_ruta')->nullable()->after('pdf_ruta');
        });
    }

    public function down(): void
    {
        Schema::table('resultados', function (Blueprint $table) {
            $table->dropColumn('pdf_resumen_ruta');
        });
    }
};
