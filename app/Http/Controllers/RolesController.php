<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\RolCambiado;
use App\Support\DatosUsuarios;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * A5.1 · Usuarios y roles, pestaña Roles (HU-048 a HU-052).
 */
class RolesController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $yo */
        $yo = $request->user();

        return Inertia::render('usuarios/Roles', [
            'roles' => DatosUsuarios::roles(),
            'bloques' => DatosUsuarios::bloques(),
            'cuentas' => DatosUsuarios::cuentas($yo),
            'rolId' => $request->integer('rol') ?: null,
        ]);
    }

    /** A5.2 · Crear rol. Se abre el rol nuevo. */
    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos += $request->validate([
            'cuentas' => ['array'],
            'cuentas.*' => ['integer', Rule::exists('users', 'id'), Rule::notIn([$request->user()?->id])],
        ], [], ['cuentas.*' => 'cuenta']);

        $cuentas = User::whereIn('id', $datos['cuentas'] ?? [])->get();

        if ($cuentas->isNotEmpty() && ! $datos['activo']) {
            throw ValidationException::withMessages(['cuentas' => 'Un rol inactivo no se puede asignar. Actívalo o quita las cuentas.']);
        }

        $rol = DB::transaction(function () use ($datos, $cuentas): Role {
            /** @var Role $rol */
            $rol = Role::create(['name' => $datos['nombre'], 'guard_name' => 'web']);
            $rol->forceFill([
                'descripcion' => $datos['descripcion'] ?? null,
                'activo' => $datos['activo'],
                'del_sistema' => false,
            ])->save();
            $rol->syncPermissions($datos['permisos'] ?? []);

            foreach ($cuentas as $cuenta) {
                $cuenta->syncRoles([$rol]);
            }

            return $rol;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Rol {$rol->name} creado."]);

        return redirect()->route('usuarios.roles', ['rol' => $rol->id]);
    }

    /** A5.1c · Editar un rol creado (HU-050). Los del sistema no se editan. */
    public function update(Role $rol, Request $request): RedirectResponse
    {
        $this->noDelSistema($rol, 'Los roles del sistema no se editan.');

        $datos = $this->validar($request, $rol);

        if (! $datos['activo'] && $rol->users()->exists()) {
            throw ValidationException::withMessages(['activo' => 'Este rol tiene cuentas: pásalas a otro rol antes de desactivarlo.']);
        }

        DB::transaction(function () use ($rol, $datos): void {
            $rol->forceFill([
                'name' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'activo' => $datos['activo'],
            ])->save();
            $rol->syncPermissions($datos['permisos'] ?? []);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cambios guardados. Aplican a todas las cuentas con este rol.']);

        return back();
    }

    /** A5.1d · Solo roles creados y sin cuentas (HU-051, RN-027). */
    public function destroy(Role $rol): RedirectResponse
    {
        $this->noDelSistema($rol, 'Los roles del sistema no se eliminan.');
        abort_if($rol->users()->exists(), 422, 'Un rol con cuentas no se puede eliminar.');

        $nombre = $rol->name;
        $rol->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Rol {$nombre} eliminado."]);

        return redirect()->route('usuarios.roles');
    }

    /** A5.5 · Asignar el rol a una cuenta que ya existe; reemplaza su rol. */
    public function asignar(Role $rol, Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'cuenta_id' => ['required', 'integer', Rule::exists('users', 'id'), Rule::notIn([$request->user()?->id])],
            'avisar' => ['boolean'],
        ], ['cuenta_id.not_in' => 'No puedes cambiar el rol de tu propia cuenta.'], ['cuenta_id' => 'cuenta']);

        if (! $rol->getAttribute('activo')) {
            throw ValidationException::withMessages(['cuenta_id' => 'Un rol inactivo no se puede asignar.']);
        }

        /** @var User $cuenta */
        $cuenta = User::query()->findOrFail($datos['cuenta_id']);
        UsuariosController::validarRolParaCuenta($rol, $cuenta, 'cuenta_id');

        $cuenta->syncRoles([$rol]);

        if ($request->boolean('avisar')) {
            $cuenta->notify(new RolCambiado($rol->name));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$cuenta->name} ahora tiene el rol {$rol->name}."]);

        return back();
    }

    /**
     * @return array{nombre: string, descripcion: string|null, activo: bool, permisos?: list<string>}
     */
    private function validar(Request $request, ?Role $rol = null): array
    {
        $permisos = array_merge(...array_map(array_keys(...), array_values(RolesYPermisosSeeder::PERMISOS)));

        /** @var array{nombre: string, descripcion: string|null, activo: bool, permisos?: list<string>} $datos */
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:40', Rule::unique('roles', 'name')->ignore($rol?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
            'permisos' => ['array'],
            'permisos.*' => ['string', Rule::in($permisos)],
        ], ['nombre.unique' => 'Ya existe un rol con ese nombre.'], ['nombre' => 'nombre del rol']);

        $datos['activo'] = (bool) $datos['activo'];

        return $datos;
    }

    private function noDelSistema(Role $rol, string $mensaje): void
    {
        abort_if((bool) $rol->getAttribute('del_sistema'), 403, $mensaje);
    }
}
