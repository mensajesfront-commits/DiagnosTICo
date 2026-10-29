<?php

use App\Models\Diagnostico;
use App\Models\Sector;
use App\Models\User;
use App\Services\Resultados\ResultadoEjemplo;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\LaravelPdf\Facades\Pdf;

/*
 * A2.6 · Resultado de ejemplo (HU-030): mejor, medio y peor caso con el mismo
 * cálculo que un resultado real; no llama a la IA ni guarda nada.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $this->admin = User::factory()->create()->assignRole('Administrador');
    $this->diagnostico = Diagnostico::factory()->publicado()->create(['sector_id' => Sector::factory()->create(['nombre' => 'Abogados'])->id]);
});

$contenido = [
    'categorias' => [
        ['ref' => 'c1', 'nombre' => 'Análisis de la competencia', 'importancia' => 50.0, 'preguntas' => [
            ['ref' => 'p1', 'tipo' => 'opcion_unica', 'texto' => '¿Única?', 'opciones' => [
                ['ref' => 'o1', 'texto' => 'Nada', 'puntaje' => 0],
                ['ref' => 'o2', 'texto' => 'Algo', 'puntaje' => 45],
                ['ref' => 'o3', 'texto' => 'Todo', 'puntaje' => 100],
            ]],
            ['ref' => 'p2', 'tipo' => 'seleccion_multiple', 'texto' => '¿Varias?', 'opciones' => [
                ['ref' => 'o4', 'texto' => 'A', 'puntaje' => 30],
                ['ref' => 'o5', 'texto' => 'B', 'puntaje' => 40],
                ['ref' => 'o6', 'texto' => 'C', 'puntaje' => 50],
                ['ref' => 'o7', 'texto' => 'Ninguna', 'puntaje' => 0, 'exclusiva' => true],
            ]],
        ]],
        ['ref' => 'c2', 'nombre' => 'Análisis del mercado', 'importancia' => 50.0, 'preguntas' => [
            ['ref' => 'p3', 'tipo' => 'abierta', 'texto' => '¿Abierta?', 'opciones' => []],
        ]],
    ],
];

it('elige las respuestas de cada caso', function () use ($contenido) {
    $unica = $contenido['categorias'][0]['preguntas'][0];
    $multiple = $contenido['categorias'][0]['preguntas'][1];

    expect(ResultadoEjemplo::elegir($unica, 'mejor'))->toBe(['o3'])
        ->and(ResultadoEjemplo::elegir($unica, 'medio'))->toBe(['o2'])
        ->and(ResultadoEjemplo::elegir($unica, 'peor'))->toBe(['o1'])
        ->and(ResultadoEjemplo::elegir($multiple, 'mejor'))->toBe(['o4', 'o5', 'o6'])
        ->and(ResultadoEjemplo::elegir($multiple, 'medio'))->toBe(['o4', 'o5'])
        ->and(ResultadoEjemplo::elegir($multiple, 'peor'))->toBe(['o7']);
});

it('calcula el mejor caso cerca del máximo y el peor cerca del mínimo', function () use ($contenido) {
    $mejor = ResultadoEjemplo::calcular($contenido, 'mejor');
    $medio = ResultadoEjemplo::calcular($contenido, 'medio');
    $peor = ResultadoEjemplo::calcular($contenido, 'peor');

    // Mejor: c1 = (100 + 100) / 2 = 100; c2 = 90 → 95.
    expect($mejor['puntaje_total'])->toBe(95)->and($mejor['nivel'])->toBe('sigue')
        // Medio: c1 = (45 + 70) / 2 = 58; c2 = 50 → 54.
        ->and($medio['puntaje_total'])->toBe(54)
        // Peor: c1 = 0; c2 = 10 → 5.
        ->and($peor['puntaje_total'])->toBe(5)->and($peor['nivel'])->toBe('critico')
        ->and($medio['por_categoria'][0]['metodo']['clave'])->toBe('PORTER')
        ->and($medio['por_categoria'][1]['metodo']['clave'])->toBe('PESTEL')
        ->and($medio['por_categoria'][1]['preguntas'][0]['puntaje'])->toBe(50);
});

it('muestra el resultado de ejemplo del borrador y de una versión con la pantalla de E6', function () {
    $this->actingAs($this->admin)->get(route('diagnosticos.resultado-ejemplo', [$this->diagnostico, 'caso' => 'peor']))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('resultados/Resultado')
            ->where('ejemplo.caso', 'peor')
            ->where('medicion.empresa.nombre', 'Empresa de ejemplo')
            ->where('medicion.sector', 'Abogados')
            ->has('resultado.categorias', 2)
            ->has('resultado.prioridades', 2)
            ->where('resultado.anterior', null));

    $this->actingAs($this->admin)->get(route('diagnosticos.resultado-ejemplo', [$this->diagnostico, 'version' => 1]))
        ->assertInertia(fn (Assert $p) => $p->where('ejemplo.caso', 'medio')->where('ejemplo.etiqueta', 'Versión 1'));

    $this->actingAs($this->admin)->get(route('diagnosticos.resultado-ejemplo', [$this->diagnostico, 'version' => 9]))->assertNotFound();
});

it('abre el formato del PDF con los datos de ejemplo', function () {
    Pdf::fake();

    $this->actingAs($this->admin)->get(route('diagnosticos.resultado-ejemplo.pdf', [$this->diagnostico, 'caso' => 'mejor']))->assertOk();

    Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->viewName === 'pdf.informe' && $pdf->viewData['informe']['empresa']['nombre'] === 'Empresa de ejemplo');
});

it('solo lo ve quien tiene diagnosticos.ver', function () {
    $empresa = User::factory()->create()->assignRole('Empresa');

    $this->actingAs($empresa)->get(route('diagnosticos.resultado-ejemplo', $this->diagnostico))->assertForbidden();
});
