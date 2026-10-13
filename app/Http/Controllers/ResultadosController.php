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
 * Permisos `resultados.ver` y `resultados.pdf`. La empresa y su colaborador
 * solo ven su empresa (RN-007; de otra, 404). Un usuario interno, además,
 * necesita `empresas.ver`.
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
            'categorias' => $resultado->por_categoria,
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

        $resultado = $this->resultado($medicion);
        $disco = Storage::disk('local');

        if ($resultado->pdf_ruta === null || ! $disco->exists($resultado->pdf_ruta)) {
            try {
                InformePdf::generar($resultado);
            } catch (Throwable $e) {
                report($e);
            }
        }

        if ($resultado->pdf_ruta === null || ! $disco->exists($resultado->pdf_ruta)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No pudimos generar el PDF. Inténtalo de nuevo en un momento.']);

            return back();
        }

        return $disco->download($resultado->pdf_ruta, InformePdf::nombreArchivo($resultado));
    }

    /** Sin resultado todavía: la empresa vuelve a la espera (E4); los demás, 404. */
    private function siNoHay(Request $request, Medicion $medicion): ?RedirectResponse
    {
        $this->puedeVer($request, $medicion);

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

        if ($yo->empresa_id !== null) {
            abort_unless($medicion->empresa_id === $yo->empresa_id, 404);
        } else {
            abort_unless($yo->can('empresas.ver'), 404);
        }
    }

    private function interno(Request $request): bool
    {
        /** @var User $yo */
        $yo = $request->user();

        return $yo->empresa_id === null;
    }
}
