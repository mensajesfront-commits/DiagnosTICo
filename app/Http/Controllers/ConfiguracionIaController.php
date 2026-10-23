<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Prompt;
use App\Models\Sector;
use App\Models\User;
use App\Services\Ia\Analizador;
use App\Services\Ia\ArmadorPrompt;
use App\Support\DatosConfiguracionIa;
use App\Support\DatosResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * A4 · Configuración de la IA, etapa "Analizar categoría" (HU-041 a HU-045,
 * HU-080). Una sola pantalla con tres alcances: todas las empresas
 * (instrucciones generales, A4), un sector (A4.5) o una empresa (A4.6).
 *
 * Ver: `ia.ver` (también "Ver instrucciones completas", A4.4). Guardar,
 * quitar ajustes y "Probar con un ejemplo" (A4.7, gasta una llamada a la IA):
 * `ia.editar`. Los cambios aplican a los análisis que se hagan después de
 * guardar; los ya hechos guardan su prompt (RN-022).
 */
class ConfiguracionIaController extends Controller
{
    /** Pruebas de "Probar con un ejemplo" al día (PA-003). */
    public const int PRUEBAS_POR_DIA = 100;

    private const array MODOS = ['usar', 'agregar', 'reemplazar'];

    public function index(Request $request): Response
    {
        $alcance = in_array($request->query('alcance'), ['sector', 'empresa'], true) ? (string) $request->query('alcance') : 'general';
        $sectorId = $request->integer('sector') ?: Sector::query()->orderBy('nombre')->value('id');

        return Inertia::render('configuracion-ia/Index', DatosConfiguracionIa::pantalla(
            $alcance,
            $alcance === 'sector' ? $sectorId : null,
            $alcance === 'empresa' ? ($request->integer('empresa') ?: null) : null,
        ));
    }

    /** A4.9 · Cómo se arma el prompt final (solo lectura). */
    public function comoSeArma(): Response
    {
        return Inertia::render('configuracion-ia/ComoSeArma');
    }

