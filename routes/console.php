<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cuentas eliminadas hace más de 90 días: se borran para siempre (DEC-017).
Schedule::command('cuentas:purgar')->dailyAt('03:10');
