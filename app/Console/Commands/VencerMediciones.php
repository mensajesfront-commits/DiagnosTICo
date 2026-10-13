<?php

namespace App\Console\Commands;

use App\Models\Medicion;
use Illuminate\Console\Command;

/**
 * HU-070 · Marca como vencidas las mediciones sin enviar (no iniciadas o en
 * curso) cuya fecha límite ya pasó (RN-015, RN-016). Las que vencen hoy
 * siguen pendientes hasta el final del día. Corre cada día
 * (routes/console.php).
 *
 * [INFORMACIÓN PENDIENTE] El día se cuenta en la zona horaria de la
 * aplicación (`APP_TIMEZONE`); falta confirmar si debe ser la de cada
 * empresa.
 */
class VencerMediciones extends Command
{
    protected $signature = 'mediciones:vencer';

    protected $description = 'Marca como vencidas las mediciones sin enviar que pasaron su fecha límite';

    public function handle(): int
    {
        $vencidas = Medicion::query()
            ->whereIn('estado', ['no_iniciada', 'en_curso'])
            ->whereNotNull('fecha_limite')
            ->whereDate('fecha_limite', '<', today())
            ->update(['estado' => 'vencida', 'updated_at' => now()]);

        $this->info("Mediciones vencidas: {$vencidas}");

        return self::SUCCESS;
    }
}
