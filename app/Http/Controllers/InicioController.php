<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DatosInicio;
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
 * A1 tiene indicadores, mediciones y paneles (HU-006, HU-007, DatosInicio).
 * [FUNCIONALIDAD POR DEFINIR] E1 se construye en la semana 6; por ahora es
 * una pantalla de bienvenida.
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

        return Inertia::render('inicio/Administrador', DatosInicio::administrador());
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
