<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use App\Models\DiagnosticoCategoria;
use App\Models\Medicion;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\VersionDiagnostico;
use App\Services\Diagnosticos\ContenidoDiagnostico;
use App\Support\DatosEditor;
use App\Support\EstadoDiagnostico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A2.1 · Editor del diagnóstico (HU-023 a HU-029): preguntas, orden,
 * categorías, importancia (A2.1c), vista previa (A2.4) y publicar (A2.1d).
 *
 * Todo cambio va al borrador (RN-010): si el diagnóstico está publicado y no
 * tiene borrador, el primer cambio abre el siguiente (v2, v3…). Las
 * versiones publicadas no se tocan. Un diagnóstico archivado no se edita.
 * Permiso `diagnosticos.editar` (la vista previa, `diagnosticos.ver`).
 */
class EditorDiagnosticoController extends Controller
{
    public function __construct(private readonly ContenidoDiagnostico $contenido) {}

    /** A2.1 · `?categoria=` (id de la parte) abre esa categoría. */
    public function editar(Request $request, Diagnostico $diagnostico): Response|RedirectResponse
    {
        if ($diagnostico->estado === Diagnostico::ARCHIVADO) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Un diagnóstico archivado no se edita.']);

            return to_route('diagnosticos.index', ['sector' => $diagnostico->sector_id]);
        }

