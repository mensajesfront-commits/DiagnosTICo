<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cuentas eliminadas hace más de 90 días: se borran para siempre (DEC-017).
Schedule::command('cuentas:purgar')->dailyAt('03:10');

// HU-070: las mediciones sin enviar que pasaron su fecha límite quedan vencidas.
Schedule::command('mediciones:vencer')->dailyAt('00:05');

// Resumen semanal de mediciones pendientes para quien lo encendió en Mi perfil (A6).
Schedule::command('mediciones:resumen')->weeklyOn(1, '07:50');
