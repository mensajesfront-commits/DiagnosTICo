<?php

use App\Models\Categoria;
use App\Models\Diagnostico;
use App\Models\DiagnosticoCategoria;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Pregunta;
use App\Models\Respuesta;
use App\Models\Sector;
use App\Models\User;
use App\Support\EstadoDiagnostico;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * A2.1 · Editor del diagnóstico (HU-023 a HU-029).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $this->admin = User::factory()->create()->assignRole('Administrador');
    $this->sector = Sector::factory()->create(['nombre' => 'Abogados']);
    $this->diagnostico = Diagnostico::factory()->create(['sector_id' => $this->sector->id, 'nombre' => 'Diagnóstico general']);
    [$this->cliente, $this->mercado] = Categoria::factory()->count(2)->create()->all();

    foreach ([$this->cliente, $this->mercado] as $i => $c) {
        DiagnosticoCategoria::create(['diagnostico_id' => $this->diagnostico->id, 'categoria_id' => $c->id, 'importancia' => 50, 'orden' => $i]);
    }

    $this->parte = $this->diagnostico->partes()->firstOrFail();
});

/** @return array<string, mixed> */
function preguntaUnica(array $cambios = []): array
{
    return [
        'texto' => '¿Tienes definido quién es tu cliente ideal?',
        'tipo' => 'opcion_unica',
        'indicacion' => 'Piensa en tu cliente más frecuente.',
        'obligatoria' => true,
        'opciones' => [
            ['texto' => 'Sí, con un perfil escrito', 'puntaje' => 100, 'ten_en_cuenta' => 'Reconócelo como fortaleza.'],
            ['texto' => 'No lo tengo definido', 'puntaje' => 0],
        ],
        ...$cambios,
    ];
}

function agregarPregunta($test, array $datos = []): Pregunta
{
    $test->actingAs($test->admin)->post(route('diagnosticos.preguntas.guardar', $test->diagnostico), [
        'parte_id' => $test->parte->id, ...preguntaUnica($datos),
    ])->assertSessionHasNoErrors();

    return Pregunta::latest('id')->firstOrFail();
}

it('abre el editor con las categorías, el estado y el catálogo', function () {
    $this->actingAs($this->admin)->get(route('diagnosticos.editar', $this->diagnostico))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('diagnosticos/Editor')
            ->has('partes', 2)
            ->where('diagnostico.version', 1)
            ->where('estado.publicable', false)
            ->where('estado.requisitos.0.ok', false)
            ->where('partes.0.problemas.0', 'No tiene preguntas'));
});

it('agrega una pregunta al final con sus opciones y la indicación para la IA', function () {
    agregarPregunta($this);
    $segunda = agregarPregunta($this, ['texto' => 'Otra']);

    expect($segunda->orden)->toBe(1)
        ->and($segunda->opciones)->toHaveCount(2)
        ->and($segunda->opciones[0]->ten_en_cuenta)->toBe('Reconócelo como fortaleza.');
});

it('no agrega una pregunta sin indicación, sin opciones o con puntajes fuera de 0–100', function () {
    $ruta = route('diagnosticos.preguntas.guardar', $this->diagnostico);

    $this->actingAs($this->admin)->post($ruta, ['parte_id' => $this->parte->id, ...preguntaUnica(['indicacion' => ''])])->assertSessionHasErrors('indicacion');
    $this->actingAs($this->admin)->post($ruta, ['parte_id' => $this->parte->id, ...preguntaUnica(['opciones' => [['texto' => 'Una', 'puntaje' => 10]]])])->assertSessionHasErrors('opciones');
    $this->actingAs($this->admin)->post($ruta, ['parte_id' => $this->parte->id, ...preguntaUnica(['opciones' => [['texto' => 'A', 'puntaje' => 120], ['texto' => 'B', 'puntaje' => 0]]])])->assertSessionHasErrors('opciones.0.puntaje');

    // Una abierta no lleva opciones.
    $this->actingAs($this->admin)->post($ruta, ['parte_id' => $this->parte->id, ...preguntaUnica(['tipo' => 'abierta', 'opciones' => [], 'criterio_ia' => 'Que sea concreta'])])->assertSessionHasNoErrors();
    expect(Pregunta::where('tipo', 'abierta')->firstOrFail()->criterio_ia)->toBe('Que sea concreta');
});

