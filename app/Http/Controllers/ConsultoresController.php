<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * HU-085 · Vincular y quitar consultores de una empresa (A3.1, pestaña
 * "Consultores"). Solo quien tiene `usuarios.gestionar` (RN-030). Un
 * consultor puede acompañar una o varias empresas.
 */
class ConsultoresController extends Controller
{
    public function vincular(Request $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validate([
            'consultor_id' => ['required', 'integer'],
        ], [], ['consultor_id' => 'consultor']);

        $consultor = User::query()->whereKey($datos['consultor_id'])->first();

        if ($consultor === null || ! $consultor->esConsultor()) {
            throw ValidationException::withMessages(['consultor_id' => 'Elige una cuenta con el rol Consultor.']);
        }

        $empresa->consultores()->syncWithoutDetaching([$consultor->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$consultor->name} ahora acompaña a {$empresa->nombre}."]);

        return back();
    }

    public function quitar(Empresa $empresa, User $consultor): RedirectResponse
    {
        $empresa->consultores()->detach($consultor->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$consultor->name} ya no ve a {$empresa->nombre}."]);

        return back();
    }
}
