<?php

use App\Rules\SinDatosDelCorreo;
use Illuminate\Support\Facades\Validator;

/*
 * La contraseña no puede tener partes del correo (17 de octubre de 2026).
 */

it('saca las partes del correo, sin las de menos de 3 caracteres', function () {
    expect(SinDatosDelCorreo::partes('Juan.Perez1102@Empresa.co'))
        ->toBe(['juanperez1102', 'juan', 'perez1102', 'perez', '1102', 'empresa'])
        ->and(SinDatosDelCorreo::partes('maria20lopez04@gmail.com'))
        ->toBe(['maria20lopez04', 'maria', 'lopez', 'gmail'])
        ->and(SinDatosDelCorreo::partes(null))->toBe([]);
});

it('rechaza la contraseña con una parte del correo, sin importar mayúsculas ni tildes', function (string $contrasena, bool $pasa) {
    $v = Validator::make(['password' => $contrasena], ['password' => [new SinDatosDelCorreo('juan.perez1102@empresa.co')]]);

    expect($v->passes())->toBe($pasa);
})->with([
    ['Clave-1102!', false],
    ['PÉREZ-2026!', false],
    ['Juan-2026!', false],
    ['Empresa#2026', false],
    ['Mesa-47-Sol', true],
]);
