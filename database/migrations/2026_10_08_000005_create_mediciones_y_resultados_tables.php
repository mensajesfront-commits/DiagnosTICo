<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mediciones, respuestas, análisis de la IA, resultados y solicitudes de
 * nueva medición (MER en docs/12_BASE_DE_DATOS.md, T-045).
 *
 * Las respuestas y los análisis apuntan a la pregunta y a la categoría por su
 * identificador dentro del JSON de la versión (`pregunta_ref`,
 * `categoria_ref`), no por llave foránea: así siguen valiendo aunque el
 * borrador cambie (RN-010).
 *
 * [FUNCIONALIDAD POR DEFINIR] Al borrar para siempre una empresa (DEC-017)
 * se borran sus mediciones y resultados (cascade). Falta decidir si se
 * conservan anonimizados para las estadísticas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('version_diagnostico_id')->constrained('versiones_diagnostico')->restrictOnDelete();
            // Medición 1, 2, 3… de la empresa.
            $table->unsignedSmallInteger('numero');
            // no_iniciada · en_curso · enviada · terminada · vencida · cancelada (RN-015)
            $table->string('estado', 20)->default('no_iniciada')->index();
            $table->date('fecha_limite')->nullable();
            $table->text('mensaje')->nullable();
            $table->boolean('aviso_por_correo')->default(true);
            // null = el de la plantilla (A3.1e).
            $table->string('correo_asunto')->nullable();
            $table->text('correo_cuerpo')->nullable();
            $table->foreignId('reemplazada_por')->nullable()->constrained('mediciones')->nullOnDelete();
            $table->timestamp('cancelada_en')->nullable();
            $table->foreignId('asignada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enviada_en')->nullable();
            $table->timestamp('terminada_en')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'numero']);
        });

        Schema::create('respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicion_id')->constrained('mediciones')->cascadeOnDelete();
            $table->string('pregunta_ref');
            // Opción única o selección múltiple: ids de las opciones elegidas.
            $table->jsonb('opciones_elegidas')->nullable();
            // Abierta, máx. 1000 caracteres.
            $table->text('texto')->nullable();
            // Calculado o dado por la IA (RN-018).
            $table->unsignedTinyInteger('puntaje')->nullable();
            $table->text('observacion_ia')->nullable();
            $table->foreignId('respondida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['medicion_id', 'pregunta_ref']);
        });

        Schema::create('analisis_categoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicion_id')->constrained('mediciones')->cascadeOnDelete();
            $table->string('categoria_ref');
            // pendiente · terminado · fallido
            $table->string('estado', 20)->default('pendiente');
            // Máximo 3 (RN-021).
            $table->unsignedTinyInteger('intentos')->default(0);
            // Prompt exacto que se envió.
            $table->text('prompt')->nullable();
            $table->jsonb('respuesta_ia')->nullable();
            $table->timestamps();

            $table->unique(['medicion_id', 'categoria_ref']);
        });

        Schema::create('resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicion_id')->unique()->constrained('mediciones')->cascadeOnDelete();
            // Promedio ponderado (RN-018).
            $table->unsignedTinyInteger('puntaje_total');
            // RN-019: critico · mejorar · camino · sigue (App\Support\Niveles)
            $table->string('nivel', 20);
            // Frente a la medición anterior; null en la primera (RN-020).
            $table->smallInteger('variacion')->nullable();
            $table->jsonb('por_categoria');
            // PDF guardado (RN-024).
            $table->string('pdf_ruta')->nullable();
            $table->timestamp('publicado_en');
            $table->timestamps();
        });

        Schema::create('solicitudes_medicion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('medicion_vencida_id')->nullable()->constrained('mediciones')->nullOnDelete();
            // abierta · atendida (una abierta a la vez)
            $table->string('estado', 20)->default('abierta');
            $table->foreignId('solicitada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('atendida_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_medicion');
        Schema::dropIfExists('resultados');
        Schema::dropIfExists('analisis_categoria');
        Schema::dropIfExists('respuestas');
        Schema::dropIfExists('mediciones');
    }
};
