<?php

namespace App\Jobs;

use App\Models\Resultado;
use App\Services\Resultados\InformePdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * HU-084 · Genera y guarda los dos PDF al publicar el resultado (RN-024):
 * el informe completo y el resumen para la empresa. Si falla tras 3
 * intentos, el resultado sigue publicado y el PDF que falte se vuelve a
 * intentar cuando alguien lo descarga (HU-066 CA-004).
 */
class GenerarInformePdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public function __construct(public int $resultadoId) {}

    public function handle(): void
    {
        $resultado = Resultado::with('medicion.empresa')->find($this->resultadoId);

        if ($resultado === null) {
            return;
        }

        foreach (InformePdf::TIPOS as $tipo) {
            if ($resultado->{InformePdf::columna($tipo)} === null) {
                InformePdf::generar($resultado, $tipo);
            }
        }
    }
}
