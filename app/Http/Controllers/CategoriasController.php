<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Diagnostico;
use App\Models\DiagnosticoCategoria;
use App\Support\DatosDiagnosticos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A2.3 · Catálogo de categorías (T-049, HU-017 a HU-019). Una categoría que
 * alguna empresa ya respondió no se borra: se archiva (RN-009). Archivar la
 * quita de los borradores; las versiones publicadas no cambian.
 */
class CategoriasController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('categorias/Index', [
            'categorias' => DatosDiagnosticos::categorias(),
            'totalDiagnosticos' => Diagnostico::activos()->count(),
            'borradores' => DatosDiagnosticos::borradores(),
        ]);
    }

    /** A2.3b · Crear y, si se elige, agregarla a borradores (HU-018). */
    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request) + $request->validate([
            'diagnosticos' => ['array'],
            'diagnosticos.*' => ['integer', Rule::exists('diagnosticos', 'id')],
        ]);

        $categoria = DB::transaction(function () use ($datos): Categoria {
            $categoria = Categoria::create(['nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion']]);

            foreach (Diagnostico::whereIn('id', $datos['diagnosticos'] ?? [])->get() as $diagnostico) {
                if (! DatosDiagnosticos::esBorrador($diagnostico)) {
                    continue;
                }

                // Entra al final con importancia 0; se reparte en el editor
                // antes de publicar (RN-011).
                DiagnosticoCategoria::create([
                    'diagnostico_id' => $diagnostico->id,
                    'categoria_id' => $categoria->id,
                    'importancia' => 0,
                    'orden' => (int) $diagnostico->partes()->max('orden') + 1,
                ]);
                $diagnostico->touch();
            }

            return $categoria;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Categoría «{$categoria->nombre}» creada."]);

        return back();
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($this->validar($request, $categoria));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría guardada.']);

        return back();
    }

    /** A2.3c · Archivar (con respuestas): sale de los borradores (HU-019). */
    public function archivar(Categoria $categoria): RedirectResponse
    {
        DB::transaction(function () use ($categoria): void {
            $this->quitarDeBorradores($categoria);
            $categoria->forceFill(['archivado_en' => now()])->save();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Categoría «{$categoria->nombre}» archivada."]);

        return back();
    }

    /** Vuelve al catálogo; no se agrega sola a los borradores. */
    public function restaurar(Categoria $categoria): RedirectResponse
    {
        $categoria->forceFill(['archivado_en' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Categoría «{$categoria->nombre}» restaurada."]);

        return back();
    }

    /** A2.3c · Eliminar una categoría que nadie ha respondido (RN-009). */
    public function destroy(Categoria $categoria): RedirectResponse
    {
        $datos = collect(DatosDiagnosticos::categorias())->firstWhere('id', $categoria->id);

        if (($datos['tiene_respuestas'] ?? false) === true) {
            throw ValidationException::withMessages(['categoria' => 'Ya tiene respuestas: archívala en lugar de eliminarla.']);
        }

        DB::transaction(function () use ($categoria): void {
            $this->quitarDeBorradores($categoria);
            // Si sigue en algún diagnóstico publicado sin borrador, no se puede borrar la fila.
            if ($categoria->usos()->exists()) {
                throw ValidationException::withMessages(['categoria' => 'Está en un diagnóstico publicado: archívala en lugar de eliminarla.']);
            }
            $categoria->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Categoría «{$categoria->nombre}» eliminada."]);

        return back();
    }

    private function quitarDeBorradores(Categoria $categoria): void
    {
        foreach ($categoria->usos()->with('diagnostico')->get() as $uso) {
            if ($uso->diagnostico && DatosDiagnosticos::esBorrador($uso->diagnostico)) {
                $uso->delete();
                $uso->diagnostico->touch();
            }
        }
    }

    /** @return array{nombre: string, descripcion: string|null} */
    private function validar(Request $request, ?Categoria $categoria = null): array
    {
        /** @var array{nombre: string, descripcion?: string|null} $datos */
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:40', Rule::unique('categorias', 'nombre')->ignore($categoria?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], ['nombre.unique' => 'Ya existe una categoría con ese nombre.']);

        return ['nombre' => trim($datos['nombre']), 'descripcion' => $datos['descripcion'] ?? null];
    }
}
