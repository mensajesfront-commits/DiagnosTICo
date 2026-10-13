<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuándo se envió el último aviso por correo de la medición: al asignarla,
 * con "Recordatorio" o con "Reenviar aviso" (HU-008 CA-003). Lo muestra A1
 * ("Último aviso: hace 2 días") y la ficha de la empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            $table->timestamp('ultimo_aviso_en')->nullable()->after('correo_cuerpo');
        });
    }

    public function down(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            $table->dropColumn('ultimo_aviso_en');
        });
    }
};