it('edita una pregunta conservando el id de sus opciones y la elimina', function () {
    $pregunta = agregarPregunta($this);
    $opcion = $pregunta->opciones[0];

    $this->actingAs($this->admin)->put(route('diagnosticos.preguntas.actualizar', [$this->diagnostico, $pregunta]), preguntaUnica([
        'tipo' => 'seleccion_multiple',
        'opciones' => [
            ['id' => $opcion->id, 'texto' => 'Sí, escrito', 'puntaje' => 60],
            ['texto' => 'Nueva', 'puntaje' => 40],
        ],
    ]))->assertSessionHasNoErrors();

    $pregunta->refresh();
    expect($pregunta->tipo)->toBe('seleccion_multiple')
        ->and($pregunta->opciones->pluck('texto')->all())->toBe(['Sí, escrito', 'Nueva'])
        ->and($pregunta->opciones[0]->id)->toBe($opcion->id);

    $this->actingAs($this->admin)->delete(route('diagnosticos.preguntas.eliminar', [$this->diagnostico, $pregunta]))->assertSessionHasNoErrors();
    expect(Pregunta::count())->toBe(0);
});

it('no edita preguntas de otro diagnóstico', function () {
    $pregunta = agregarPregunta($this);
    $otro = Diagnostico::factory()->create(['sector_id' => $this->sector->id]);

    $this->actingAs($this->admin)->delete(route('diagnosticos.preguntas.eliminar', [$otro, $pregunta]))->assertNotFound();
});

it('reordena las preguntas de una categoría', function () {
    $a = agregarPregunta($this, ['texto' => 'A']);
    $b = agregarPregunta($this, ['texto' => 'B']);

    $this->actingAs($this->admin)->put(route('diagnosticos.preguntas.ordenar', [$this->diagnostico, $this->parte]), ['preguntas' => [$b->id, $a->id]])
        ->assertSessionHasNoErrors();

    expect($this->parte->preguntas()->pluck('texto')->all())->toBe(['B', 'A']);
});

it('agrega categorías del catálogo manteniendo los pesos o repartiendo en partes iguales', function () {
    $nueva = Categoria::factory()->create();
    $archivada = Categoria::factory()->create(['archivado_en' => now()]);
    $ruta = route('diagnosticos.categorias.agregar', $this->diagnostico);

    $this->actingAs($this->admin)->post($ruta, ['categorias' => [$archivada->id], 'reparto' => 'mantener'])->assertSessionHasErrors('categorias.0');
    $this->actingAs($this->admin)->post($ruta, ['categorias' => [$nueva->id], 'reparto' => 'mantener'])->assertSessionHasNoErrors();
    expect($this->diagnostico->partes()->pluck('importancia')->map(fn ($v) => (float) $v)->all())->toBe([50.0, 50.0, 0.0]);

    $otra = Categoria::factory()->create();
    $this->actingAs($this->admin)->post($ruta, ['categorias' => [$otra->id], 'reparto' => 'iguales'])->assertSessionHasNoErrors();
    expect($this->diagnostico->partes()->pluck('importancia')->map(fn ($v) => (float) $v)->all())->toBe([25.0, 25.0, 25.0, 25.0]);
});

it('guarda la importancia solo si suma exactamente 100', function () {
    $partes = $this->diagnostico->partes()->pluck('id')->all();
    $ruta = route('diagnosticos.importancia.guardar', $this->diagnostico);

    $this->actingAs($this->admin)->put($ruta, ['importancias' => [$partes[0] => 60, $partes[1] => 44]])->assertSessionHasErrors('importancias');
    $this->actingAs($this->admin)->put($ruta, ['importancias' => [$partes[0] => 60.5, $partes[1] => 39.5]])
        ->assertRedirect(route('diagnosticos.editar', $this->diagnostico));

    expect((float) DiagnosticoCategoria::find($partes[0])->importancia)->toBe(60.5);
});

it('calcula los 5 requisitos y lo que falta en cada categoría', function () {
    $estado = EstadoDiagnostico::calcular(['categorias' => [
        ['ref' => 'c1', 'importancia' => 60, 'preguntas' => [
            ['ref' => 'p1', 'tipo' => 'seleccion_multiple', 'indicacion' => '', 'opciones' => [['puntaje' => 30], ['puntaje' => 50]]],
        ]],
        ['ref' => 'c2', 'importancia' => 30, 'preguntas' => []],
    ]]);

    expect($estado['publicable'])->toBeFalse()
        ->and(array_column($estado['requisitos'], 'ok'))->toBe([false, true, false, false, false])
        ->and($estado['requisitos'][4]['detalle'])->toBe('1 suma solo 80')
        ->and($estado['preguntas']['p1'])->toBe(['Falta la indicación de cómo responder', 'La selección múltiple suma solo 80'])
        ->and($estado['categorias']['c2'])->toBe(['No tiene preguntas'])
        ->and($estado['completas'])->toBe(0);
});

