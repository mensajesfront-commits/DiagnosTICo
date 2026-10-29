<?php

use App\Rules\TelefonoValido;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

/*
 * El teléfono lleva un indicativo de la lista y los dígitos de ese país.
 */

function errorTelefono(?string $valor): ?string
{
    $v = Validator::make(['telefono' => $valor], ['telefono' => ['nullable', new TelefonoValido]]);

    return $v->errors()->first('telefono') ?: null;
}

it('acepta 10 dígitos en Colombia y 11 en Argentina', function () {
    expect(errorTelefono('+57 3001234567'))->toBeNull()
        ->and(errorTelefono('+54 91123456789'))->toBeNull()
        ->and(errorTelefono(null))->toBeNull();
});

it('rechaza un número con más o menos dígitos de los del país', function () {
    expect(errorTelefono('+57 30012345'))->toContain('Colombia tiene 10 dígitos')
        ->and(errorTelefono('+54 9112345678'))->toContain('Argentina tiene 11 dígitos');
});

it('rechaza un indicativo que no está en la lista o un número con letras', function () {
    expect(errorTelefono('+99 3001234567'))->toBe('Elige un indicativo de la lista.')
        ->and(errorTelefono('3001234567'))->not->toBeNull()
        ->and(errorTelefono('+57 300-123-4567'))->not->toBeNull();
});
