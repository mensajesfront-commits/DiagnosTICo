<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * En una selección múltiple, la opción que excluye a las demás ("Ninguna",
 * "Nada"): si la empresa la marca, se desmarcan las otras.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opciones', function (Blueprint $table) {
            $table->boolean('exclusiva')->default(false)->after('ten_en_cuenta');
        });
    }

    public function down(): void
    {
        Schema::table('opciones', function (Blueprint $table) {
            $table->dropColumn('exclusiva');
        });
    }
};
