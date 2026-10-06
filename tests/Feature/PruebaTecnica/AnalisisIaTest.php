<?php

/*
| Prueba técnica T-019: la llamada a OpenAI que califica una respuesta abierta
| y devuelve el JSON con el formato fijo. Se simula la API (OpenAI::fake) para
| no gastar; la llamada real se prueba a mano con `php artisan prueba:ia`.
*/

use App\Console\Commands\PruebaIa;
use App\Services\Ia\AnalizadorDeCategoria;
use App\Services\Ia\RespuestaIaInvalida;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Resources\Chat;
use OpenAI\Responses\Chat\CreateResponse;

function respuestaIa(array $contenido): CreateResponse
{
    return CreateResponse::fake([
        'choices' => [[
            'index' => 0,
            'message' => ['role' => 'assistant', 'content' => json_encode($contenido)],
            'finish_reason' => 'stop',
        ]],
    ]);
}

function analisisValido(int $puntajeAbierta = 45): array
{
    return [
        'preguntas' => [
            ['id' => 1, 'puntaje' => null, 'observacion' => 'Los pedidos dependen de WhatsApp personal y llamadas.'],
            ['id' => 2, 'puntaje' => null, 'observacion' => 'Cada cliente espera una respuesta manual.'],
            ['id' => 3, 'puntaje' => $puntajeAbierta, 'observacion' => 'Responde, pero con demoras de hasta un día.'],
        ],
        'observacion_categoria' => 'Hoy tomas pedidos y reservas a mano por WhatsApp.',
        'recomendacion' => 'Activa pedidos y respuestas automáticas.',
    ];
}

beforeEach(function () {
    config(['openai.model' => 'modelo-de-prueba']);
});

it('devuelve el análisis con el puntaje de la pregunta abierta', function () {
    OpenAI::fake([respuestaIa(analisisValido())]);

    $resultado = app(AnalizadorDeCategoria::class)->analizar(PruebaIa::categoriaDeEjemplo());

    expect($resultado['preguntas'][2]['puntaje'])->toBe(45)
        ->and($resultado['recomendacion'])->toBe('Activa pedidos y respuestas automáticas.');
});

it('pide la salida estructurada con el esquema fijo', function () {
    $fake = OpenAI::fake([respuestaIa(analisisValido())]);

    app(AnalizadorDeCategoria::class)->analizar(PruebaIa::categoriaDeEjemplo());

    $fake->assertSent(Chat::class, function (string $metodo, array $parametros): bool {
        return $metodo === 'create'
            && $parametros['model'] === 'modelo-de-prueba'
            && $parametros['response_format']['type'] === 'json_schema'
            && $parametros['response_format']['json_schema']['strict'] === true
            && $parametros['response_format']['json_schema']['schema'] === AnalizadorDeCategoria::esquema();
    });
});

it('rechaza un puntaje fuera de 0 a 100', function () {
    OpenAI::fake([respuestaIa(analisisValido(puntajeAbierta: 140))]);

    app(AnalizadorDeCategoria::class)->analizar(PruebaIa::categoriaDeEjemplo());
})->throws(RespuestaIaInvalida::class, 'Puntaje fuera de rango en la pregunta 3.');

it('rechaza una respuesta a la que le falta una pregunta', function () {
    $analisis = analisisValido();
    array_pop($analisis['preguntas']);
    OpenAI::fake([respuestaIa($analisis)]);

    app(AnalizadorDeCategoria::class)->analizar(PruebaIa::categoriaDeEjemplo());
})->throws(RespuestaIaInvalida::class, 'Falta el análisis de la pregunta 3.');

it('rechaza un texto que no es JSON', function () {
    OpenAI::fake([CreateResponse::fake()]);

    app(AnalizadorDeCategoria::class)->analizar(PruebaIa::categoriaDeEjemplo());
})->throws(RespuestaIaInvalida::class, 'La IA no devolvió el formato esperado.');

it('avisa si falta el modelo en el .env', function () {
    config(['openai.model' => null]);

    app(AnalizadorDeCategoria::class)->analizar(PruebaIa::categoriaDeEjemplo());
})->throws(RespuestaIaInvalida::class, 'Falta OPENAI_MODEL');

it('muestra el JSON con el comando prueba:ia', function () {
    OpenAI::fake([respuestaIa(analisisValido())]);

    $this->artisan('prueba:ia')
        ->expectsOutputToContain('"observacion_categoria"')
        ->assertSuccessful();
});
