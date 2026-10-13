<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y permisos de la matriz de docs/17_SEGURIDAD.md (T-006), tomada del
 * wireframe A5.2. Se puede correr varias veces: no duplica nada y borra los
 * permisos que ya no están en la lista.
 */
class RolesYPermisosSeeder extends Seeder
{
    /**
     * Los 15 permisos de A5.2, en sus 6 bloques. La clave es el nombre
     * técnico y el valor, el texto que muestra la pantalla.
     *
     * @var array<string, array<string, string>>
     */
    public const array PERMISOS = [
        'Diagnósticos' => [
            'diagnosticos.ver' => 'Ver diagnósticos',
            'diagnosticos.editar' => 'Editar preguntas e importancia',
            'diagnosticos.publicar' => 'Publicar versiones',
        ],
        'Empresas' => [
            'empresas.ver' => 'Ver empresas',
            'empresas.registrar' => 'Registrar empresas',
            'mediciones.asignar' => 'Asignar mediciones',
        ],
        'Mediciones y resultados' => [
            'diagnostico.responder' => 'Responder el diagnóstico',
            'resultados.ver' => 'Ver resultados y respuestas',
            'resultados.pdf' => 'Descargar PDF',
        ],
        'Configuración IA' => [
            'ia.ver' => 'Ver configuración',
            'ia.editar' => 'Editar prompts',
        ],
        'Usuarios y roles' => [
            'usuarios.ver' => 'Ver usuarios',
            'usuarios.gestionar' => 'Gestionar usuarios y roles',
        ],
        'Su cuenta' => [
            'perfil.editar' => 'Editar su perfil',
            'contrasena.cambiar' => 'Cambiar su contraseña',
        ],
    ];

    /**
     * Roles del sistema (RN-027) con su descripción y sus permisos fijos,
     * como en A5.2. El Administrador tiene los 15.
     *
     * @var array<string, string>
     */
    public const array DESCRIPCIONES = [
        'Administrador' => 'Acceso total al sistema',
        'Empresa' => 'Responde y ve los resultados de su empresa',
        'Colaborador' => 'Trabaja con la empresa, sin gestionar el equipo',
    ];

    /** @var array<string, list<string>> */
    public const array ROLES = [
        'Empresa' => [
            'diagnostico.responder',
            'resultados.ver',
            'resultados.pdf',
            'perfil.editar',
            'contrasena.cambiar',
        ],
        // RN-025: igual que la empresa, pero no cambia los datos de la empresa
        // ni gestiona colaboradores (eso lo decide el rol, no un permiso).
        // Sí edita su propio perfil (respuesta del equipo, 6 de octubre).
        'Colaborador' => [
            'diagnostico.responder',
            'resultados.ver',
            'resultados.pdf',
            'perfil.editar',
            'contrasena.cambiar',
        ],
    ];

    /**
     * Consultor: rol creado de ejemplo, inactivo hasta que exista la
     * vinculación de empresas (A5.2, PA-006). No es del sistema, así que el
     * Administrador lo puede editar; el seeder solo lo crea si no existe.
     *
     * @var list<string>
     */
    public const array CONSULTOR = [
        'empresas.ver',
        'diagnostico.responder',
        'resultados.ver',
        'resultados.pdf',
        'perfil.editar',
        'contrasena.cambiar',
    ];

    public function run(): void
    {
        $registro = app(PermissionRegistrar::class);
        $registro->forgetCachedPermissions();

        $todos = array_merge(...array_map(array_keys(...), array_values(self::PERMISOS)));

        foreach ($todos as $permiso) {
            Permission::findOrCreate($permiso);
        }

        Permission::whereNotIn('name', $todos)->delete();

        // Se limpia otra vez para que syncPermissions vea los permisos nuevos.
        $registro->forgetCachedPermissions();

        $permisosPorRol = ['Administrador' => $todos, ...self::ROLES];

        foreach ($permisosPorRol as $nombre => $permisos) {
            $rol = Role::findOrCreate($nombre);
            $rol->forceFill([
                'descripcion' => self::DESCRIPCIONES[$nombre],
                'activo' => true,
                'del_sistema' => true,
            ])->save();
            $rol->syncPermissions($permisos);
        }

        if (! Role::where('name', 'Consultor')->exists()) {
            $consultor = Role::findOrCreate('Consultor');
            $consultor->forceFill([
                'descripcion' => 'Acompaña a sus empresas vinculadas.',
                'activo' => false,
                'del_sistema' => false,
            ])->save();
            $consultor->syncPermissions(self::CONSULTOR);
        }
    }
}
