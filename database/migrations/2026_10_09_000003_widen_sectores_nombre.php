<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El nombre del sector pasa de 40 a 60 caracteres, para que quepan los
 * nombres cortos de todas las divisiones CIIU (el más largo tiene 54).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sectores', function (Blueprint $table) {
            $table->string('nombre', 60)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sectores', function (Blueprint $table) {
            $table->string('nombre', 40)->change();
        });
    }
};
