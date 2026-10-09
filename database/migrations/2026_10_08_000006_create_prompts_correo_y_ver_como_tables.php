<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuración de la IA por capas (RN-022), plantilla del correo de aviso
 * (A3.1e), registro de "Ver como" (RN-026) y los datos de la empresa que
 * faltaban (registrada por el Administrador, desactivación). T-045.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            // analizar_categoria (la única etapa por ahora).
            $table->string('etapa', 40)->default('analizar_categoria');
            // general · sector · empresa (RN-022)
            $table->string('alcance', 20);
            $table->foreignId('sector_id')->nullable()->constrained('sectores')->cascadeOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            // usar · agregar · reemplazar (A4.3, A4.4)
            $table->string('modo_contexto', 20)->default('usar');
            $table->string('modo_tarea', 20)->default('usar');
            $table->string('modo_detalles', 20)->default('usar');
            $table->string('modo_ejemplos', 20)->default('usar');
            $table->text('contexto')->nullable();
            $table->text('tarea')->nullable();
            $table->text('detalles')->nullable();
            $table->text('ejemplos')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['etapa', 'alcance', 'sector_id', 'empresa_id']);
        });

        Schema::create('plantillas_correo', function (Blueprint $table) {
            $table->id();
            // aviso_medicion
            $table->string('clave', 40)->unique();
            $table->string('asunto');
            // Con variables: {nombre_usuario}, {empresa}…
            $table->text('cuerpo');
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('registros_ver_como', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrador_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cuenta_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('inicio');
            $table->timestamp('fin')->nullable();
        });

        Schema::table('empresas', function (Blueprint $table) {
            // null si se registró sola (L2); el Administrador si la registró (A3.2).
            $table->foreignId('registrada_por')->nullable()->after('logo_ruta')->constrained('users')->nullOnDelete();
            $table->timestamp('desactivada_en')->nullable()->after('activa');
            $table->string('motivo_desactivacion')->nullable()->after('desactivada_en');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registrada_por');
            $table->dropColumn(['desactivada_en', 'motivo_desactivacion']);
        });

        Schema::dropIfExists('registros_ver_como');
        Schema::dropIfExists('plantillas_correo');
        Schema::dropIfExists('prompts');
    }
};