    /** Guarda las instrucciones generales o el ajuste de un sector o una empresa. */
    public function guardar(Request $request): RedirectResponse
    {
        $datos = $this->validarBorrador($request);
        /** @var User $yo */
        $yo = $request->user();

        $llave = ['etapa' => ArmadorPrompt::ETAPA, 'alcance' => $datos['alcance'], 'sector_id' => null, 'empresa_id' => null];

        if ($datos['alcance'] !== 'general') {
            $llave["{$datos['alcance']}_id"] = $datos['id'];
        }

        $valores = ['actualizado_por' => $yo->id];

        foreach (ArmadorPrompt::ELEMENTOS as $e) {
            $valores[$e] = trim((string) ($datos['textos'][$e] ?? '')) ?: null;
            $valores["modo_{$e}"] = $datos['alcance'] === 'general' ? 'usar' : $datos['modos'][$e];
        }

        Prompt::updateOrCreate($llave, $valores)->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => match ($datos['alcance']) {
            'general' => 'Instrucciones guardadas. Aplican a los análisis que se hagan desde ahora.',
            default => 'Ajuste guardado. Aplica a los análisis que se hagan desde ahora.',
        }]);

        return back();
    }

    /** HU-042 CA-005, HU-043 CA-004 · Quita el ajuste: vuelven a la capa anterior. */
    public function quitar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'alcance' => ['required', Rule::in(['sector', 'empresa'])],
            'id' => ['required', 'integer'],
        ]);

        Prompt::where('etapa', ArmadorPrompt::ETAPA)->where('alcance', $datos['alcance'])
            ->where("{$datos['alcance']}_id", $datos['id'])->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => $datos['alcance'] === 'sector'
            ? 'Ajuste del sector quitado: sus empresas usan las instrucciones generales.'
            : 'Ajuste de la empresa quitado: usa las instrucciones de su sector.']);

        return back();
    }

    /**
     * A4.4 · El prompt final de un ejemplo real, con los cambios sin guardar
     * de la capa que se está editando. Cada trozo trae su origen.
     */
    public function vistaPrevia(Request $request): JsonResponse
    {
        [$medicion, $categoria, $borrador] = $this->ejemplo($request);
        $armado = app(ArmadorPrompt::class)->armar($medicion, $categoria, DatosResponder::respuestas($medicion), $borrador);
        $exacto = $armado['instrucciones']."\n\n## Preguntas con respuestas\n"
            .json_encode($armado['categoria'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return response()->json([
            'ejemplo' => [
                'empresa' => $medicion->empresa->nombre ?? '',
                'sector' => $armado['categoria']['sector'],
                'categoria' => $armado['categoria']['nombre'],
            ],
            'partes' => collect(ArmadorPrompt::ELEMENTOS)->map(fn (string $e) => [
                'elemento' => $e,
                'titulo' => ArmadorPrompt::TITULOS[$e],
                'piezas' => $armado['partes'][$e] ?? [],
            ])->values()->all(),
            'preguntas' => $armado['categoria']['preguntas'],
            'formato' => ArmadorPrompt::FORMATO,
            'texto_exacto' => $exacto,
            'palabras' => str_word_count($exacto, 0, 'áéíóúñÁÉÍÓÚÑü'),
        ]);
    }

    /**
     * A4.7 · Prueba con un ejemplo real y los cambios sin guardar. Gasta una
     * llamada a la IA; no guarda nada (HU-045 CA-003).
     */
    public function probar(Request $request, Analizador $analizador): JsonResponse
    {
        [$medicion, $categoria, $borrador] = $this->ejemplo($request);
        $armado = app(ArmadorPrompt::class)->armar($medicion, $categoria, DatosResponder::respuestas($medicion), $borrador);

        if ($armado['categoria']['preguntas'] === []) {
            return response()->json(['message' => 'La empresa no respondió ninguna pregunta de esta categoría. Elige otra.'], 422);
        }

        // PA-003 (NuevasTIC, 23 de octubre de 2026): 100 pruebas al día entre
        // todos los administradores, porque cada una gasta OpenAI.
        $limite = self::PRUEBAS_POR_DIA;
        $llave = 'ia_pruebas:'.now(config('diagnostico.zona_horaria'))->toDateString();

        if ((int) Cache::get($llave, 0) >= $limite) {
            return response()->json(['message' => "Ya se hicieron las {$limite} pruebas de hoy. Mañana se puede volver a probar."], 429);
        }

        Cache::add($llave, 0, now()->addDays(2));
        $usadas = (int) Cache::increment($llave);

        try {
            $salida = $analizador->analizar($armado['instrucciones'], $armado['categoria']);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'La IA no respondió bien: '.$e->getMessage().' No se guardó nada.'], 422);
        }

        return response()->json([
            'preguntas' => $armado['categoria']['preguntas'],
            'resultado' => $salida,
            'restantes' => max(0, $limite - $usadas),
        ]);
    }

    /**
     * @return array{0: Medicion, 1: array<string, mixed>, 2: array<string, mixed>|null}
     */
    private function ejemplo(Request $request): array
    {
        $datos = $request->validate([
            'medicion_id' => ['required', 'integer', 'exists:mediciones,id'],
            'categoria_ref' => ['required', 'string'],
            'borrador' => ['nullable', 'array'],
        ]);

        /** @var Medicion $medicion */
        $medicion = Medicion::with(['version', 'empresa:id,nombre'])->findOrFail((int) $datos['medicion_id']);
        /** @var list<array<string, mixed>> $categorias */
        $categorias = $medicion->version->contenido['categorias'] ?? [];
        $categoria = collect($categorias)->firstWhere('ref', $datos['categoria_ref']);
        abort_if($categoria === null, 422, 'La categoría no es de esta medición.');

        $borrador = null;

        if (! empty($datos['borrador'])) {
            $sub = new Request($datos['borrador']);
            $sub->setUserResolver($request->getUserResolver());
            $borrador = $this->validarBorrador($sub, estricto: false);
        }

        return [$medicion, $categoria, $borrador];
    }

    /**
     * @return array{alcance: string, id: int|null, modos: array<string, string>, textos: array<string, string|null>}
     */
    private function validarBorrador(Request $request, bool $estricto = true): array
    {
        $alcance = $request->input('alcance');
        $reglas = [
            'alcance' => ['required', Rule::in(['general', 'sector', 'empresa'])],
            'id' => [Rule::requiredIf($alcance !== 'general'), 'nullable', 'integer'],
            'textos' => ['array'],
            'modos' => ['array'],
        ];

        foreach (ArmadorPrompt::ELEMENTOS as $e) {
            $reglas["textos.{$e}"] = ['nullable', 'string', 'max:4000'];

            if ($alcance !== 'general') {
                $reglas["modos.{$e}"] = ['required', Rule::in(self::MODOS)];

                if ($estricto) {
                    $reglas["textos.{$e}"][] = Rule::requiredIf(fn () => in_array($request->input("modos.{$e}"), ['agregar', 'reemplazar'], true));
                }
            }
        }

        if ($alcance === 'general' && $estricto) {
            $reglas['textos.contexto'][] = 'required';
            $reglas['textos.tarea'][] = 'required';
        }

        if ($alcance === 'sector') {
            $reglas['id'][] = Rule::exists(Sector::class, 'id');
        } elseif ($alcance === 'empresa') {
            $reglas['id'][] = Rule::exists(Empresa::class, 'id');
        }

        $mensajes = [];

        foreach (ArmadorPrompt::ELEMENTOS as $e) {
            $mensajes["textos.{$e}.required"] = $alcance === 'general'
                ? 'Escribe este elemento: la IA lo necesita.'
                : 'Escribe tu texto o elige usar el de la capa anterior.';
            $mensajes["textos.{$e}.max"] = 'Máximo 4000 caracteres.';
        }

        /** @var array{alcance: string, id?: int|null, modos?: array<string, string>, textos?: array<string, string|null>} $valido */
        $valido = $request->validate($reglas, $mensajes);

        return [
            'alcance' => $valido['alcance'],
            'id' => isset($valido['id']) ? (int) $valido['id'] : null,
            'modos' => $valido['modos'] ?? [],
            'textos' => $valido['textos'] ?? [],
        ];
    }
}