it('publica la v1 con nota; después, un cambio abre el borrador v2 sin tocar la v1', function () {
    $this->parte = $this->diagnostico->partes()->firstOrFail();
    agregarPregunta($this);
    $this->parte = $this->diagnostico->partes()->skip(1)->firstOrFail();
    agregarPregunta($this);

    $ruta = route('diagnosticos.publicar', $this->diagnostico);
    $this->actingAs($this->admin)->post($ruta, ['nota' => '', 'pendientes' => 'dejar'])->assertSessionHasErrors('nota');
    $this->actingAs($this->admin)->post($ruta, ['nota' => 'Primera versión', 'pendientes' => 'dejar'])->assertSessionHasNoErrors();

    $this->diagnostico->refresh();
    $v1 = $this->diagnostico->versiones()->firstOrFail();
    expect($this->diagnostico->estado)->toBe(Diagnostico::PUBLICADO)
        ->and($this->diagnostico->version_borrador)->toBeNull()
        ->and($v1->numero)->toBe(1)
        ->and($v1->contenido['categorias'][0]['preguntas'])->toHaveCount(1);

    // Sin cambios no hay nada que publicar.
    $this->actingAs($this->admin)->post($ruta, ['nota' => 'Otra', 'pendientes' => 'dejar'])->assertSessionHasErrors('nota');

    agregarPregunta($this, ['texto' => 'Nueva en v2']);
    expect($this->diagnostico->refresh()->version_borrador)->toBe(2)
        ->and($v1->refresh()->contenido['categorias'][1]['preguntas'])->toHaveCount(1);
});

it('no publica si falta algún requisito', function () {
    agregarPregunta($this);

    $this->actingAs($this->admin)->post(route('diagnosticos.publicar', $this->diagnostico), ['nota' => 'v1', 'pendientes' => 'dejar'])
        ->assertSessionHasErrors('nota');
    expect($this->diagnostico->versiones()->count())->toBe(0);
});

it('al publicar pasa a la nueva versión las mediciones pendientes sin respuestas si se pide', function () {
    $publicado = Diagnostico::factory()->publicado()->create(['sector_id' => $this->sector->id]);
    $v1 = $publicado->versiones()->firstOrFail();
    $empresa = Empresa::factory()->create(['sector_id' => $this->sector->id]);
    $sinEmpezar = Medicion::factory()->create(['empresa_id' => $empresa->id, 'version_diagnostico_id' => $v1->id, 'numero' => 1]);
    $otra = Empresa::factory()->create(['sector_id' => $this->sector->id]);
    $empezada = Medicion::factory()->create(['empresa_id' => $otra->id, 'version_diagnostico_id' => $v1->id, 'numero' => 1, 'estado' => 'en_curso']);
    Respuesta::create(['medicion_id' => $empezada->id, 'pregunta_ref' => 'p1', 'texto' => 'x']);

    // Un cambio abre el borrador v2 (la factory deja preguntas válidas).
    $publicado->partes()->each(fn ($p) => $p->update(['importancia' => 50]));
    $publicado->forceFill(['version_borrador' => 2])->save();

    $this->actingAs($this->admin)->post(route('diagnosticos.publicar', $publicado), ['nota' => 'Ajustes', 'pendientes' => 'pasar'])
        ->assertSessionHasNoErrors();

    $v2 = $publicado->versiones()->where('numero', 2)->firstOrFail();
    expect($sinEmpezar->refresh()->version_diagnostico_id)->toBe($v2->id)
        ->and($empezada->refresh()->version_diagnostico_id)->toBe($v1->id);
});

it('muestra la vista previa del borrador y de una versión', function () {
    agregarPregunta($this);

    $this->actingAs($this->admin)->get(route('diagnosticos.vista-previa', $this->diagnostico))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('diagnosticos/VistaPrevia')
            ->has('categorias', 2)
            ->where('categorias.0.preguntas.0.opciones.0.texto', 'Sí, con un perfil escrito')
            ->where('version', null));

    $this->actingAs($this->admin)->get(route('diagnosticos.vista-previa', [$this->diagnostico, 'version' => 9]))->assertNotFound();
});

it('no deja editar un diagnóstico archivado ni entrar a una empresa', function () {
    $this->diagnostico->update(['estado' => Diagnostico::ARCHIVADO]);

    $this->actingAs($this->admin)->post(route('diagnosticos.preguntas.guardar', $this->diagnostico), ['parte_id' => $this->parte->id, ...preguntaUnica()])
        ->assertSessionHasErrors('diagnostico');

    $empresa = User::factory()->create()->assignRole('Empresa');
    $this->actingAs($empresa)->get(route('diagnosticos.editar', $this->diagnostico))->assertForbidden();
});
