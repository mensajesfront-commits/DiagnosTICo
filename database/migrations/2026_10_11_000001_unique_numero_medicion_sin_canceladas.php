<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El número de medición es único por empresa sin contar las canceladas: al
 * reemplazar la medición 4, la cancelada conserva el 4 y la nueva también es
 * la "Medición 4" (A3.1c). Índice único parcial de PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mediciones', fn ($table) => $table->dropUnique(['empresa_id', 'numero']));
        DB::statement("create unique index mediciones_empresa_numero_activas on mediciones (empresa_id, numero) where estado <> 'cancelada'");
    }

    public function down(): void
    {
        DB::statement('drop index if exists mediciones_empresa_numero_activas');
        Schema::table('mediciones', fn ($table) => $table->unique(['empresa_id', 'numero']));
    }
};
