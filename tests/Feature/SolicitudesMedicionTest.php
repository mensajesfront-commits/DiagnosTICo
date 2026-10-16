<?php

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Resultado;
use App\Models\Sector;
use App\Models\SolicitudMedicion;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * HU-083 · El Administrador ve y atiende las solicitudes de medición nueva:
 * en A1, en la lista y la ficha de la empresa y en Asignar medición. Se
 * atiende asignando otra, dándole nueva fecha o cancelando la vencida.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $this->admin = User::factory()->create()->assignRole('Administrador');
    $sector = Sector::factory()->create();
    $this->diagnostico = Diagnostico::factory()->publicado()->create(['sector_id' => $sector->id]);
    $version = $this->diagnostico->versiones()->firstOrFail();
    $this->empresa = Empresa::factory()->create(['nombre' => 'La Esquina', 'sector_id' => $sector->id]);
    $this->vencida = Medicion::factory()->create([
        'empresa_id' => $this->empresa->id, 'version_diagnostico_id' => $version->id,
        'numero' => 1, 'estado' => 'vencida', 'fecha_limite' => today()->subDays(3),
    ]);
    $this->solicitud = SolicitudMedicion::create([
        'empresa_id' => $this->empresa->id, 'medicion_vencida_id' => $this->vencida->id, 'estado' => 'abierta',
    ]);
    // Otra empresa con una vencida sin solicitud, que va después.
    $otra = Empresa::factory()->create(['sector_id' => $sector->id]);
    Medicion::factory()->create([
        'empresa_id' => $otra->id, 'version_diagnostico_id' => $version->id,
        'numero' => 1, 'estado' => 'vencida', 'fecha_limite' => today()->subDays(9),
        'created_at' => now()->subMonth(),
    ]);
});

it('A1 cuenta las solicitudes y pone primero la vencida que pidió una nueva', function () {
    $this->actingAs($this->admin)->get(route('inicio.administrador'))
        ->assertInertia(fn (Assert $p) => $p
            ->where('solicitudes', 1)
            ->where('mediciones.0.empresa', 'La Esquina')
            ->whereNot('mediciones.0.solicitud', null)
            ->where('mediciones.1.solicitud', null));
});

it('la lista, la ficha, el historial y Asignar medición muestran la solicitud', function () {
    $this->actingAs($this->admin)->get(route('empresas.index'))
        ->assertInertia(fn (Assert $p) => $p->where('empresas', fn ($empresas) => collect($empresas)->firstWhere('id', $this->empresa->id)['pendiente']['solicitud'] !== null
            && collect($empresas)->where('id', '!=', $this->empresa->id)->first()['pendiente']['solicitud'] === null));
    $this->actingAs($this->admin)->get(route('empresas.ver', $this->empresa))
        ->assertInertia(fn (Assert $p) => $p->whereNot('pendiente.solicitud', null));
    $this->actingAs($this->admin)->get(route('empresas.historial', $this->empresa))
        ->assertInertia(fn (Assert $p) => $p->whereNot('pendiente.solicitud', null));
    $this->actingAs($this->admin)->get(route('mediciones.crear', ['empresa' => $this->empresa->id]))
        ->assertInertia(fn (Assert $p) => $p->where('empresaInicial', $this->empresa->id)
            ->where('empresas', fn ($empresas) => collect($empresas)->firstWhere('id', $this->empresa->id)['solicitud'] !== null));
});

it('asignar otra medición atiende la solicitud', function () {
    $this->actingAs($this->admin)->post(route('mediciones.guardar'), [
        'empresa_id' => $this->empresa->id,
        'diagnostico_id' => $this->diagnostico->id,
        'fecha_limite' => today()->addWeek()->toDateString(),
        'reemplazar' => true,
    ])->assertSessionHasNoErrors();

    expect($this->solicitud->fresh()->estado)->toBe('atendida')
        ->and($this->solicitud->fresh()->atendida_en)->not->toBeNull();
});

it('darle una nueva fecha a la vencida atiende la solicitud', function () {
    $this->actingAs($this->admin)->put(route('mediciones.fecha', $this->vencida), [
        'fecha_limite' => today()->addWeek()->toDateString(),
    ])->assertSessionHasNoErrors();

    expect($this->solicitud->fresh()->estado)->toBe('atendida');
});

it('cancelar la vencida atiende la solicitud', function () {
    $this->actingAs($this->admin)->post(route('mediciones.cancelar', $this->vencida))->assertSessionHasNoErrors();

    expect($this->solicitud->fresh()->estado)->toBe('atendida');
});

it('si no se atiende, la vencida queda como está y la solicitud sigue abierta (CA-004)', function () {
    $this->actingAs($this->admin)->get(route('inicio.administrador'))->assertOk();

    expect($this->vencida->fresh()->estado)->toBe('vencida')
        ->and($this->solicitud->fresh()->estado)->toBe('abierta');
});

it('una terminada con otra sin iniciar no dice "en curso"', function () {
    $this->vencida->update(['estado' => 'terminada', 'terminada_en' => now()]);
    Resultado::create(['medicion_id' => $this->vencida->id, 'puntaje_total' => 50, 'nivel' => 'mejorar', 'por_categoria' => [], 'publicado_en' => now()]);
    Medicion::factory()->create([
        'empresa_id' => $this->empresa->id, 'version_diagnostico_id' => $this->vencida->version_diagnostico_id,
        'numero' => 2, 'estado' => 'no_iniciada', 'fecha_limite' => today()->addWeek(),
    ]);

    $this->actingAs($this->admin)->get(route('inicio.administrador'))
        ->assertInertia(fn (Assert $p) => $p->where('mediciones', fn ($filas) => collect($filas)
            ->firstWhere('id', $this->vencida->id)['pendiente'] === ['numero' => 2, 'estado' => 'no_iniciada']));
});
