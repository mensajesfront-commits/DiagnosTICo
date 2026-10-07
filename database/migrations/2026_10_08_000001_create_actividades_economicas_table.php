<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actividades económicas (CIIU Rev. 4 A.C. de la DIAN) agrupadas por sector,
 * y los datos nuevos de la empresa en el registro (L2, paso «Mi empresa»):
 * actividad económica y descripción corta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades_economicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectores')->cascadeOnDelete();
            // Código CIIU de 4 dígitos ("5611").
            $table->string('codigo', 4);
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['sector_id', 'codigo']);
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->foreignId('actividad_economica_id')->nullable()->after('sector_id')
                ->constrained('actividades_economicas')->nullOnDelete();
            // Máximo 300 caracteres (lo valida el registro).
            $table->string('descripcion', 300)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('actividad_economica_id');
            $table->dropColumn('descripcion');
        });

        Schema::dropIfExists('actividades_economicas');
    }
};
