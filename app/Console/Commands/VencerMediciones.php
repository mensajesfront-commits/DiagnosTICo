<?php

namespace App\Console\Commands;

use App\Models\Medicion;
use App\Support\Plazos;
use Illuminate\Console\Command;

/**
 * HU-070 · Marca como vencidas las mediciones sin enviar (no iniciadas o en
 * curso) cuya fecha límite ya pasó (RN-015, RN-016). La fecha límite vale
 * hasta las 11:59 p. m. de ese día en la hora de la empresa (Plazos), así que
 * la tarea corre cada hora (routes/console.php) y cada empresa vence en su
 * propia medianoche.
 */
class VencerMediciones extends Command
{
    protected $signature = 'mediciones:vencer';

    protected $description = 'Marca como vencidas las mediciones sin enviar que pasaron su fecha límite';

    public function handle(): int
    {
        // Candidatas: hasta mañana en UTC, porque en algunas zonas ya es mañana.
        $candidatas = Medicion::query()
            ->whereIn('estado', ['no_iniciada', 'en_curso'])
            ->whereNotNull('fecha_limite')
            ->whereDate('fecha_limite', '<', now('UTC')->addDay()->toDateString())
            ->get(['id', 'empresa_id', 'fecha_limite', 'estado']);

        Plazos::precargar($candidatas->pluck('empresa_id')->all());
        $ids = $candidatas->filter(fn (Medicion $m) => Plazos::vencio($m))->modelKeys();

        $vencidas = $ids === [] ? 0 : Medicion::query()->whereKey($ids)
            ->update(['estado' => 'vencida', 'updated_at' => now()]);

        $this->info("Mediciones vencidas: {$vencidas}");

        return self::SUCCESS;
    }
}
