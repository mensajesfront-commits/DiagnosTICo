<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\InvitacionCuenta;
use App\Notifications\RolCambiado;
use App\Support\DatosUsuarios;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * A5 · Usuarios y roles, pestaña Usuarios (HU-046, HU-047, HU-052).
 */
class UsuariosController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $yo */
        $yo = $request->user();

        return Inertia::render('usuarios/Index', [
            'cuentas' => DatosUsuarios::cuentas($yo),
            'roles' => array_map(
                fn (array $r) => array_intersect_key($r, array_flip(['id', 'nombre', 'descripcion', 'activo', 'del_sistema', 'aviso'])),
                DatosUsuarios::roles(0),
            ),
        ]);
    }

    /**
     * "Invitar usuario": cuenta interna sin contraseña; la persona la crea con
     * el enlace del correo (RN-006).
     */
    public function invitar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'rol_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('activo', true)->whereNotIn('name', DatosUsuarios::ROLES_DE_EMPRESA)],
            'mensaje' => ['nullable', 'string', 'max:500'],
        ], [
            'rol_id.exists' => 'Elige un rol interno activo.',
        ], ['rol_id' => 'rol', 'mensaje' => 'mensaje']);

        /** @var Role $rol */
        $rol = Role::query()->findOrFail($datos['rol_id']);

        $usuario = DB::transaction(function () use ($datos, $rol): User {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => null,
            ]);
            $usuario->assignRole($rol);

            return $usuario;
        });

        $this->enviarInvitacion($usuario, $rol->name, $datos['mensaje'] ?? null, $request->user()?->name);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Invitación enviada a {$usuario->email}."]);

        return back();
    }

    public function reenviarInvitacion(User $usuario, Request $request): RedirectResponse
    {
        abort_unless($usuario->invitacionPendiente(), 422, 'Esta cuenta ya creó su contraseña.');

        $this->enviarInvitacion($usuario, $usuario->getRoleNames()->first() ?? '', null, $request->user()?->name);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Invitación reenviada a {$usuario->email}."]);

        return back();
    }

    /**
     * A5.3b (RN-004). Si es la cuenta principal, también se desactiva la
     * empresa: ella y sus colaboradores se quedan sin acceso (RN-025).
     */
    public function desactivar(User $usuario, Request $request): RedirectResponse
    {
        $this->noSobreLaPropia($usuario, $request);

        DB::transaction(function () use ($usuario): void {
            $usuario->forceFill(['activo' => false])->save();

            if ($usuario->esPrincipal()) {
                $usuario->empresa?->update(['activa' => false]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Cuenta de {$usuario->name} desactivada."]);

        return back();
    }

    public function reactivar(User $usuario, Request $request): RedirectResponse
    {
        $this->noSobreLaPropia($usuario, $request);

        DB::transaction(function () use ($usuario): void {
            $usuario->forceFill(['activo' => true])->save();

            if ($usuario->esPrincipal()) {
                $usuario->empresa?->update(['activa' => true]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Cuenta de {$usuario->name} reactivada."]);

        return back();
    }

    /**
     * A5.5 · Cambia el rol (RN-027: uno por cuenta). Solo roles activos; no
     * "Colaborador" (lo crea la empresa); "Empresa" solo a cuentas de una
     * empresa.
     */
    public function cambiarRol(User $usuario, Request $request): RedirectResponse
    {
        $this->noSobreLaPropia($usuario, $request);

        $datos = $request->validate([
            'rol_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('activo', true)],
            'avisar' => ['boolean'],
        ], ['rol_id.exists' => 'Elige un rol activo.'], ['rol_id' => 'rol']);

        /** @var Role $rol */
        $rol = Role::query()->findOrFail($datos['rol_id']);
        self::validarRolParaCuenta($rol, $usuario);

        $usuario->syncRoles([$rol]);

        if ($request->boolean('avisar')) {
            $usuario->notify(new RolCambiado($rol->name));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$usuario->name} ahora tiene el rol {$rol->name}."]);

        return back();
    }

    /**
     * A5.4 · "Ver como".
     *
     * [FUNCIONALIDAD POR DEFINIR] Se construye con la pantalla A5.4 (semana
     * 6): solo lectura, con registro (RN-026) y nunca sobre un Administrador.
     */
    public function verComo(User $usuario): RedirectResponse
    {
        abort_if($usuario->hasRole('Administrador'), 403, '"Ver como" no se usa sobre cuentas de Administrador.');

        Inertia::flash('toast', ['type' => 'info', 'message' => '"Ver como" llega con la pantalla A5.4.']);

        return back();
    }

    /**
     * Reglas comunes al cambiar o asignar un rol (A5.5).
     *
     * @throws ValidationException
     */
    public static function validarRolParaCuenta(Role $rol, User $usuario, string $campo = 'rol_id'): void
    {
        if ($rol->name === 'Colaborador') {
            throw ValidationException::withMessages([$campo => 'Los colaboradores los crea cada empresa desde su menú.']);
        }

        if ($rol->name === 'Empresa' && $usuario->empresa_id === null) {
            throw ValidationException::withMessages([$campo => 'Solo una cuenta de una empresa puede tener el rol Empresa.']);
        }
    }

    private function noSobreLaPropia(User $usuario, Request $request): void
    {
        abort_if($usuario->is($request->user()), 403, 'No puedes hacer esto sobre tu propia cuenta.');
    }

    private function enviarInvitacion(User $usuario, string $rol, ?string $mensaje, ?string $invitadaPor): void
    {
        /** @var PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($usuario);

        $usuario->notify(new InvitacionCuenta($token, $rol, $mensaje, $invitadaPor));
        $usuario->forceFill(['invitacion_enviada_en' => now()])->save();
    }
}
