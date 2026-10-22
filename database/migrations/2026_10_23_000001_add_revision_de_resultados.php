<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revisión antes de publicar (23 de octubre de 2026, compromiso de
 * supervisión humana del Anexo 4 de NEIVA FEST):
 *
 * - `ajustes`: opciones generales del sistema (clave → valor JSON). La
 *   primera es `revision_resultados`.
 * - `mediciones.revision_desde`: la IA terminó y el resultado espera que un
 *   administrador lo apruebe.
 * - `resultados.aprobado_por`: quién lo aprobó (null si se publicó solo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes', function (Blueprint $table) {
            $table->string('clave')->primary();
            $table->jsonb('valor');
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('mediciones', function (Blueprint $table) {
            $table->timestamp('revision_desde')->nullable()->after('enviada_en');
        });

        Schema::table('resultados', function (Blueprint $table) {
            $table->foreignId('aprobado_por')->nullable()->after('publicado_en')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resultados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aprobado_por');
        });

        Schema::table('mediciones', function (Blueprint $table) {
            $table->dropColumn('revision_desde');
        });

        Schema::dropIfExists('ajustes');
    }
};
