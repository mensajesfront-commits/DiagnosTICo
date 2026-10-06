<?php

use Database\Seeders\RolesYPermisosSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * Matriz de roles y permisos de A5.2 (T-006, docs/17_SEGURIDAD.md).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

it('crea los 15 permisos en 6 bloques', function () {
    expect(RolesYPermisosSeeder::PERMISOS)->toHaveCount(6)
        ->and(Permission::count())->toBe(15);
});

it('da los 15 permisos al Administrador', function () {
    expect(Role::findByName('Administrador')->permissions)->toHaveCount(15);
});

it('da 5 permisos a la Empresa y 4 al Colaborador', function () {
    $empresa = Role::findByName('Empresa')->permissions->pluck('name');
    $colaborador = Role::findByName('Colaborador')->permissions->pluck('name');

    expect($empresa)->toHaveCount(5)->toContain('perfil.editar')
        ->and($colaborador)->toHaveCount(4)->not->toContain('perfil.editar');
});

it('se puede correr otra vez sin duplicar y borra permisos viejos', function () {
    Permission::findOrCreate('inicio.ver');

    $this->seed(RolesYPermisosSeeder::class);

    expect(Permission::count())->toBe(15)
        ->and(Role::count())->toBe(3);
});
