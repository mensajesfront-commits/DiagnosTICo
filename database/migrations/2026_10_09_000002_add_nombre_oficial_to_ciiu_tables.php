<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El catálogo CIIU guarda dos nombres (DEC-018): `nombre`, la versión corta
 * que se muestra en el sistema, y `nombre_oficial`, el título del DANE.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['ciiu_divisiones', 'ciiu_clases'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('nombre_oficial')->nullable()->after('nombre');
            });
        }
    }

    public function down(): void
    {
        foreach (['ciiu_divisiones', 'ciiu_clases'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn('nombre_oficial');
            });
        }
    }
};
