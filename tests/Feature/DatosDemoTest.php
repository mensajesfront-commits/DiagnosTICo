<?php

use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Resultado;
use App\Models\User;
use Database\Seeders\CategoriasSeeder;
use Database\Seeders\CiiuSeeder;
use Database\Seeders\DatosDemoSeeder;
use Database\Seeders\DiagnosticoEjemploSeeder;
use Database\Seeders\DiagnosticoProgramacionSeeder;
use Database\Seeders\RolesYPermisosSeeder;
use Database\Seeders\SectoresSeeder;

/*
 * DatosDemoSeeder: datos de demostración para ver las pantallas con volumen
 * (solo desarrollo local).
 */

beforeEach(function () {
    $this->seed([RolesYPermisosSeeder::class, SectoresSeeder::class, CategoriasSeeder::class, CiiuSeeder::class]);
    $this->seed([DiagnosticoEjemploSeeder::class, DiagnosticoProgramacionSeeder::class]);
});

it('crea 20 empresas con mediciones terminadas, una con 6 colaboradores y una consultora con 10', function () {
    $this->seed(DatosDemoSeeder::class);

    $empresas = Empresa::whereHas('usuarios', fn ($q) => $q->where('email', 'like', '%'.DatosDemoSeeder::DOMINIO))->get();

    expect($empresas)->toHaveCount(20)
        ->and(Resultado::count())->toBeGreaterThanOrEqual(20)
        ->and(User::role('Colaborador')->where('email', 'like', '%@la-espiga.'.DatosDemoSeeder::DOMINIO)->count())->toBe(6)
        ->and(User::where('email', 'valeria.rios@'.DatosDemoSeeder::DOMINIO)->firstOrFail()->empresasAsesoradas()->count())->toBe(10);

    // Cada empresa tiene al menos una medición terminada con su resultado.
    foreach ($empresas as $empresa) {
        expect(Medicion::where('empresa_id', $empresa->id)->where('estado', 'terminada')->has('resultado')->count())->toBeGreaterThan(0);
    }
});

it('se puede correr otra vez sin duplicar y limpiar() lo borra todo', function () {
    $this->seed(DatosDemoSeeder::class);
    $this->seed(DatosDemoSeeder::class);

    expect(User::where('email', 'like', '%'.DatosDemoSeeder::DOMINIO)->count())->toBe(27);

    DatosDemoSeeder::limpiar();

    expect(User::where('email', 'like', '%'.DatosDemoSeeder::DOMINIO)->count())->toBe(0)
        ->and(Empresa::withTrashed()->count())->toBe(0)
        ->and(Medicion::count())->toBe(0);
});
