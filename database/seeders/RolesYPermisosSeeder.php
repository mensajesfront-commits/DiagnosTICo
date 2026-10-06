<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y permisos de la matriz de docs/17_SEGURIDAD.md (propuesta T-006).
 * Se puede correr varias veces: no duplica nada.
 */
class RolesYPermisosSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    public const array MATRIZ = [
        'Administrador' => [
            'inicio.ver',
            'diagnosticos.ver',
            'diagnosticos.editar',
            'diagnosticos.publicar',
            'empresas.ver',
            'empresas.gestionar',
            'mediciones.asignar',
            'ia.configurar',
            'usuarios.gestionar',
            'roles.gestionar',
            'usuarios.ver-como',
        ],
        'Empresa' => [
            'diagnostico.responder',
            'resultados.ver',
            'colaboradores.gestionar',
            'empresa.editar',
        ],
        'Colaborador' => [
            'diagnostico.responder',
            'resultados.ver',
        ],
    ];

    public function run(): void
    {
        $registro = app(PermissionRegistrar::class);
        $registro->forgetCachedPermissions();

        foreach (array_unique(array_merge(...array_values(self::MATRIZ))) as $permiso) {
            Permission::findOrCreate($permiso);
        }

        // Se limpia otra vez para que syncPermissions vea los permisos nuevos.
        $registro->forgetCachedPermissions();

        foreach (self::MATRIZ as $rol => $permisos) {
            Role::findOrCreate($rol)->syncPermissions($permisos);
        }
    }
}
