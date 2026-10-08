<?php

use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * T-048 · Redirección después del login según el rol.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

it('manda al login a quien no ha iniciado sesión', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('inicio.administrador'))->assertRedirect(route('login'));
    $this->get(route('inicio.empresa'))->assertRedirect(route('login'));
});

it('lleva al Administrador a A1', function () {
    $admin = User::factory()->create()->assignRole('Administrador');

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('inicio.administrador'));
    $this->actingAs($admin)->get(route('inicio.administrador'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('inicio/Administrador'));
});

it('lleva a la Empresa y al Colaborador a E1', function (string $rol) {
    $cuenta = User::factory()->create()->assignRole($rol);

    $this->actingAs($cuenta)->get(route('dashboard'))->assertRedirect(route('inicio.empresa'));
    $this->actingAs($cuenta)->get(route('inicio.empresa'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('inicio/Empresa'));
})->with(['Empresa', 'Colaborador']);

it('no deja a cada cuenta entrar al inicio de la otra', function () {
    $admin = User::factory()->create()->assignRole('Administrador');
    $empresa = User::factory()->create()->assignRole('Empresa');

    $this->actingAs($empresa)->get(route('inicio.administrador'))->assertRedirect(route('inicio.empresa'));
    $this->actingAs($admin)->get(route('inicio.empresa'))->assertRedirect(route('inicio.administrador'));
});

it('después del login va a /dashboard', function () {
    $empresa = User::factory()->create()->assignRole('Empresa');

    $this->post(route('login.store'), ['email' => $empresa->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
});
