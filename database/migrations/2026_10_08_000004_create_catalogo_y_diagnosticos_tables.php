<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de categorías y diagnósticos (MER en docs/12_BASE_DE_DATOS.md,
 * T-045): categorías, diagnósticos, sus categorías con importancia,
 * preguntas, opciones y las versiones publicadas (copias congeladas en JSONB,
 * RN-010).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            // Máximo 40 caracteres y sin repetir (RN-009).
            $table->string('nombre', 40)->unique();
            $table->string('descripcion')->nullable();
            // Archivada: no se ofrece en diagnósticos nuevos; las versiones
            // publicadas no cambian (RN-009).
            $table->timestamp('archivado_en')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectores')->restrictOnDelete();
            $table->string('nombre', 60);
            $table->text('descripcion')->nullable();
            // borrador: nunca publicado · publicado: tiene versiones · archivado.
            $table->string('estado', 20)->default('borrador')->index();
            // Número del borrador en curso (v1, v2…); null si no hay cambios
            // pendientes sobre la última versión publicada.
            $table->unsignedSmallInteger('version_borrador')->nullable()->default(1);
            $table->timestamp('archivado_en')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('diagnostico_categoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostico_id')->constrained('diagnosticos')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            // Porcentaje del puntaje total; las del diagnóstico suman 100 (RN-011).
            $table->decimal('importancia', 5, 2)->default(0);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['diagnostico_id', 'categoria_id']);
        });

        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostico_categoria_id')->constrained('diagnostico_categoria')->cascadeOnDelete();
            $table->text('texto');
            // abierta · opcion_unica · seleccion_multiple
            $table->string('tipo', 20);
            // Explica cómo responder; obligatoria (RN-013).
            $table->text('indicacion');
            // Solo en abiertas: cómo la califica la IA.
            $table->text('criterio_ia')->nullable();
            $table->boolean('obligatoria')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained('preguntas')->cascadeOnDelete();
            $table->string('texto');
            // 0 a 100.
            $table->unsignedTinyInteger('puntaje');
            // Nota para la IA que la empresa no ve (máx. 200).
            $table->string('ten_en_cuenta', 200)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('versiones_diagnostico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostico_id')->constrained('diagnosticos')->restrictOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->text('nota_cambios')->nullable();
            // Copia congelada: categorías, importancia, preguntas y opciones (RN-010).
            $table->jsonb('contenido');
            $table->foreignId('publicada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('publicada_en');
            $table->timestamps();

            $table->unique(['diagnostico_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versiones_diagnostico');
        Schema::dropIfExists('opciones');
        Schema::dropIfExists('preguntas');
        Schema::dropIfExists('diagnostico_categoria');
        Schema::dropIfExists('diagnosticos');
        Schema::dropIfExists('categorias');
    }
};
