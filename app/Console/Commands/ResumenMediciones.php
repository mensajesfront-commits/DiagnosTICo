<?php

namespace App\Console\Commands;

use App\Models\Medicion;
use App\Models\SolicitudMedicion;
use App\Models\User;
use App\Notifications\ResumenSemanal;
use App\Support\DatosEmpresas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Envía el resumen semanal de mediciones pendientes a los Administradores
 * que lo encendieron en Mi perfil (A6). Corre cada semana
 * (routes/console.php).
 *
 * Los lunes a las 07:50, hora de Colombia: NuevasTIC lo aprobó (23 de
 * octubre de 2026).
 */
class ResumenMediciones extends Command
{
    protected $signature = 'mediciones:resumen';

    protected $description = 'Envía el resumen semanal de mediciones pendientes a los administradores';

    public function handle(): int
    {
        $pendientes = Medicion::query()
            ->whereIn('estado', DatosEmpresas::PENDIENTES)
            ->whereHas('empresa')
            ->with('empresa:id,nombre')
            ->get();

        $cuenta = ['no_iniciada' => 0, 'en_curso' => 0, 'enviada' => 0, 'vencida' => 0];
        $vencidas = [];

        foreach ($pendientes as $m) {
            $estado = DatosEmpresas::estadoVisible($m);
            $cuenta[$estado] = ($cuenta[$estado] ?? 0) + 1;

            if ($estado === 'vencida') {
                $vencidas[] = "{$m->empresa?->nombre} · medición {$m->numero}";
            }
        }

        $admins = User::role('Administrador')->where('activo', true)->get()
            ->filter(fn (User $u) => $u->quiereAviso('resumen_semanal'));

        Notification::send($admins, new ResumenSemanal(
            $cuenta,
            $vencidas,
            SolicitudMedicion::where('estado', 'abierta')->count(),
        ));

        $this->info("Resumen enviado a {$admins->count()} administradores.");

        return self::SUCCESS;
    }
}
