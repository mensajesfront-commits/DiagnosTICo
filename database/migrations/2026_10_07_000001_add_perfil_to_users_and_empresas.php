<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de "Mi perfil" (A6 para el Administrador, E11 para la empresa).
 * Ver la revisión del MER en docs/12_BASE_DE_DATOS.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ciudad')->nullable()->after('telefono');
            $table->string('pais')->nullable()->after('ciudad');
            $table->string('zona_horaria')->default('America/Bogota')->after('pais');
            $table->string('idioma', 5)->default('es')->after('zona_horaria');
            // Avisos por correo elegidos en el perfil: {"clave": true|false}.
            $table->jsonb('avisos')->nullable()->after('idioma');
            $table->string('foto_ruta')->nullable()->after('avisos');
            $table->timestamp('ultimo_acceso_en')->nullable()->after('activo');
            $table->timestamp('contrasena_actualizada_en')->nullable()->after('ultimo_acceso_en');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->string('telefono')->nullable()->after('pais');
            $table->string('sitio_web')->nullable()->after('telefono');
            // Rango, como lo ofrece E11: "1 a 10", "11 a 50"...
            $table->string('numero_empleados')->nullable()->after('sitio_web');
            $table->string('logo_ruta')->nullable()->after('numero_empleados');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ciudad', 'pais', 'zona_horaria', 'idioma', 'avisos',
                'foto_ruta', 'ultimo_acceso_en', 'contrasena_actualizada_en',
            ]);
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'sitio_web', 'numero_empleados', 'logo_ruta']);
        });
    }
};
