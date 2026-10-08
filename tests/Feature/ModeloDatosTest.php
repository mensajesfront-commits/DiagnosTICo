<?php

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;

/*
 * T-045 / T-058 · Las tablas del MER y las factories funcionan juntas.
 */

it('crea un diagnóstico publicado con categorías, preguntas, opciones y su versión', function () {
    $diagnostico = Diagnostico::factory()->publicado()->create();

    expect($diagnostico->partes)->toHaveCount(2)
        ->and($diagnostico->preguntas()->count())->toBe(2)
        ->and($diagnostico->partes->first()->preguntas->first()->opciones)->toHaveCount(2)
        ->and($diagnostico->ultimaVersion?->numero)->toBe(1)
        ->and((float) $diagnostico->partes->sum('importancia'))->toBe(100.0);
});

it('asigna una medición a una empresa sobre una versión publicada', function () {
    $medicion = Medicion::factory()->create();

    expect($medicion->empresa)->toBeInstanceOf(Empresa::class)
        ->and($medicion->version->diagnostico->estado)->toBe(Diagnostico::PUBLICADO)
        ->and($medicion->empresa->mediciones)->toHaveCount(1);
});
