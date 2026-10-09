<?php

namespace App\Support;

use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Arma los datos de "Usuarios y roles" (A5) con la forma que esperan las
 * pantallas (resources/js/types/usuarios.ts, docs/14_FRONTEND.md).
 */
class DatosUsuarios
{
    /** Roles que solo tienen las cuentas de una empresa. */
    public const array ROLES_DE_EMPRESA = ['Empresa', 'Colaborador'];

    /** Texto corto de la lista de roles (A5.1). */
    private const array RESUMENES = [
        'Administrador' => 'Acceso total',
        'Empresa' => 'Responde y ve sus resultados',
        'Colaborador' => 'Sin gestionar equipo',
        'Consultor' => 'Próxima fase',
    ];

    /** Aclaraciones junto a los permisos (A5.1b, A5.1e, A5.1c). */
    private const array NOTAS = [
        'Empresa' => self::SOLO_SU_EMPRESA,
        'Colaborador' => self::SOLO_SU_EMPRESA,
        'Consultor' => [
            'empresas.ver' => 'solo las vinculadas',
            'diagnostico.responder' => 'en nombre de la empresa',
        ],
    ];

    private const array SOLO_SU_EMPRESA = [
        'diagnostico.responder' => 'solo su empresa',
        'resultados.ver' => 'solo su empresa',
        'resultados.pdf' => 'solo su empresa',
    ];

    /** Aviso del rol Consultor (A5.1c, PA-006). */
    private const string AVISO_CONSULTOR = 'Disponible en la próxima fase: vincular empresas. Hasta entonces este rol queda inactivo y no se puede asignar a ninguna cuenta.';

    /**
     * Todas las cuentas, de la más reciente a la más antigua, con su rol y su
     * empresa.
     *
     * @return list<array<string, mixed>>
     */
    public static function cuentas(User $yo): array
    {
        // Incluye las eliminadas: la pantalla las muestra solo con el filtro
        // "Eliminadas", para recuperarlas (DEC-017).
        $usuarios = User::withTrashed()
            ->with(['roles:id,name', 'empresa' => fn ($q) => $q->withTrashed()->select('id', 'nombre')])
            ->orderByRaw('id = ? desc', [$yo->id])
            ->orderBy('name')
            ->get();

        $colaboradoresActivos = User::role('Colaborador')
            ->where('activo', true)
            ->whereNotNull('empresa_id')
            ->selectRaw('empresa_id, count(*) as total')
            ->groupBy('empresa_id')
            ->pluck('total', 'empresa_id');

        $cuentas = [];

        foreach ($usuarios as $u) {
            $cuentas[] = self::cuenta($u, $yo, (int) ($colaboradoresActivos[$u->empresa_id] ?? 0));
        }

        return $cuentas;
    }

    /**
     * @return array<string, mixed>
     */
    public static function cuenta(User $u, User $yo, int $colaboradoresActivos = 0): array
    {
        /** @var Role|null $primero */
        $primero = $u->roles->first();
        $rol = $primero?->name;

        return [
            'id' => $u->id,
            'nombre' => $u->name,
            'correo' => $u->email,
            'rol' => $rol,
            'empresa' => $u->empresa?->nombre,
            'es_principal' => $rol === 'Empresa' && $u->empresa_id !== null,
            'colaboradores_activos' => $rol === 'Empresa' ? $colaboradoresActivos : 0,
            // [FUNCIONALIDAD POR DEFINIR] Se llena cuando exista la tabla de
            // mediciones (semana 4).
            'medicion_pendiente' => null,
            'estado' => match (true) {
                $u->trashed() => 'eliminada',
                $u->invitacionPendiente() => 'invitacion',
                ! $u->activo => 'desactivada',
                default => 'activa',
            },
            // El último acceso se guarda (users.ultimo_acceso_en) pero A5 ya
            // no lo muestra: se consulta en la base de datos.
            'invitacion_enviada_en' => $u->invitacion_enviada_en?->toIso8601String(),
            'es_tuya' => $u->id === $yo->id,
            'eliminada_en' => $u->deleted_at?->toIso8601String(),
            // Último día para recuperarla; después se borra para siempre.
            'se_borra_el' => $u->deleted_at?->copy()->addDays((int) config('diagnostico.eliminacion.dias'))->toIso8601String(),
        ];
    }

    /**
     * Roles con sus permisos y las primeras cuentas de cada uno. Primero los
     * del sistema, en el orden de A5.1, y después los creados.
     *
     * @return list<array<string, mixed>>
     */
    public static function roles(int $cuentasPorRol = 3): array
    {
        $orden = ['Administrador' => 0, 'Empresa' => 1, 'Colaborador' => 2];

        $roles = Role::with('permissions:id,name')
            ->withCount('users')
            ->get()
            ->sortBy(fn (Role $r) => [$orden[$r->name] ?? 9, $r->name]);

        $datos = [];

        foreach ($roles as $rol) {
            $datos[] = self::rol($rol, $cuentasPorRol);
        }

        return $datos;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rol(Role $r, int $cuentasPorRol = 3): array
    {
        /** @var Collection<int, User> $cuentas */
        $cuentas = User::role($r->name)
            ->with('empresa:id,nombre')
            ->whereNotNull('password')
            ->orderBy('name')
            ->limit($cuentasPorRol)
            ->get();

        $activo = (bool) ($r->getAttribute('activo') ?? true);

        return [
            'id' => $r->id,
            'nombre' => $r->name,
            'descripcion' => $r->getAttribute('descripcion'),
            'resumen' => self::RESUMENES[$r->name] ?? ($activo ? ($r->getAttribute('descripcion') ?: 'Rol creado') : 'Inactivo'),
            'activo' => $activo,
            'del_sistema' => (bool) $r->getAttribute('del_sistema'),
            'permisos' => $r->permissions->pluck('name')->values()->all(),
            'notas_permisos' => (object) (self::NOTAS[$r->name] ?? []),
            'cuentas_total' => (int) $r->getAttribute('users_count'),
            'cuentas' => $cuentas->map(fn (User $u) => [
                'id' => $u->id,
                'nombre' => $u->name,
                'detalle' => $u->empresa
                    ? $u->empresa->nombre.($r->name === 'Colaborador' ? ' · colaborador' : '')
                    : $u->email,
                'es_tuya' => $u->id === auth()->id(),
            ])->values()->all(),
            'aviso' => $activo ? null : ($r->name === 'Consultor' ? self::AVISO_CONSULTOR : null),
        ];
    }

    /**
     * Los 15 permisos de A5.2 en sus 6 bloques.
     *
     * @return list<array{bloque: string, permisos: list<array{nombre: string, texto: string}>}>
     */
    public static function bloques(): array
    {
        $bloques = [];

        foreach (RolesYPermisosSeeder::PERMISOS as $bloque => $permisos) {
            $bloques[] = [
                'bloque' => $bloque,
                'permisos' => array_map(
                    fn (string $nombre, string $texto) => ['nombre' => $nombre, 'texto' => $texto],
                    array_keys($permisos),
                    array_values($permisos),
                ),
            ];
        }

        return $bloques;
    }
}
