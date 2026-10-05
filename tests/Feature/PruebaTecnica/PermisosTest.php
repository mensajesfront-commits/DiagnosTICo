<?php

/*
| Prueba técnica T-018: spatie/laravel-permission bloquea en el servidor una
| ruta para cualquier rol que no sea Administrador.
*/

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('Administrador');
    Role::findOrCreate('Empresa');
});

it('deja entrar al Administrador', function () {
    $administrador = User::factory()->create()->assignRole('Administrador');

    $this->actingAs($administrador)
        ->get(route('prueba-tecnica.solo-administrador'))
        ->assertOk()
        ->assertJson(['mensaje' => 'Acceso permitido: tienes el rol Administrador.']);
});

it('bloquea la ruta para el rol Empresa', function () {
    $empresa = User::factory()->create()->assignRole('Empresa');

    $this->actingAs($empresa)
        ->get(route('prueba-tecnica.solo-administrador'))
        ->assertForbidden();
});

it('manda a iniciar sesión a quien no entró', function () {
    $this->get(route('prueba-tecnica.solo-administrador'))
        ->assertRedirect(route('login'));
});

it('comparte el rol y los permisos con las pantallas', function () {
    $administrador = User::factory()->create()->assignRole('Administrador');
    $administrador->givePermissionTo(
        Permission::findOrCreate('diagnosticos.ver'),
    );

    $this->actingAs($administrador)
        ->get(route('prueba-tecnica.componentes'))
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('prueba-tecnica/Componentes')
            ->where('auth.rol', 'Administrador')
            ->where('auth.permisos', ['diagnosticos.ver']));
});
