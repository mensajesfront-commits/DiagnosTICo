<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Empresas y su vínculo con las cuentas (MER en docs/12_BASE_DE_DATOS.md).
 * El usuario principal y los colaboradores apuntan a su empresa; el
 * Administrador no tiene empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('sector_id')->constrained('sectores')->restrictOnDelete();
            $table->string('ciudad');
            $table->string('pais');
            // Desactivarla desactiva a sus colaboradores (RN-025).
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresas')->restrictOnDelete();
            $table->string('cargo')->nullable()->after('email');
            $table->string('telefono')->nullable()->after('cargo');
            // Las cuentas se desactivan, no se borran (RN-004).
            $table->boolean('activo')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empresa_id');
            $table->dropColumn(['cargo', 'telefono', 'activo']);
        });

        Schema::dropIfExists('empresas');
    }
};
