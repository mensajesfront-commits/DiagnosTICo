<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\SectorReasignado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * A2.2 · Sectores (T-049, HU-012 a HU-016): crear, editar, reasignar sus
 * empresas, desactivar, reactivar y eliminar. Un sector con empresas o
 * mediciones no se elimina: se reasigna o se desactiva (RN-008).
 */
class SectoresController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);

        $sector = Sector::create([...$datos, 'activo' => $request->boolean('activo', true)]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Sector «{$sector->nombre}» creado."]);

        return to_route('diagnosticos.index', ['sector' => $sector->id]);
    }

    public function update(Request $request, Sector $sector): RedirectResponse
    {
        $sector->update($this->validar($request, $sector));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sector guardado.']);

        return back();
    }

    /** A2.2b · Las empresas pasan a otro sector con sus mediciones (HU-014). */
    public function reasignar(Request $request, Sector $sector): RedirectResponse
    {
        $datos = $request->validate([
            'sector_destino_id' => ['required', 'integer', Rule::exists('sectores', 'id')->where('activo', true), Rule::notIn([$sector->id])],
            'avisar' => ['boolean'],
        ], [], ['sector_destino_id' => 'sector destino']);

        /** @var Sector $destino */
        $destino = Sector::findOrFail($datos['sector_destino_id']);
        $ids = Empresa::where('sector_id', $sector->id)->pluck('id');

        Empresa::withTrashed()->where('sector_id', $sector->id)->update(['sector_id' => $destino->id]);

        if ($request->boolean('avisar')) {
            // A la cuenta principal de cada empresa.
            User::whereIn('empresa_id', $ids)->role('Empresa')->where('activo', true)->get()
                ->each(fn (User $u) => $u->notify(new SectorReasignado($sector->nombre, $destino->nombre)));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$ids->count()} empresas pasaron a «{$destino->nombre}»."]);

        return back();
    }

    /** A2.2c · Deja de ofrecerse al registrarse; conserva todo (HU-015, RN-003). */
    public function desactivar(Sector $sector): RedirectResponse
    {
        $sector->update(['activo' => false]);
        Inertia::flash('toast', ['type' => 'success', 'message' => "Sector «{$sector->nombre}» desactivado."]);

        return back();
    }

    public function reactivar(Sector $sector): RedirectResponse
    {
        $sector->update(['activo' => true]);
        Inertia::flash('toast', ['type' => 'success', 'message' => "Sector «{$sector->nombre}» reactivado."]);

        return back();
    }

    /**
     * A2.2e · Solo un sector sin empresas ni mediciones (RN-008). Sus
     * borradores nunca publicados se borran con él.
     */
    public function destroy(Sector $sector): RedirectResponse
    {
        $conEmpresas = Empresa::withTrashed()->where('sector_id', $sector->id)->exists();
        $conVersiones = Diagnostico::where('sector_id', $sector->id)->whereHas('versiones')->exists();

        if ($conEmpresas || $conVersiones) {
            throw ValidationException::withMessages([
                'sector' => 'Tiene empresas o diagnósticos publicados: reasigna sus empresas o desactívalo.',
            ]);
        }

        DB::transaction(function () use ($sector): void {
            Diagnostico::where('sector_id', $sector->id)->delete();
            $sector->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Sector «{$sector->nombre}» eliminado."]);

        return to_route('diagnosticos.index');
    }

    /** @return array{nombre: string, descripcion: string|null} */
    private function validar(Request $request, ?Sector $sector = null): array
    {
        /** @var array{nombre: string, descripcion?: string|null} $datos */
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:40', Rule::unique('sectores', 'nombre')->ignore($sector?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], ['nombre.unique' => 'Ya existe un sector con ese nombre.']);

        return ['nombre' => trim($datos['nombre']), 'descripcion' => $datos['descripcion'] ?? null];
    }
}
