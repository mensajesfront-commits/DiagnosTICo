<?php

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Sector;
use App\Models\User;
use App\Support\DatosResponder;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;

/*
 * Fecha límite (22 de octubre de 2026): mínimo 3 días al asignar o cambiar
 * la fecha, y el día se cuenta en la hora de cada empresa (App\Support\Plazos).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    Notification::fake();
    $this->admin = User::factory()->create()->assignRole('Administrador');
    $this->sector = Sector::factory()->create();
    $this->diagnostico = Diagnostico::factory()->publicado()->create(['sector_id' => $this->sector->id]);
});

function empresaEnZona(Sector $sector, string $zona): Empresa
{
    $empresa = Empresa::factory()->create(['sector_id' => $sector->id]);
    User::factory()->create(['empresa_id' => $empresa->id, 'zona_horaria' => $zona])->assignRole('Empresa');

    return $empresa;
}

it('no deja asignar una medición con menos de 3 días para responder', function () {
    // 22 de octubre, 11:58 p. m. en Colombia.
    $this->travelTo(CarbonImmutable::parse('2026-10-22 23:58', 'America/Bogota'));
    $empresa = empresaEnZona($this->sector, 'America/Bogota');
    $datos = ['empresa_id' => $empresa->id, 'diagnostico_id' => $this->diagnostico->id];

    foreach (['2026-10-22', '2026-10-24'] as $fecha) {
        $this->actingAs($this->admin)
            ->post(route('mediciones.guardar'), [...$datos, 'fecha_limite' => $fecha])
            ->assertSessionHasErrors(['fecha_limite' => 'La fecha límite debe dar al menos 3 días para responder: elige el 25 de octubre o después.']);
    }

    $this->actingAs($this->admin)
        ->post(route('mediciones.guardar'), [...$datos, 'fecha_limite' => '2026-10-25'])
        ->assertSessionHasNoErrors();
});

it('tampoco deja poner una nueva fecha a menos de 3 días', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-22 10:00', 'America/Bogota'));
    $empresa = empresaEnZona($this->sector, 'America/Bogota');
    $vencida = Medicion::factory()->create(['empresa_id' => $empresa->id, 'estado' => 'vencida', 'fecha_limite' => '2026-10-20']);

    $this->actingAs($this->admin)->put(route('mediciones.fecha', $vencida), ['fecha_limite' => '2026-10-23'])
        ->assertSessionHasErrors('fecha_limite');

    $this->actingAs($this->admin)->put(route('mediciones.fecha', $vencida), ['fecha_limite' => '2026-10-25'])
        ->assertSessionHasNoErrors();
});

it('la fecha límite vale hasta las 11:59 p. m. en la hora de la empresa', function () {
    $empresa = empresaEnZona($this->sector, 'America/Bogota');
    $medicion = Medicion::factory()->create(['empresa_id' => $empresa->id, 'fecha_limite' => '2026-10-22']);

    // 9 p. m. en Colombia (ya es 23 de octubre en UTC): todavía puede responder.
    $this->travelTo(CarbonImmutable::parse('2026-10-22 21:00', 'America/Bogota'));
    $this->artisan('mediciones:vencer')->assertSuccessful();
    expect($medicion->refresh()->estado)->toBe('no_iniciada')
        ->and(DatosResponder::respondible($medicion))->toBeTrue();

    // 12:05 a. m. del 23 en Colombia: venció.
    $this->travelTo(CarbonImmutable::parse('2026-10-23 00:05', 'America/Bogota'));
    $this->artisan('mediciones:vencer')->assertSuccessful();
    expect($medicion->refresh()->estado)->toBe('vencida');
});

it('cada empresa vence a la medianoche de su propia zona horaria', function () {
    $argentina = Medicion::factory()->create([
        'empresa_id' => empresaEnZona($this->sector, 'America/Argentina/Buenos_Aires')->id,
        'fecha_limite' => '2026-10-22',
    ]);
    $mexico = Medicion::factory()->create([
        'empresa_id' => empresaEnZona($this->sector, 'America/Mexico_City')->id,
        'fecha_limite' => '2026-10-22',
    ]);

    // 12:30 a. m. del 23 en Argentina = 9:30 p. m. del 22 en México.
    $this->travelTo(CarbonImmutable::parse('2026-10-23 00:30', 'America/Argentina/Buenos_Aires'));
    $this->artisan('mediciones:vencer')->assertSuccessful();

    expect($argentina->refresh()->estado)->toBe('vencida')
        ->and($mexico->refresh()->estado)->toBe('no_iniciada');
});