        return Inertia::render('diagnosticos/Editor', [
            ...DatosEditor::editor($diagnostico),
            'parteInicial' => $request->integer('categoria') ?: null,
            'preguntaInicial' => $request->integer('pregunta') ?: null,
        ]);
    }

    /** HU-024 · La pregunta queda al final de su categoría. */
    public function guardarPregunta(Request $request, Diagnostico $diagnostico): RedirectResponse
    {
        $this->abrirBorrador($diagnostico);
        $datos = $this->validarPregunta($request);
        $parte = $this->parte($diagnostico, (int) $request->input('parte_id'));

        DB::transaction(function () use ($parte, $datos): void {
            $pregunta = Pregunta::create([
                ...$this->camposPregunta($datos),
                'diagnostico_categoria_id' => $parte->id,
                'orden' => $parte->preguntas()->count(),
            ]);
            $this->guardarOpciones($pregunta, $datos);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pregunta agregada.']);

        return back();
    }

    /** HU-025 · Editar; las opciones conservan su id (y su referencia). */
    public function actualizarPregunta(Request $request, Diagnostico $diagnostico, Pregunta $pregunta): RedirectResponse
    {
        $this->abrirBorrador($diagnostico);
        $this->deEste($diagnostico, $pregunta);
        $datos = $this->validarPregunta($request, nueva: false);

        DB::transaction(function () use ($pregunta, $datos): void {
            $pregunta->update($this->camposPregunta($datos));
            $this->guardarOpciones($pregunta, $datos);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pregunta guardada.']);

        return back();
    }

    /** HU-025 CA-003 · Las mediciones ya respondidas no cambian (RN-010). */
    public function eliminarPregunta(Diagnostico $diagnostico, Pregunta $pregunta): RedirectResponse
    {
        $this->abrirBorrador($diagnostico);
        $this->deEste($diagnostico, $pregunta);

        DB::transaction(function () use ($pregunta): void {
            $parteId = $pregunta->diagnostico_categoria_id;
            $pregunta->delete();
            $this->renumerar(Pregunta::where('diagnostico_categoria_id', $parteId)->orderBy('orden')->pluck('id')->all());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pregunta eliminada.']);

        return back();
    }

    /** HU-023 CA-004 · Nuevo orden de las preguntas de una categoría. */
    public function ordenarPreguntas(Request $request, Diagnostico $diagnostico, DiagnosticoCategoria $parte): RedirectResponse
    {
        $this->abrirBorrador($diagnostico);
        abort_unless($parte->diagnostico_id === $diagnostico->id, 404);

        $ids = $request->validate(['preguntas' => ['required', 'array'], 'preguntas.*' => ['integer']])['preguntas'];
        $actuales = $parte->preguntas()->pluck('id')->all();

        if (array_diff($actuales, $ids) !== [] || count(array_unique($ids)) !== count($actuales)) {
            throw ValidationException::withMessages(['preguntas' => 'El orden no coincide con las preguntas de la categoría. Recarga la página.']);
        }

        DB::transaction(fn () => $this->renumerar(array_map(fn ($id) => (int) $id, (array) $ids)));

        return back();
    }

    /**
     * HU-026 · Agrega categorías del catálogo (no archivadas). `reparto`:
     * `mantener` (las actuales siguen igual, las nuevas en 0%) o `iguales`.
     */
    public function agregarCategorias(Request $request, Diagnostico $diagnostico): RedirectResponse
    {
        $this->abrirBorrador($diagnostico);
        $datos = $request->validate([
            'categorias' => ['required', 'array', 'min:1'],
            'categorias.*' => ['integer', Rule::exists('categorias', 'id')->whereNull('archivado_en')],
            'reparto' => ['required', Rule::in(['mantener', 'iguales'])],
        ], ['categorias.required' => 'Elige al menos una categoría.'], ['categorias.*' => 'categoría']);

        $ya = $diagnostico->partes()->pluck('categoria_id')->all();
        $nuevas = array_values(array_diff(array_unique(array_map('intval', $datos['categorias'])), $ya));

        DB::transaction(function () use ($diagnostico, $nuevas, $datos): void {
            $orden = (int) $diagnostico->partes()->max('orden');

            foreach ($nuevas as $id) {
                DiagnosticoCategoria::create(['diagnostico_id' => $diagnostico->id, 'categoria_id' => $id, 'importancia' => 0, 'orden' => ++$orden]);
            }

            if ($datos['reparto'] === 'iguales') {
                $partes = $diagnostico->partes()->get();

                foreach (ContenidoDiagnostico::repartir($partes->count()) as $i => $importancia) {
                    $partes[$i]->update(['importancia' => $importancia]);
                }
            }
        });

        $n = count($nuevas);
        Inertia::flash('toast', ['type' => 'success', 'message' => $n === 1 ? 'Categoría agregada. Agrégale al menos 1 pregunta.' : "{$n} categorías agregadas."]);

        return back();
    }

    /** A2.1c · Editar importancia (HU-027). */
    public function importancia(Diagnostico $diagnostico): Response
    {
        abort_if($diagnostico->estado === Diagnostico::ARCHIVADO, 404);
        $diagnostico->load(['sector:id,nombre', 'partes.categoria:id,nombre', 'versiones']);
        $estado = EstadoDiagnostico::calcular($this->contenido->congelar($diagnostico));

        return Inertia::render('diagnosticos/Importancia', [
            'diagnostico' => DatosEditor::cabecera($diagnostico, $estado),
            'partes' => $diagnostico->partes->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->categoria->nombre ?? '',
                'importancia' => (float) $p->importancia,
            ])->values()->all(),
            'pendientes' => DatosEditor::pendientesConUltima($diagnostico),
        ]);
    }

    /** Guarda la importancia; debe sumar exactamente 100 (RN-011). */
    public function guardarImportancia(Request $request, Diagnostico $diagnostico): RedirectResponse
    {
        $this->abrirBorrador($diagnostico);
        $partes = $diagnostico->partes()->get()->keyBy('id');
        $datos = $request->validate([
            'importancias' => ['required', 'array'],
            'importancias.*' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
        ], [], ['importancias.*' => 'importancia']);

        /** @var array<int|string, float|int|string> $importancias */
        $importancias = $datos['importancias'];

        if (array_diff(array_keys($importancias), $partes->keys()->all()) !== [] || count($importancias) !== $partes->count()) {
            throw ValidationException::withMessages(['importancias' => 'Las categorías cambiaron. Recarga la página.']);
        }

        $total = round(array_sum(array_map('floatval', $importancias)), 2);

        if (abs($total - 100) >= 0.005) {
            throw ValidationException::withMessages(['importancias' => "El total debe sumar 100% (ahora {$total}%)."]);
        }

        DB::transaction(function () use ($partes, $importancias): void {
            foreach ($importancias as $id => $valor) {
                $partes[$id]->update(['importancia' => (float) $valor]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Importancia guardada.']);

        return to_route('diagnosticos.editar', $diagnostico);
    }

    /**
     * A2.4 · Vista previa del borrador, o de una versión publicada en solo
     * lectura con `?version=`.
     */
    public function vistaPrevia(Request $request, Diagnostico $diagnostico): Response
    {
        $diagnostico->load(['sector:id,nombre', 'partes.categoria:id,nombre', 'partes.preguntas.opciones', 'versiones']);
        $numero = $request->integer('version') ?: null;
        $version = $numero ? $diagnostico->versiones->firstWhere('numero', $numero) : null;
        abort_if($numero && ! $version, 404);

        $contenido = $version ? $version->contenido : $this->contenido->congelar($diagnostico);
        // Para "Corregir": de la referencia de la categoría a su parte en el borrador.
        $partes = $diagnostico->partes->mapWithKeys(fn ($p) => ['c'.$p->categoria_id => $p->id])->all();
        $estado = EstadoDiagnostico::calcular($contenido);

        return Inertia::render('diagnosticos/VistaPrevia', [
            'diagnostico' => DatosEditor::cabecera($diagnostico, EstadoDiagnostico::calcular($this->contenido->congelar($diagnostico))),
            'version' => $version ? ['numero' => $version->numero, 'publicada_en' => $version->publicada_en->toIso8601String()] : null,
            'categorias' => array_map(fn (array $c) => [
                'ref' => $c['ref'],
                'parte_id' => $partes[$c['ref']] ?? null,
                'nombre' => $c['nombre'],
                'importancia' => $c['importancia'],
                'problemas' => $estado['categorias'][$c['ref']] ?? [],
                'preguntas' => array_map(fn (array $p) => [
                    'ref' => $p['ref'],
                    'id' => (int) substr((string) $p['ref'], 1),
                    'texto' => $p['texto'],
                    'tipo' => $p['tipo'],
                    'indicacion' => $p['indicacion'],
                    'obligatoria' => $p['obligatoria'],
                    'problemas' => $estado['preguntas'][$p['ref']] ?? [],
                    'opciones' => array_map(fn (array $o) => ['ref' => $o['ref'], 'texto' => $o['texto'], 'exclusiva' => (bool) ($o['exclusiva'] ?? false)], $p['opciones']),
                ], $c['preguntas']),
            ], $contenido['categorias'] ?? []),
            'estado' => DatosEditor::estado($estado),
            'versiones' => DatosEditor::versiones($diagnostico),
            'pendientes' => DatosEditor::pendientesConUltima($diagnostico),
        ]);
    }

    /**
     * A2.1d · Publica el borrador como la versión siguiente (HU-029): nota
     * obligatoria y los 5 requisitos cumplidos. `pendientes`: `dejar` las
     * mediciones pendientes con su versión o `pasar` a la nueva las que aún
     * no tienen respuestas.
     */
    public function publicar(Request $request, Diagnostico $diagnostico): RedirectResponse
    {
        abort_if($diagnostico->estado === Diagnostico::ARCHIVADO, 404);
        $datos = $request->validate([
            'nota' => ['required', 'string', 'max:500'],
            'pendientes' => ['required', Rule::in(['dejar', 'pasar'])],
        ], ['nota.required' => 'Escribe qué cambió en esta versión.'], ['nota' => 'nota de cambios']);

        $diagnostico->load('versiones');

        if ($diagnostico->estado === Diagnostico::PUBLICADO && $diagnostico->version_borrador === null) {
            throw ValidationException::withMessages(['nota' => 'No hay cambios sin publicar.']);
        }

        $contenido = $this->contenido->congelar($diagnostico);

        if (! EstadoDiagnostico::calcular($contenido)['publicable']) {
            throw ValidationException::withMessages(['nota' => 'Corrige lo que falta antes de publicar.']);
        }

        $version = DB::transaction(function () use ($diagnostico, $contenido, $datos, $request): VersionDiagnostico {
            $anteriores = $diagnostico->versiones->modelKeys();
            $version = VersionDiagnostico::create([
                'diagnostico_id' => $diagnostico->id,
                'numero' => ((int) $diagnostico->versiones->max('numero')) + 1,
                'nota_cambios' => trim($datos['nota']),
                'contenido' => $contenido,
                'publicada_por' => $request->user()?->id,
                'publicada_en' => now(),
            ]);

            if ($datos['pendientes'] === 'pasar' && $anteriores !== []) {
                Medicion::query()
                    ->whereIn('version_diagnostico_id', $anteriores)
                    ->whereIn('estado', ['no_iniciada', 'vencida'])
                    ->whereDoesntHave('respuestas')
                    ->update(['version_diagnostico_id' => $version->id, 'updated_at' => now()]);
            }

            $diagnostico->forceFill(['estado' => Diagnostico::PUBLICADO, 'version_borrador' => null])->save();

            return $version;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "«{$diagnostico->nombre}» v{$version->numero} publicada. Ya se puede asignar."]);

        return to_route('diagnosticos.index', ['sector' => $diagnostico->sector_id]);
    }

    /**
     * Antes de cambiar algo: no archivado y, si está publicado sin borrador,
     * abre el siguiente (RN-010). Marca la hora de "Borrador guardado".
     */
    private function abrirBorrador(Diagnostico $diagnostico): void
    {
        if ($diagnostico->estado === Diagnostico::ARCHIVADO) {
            throw ValidationException::withMessages(['diagnostico' => 'Un diagnóstico archivado no se edita.']);
        }

        if ($diagnostico->version_borrador === null) {
            $diagnostico->version_borrador = ((int) $diagnostico->versiones()->max('numero')) + 1;
        }

        $diagnostico->touch();
    }

    private function parte(Diagnostico $diagnostico, int $id): DiagnosticoCategoria
    {
        $parte = DiagnosticoCategoria::where('diagnostico_id', $diagnostico->id)->find($id);

        if (! $parte) {
            throw ValidationException::withMessages(['parte_id' => 'Elige una categoría de este diagnóstico.']);
        }

        return $parte;
    }

    private function deEste(Diagnostico $diagnostico, Pregunta $pregunta): void
    {
        abort_unless($pregunta->parte?->diagnostico_id === $diagnostico->id, 404);
    }

    /**
     * Reglas de HU-024 y RN-013.
     *
     * @return array<string, mixed>
     */
    private function validarPregunta(Request $request, bool $nueva = true): array
    {
        $conOpciones = $request->input('tipo') !== 'abierta';

        return $request->validate([
            ...($nueva ? ['parte_id' => ['required', 'integer']] : []),
            'texto' => ['required', 'string', 'max:500'],
            'tipo' => ['required', Rule::in(Pregunta::TIPOS)],
            'indicacion' => ['required', 'string', 'max:500'],
            'criterio_ia' => ['nullable', 'string', 'max:1000'],
            'obligatoria' => ['boolean'],
            'opciones' => $conOpciones ? ['required', 'array', 'min:2', 'max:10'] : ['nullable', 'array'],
            'opciones.*.id' => ['nullable', 'integer'],
            'opciones.*.texto' => $conOpciones ? ['required', 'string', 'max:255'] : ['nullable'],
            'opciones.*.puntaje' => $conOpciones ? ['required', 'integer', 'min:0', 'max:100'] : ['nullable'],
            'opciones.*.ten_en_cuenta' => ['nullable', 'string', 'max:200'],
            'opciones.*.exclusiva' => ['boolean'],
        ], [
            'indicacion.required' => 'Escribe la indicación para responder: la empresa la ve debajo de la pregunta.',
            'opciones.required' => 'Agrega al menos 2 opciones con su puntaje.',
            'opciones.min' => 'Agrega al menos 2 opciones con su puntaje.',
            'opciones.*.texto.required' => 'Escribe el texto de la opción.',
            'opciones.*.puntaje.required' => 'Pon el puntaje (0 a 100).',
        ], [
            'texto' => 'texto de la pregunta',
            'indicacion' => 'indicación para responder',
            'criterio_ia' => 'criterio de calificación',
            'opciones.*.puntaje' => 'puntaje',
            'opciones.*.ten_en_cuenta' => 'indicación para la IA',
        ]);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function camposPregunta(array $datos): array
    {
        return [
            'texto' => trim((string) $datos['texto']),
            'tipo' => $datos['tipo'],
            'indicacion' => trim((string) $datos['indicacion']),
            'criterio_ia' => $datos['tipo'] === 'abierta' ? ($datos['criterio_ia'] ?? null) : null,
            'obligatoria' => (bool) ($datos['obligatoria'] ?? true),
        ];
    }

    /**
     * Las opciones con `id` se actualizan, las nuevas se crean y las que ya
     * no vienen se borran. Una abierta no tiene opciones.
     *
     * @param  array<string, mixed>  $datos
     */
    private function guardarOpciones(Pregunta $pregunta, array $datos): void
    {
        /** @var list<array<string, mixed>> $opciones */
        $opciones = $datos['tipo'] === 'abierta' ? [] : ($datos['opciones'] ?? []);
        // Solo en selección múltiple, y una sola.
        $exclusiva = $datos['tipo'] === 'seleccion_multiple'
            ? collect($opciones)->search(fn (array $o) => (bool) ($o['exclusiva'] ?? false))
            : false;
        $actuales = $pregunta->opciones()->get()->keyBy('id');
        $quedan = [];

        foreach ($opciones as $orden => $o) {
            $campos = [
                'texto' => trim((string) $o['texto']),
                'puntaje' => (int) $o['puntaje'],
                'ten_en_cuenta' => isset($o['ten_en_cuenta']) && trim((string) $o['ten_en_cuenta']) !== '' ? trim((string) $o['ten_en_cuenta']) : null,
                'exclusiva' => $exclusiva === $orden,
                'orden' => $orden,
            ];
            $id = isset($o['id']) ? (int) $o['id'] : null;

            if ($id && $actuales->has($id)) {
                $actuales[$id]->update($campos);
                $quedan[] = $id;
            } else {
                Opcion::create([...$campos, 'pregunta_id' => $pregunta->id]);
            }
        }

        $pregunta->opciones()->whereNotIn('id', $quedan)->whereIn('id', $actuales->keys())->delete();
    }

    /** @param  array<int, int>  $ids */
    private function renumerar(array $ids): void
    {
        foreach (array_values($ids) as $orden => $id) {
            Pregunta::whereKey($id)->update(['orden' => $orden]);
        }
    }
}
