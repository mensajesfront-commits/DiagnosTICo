<?php

namespace App\Jobs;

use App\Models\AnalisisCategoria;
use App\Models\Respuesta;
use App\Models\User;
use App\Notifications\AnalisisFallido;
use App\Services\Ia\Analizador;
use App\Services\Ia\ArmadorPrompt;
use App\Services\Ia\RespuestaIaInvalida;
use App\Services\Resultados\PublicarResultado;
use App\Support\DatosResponder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * HU-069 · Analiza una categoría con la IA (RN-021).
 *
 * Si falla (error de red o respuesta fuera del formato fijo), la cola
 * reintenta solo esta categoría, hasta 3 intentos en total. Tras el tercero
 * queda "fallido", se avisa por correo a los administradores y la empresa ve
 * el aviso con "Reintentar" en E4. Las demás categorías conservan su análisis.
 *
 * Al terminar, intenta publicar el resultado (solo pasa si ya están todas).
 */
class AnalizarCategoria implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var list<int> Segundos de espera antes del 2.º y del 3.er intento. */
    public array $backoff = [15, 60];

    public function __construct(public int $analisisId) {}

    public function handle(Analizador $analizador, ArmadorPrompt $armador): void
    {
        $analisis = AnalisisCategoria::with('medicion.version')->find($this->analisisId);

        if ($analisis === null || $analisis->estado === 'terminado' || $analisis->medicion->estado !== 'enviada') {
            return;
        }

        $medicion = $analisis->medicion;
        /** @var list<array<string, mixed>> $categorias */
        $categorias = $medicion->version->contenido['categorias'] ?? [];
        $categoria = collect($categorias)->firstWhere('ref', $analisis->categoria_ref);

        if ($categoria === null) {
            throw new RespuestaIaInvalida("La categoría {$analisis->categoria_ref} no está en la versión del diagnóstico.");
        }

        $prompt = $armador->armar($medicion, $categoria, DatosResponder::respuestas($medicion));

        $analisis->forceFill([
            'estado' => 'pendiente',
            'intentos' => min(255, $analisis->intentos + 1),
            'prompt' => $prompt['instrucciones']."\n\n## Preguntas con respuestas\n"
                .json_encode($prompt['categoria'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ])->save();

        // [FUNCIONALIDAD POR DEFINIR] Categoría sin ninguna pregunta respondida
        // (todas opcionales): no se llama a la IA y vale 0.
        $salida = $prompt['categoria']['preguntas'] === []
            ? [
                'preguntas' => [],
                'metodo' => 'CAME',
                'metodo_motivo' => 'Sin respuestas, la única acción posible es responder la categoría.',
                'observacion_categoria' => 'No respondiste ninguna pregunta de esta categoría.',
                'recomendacion' => ['titulo' => 'Responde esta categoría', 'detalle' => 'En tu próxima medición responde sus preguntas para saber cómo vas en este tema.'],
            ]
            : $analizador->analizar($prompt['instrucciones'], $prompt['categoria']);

        DB::transaction(function () use ($analisis, $salida): void {
            foreach ($salida['preguntas'] as $p) {
                $cambios = ['observacion_ia' => $p['observacion']];

                if ($p['puntaje'] !== null) {
                    $cambios['puntaje'] = $p['puntaje'];
                }

                Respuesta::where('medicion_id', $analisis->medicion_id)->where('pregunta_ref', $p['id'])->update($cambios);
            }

            $analisis->forceFill(['estado' => 'terminado', 'respuesta_ia' => $salida])->save();
        });

        PublicarResultado::siTermino($medicion);
    }

    /** Agotó los 3 intentos: queda fallida y se avisa a los administradores. */
    public function failed(?Throwable $error): void
    {
        $analisis = AnalisisCategoria::with('medicion.empresa')->find($this->analisisId);

        if ($analisis === null) {
            return;
        }

        $analisis->forceFill(['estado' => 'fallido'])->save();

        $admins = User::role('Administrador')->where('activo', true)->get();
        Notification::send($admins, new AnalisisFallido($analisis, $error?->getMessage()));
    }
}
