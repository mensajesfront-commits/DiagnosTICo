<?php

use App\Support\Niveles;

/*
 * RN-019 · Niveles fijos: Crítico 0–29, Se puede mejorar 30–59, Vas en buen
 * camino 60–79, Sigue así 80–100.
 */

it('asigna el nivel según el rango', function (float $puntaje, string $nivel) {
    expect(Niveles::de($puntaje))->toBe($nivel);
})->with([
    [0, 'critico'], [29, 'critico'], [29.4, 'critico'],
    [29.5, 'mejorar'], [30, 'mejorar'], [59, 'mejorar'],
    [60, 'camino'], [79, 'camino'],
    [80, 'sigue'], [100, 'sigue'], [130, 'sigue'],
]);

it('tiene los nombres en español', function () {
    expect(Niveles::nombre('camino'))->toBe('Vas en buen camino');
});
