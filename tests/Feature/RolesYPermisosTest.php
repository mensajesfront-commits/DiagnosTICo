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

it('da 5 permisos a la Empresa y al Colaborador', function () {
    $empresa = Role::findByName('Empresa')->permissions->pluck('name');
    $colaborador = Role::findByName('Colaborador')->permissions->pluck('name');

    expect($empresa)->toHaveCount(5)->toContain('perfil.editar')
        ->and($colaborador)->toHaveCount(5)->toContain('perfil.editar');
});

it('marca los roles del sistema y deja al Consultor inactivo', function () {
    expect(Role::where('del_sistema', true)->pluck('name')->sort()->values()->all())
        ->toBe(['Administrador', 'Colaborador', 'Empresa']);

    $consultor = Role::findByName('Consultor');

    expect((bool) $consultor->activo)->toBeFalse()
        ->and((bool) $consultor->del_sistema)->toBeFalse()
        ->and($consultor->permissions)->toHaveCount(6);
});

it('no pisa los cambios que el Administrador le haga al Consultor', function () {
    Role::findByName('Consultor')->syncPermissions(['empresas.ver']);

    $this->seed(RolesYPermisosSeeder::class);

    expect(Role::findByName('Consultor')->permissions)->toHaveCount(1);
});

it('se puede correr otra vez sin duplicar y borra permisos viejos', function () {
    Permission::findOrCreate('inicio.ver');

    $this->seed(RolesYPermisosSeeder::class);

    expect(Permission::count())->toBe(15)
        ->and(Role::count())->toBe(4);
});
