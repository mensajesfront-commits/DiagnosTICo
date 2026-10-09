<?php

use App\Models\ActividadEconomica;
use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\ActividadesEconomicasSeeder;
use Database\Seeders\CiiuSeeder;
use Database\Seeders\RolesYPermisosSeeder;
use Database\Seeders\SectoresSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * Catálogo CIIU Rev. 5 A.C. y subsectores automáticos por división (DEC-018).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $this->seed(CiiuSeeder::class);
    $this->admin = User::factory()->create()->assignRole('Administrador');
});

it('carga el catálogo oficial completo', function () {
    expect(DB::table('ciiu_divisiones')->count())->toBe(87)
        ->and(DB::table('ciiu_clases')->count())->toBe(544)
        ->and(DB::table('ciiu_clases')->where('codigo', '5611')->value('nombre'))->toBe('Expendio a la mesa de comidas preparadas');

    // Nombre corto para mostrar y el oficial del DANE.
    $division = DB::table('ciiu_divisiones')->where('codigo', '56')->first();
    expect($division->nombre)->toBe('Comidas y bebidas')
        ->and($division->nombre_oficial)->toBe('Actividades de servicios de comidas y bebidas');

    // Se puede volver a correr sin duplicar.
    $this->seed(CiiuSeeder::class);
    expect(DB::table('ciiu_clases')->count())->toBe(544);
});

it('al crear un sector con una división le pone todas sus clases', function () {
    $this->actingAs($this->admin)->post(route('sectores.store'), [
        'nombre' => 'Restaurantes',
        'ciiu_division' => '56',
    ])->assertSessionHasNoErrors();

    $sector = Sector::where('nombre', 'Restaurantes')->firstOrFail();
    expect($sector->ciiu_division)->toBe('56')
        ->and($sector->actividades()->where('activo', true)->pluck('codigo')->sort()->values()->all())
        ->toBe(['5611', '5612', '5613', '5619', '5621', '5629', '5630', '5640']);
});

it('si el nombre es exactamente el de una división, la asigna sola', function () {
    $this->actingAs($this->admin)->post(route('sectores.store'), ['nombre' => 'alojamiento'])
        ->assertSessionHasNoErrors();

    $sector = Sector::where('nombre', 'alojamiento')->firstOrFail();
    expect($sector->ciiu_division)->toBe('55')
        ->and($sector->actividades()->count())->toBe(10);

    // Un nombre que no es de una división queda sin subsectores.
    $this->actingAs($this->admin)->post(route('sectores.store'), ['nombre' => 'Veterinarias'])
        ->assertSessionHasNoErrors();
    expect(Sector::where('nombre', 'Veterinarias')->firstOrFail()->actividades()->count())->toBe(0);
});

it('al cambiar la división desactiva los subsectores que ya no van, sin borrarlos', function () {
    $sector = Sector::factory()->create(['nombre' => 'Salud']);
    $this->actingAs($this->admin)->put(route('sectores.update', $sector), ['nombre' => 'Salud', 'ciiu_division' => '86']);
    $clase = ActividadEconomica::where('sector_id', $sector->id)->where('codigo', '8621')->firstOrFail();
    Empresa::factory()->create(['sector_id' => $sector->id, 'actividad_economica_id' => $clase->id]);

    $this->actingAs($this->admin)->put(route('sectores.update', $sector), ['nombre' => 'Salud', 'ciiu_division' => '75'])
        ->assertSessionHasNoErrors();

    expect($sector->fresh()->ciiu_division)->toBe('75')
        ->and($clase->fresh()->activo)->toBeFalse()
        ->and(ActividadEconomica::where('sector_id', $sector->id)->where('activo', true)->pluck('codigo')->all())->toBe(['7500']);
});

it('reconoce el nombre corto y el oficial de la división', function () {
    $this->actingAs($this->admin)->post(route('sectores.store'), ['nombre' => 'Comidas y bebidas']);
    $this->actingAs($this->admin)->post(route('sectores.store'), ['nombre' => 'Actividades jurídicas y de contabilidad']);

    expect(Sector::where('nombre', 'Comidas y bebidas')->value('ciiu_division'))->toBe('56')
        ->and(Sector::where('nombre', 'Actividades jurídicas y de contabilidad')->value('ciiu_division'))->toBe('69');
});

it('al volver a cargar el catálogo, los subsectores toman el nombre corto', function () {
    $sector = Sector::factory()->create();
    $vieja = ActividadEconomica::create(['sector_id' => $sector->id, 'codigo' => '1104', 'nombre' => 'Elaboración de bebidas no alcohólicas, producción de aguas minerales y otras aguas embotelladas']);

    $this->seed(CiiuSeeder::class);

    expect($vieja->fresh()->nombre)->toBe('Bebidas no alcohólicas y aguas embotelladas');
});

it('rechaza una división que no existe', function () {
    $this->actingAs($this->admin)->post(route('sectores.store'), ['nombre' => 'Otro', 'ciiu_division' => '04'])
        ->assertSessionHasErrors('ciiu_division');
});

it('los sectores iniciales usan la Rev. 5 y desactivan los códigos de la Rev. 4', function () {
    $this->seed(SectoresSeeder::class);
    $inmobiliarias = Sector::where('nombre', 'Inmobiliarias')->firstOrFail();
    // Un código de la Rev. 4 que ya no existe.
    $vieja = ActividadEconomica::create(['sector_id' => $inmobiliarias->id, 'codigo' => '6820', 'nombre' => 'Rev. 4', 'activo' => true]);

    $this->seed(ActividadesEconomicasSeeder::class);

    expect($vieja->fresh()->activo)->toBeFalse()
        ->and($inmobiliarias->actividades()->where('activo', true)->pluck('codigo')->sort()->values()->all())->toBe(['6810', '6821', '6829'])
        ->and(Sector::where('nombre', 'Abogados')->firstOrFail()->actividades()->pluck('codigo')->all())->toBe(['6910']);
});

it('A2 recibe las divisiones para elegir', function () {
    $this->actingAs($this->admin)->get(route('diagnosticos.index'))
        ->assertInertia(fn (Assert $p) => $p->has('divisionesCiiu', 87)
            ->where('divisionesCiiu.0.codigo', '01'));
});
