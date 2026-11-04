<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use App\Models\VersionDiagnostico;
use App\Services\Diagnosticos\ContenidoDiagnostico;
use App\Services\Resultados\InformePdf;
use App\Services\Resultados\ResultadoEjemplo;
use App\Support\DatosResultado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * A2.6 · Resultado de ejemplo (HU-030): cómo se vería el resultado de un
 * diagnóstico con respuestas de muestra (mejor caso, caso medio, peor caso),
 * antes de publicarlo. Usa la misma pantalla que E6 y el mismo PDF.
 *
 * `?version=N` usa una versión publicada; sin ella, el borrador actual.
 * No llama a la IA ni guarda nada. Permiso `diagnosticos.ver`.
 */
class ResultadoEjemploController extends Controller
{
    public function __construct(private readonly ContenidoDiagnostico $contenido) {}

    public function ver(Request $request, Diagnostico $diagnostico): Response|RedirectResponse
    {
        [$contenido, $version] = $this->contenidoDe($request, $diagnostico);

        if (($contenido['categorias'] ?? []) === []) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Agrega categorías y preguntas para ver un resultado de ejemplo.']);

            return to_route('diagnosticos.vista-previa', $diagnostico);
        }

        $caso = $this->caso($request);

        return Inertia::render('resultados/Resultado', [
            'medicion' => $this->cabecera($diagnostico, $version),
            'resultado' => DatosResultado::pagina(ResultadoEjemplo::calcular($contenido, $caso), null, now(), false),
            'interno' => true,
            'ejemplo' => [
                'caso' => $caso,
                'etiqueta' => $this->numeroYEtiqueta($diagnostico, $version)[1],
                'volver' => route('diagnosticos.vista-previa', array_filter(['diagnostico' => $diagnostico->id, 'version' => $version?->numero])),
                'pdf' => route('diagnosticos.resultado-ejemplo.pdf', array_filter(['diagnostico' => $diagnostico->id, 'caso' => $caso, 'version' => $version?->numero])),
                'pdf_resumen' => route('diagnosticos.resultado-ejemplo.pdf', array_filter(['diagnostico' => $diagnostico->id, 'caso' => $caso, 'version' => $version?->numero, 'tipo' => InformePdf::RESUMEN])),
            ],
        ]);
    }

    /** "Ver formato del PDF": el informe completo o el resumen de la empresa (`?tipo=resumen`), en el navegador. */
    public function pdf(Request $request, Diagnostico $diagnostico): PdfBuilder
    {
        [$contenido, $version] = $this->contenidoDe($request, $diagnostico);
        abort_if(($contenido['categorias'] ?? []) === [], 404);

        $datos = DatosResultado::pagina(ResultadoEjemplo::calcular($contenido, $this->caso($request)), null, now(), false);

        $tipo = $request->query('tipo') === InformePdf::RESUMEN ? InformePdf::RESUMEN : InformePdf::COMPLETO;

        return InformePdf::desde(DatosResultado::informeDe($this->cabecera($diagnostico, $version), $datos, null, now(), ejemplo: true), $tipo)
            ->inline($tipo === InformePdf::RESUMEN ? 'resumen_de_ejemplo.pdf' : 'informe_de_ejemplo.pdf');
    }

    /** @return array{0: array<string, mixed>, 1: VersionDiagnostico|null} */
    private function contenidoDe(Request $request, Diagnostico $diagnostico): array
    {
        $numero = $request->integer('version') ?: null;
        $version = $numero ? $diagnostico->versiones()->where('numero', $numero)->first() : null;
        abort_if($numero && ! $version, 404);

        return [$version ? $version->contenido : $this->contenido->congelar($diagnostico), $version];
    }

    private function caso(Request $request): string
    {
        $caso = $request->string('caso')->toString();

        return in_array($caso, ResultadoEjemplo::CASOS, true) ? $caso : 'medio';
    }

    /** @return array<string, mixed> */
    private function cabecera(Diagnostico $diagnostico, ?VersionDiagnostico $version): array
    {
        $diagnostico->loadMissing('sector:id,nombre');

        return [
            'id' => 0,
            'numero' => 1,
            'empresa' => ['id' => 0, 'nombre' => 'Empresa de ejemplo'],
            'sector' => $diagnostico->sector->nombre ?? '',
            'diagnostico' => $diagnostico->nombre,
            'version' => $this->numeroYEtiqueta($diagnostico, $version)[0],
            'enviada_en' => null,
            'terminada_en' => null,
        ];
    }

    /**
     * Qué versión se muestra: la pedida con ?version, el borrador pendiente
     * o, si no hay borrador, la última publicada (su contenido es el mismo).
     *
     * @return array{0: int, 1: string}
     */
    private function numeroYEtiqueta(Diagnostico $diagnostico, ?VersionDiagnostico $version): array
    {
        if ($version !== null) {
            return [$version->numero, "Versión {$version->numero}"];
        }

        if ($diagnostico->version_borrador !== null) {
            return [$diagnostico->version_borrador, "Borrador v{$diagnostico->version_borrador}"];
        }

        $ultima = (int) $diagnostico->versiones()->max('numero');

        return $ultima > 0 ? [$ultima, "Versión {$ultima}"] : [1, 'Borrador v1'];
    }
}
