<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eliminar una cuenta la marca con `deleted_at`: sale de la vista y nadie
 * entra, pero se puede recuperar durante 90 días. Después la tarea diaria
 * `cuentas:purgar` la borra para siempre (DEC-017).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('empresas', fn (Blueprint $table) => $table->softDeletes());
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('empresas', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
