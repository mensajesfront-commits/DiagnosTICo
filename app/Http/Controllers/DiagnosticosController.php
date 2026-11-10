<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use App\Models\DiagnosticoCategoria;
use App\Models\Sector;
use App\Rules\NombreDiagnosticoUnico;
use App\Services\Diagnosticos\ContenidoDiagnostico;
use App\Services\Diagnosticos\LectorPlantilla;
use App\Support\CatalogoCiiu;
use App\Support\DatosDiagnosticos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A2, A2·T, A2b · Diagnósticos por sector y "Todos" (T-050), A2.5 · Crear,
 * A2.7 · Duplicar, archivar y eliminar (HU-010, HU-011, HU-020 a HU-022).
 * A2.5 también crea el borrador pegando su contenido en la plantilla (HU-020 CA-005).
 *
 * Crear y duplicar llevan al editor (A2.1, `EditorDiagnosticoController`).
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
            // Ventana "Archivados" (HU-022): restaurar o eliminar.
            'archivados' => DatosDiagnosticos::archivados($sector),
            // Para elegir la división CIIU al crear o editar un sector (DEC-018).
            'divisionesCiiu' => CatalogoCiiu::divisiones(),
        ]);
    }

    public function crear(Request $request): Response
    {
        return Inertia::render('diagnosticos/Crear', [
            'sectores' => DatosDiagnosticos::sectores(soloActivos: true),
            'sectorId' => $request->filled('sector') ? $request->integer('sector') : null,
            'categorias' => self::categoriasActivas(),
            'publicados' => DatosDiagnosticos::publicados(),
            // HU-020 CA-005: la plantilla de ejemplo que se copia para pegar el contenido.
            'plantilla' => LectorPlantilla::EJEMPLO,
        ]);
    }

    /**
     * A2.5 · «Pegar el contenido» (HU-020 CA-005): lee el texto sin guardar nada y
     * devuelve qué se entendió, los errores que impiden crearlo y lo que
     * faltaría para publicarlo, con su número de línea.
     */
    public function revisarPlantilla(Request $request, LectorPlantilla $lector): JsonResponse
    {
        $datos = $request->validate([
            'texto' => ['required', 'string', 'max:'.LectorPlantilla::MAXIMO],
        ], ['texto.required' => 'Pega el contenido del diagnóstico.'], ['texto' => 'contenido']);

        $lectura = $lector->leer($datos['texto'], self::categoriasActivas());

        return response()->json([
            'resumen' => $lectura['resumen'],
            'errores' => $lectura['errores'],
            'avisos' => $lectura['avisos'],
        ]);
    }

    /** A2.5 · Crea el borrador v1, en blanco o copiando un publicado (HU-020). */
    public function guardar(Request $request, LectorPlantilla $lector): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60', new NombreDiagnosticoUnico($request->input('sector_id'))],
            'sector_id' => ['required', 'integer', Rule::exists('sectores', 'id')->where('activo', true)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'punto_partida' => ['required', Rule::in(['blanco', 'copia', 'pegar'])],
            'categorias' => ['required_if:punto_partida,blanco', 'array'],
            'categorias.*' => ['integer', Rule::exists('categorias', 'id')->whereNull('archivado_en')],
            'copiar_de' => ['required_if:punto_partida,copia', 'nullable', 'integer', Rule::exists('diagnosticos', 'id')->where('estado', Diagnostico::PUBLICADO)],
            'texto' => ['required_if:punto_partida,pegar', 'nullable', 'string', 'max:'.LectorPlantilla::MAXIMO],
        ], [
            'categorias.required_if' => 'Elige al menos una categoría.',
            'copiar_de.required_if' => 'Elige el diagnóstico que quieres copiar.',
            'texto.required_if' => 'Pega el contenido del diagnóstico.',
        ], ['sector_id' => 'sector', 'copiar_de' => 'diagnóstico a copiar', 'texto' => 'contenido']);

        // HU-020 CA-005: el texto se vuelve a leer aquí; con errores no se crea nada.
        $lectura = null;

        if ($datos['punto_partida'] === 'pegar') {
            $lectura = $lector->leer((string) $datos['texto'], self::categoriasActivas());

            if ($lectura['errores'] !== []) {
                $primeros = array_map(fn (array $e) => "línea {$e['linea']}: {$e['mensaje']}", array_slice($lectura['errores'], 0, 3));
                $total = count($lectura['errores']);

                throw ValidationException::withMessages([
                    'texto' => ($total === 1 ? 'El contenido tiene 1 error' : "El contenido tiene {$total} errores").' · '.implode(' · ', $primeros),
                ]);
            }
        }

        $diagnostico = DB::transaction(function () use ($datos, $request, $lectura): Diagnostico {
            $diagnostico = Diagnostico::create([
                'sector_id' => $datos['sector_id'],
                'nombre' => NombreDiagnosticoUnico::limpiar($datos['nombre']),
                'descripcion' => $datos['descripcion'] ?? null,
                'estado' => Diagnostico::BORRADOR,
                'version_borrador' => 1,
                'creado_por' => $request->user()?->id,
            ]);

            if ($lectura !== null) {
                $this->contenido->aplicar($diagnostico, $lectura['contenido']);
            } elseif ($datos['punto_partida'] === 'copia') {
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

        $mensaje = match (true) {
            $lectura === null => "Borrador «{$diagnostico->nombre}» creado. Agrega sus preguntas.",
            $lectura['avisos'] === [] => "Borrador «{$diagnostico->nombre}» creado con {$lectura['resumen']['preguntas']} preguntas. Revísalo y publícalo.",
            default => "Borrador «{$diagnostico->nombre}» creado con {$lectura['resumen']['preguntas']} preguntas. Corrige lo que falta para publicarlo.",
        };
        Inertia::flash('toast', ['type' => 'success', 'message' => $mensaje]);

        return to_route('diagnosticos.editar', $diagnostico);
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

        return to_route('diagnosticos.editar', $copia);
    }

    /** Archiva un diagnóstico publicado: no se asigna más; sus mediciones siguen (HU-022). */
    public function archivar(Diagnostico $diagnostico): RedirectResponse
    {
        abort_unless($diagnostico->estado === Diagnostico::PUBLICADO, 422, 'Solo se archiva un diagnóstico publicado.');

        $diagnostico->forceFill(['estado' => Diagnostico::ARCHIVADO, 'archivado_en' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "«{$diagnostico->nombre}» archivado."]);

        return back();
    }

    /** Saca un diagnóstico del archivo: vuelve a publicado y se puede asignar. */
    public function restaurar(Diagnostico $diagnostico): RedirectResponse
    {
        abort_unless($diagnostico->estado === Diagnostico::ARCHIVADO, 422, 'El diagnóstico no está archivado.');

        $diagnostico->forceFill(['estado' => Diagnostico::PUBLICADO, 'archivado_en' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "«{$diagnostico->nombre}» volvió a publicados."]);

        return back();
    }

    /**
     * Elimina para siempre un diagnóstico archivado, con sus versiones. Solo
     * si ninguna empresa lo respondió: los resultados guardan la versión con
     * la que se hicieron y no se pueden perder (RN-010, RN-023).
     */
    public function eliminarArchivado(Diagnostico $diagnostico): RedirectResponse
    {
        abort_unless($diagnostico->estado === Diagnostico::ARCHIVADO, 422, 'Solo se eliminan diagnósticos archivados.');

        $mediciones = $diagnostico->mediciones()->count();

        if ($mediciones > 0) {
            throw ValidationException::withMessages(['diagnostico' => "No se puede eliminar: tiene {$mediciones} ".($mediciones === 1 ? 'medición' : 'mediciones').' de empresas y sus resultados dependen de él. Puede quedarse archivado.']);
        }

        DB::transaction(function () use ($diagnostico): void {
            $diagnostico->versiones()->delete();
            $diagnostico->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "«{$diagnostico->nombre}» eliminado para siempre."]);

        return back();
    }

    /**
     * Elimina varios diagnósticos a la vez (A2, selección en la tabla). Solo
     * los que nunca se publicaron; si alguno tiene versiones, no se elimina
     * ninguno y se dice cuáles hay que archivar.
     */
    public function eliminarVarios(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'distinct', Rule::exists('diagnosticos', 'id')],
        ], ['ids.required' => 'Elige al menos un diagnóstico.']);

        $diagnosticos = Diagnostico::whereIn('id', $datos['ids'])->withExists('versiones')->get();
        $publicados = $diagnosticos->where('versiones_exists', true)->pluck('nombre');

        if ($publicados->isNotEmpty()) {
            throw ValidationException::withMessages([
                'ids' => 'Tienen versiones publicadas y no se eliminan (archívalos): '.$publicados->join(', ', ' y ').'.',
            ]);
        }

        DB::transaction(fn () => Diagnostico::whereKey($diagnosticos->modelKeys())->delete());

        $n = $diagnosticos->count();
        Inertia::flash('toast', ['type' => 'success', 'message' => $n === 1 ? 'Se eliminó 1 diagnóstico.' : "Se eliminaron {$n} diagnósticos."]);

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

    /** @return Collection<int, object{id: int, nombre: string}> */
    private static function categoriasActivas(): Collection
    {
        /** @var Collection<int, object{id: int, nombre: string}> */
        return DB::table('categorias')->whereNull('archivado_en')->orderBy('nombre')->get(['id', 'nombre']);
    }
}
