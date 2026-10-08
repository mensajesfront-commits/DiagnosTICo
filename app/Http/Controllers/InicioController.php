<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DatosUsuarios;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * T-048 · Después de iniciar sesión (o registrarse) se llega a /dashboard, y
 * de ahí cada cuenta va a su inicio:
 *
 * - Empresa y Colaborador → E1 (/mi-inicio).
 * - Administrador y cualquier rol interno → A1 (/inicio).
 *
 * [FUNCIONALIDAD POR DEFINIR] A1 y E1 se construyen en las semanas 5 y 6;
 * por ahora son pantallas de bienvenida.
 */
class InicioController extends Controller
{
    public function redirigir(Request $request): RedirectResponse
    {
        return to_route($this->esDeEmpresa($request) ? 'inicio.empresa' : 'inicio.administrador');
    }

    /** A1 · Inicio del Administrador. */
    public function administrador(Request $request): Response|RedirectResponse
    {
        if ($this->esDeEmpresa($request)) {
            return to_route('inicio.empresa');
        }

        return Inertia::render('inicio/Administrador');
    }

    /** E1 · Inicio de la empresa. */
    public function empresa(Request $request): Response|RedirectResponse
    {
        if (! $this->esDeEmpresa($request)) {
            return to_route('inicio.administrador');
        }

        return Inertia::render('inicio/Empresa');
    }

    private function esDeEmpresa(Request $request): bool
    {
        /** @var User $usuario */
        $usuario = $request->user();

        return $usuario->hasAnyRole(DatosUsuarios::ROLES_DE_EMPRESA);
    }
}
