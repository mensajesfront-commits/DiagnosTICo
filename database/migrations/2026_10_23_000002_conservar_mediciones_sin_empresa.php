<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Decisión de NuevasTIC (23 de octubre de 2026): al borrar para siempre una
 * empresa (DEC-017), sus mediciones terminadas se conservan sin nombre para
 * las estadísticas. La medición deja de apuntar a la empresa (`empresa_id`
 * queda en null) en lugar de borrarse con ella. Lo que identifica a la
 * empresa se limpia en `cuentas:purgar` (App\Support\Anonimizar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->unsignedBigInteger('empresa_id')->nullable()->change();
            $table->foreign('empresa_id')->references('id')->on('empresas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });

        // Las mediciones sin empresa no caben en la columna obligatoria.
        DB::table('mediciones')->whereNull('empresa_id')->delete();

        Schema::table('mediciones', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')->nullable(false)->change();
            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
        });
    }
};
