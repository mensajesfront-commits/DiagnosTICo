<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo oficial CIIU Rev. 5 A.C. del DANE (DEC-018): divisiones (dos
 * dígitos) y clases (cuatro dígitos). Un sector puede asociarse a una
 * división; sus clases pasan a ser los subsectores del sector.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciiu_divisiones', function (Blueprint $table) {
            $table->char('codigo', 2)->primary();
            $table->string('nombre');
            // Letra de la sección (A–V) y su nombre.
            $table->char('seccion', 1);
            $table->string('seccion_nombre');
        });

        Schema::create('ciiu_clases', function (Blueprint $table) {
            $table->char('codigo', 4)->primary();
            $table->string('nombre');
            $table->char('division_codigo', 2);
            $table->foreign('division_codigo')->references('codigo')->on('ciiu_divisiones')->cascadeOnDelete();
        });

        Schema::table('sectores', function (Blueprint $table) {
            // División CIIU de la que salen sus subsectores; null si no tiene.
            $table->char('ciiu_division', 2)->nullable()->after('descripcion');
            $table->foreign('ciiu_division')->references('codigo')->on('ciiu_divisiones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sectores', function (Blueprint $table) {
            $table->dropForeign(['ciiu_division']);
            $table->dropColumn('ciiu_division');
        });

        Schema::dropIfExists('ciiu_clases');
        Schema::dropIfExists('ciiu_divisiones');
    }
};
