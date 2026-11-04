<?php

use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\RegistroVerComo;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

/*
 * A5.4 · "Ver como" (HU-053, HU-054, RN-026): solo lectura y con registro.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $this->admin = User::factory()->create(['name' => 'Cristian'])->assignRole('Administrador');
    $this->empresa = Empresa::factory()->create(['nombre' => 'La Esquina', 'sector_id' => Sector::factory()->create()->id]);
    $this->laura = User::factory()->create(['name' => 'Laura', 'empresa_id' => $this->empresa->id])->assignRole('Empresa');
});

it('el Administrador ve el sistema como la empresa, con la franja y el registro', function () {
    $this->actingAs($this->admin)
        ->from(route('usuarios.index'))
        ->post(route('usuarios.ver-como', $this->laura))
        ->assertRedirect(route('dashboard'));

    $registro = RegistroVerComo::sole();
    expect($registro->administrador_id)->toBe($this->admin->id)
        ->and($registro->cuenta_id)->toBe($this->laura->id)
        ->and($registro->fin)->toBeNull();

    $this->get(route('inicio.empresa'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('inicio/Empresa')
            ->where('auth.user.id', $this->laura->id)
            ->where('auth.rol', 'Empresa')
            ->where('verComo.cuenta', 'Laura')
            ->where('verComo.empresa', 'La Esquina')
            ->where('verComo.administrador', 'Cristian'));

    // Las pantallas del Administrador ya no están: manda el rol de la cuenta.
    $this->get(route('usuarios.index'))->assertForbidden();
});

it('es solo de lectura: no guarda nada y avisa', function () {
    $medicion = Medicion::factory()->create(['empresa_id' => $this->empresa->id, 'numero' => 1]);
    $this->actingAs($this->admin)->post(route('usuarios.ver-como', $this->laura));

    $this->from(route('profile.edit'))->patch(route('profile.update'), ['name' => 'Otro nombre'])
        ->assertRedirect(route('profile.edit'))
        ->assertInertiaFlash('toast.type', 'warning');
    expect($this->laura->fresh()->name)->toBe('Laura');

    $this->putJson(route('responder.guardar', $medicion), ['pregunta_ref' => 'p1', 'opciones' => []])
        ->assertForbidden()
        ->assertJsonPath('message', 'Estás en "Ver como": es solo de lectura y no se guardó nada.');
});

it('al salir vuelve a donde empezó y cierra el registro', function () {
    $this->actingAs($this->admin)
        ->from(route('empresas.ver', $this->empresa))
        ->post(route('usuarios.ver-como', $this->laura));

    $this->post(route('ver-como.salir'))->assertRedirect(route('empresas.ver', $this->empresa));

    expect(RegistroVerComo::sole()->fin)->not->toBeNull();
    $this->get(route('usuarios.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->where('verComo', null));
});

it('no se usa sobre un Administrador ni sin usuarios.gestionar', function () {
    $otro = User::factory()->create()->assignRole('Administrador');
    $this->actingAs($this->admin)->post(route('usuarios.ver-como', $otro))->assertForbidden();

    $this->actingAs($this->laura)->post(route('usuarios.ver-como', $this->admin))->assertForbidden();
    expect(RegistroVerComo::count())->toBe(0);
});

it('termina sola si el Administrador pierde el permiso', function () {
    $this->actingAs($this->admin)->post(route('usuarios.ver-como', $this->laura));
    $this->admin->syncRoles([]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->get(route('dashboard'));
    expect(RegistroVerComo::sole()->fin)->not->toBeNull();
});
