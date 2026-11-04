<?php

namespace App\Support;

use App\Models\Medicion;
use App\Models\User;
use Carbon\CarbonInterface;
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
        // La cuenta principal elige estas páginas para cada colaborador (E12.1).
        'Colaborador' => [
            'diagnostico.responder' => 'si su empresa se lo da',
            'resultados.ver' => 'si su empresa se lo da',
            'resultados.pdf' => 'si su empresa se lo da',
        ],
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

    /** Aviso de un rol creado e inactivo (A5.1c). */
    private const string AVISO_INACTIVO = 'Este rol está inactivo: sus cuentas no pueden entrar hasta que les asignes otro rol, y no se puede asignar a ninguna cuenta hasta que lo actives.';

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
            ->with(['roles:id,name,activo', 'empresa' => fn ($q) => $q->withTrashed()->select('id', 'nombre')])
            ->orderByRaw('id = ? desc', [$yo->id])
            ->orderBy('name')
            ->get();

        $colaboradoresActivos = User::role('Colaborador')
            ->where('activo', true)
            ->whereNotNull('empresa_id')
            ->selectRaw('empresa_id, count(*) as total')
            ->groupBy('empresa_id')
            ->pluck('total', 'empresa_id');

        // Medición pendiente de cada empresa, para el aviso al desactivar o
        // cambiar el rol de su cuenta principal.
        $pendientes = Medicion::query()
            ->whereIn('estado', DatosEmpresas::PENDIENTES)
            ->whereIn('empresa_id', $usuarios->pluck('empresa_id')->filter()->unique()->values())
            ->get()
            ->keyBy('empresa_id');

        $cuentas = [];

        foreach ($usuarios as $u) {
            $cuentas[] = self::cuenta(
                $u,
                $yo,
                (int) ($colaboradoresActivos[$u->empresa_id] ?? 0),
                $u->empresa_id !== null ? $pendientes->get($u->empresa_id) : null,
            );
        }

        return $cuentas;
    }

    /**
     * @return array<string, mixed>
     */
    public static function cuenta(User $u, User $yo, int $colaboradoresActivos = 0, ?Medicion $pendiente = null): array
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
            // "medición 4 no iniciada (vence el 20 de octubre)".
            'medicion_pendiente' => $rol === 'Empresa' && $pendiente ? self::pendiente($pendiente) : null,
            // Su rol está desactivado: no entra hasta tener otro (A5.1c).
            'rol_inactivo' => $u->rolInactivo(),
            // Nadie le quita el rol, la desactiva ni la elimina.
            'es_admin_principal' => $u->administrador_principal,
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
                'es_admin_principal' => $u->administrador_principal,
            ])->values()->all(),
            'aviso' => $activo ? null : ($r->name === 'Consultor' ? self::AVISO_CONSULTOR : self::AVISO_INACTIVO),
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

    private static function pendiente(Medicion $m): string
    {
        $estado = DatosEmpresas::estadoVisible($m);
        $texto = match ($estado) {
            'no_iniciada' => 'no iniciada',
            'en_curso' => 'en curso',
            'enviada' => 'en análisis',
            default => 'vencida',
        };
        $fecha = $m->fecha_limite ? self::diaYMes($m->fecha_limite) : null;

        return "medición {$m->numero} {$texto}".match (true) {
            $fecha === null => '',
            $estado === 'vencida' => " (venció el {$fecha})",
            default => " (vence el {$fecha})",
        };
    }

    private static function diaYMes(CarbonInterface $fecha): string
    {
        return $fecha->locale('es')->translatedFormat('j \\d\\e F');
    }
}
