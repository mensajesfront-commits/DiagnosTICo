<?php

use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * L2 · Términos de uso y política de tratamiento de datos: públicos, para
 * consultarlos siempre (Ley 1581 de 2012).
 */

it('muestra los términos de uso sin iniciar sesión', function () {
    $this->get(route('legal.terminos'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/DocumentoLegal')
            ->where('documento', 'terminos'));
});

it('muestra la política de tratamiento de datos sin iniciar sesión', function () {
    $this->get(route('legal.politica'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/DocumentoLegal')
            ->where('documento', 'politica'));
});

it('también los deja ver con la sesión iniciada', function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->actingAs(User::factory()->create()->assignRole('Empresa'))
        ->get(route('legal.politica'))
        ->assertOk();
});
