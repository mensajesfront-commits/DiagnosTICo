<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de contraseñas (22 de octubre de 2026): la nueva no puede ser
 * ninguna de las últimas 5, sin importar mayúsculas. Solo guarda la
 * contraseña en minúsculas cifrada con bcrypt; nunca el texto
 * (App\Support\HistorialContrasenas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrasenas_anteriores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('hash_minusculas');
            $table->timestamp('created_at')->nullable();
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrasenas_anteriores');
    }
};
