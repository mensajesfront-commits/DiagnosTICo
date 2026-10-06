<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * E12 · Colaboradores de la empresa (HU-076 a HU-079, RN-025).
 *
 * Solo la cuenta principal (rol Empresa, con empresa) entra. Crea las
 * cuentas de sus colaboradores con correo y contraseña que ella misma
 * comparte; no se envía correo. Nunca toca cuentas de otra empresa (404) ni
 * la propia cuenta principal (403).
 */
class ColaboradoresController extends Controller
{
    public function index(Request $request): Response
    {
        $yo = $this->principal($request);

        $colaboradores = User::query()
            ->where('empresa_id', $yo->empresa_id)
            ->whereKeyNot($yo->id)
            ->role('Colaborador')
            ->orderBy('name')
            ->get();

        return Inertia::render('colaboradores/Index', [
            'empresa' => $yo->empresa?->nombre,
            'colaboradores' => collect([$yo])->concat($colaboradores)
                ->map(fn (User $u): array => [
                    'id' => $u->id,
                    'nombre' => $u->name,
                    'correo' => $u->email,
                    'es_principal' => $u->is($yo),
                    'activo' => $u->activo,
                    'ultimo_acceso' => $u->ultimo_acceso_en?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ]);
    }

    /** E12.1 · La cuenta queda activa y con rol Colaborador (HU-077). */
    public function store(Request $request): RedirectResponse
    {
        $yo = $this->principal($request);

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::default()],
        ], [
            'email.unique' => 'Ya hay una cuenta con este correo.',
        ]);

        $colaborador = DB::transaction(function () use ($datos, $yo): User {
            $colaborador = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => $datos['password'],
                'empresa_id' => $yo->empresa_id,
                'activo' => true,
            ]);
            $colaborador->forceFill(['contrasena_actualizada_en' => now()])->save();
            $colaborador->assignRole('Colaborador');

            return $colaborador;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Cuenta creada para {$colaborador->name}."]);

        return back();
    }

    /**
     * E12.3 · La contraseña anterior deja de funcionar y se cierran sus
     * sesiones abiertas, también las de "Recordarme" (HU-078).
     */
    public function contrasena(Request $request, User $colaborador): RedirectResponse
    {
        $this->deMiEmpresa($request, $colaborador);

        $datos = $request->validate([
            'password' => ['required', 'string', Password::default()],
        ]);

        $colaborador->forceFill([
            'password' => $datos['password'],
            'contrasena_actualizada_en' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        $this->cerrarSesiones($colaborador);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Contraseña cambiada para {$colaborador->name}."]);

        return back();
    }

    /** HU-079 · No borra nada: solo deja de poder entrar (RN-004). */
    public function desactivar(Request $request, User $colaborador): RedirectResponse
    {
        $this->deMiEmpresa($request, $colaborador);

        $colaborador->forceFill(['activo' => false])->save();
        $this->cerrarSesiones($colaborador);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$colaborador->name} ya no puede entrar."]);

        return back();
    }

    public function reactivar(Request $request, User $colaborador): RedirectResponse
    {
        $this->deMiEmpresa($request, $colaborador);

        $colaborador->forceFill(['activo' => true])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$colaborador->name} puede volver a entrar."]);

        return back();
    }

    /** La cuenta principal de una empresa (HU-076 CA-001). */
    private function principal(Request $request): User
    {
        /** @var User $yo */
        $yo = $request->user();

        abort_unless($yo->esPrincipal(), 403);

        return $yo;
    }

    /** Solo colaboradores de la misma empresa; los demás no existen para ella. */
    private function deMiEmpresa(Request $request, User $colaborador): void
    {
        $yo = $this->principal($request);

        abort_if($colaborador->is($yo), 403);
        abort_unless(
            $colaborador->empresa_id === $yo->empresa_id && $colaborador->hasRole('Colaborador'),
            404,
        );
    }

    private function cerrarSesiones(User $colaborador): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $colaborador->id)
                ->delete();
        }
    }
}
