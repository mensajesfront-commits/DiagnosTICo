<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use App\Models\DiagnosticoCategoria;
use App\Models\Sector;
use App\Rules\NombreDiagnosticoUnico;
use App\Services\Diagnosticos\ContenidoDiagnostico;
use App\Support\DatosDiagnosticos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A2, A2·T, A2b · Diagnósticos por sector y "Todos" (T-050), A2.5 · Crear,
 * A2.7 · Duplicar, archivar y eliminar (HU-010, HU-011, HU-020 a HU-022).
 *
 * [FUNCIONALIDAD POR DEFINIR] El editor (A2.1) llega en la semana 4
 * (T-074, T-081). Mientras tanto, crear y duplicar vuelven a la lista con
 * el borrador nuevo.
 */
class DiagnosticosController extends Controller
{
    public function __construct(private readonly ContenidoDiagnostico $contenido) {}

    public function index(Request $request): Response
    {
        $sector = $request->filled('sector') ? Sector::findOrFail($request->integer('sector')) : null;

        return Inertia::render('diagnosticos/Index', [
            'sectores' => DatosDiagnosticos::sectores(),
            'sector' => $sector ? DatosDiagnosticos::sector($sector) : null,
            'resumen' => DatosDiagnosticos::resumen($sector),
            'diagnosticos' => DatosDiagnosticos::diagnosticos($sector),
        ]);
    }

    public function crear(Request $request): Response
    {
        return Inertia::render('diagnosticos/Crear', [
            'sectores' => DatosDiagnosticos::sectores(soloActivos: true),
            'sectorId' => $request->filled('sector') ? $request->integer('sector') : null,
            'categorias' => DB::table('categorias')->whereNull('archivado_en')->orderBy('nombre')->get(['id', 'nombre']),
            'publicados' => DatosDiagnosticos::publicados(),
        ]);
    }

    /** A2.5 · Crea el borrador v1, en blanco o copiando un publicado (HU-020). */
    public function guardar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60', new NombreDiagnosticoUnico($request->input('sector_id'))],
            'sector_id' => ['required', 'integer', Rule::exists('sectores', 'id')->where('activo', true)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'punto_partida' => ['required', Rule::in(['blanco', 'copia'])],
            'categorias' => ['required_if:punto_partida,blanco', 'array'],
            'categorias.*' => ['integer', Rule::exists('categorias', 'id')->whereNull('archivado_en')],
            'copiar_de' => ['required_if:punto_partida,copia', 'nullable', 'integer', Rule::exists('diagnosticos', 'id')->where('estado', Diagnostico::PUBLICADO)],
        ], [
            'categorias.required_if' => 'Elige al menos una categoría.',
            'copiar_de.required_if' => 'Elige el diagnóstico que quieres copiar.',
        ], ['sector_id' => 'sector', 'copiar_de' => 'diagnóstico a copiar']);

        $diagnostico = DB::transaction(function () use ($datos, $request): Diagnostico {
            $diagnostico = Diagnostico::create([
                'sector_id' => $datos['sector_id'],
                'nombre' => NombreDiagnosticoUnico::limpiar($datos['nombre']),
                'descripcion' => $datos['descripcion'] ?? null,
                'estado' => Diagnostico::BORRADOR,
                'version_borrador' => 1,
                'creado_por' => $request->user()?->id,
            ]);

            if ($datos['punto_partida'] === 'copia') {
                /** @var Diagnostico $origen */
                $origen = Diagnostico::with('ultimaVersion')->findOrFail($datos['copiar_de']);
                $this->contenido->aplicar($diagnostico, $origen->ultimaVersion->contenido ?? []);
            } else {
                $ids = array_values(array_unique($datos['categorias']));
                // Cada categoría empieza con la misma importancia; suman 100 (RN-011).
                foreach (ContenidoDiagnostico::repartir(count($ids)) as $orden => $importancia) {
                    DiagnosticoCategoria::create([
                        'diagnostico_id' => $diagnostico->id,
                        'categoria_id' => $ids[$orden],
                        'importancia' => $importancia,
                        'orden' => $orden,
                    ]);
                }
            }

            return $diagnostico;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Borrador «{$diagnostico->nombre}» creado. El editor llega con la pantalla A2.1."]);

        return to_route('diagnosticos.index', ['sector' => $diagnostico->sector_id]);
    }

    /** A2.7 · Copia el contenido actual en un borrador v1 (HU-021). */
    public function duplicar(Request $request, Diagnostico $diagnostico): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60', new NombreDiagnosticoUnico($request->input('sector_id'))],
            'sector_id' => ['required', 'integer', Rule::exists('sectores', 'id')->where('activo', true)],
        ], [], ['sector_id' => 'sector']);

        $copia = DB::transaction(function () use ($datos, $diagnostico, $request): Diagnostico {
            $copia = Diagnostico::create([
                'sector_id' => $datos['sector_id'],
                'nombre' => NombreDiagnosticoUnico::limpiar($datos['nombre']),
                'descripcion' => $diagnostico->descripcion,
                'estado' => Diagnostico::BORRADOR,
                'version_borrador' => 1,
                'creado_por' => $request->user()?->id,
            ]);
            $this->contenido->copiar($diagnostico, $copia);

            return $copia;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Copia creada como borrador: «{$copia->nombre}»."]);

        return to_route('diagnosticos.index', ['sector' => $copia->sector_id]);
    }

    /** Archiva un diagnóstico publicado: no se asigna más; sus mediciones siguen (HU-022). */
    public function archivar(Diagnostico $diagnostico): RedirectResponse
    {
        abort_unless($diagnostico->estado === Diagnostico::PUBLICADO, 422, 'Solo se archiva un diagnóstico publicado.');

        $diagnostico->forceFill(['estado' => Diagnostico::ARCHIVADO, 'archivado_en' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "«{$diagnostico->nombre}» archivado."]);

        return back();
    }

    /** Elimina un borrador que nunca se publicó (HU-022). */
    public function eliminar(Diagnostico $diagnostico): RedirectResponse
    {
        if ($diagnostico->versiones()->exists()) {
            throw ValidationException::withMessages(['diagnostico' => 'Tiene versiones publicadas: archívalo en lugar de eliminarlo.']);
        }

        $diagnostico->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Borrador «{$diagnostico->nombre}» eliminado."]);

        return back();
    }

    /** Descarta el borrador pendiente (vN) y vuelve a la última versión publicada. */
    public function eliminarBorrador(Diagnostico $diagnostico): RedirectResponse
    {
        $version = $diagnostico->ultimaVersion;
        abort_if($version === null || $diagnostico->version_borrador === null, 422, 'No hay un borrador pendiente.');

        DB::transaction(function () use ($diagnostico, $version): void {
            $this->contenido->aplicar($diagnostico, $version->contenido);
            $diagnostico->forceFill(['version_borrador' => null])->save();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Se descartaron los cambios. «{$diagnostico->nombre}» queda en la v{$version->numero}."]);

        return back();
    }
}
