<?php

namespace App\Http\Controllers;

use App\Models\Medicion;
use App\Models\Resultado;
use App\Models\User;
use App\Services\Resultados\InformePdf;
use App\Support\DatosResultado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * E6 · Resultado (HU-064), E9 · Mis respuestas (HU-065) y E10 · PDF
 * (HU-066). El Administrador ve las mismas pantallas desde la ficha de la
 * empresa (A3.3 y A3.5).
 *
 * E10 tiene dos PDF (decisión del 4 de noviembre): la empresa y su
 * colaborador reciben siempre el resumen corto; el Administrador y el
 * Consultor eligen entre el informe completo (por defecto) y el resumen
 * (`?tipo=resumen`), por ejemplo para entregárselo a la empresa.
 *
 * Permisos `resultados.ver` y `resultados.pdf`. La empresa y su colaborador
 * solo ven su empresa (RN-007; de otra, 404). Un usuario interno, además,
 * necesita `empresas.ver`; el consultor, que la empresa esté vinculada.
 */
class ResultadosController extends Controller
{
    public function ver(Request $request, Medicion $medicion): Response|RedirectResponse
    {
        if ($salida = $this->siNoHay($request, $medicion)) {
            return $salida;
        }

        $resultado = $this->resultado($medicion);

        return Inertia::render('resultados/Resultado', [
            'medicion' => DatosResultado::cabecera($medicion),
            'resultado' => DatosResultado::resultado($resultado),
            'interno' => $this->interno($request),
            // A3.3 · Para pasar de una medición a otra sin volver a la ficha.
            'mediciones' => $this->interno($request) ? DatosResultado::mediciones($medicion) : [],
        ]);
    }

    public function respuestas(Request $request, Medicion $medicion): Response|RedirectResponse
    {
        if ($salida = $this->siNoHay($request, $medicion)) {
            return $salida;
        }

        $resultado = $this->resultado($medicion);

        return Inertia::render('resultados/Respuestas', [
            'medicion' => DatosResultado::cabecera($medicion),
            'categorias' => DatosResultado::conNivelPorPregunta($resultado->por_categoria),
            'inicial' => $request->string('categoria')->toString() ?: null,
            'interno' => $this->interno($request),
        ]);
    }

    /**
     * Descarga el PDF guardado (RN-024). Si no está (falló al publicar o se
     * borró), lo genera otra vez con lo guardado, sin llamar a la IA.
     */
    public function pdf(Request $request, Medicion $medicion): StreamedResponse|RedirectResponse
    {
        $this->puedeVer($request, $medicion);
        abort_unless($medicion->estado === 'terminada', 404);

        // La empresa no recibe el completo aunque lo pida en la URL.
        $tipo = $this->interno($request) && $request->query('tipo') !== InformePdf::RESUMEN
            ? InformePdf::COMPLETO
            : InformePdf::RESUMEN;
        $columna = InformePdf::columna($tipo);

        $resultado = $this->resultado($medicion);
        $disco = Storage::disk('local');

        if ($resultado->{$columna} === null || ! $disco->exists($resultado->{$columna})) {
            try {
                InformePdf::generar($resultado, $tipo);
            } catch (Throwable $e) {
                report($e);
            }
        }

        $ruta = $resultado->{$columna};

        if (! is_string($ruta) || ! $disco->exists($ruta)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No pudimos generar el PDF. Inténtalo de nuevo en un momento.']);

            return back();
        }

        return $disco->download($ruta, InformePdf::nombreArchivo($resultado, $tipo));
    }

    /** Sin resultado todavía: la empresa vuelve a la espera (E4); los demás, 404. */
    private function siNoHay(Request $request, Medicion $medicion): ?RedirectResponse
    {
        $this->puedeVer($request, $medicion);
        // Medición sin nombre (empresa borrada): solo cuenta para estadísticas.
        abort_if($medicion->empresa_id === null, 404);

        if ($medicion->estado === 'terminada') {
            return null;
        }

        abort_unless($medicion->estado === 'enviada' && ! $this->interno($request), 404);

        return to_route('responder.enviado', $medicion);
    }

    private function resultado(Medicion $medicion): Resultado
    {
        return Resultado::with('medicion')->where('medicion_id', $medicion->id)->firstOrFail();
    }

    private function puedeVer(Request $request, Medicion $medicion): void
    {
        /** @var User $yo */
        $yo = $request->user();

        // Su empresa; o, si es interna, con `empresas.ver` (el consultor,
        // solo las vinculadas, RN-030).
        abort_unless($medicion->empresa_id !== null && $yo->puedeVerEmpresa($medicion->empresa_id), 404);
    }

    private function interno(Request $request): bool
    {
        /** @var User $yo */
        $yo = $request->user();

        return $yo->empresa_id === null;
    }
}
