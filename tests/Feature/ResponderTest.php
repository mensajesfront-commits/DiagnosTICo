<?php

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Respuesta;
use App\Models\Resultado;
use App\Models\Sector;
use App\Models\SolicitudMedicion;
use App\Models\User;
use App\Notifications\NuevaMedicionSolicitada;
use App\Services\Resultados\CalculadoraPuntajes;
use App\Support\Niveles;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * E1 · Inicio de la empresa, E2–E4 · Responder, revisar y enviar
 * (HU-057 a HU-063) y pedir una medición nueva (HU-082).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $sector = Sector::factory()->create(['nombre' => 'Comidas']);
    $this->diagnostico = Diagnostico::factory()->publicado()->create(['sector_id' => $sector->id, 'nombre' => 'Diagnóstico general']);
    $this->version = $this->diagnostico->versiones()->firstOrFail();
    $this->empresa = Empresa::factory()->create(['nombre' => 'La Esquina', 'sector_id' => $sector->id]);
    $this->laura = User::factory()->create(['name' => 'Laura', 'empresa_id' => $this->empresa->id])->assignRole('Empresa');
    $this->medicion = Medicion::factory()->create([
        'empresa_id' => $this->empresa->id,
        'version_diagnostico_id' => $this->version->id,
        'numero' => 1,
        'mensaje' => 'Respóndelo antes del jueves.',
        'fecha_limite' => today()->addDays(10),
    ]);
    // Refs de la versión congelada: 2 categorías con 1 pregunta de 2 opciones.
    $this->preguntas = collect($this->version->contenido['categorias'])->flatMap(fn ($c) => $c['preguntas'])->values();
});

function responderTodo($test): void
{
    foreach ($test->preguntas as $p) {
        $test->actingAs($test->laura)->putJson(route('responder.guardar', $test->medicion), [
            'pregunta_ref' => $p['ref'], 'opciones' => [$p['opciones'][0]['ref']],
        ])->assertOk();
    }
}

it('muestra en el inicio la primera medición pendiente con su mensaje y fecha', function () {
    $this->actingAs($this->laura)->get(route('inicio.empresa'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('inicio/Empresa')
            ->where('nombre', 'Laura')
            ->where('realizadas', 0)
            ->where('pendiente.numero', 1)
            ->where('pendiente.primera', true)
            ->where('pendiente.estado', 'no_iniciada')
            ->where('pendiente.preguntas', 2)
            ->where('pendiente.dias_restantes', 10)
            ->where('pendiente.mensaje', 'Respóndelo antes del jueves.'));
});

it('muestra el cuestionario sin puntajes ni indicaciones para la IA', function () {
    $this->actingAs($this->laura)->get(route('responder.mostrar', $this->medicion))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('responder/Responder')
            ->has('categorias', 2)
            ->missing('categorias.0.preguntas.0.opciones.0.puntaje')
            ->missing('categorias.0.preguntas.0.opciones.0.ten_en_cuenta')
            ->where('categoriaInicial', 0)
            ->where('avance.respondidas', 0));
});

it('guarda cada respuesta al momento, pasa a en curso y la borra si se deja vacía', function () {
    $p = $this->preguntas[0];

    $this->actingAs($this->laura)->putJson(route('responder.guardar', $this->medicion), [
        'pregunta_ref' => $p['ref'], 'opciones' => [$p['opciones'][1]['ref']],
    ])->assertOk()->assertJsonPath('avance.respondidas', 1)->assertJsonPath('avance.actual', 1);

    expect($this->medicion->refresh()->estado)->toBe('en_curso')
        ->and(Respuesta::firstOrFail()->opciones_elegidas)->toBe([$p['opciones'][1]['ref']]);

    // Opción única: solo una. Una opción de otra pregunta no vale.
    $this->actingAs($this->laura)->putJson(route('responder.guardar', $this->medicion), [
        'pregunta_ref' => $p['ref'], 'opciones' => [$p['opciones'][0]['ref'], $p['opciones'][1]['ref']],
    ])->assertUnprocessable();
    $this->actingAs($this->laura)->putJson(route('responder.guardar', $this->medicion), [
        'pregunta_ref' => $p['ref'], 'opciones' => [$this->preguntas[1]['opciones'][0]['ref']],
    ])->assertUnprocessable();

    $this->actingAs($this->laura)->putJson(route('responder.guardar', $this->medicion), ['pregunta_ref' => $p['ref'], 'opciones' => []])->assertOk();
    expect(Respuesta::count())->toBe(0);
});

it('no envía con preguntas obligatorias sin responder; completo, queda enviada y no se cambia', function () {
    $this->actingAs($this->laura)->post(route('responder.enviar', $this->medicion))->assertSessionHasErrors('enviar');

    responderTodo($this);

    $this->actingAs($this->laura)->get(route('responder.revisar', $this->medicion))
        ->assertInertia(fn (Assert $p) => $p->component('responder/Revisar')->where('avance.completo', true));

    Queue::fake();
    $this->actingAs($this->laura)->post(route('responder.enviar', $this->medicion))
        ->assertRedirect(route('responder.enviado', $this->medicion));

    $this->medicion->refresh();
    expect($this->medicion->estado)->toBe('enviada')->and($this->medicion->enviada_en)->not->toBeNull();

    // RN-017: después de enviar no se responde.
    $this->actingAs($this->laura)->putJson(route('responder.guardar', $this->medicion), [
        'pregunta_ref' => $this->preguntas[0]['ref'], 'opciones' => [],
    ])->assertUnprocessable();
    $this->actingAs($this->laura)->get(route('responder.mostrar', $this->medicion))->assertRedirect(route('responder.enviado', $this->medicion));
});

it('un colaborador de la empresa responde; otra empresa no ve la medición', function () {
    $colaborador = User::factory()->create(['empresa_id' => $this->empresa->id])->assignRole('Colaborador');
    $this->actingAs($colaborador)->get(route('responder.mostrar', $this->medicion))->assertOk();

    $otra = User::factory()->create(['empresa_id' => Empresa::factory()->create()->id])->assignRole('Empresa');
    $this->actingAs($otra)->get(route('responder.mostrar', $this->medicion))->assertNotFound();
    $this->actingAs($otra)->putJson(route('responder.guardar', $this->medicion), ['pregunta_ref' => 'x'])->assertNotFound();
});

it('una medición vencida no se responde; se pide una nueva una sola vez y les llega a los administradores', function () {
    Notification::fake();
    $admin = User::factory()->create()->assignRole('Administrador');
    $this->medicion->update(['fecha_limite' => today()->subDay()]);

    $this->actingAs($this->laura)->get(route('responder.mostrar', $this->medicion))->assertRedirect(route('inicio.empresa'));
    $this->actingAs($this->laura)->get(route('inicio.empresa'))
        ->assertInertia(fn (Assert $p) => $p->where('pendiente.estado', 'vencida')->where('pendiente.solicitud_abierta', false));

    $this->actingAs($this->laura)->post(route('responder.solicitar', $this->medicion))->assertSessionHasNoErrors();
    Notification::assertSentTo($admin, NuevaMedicionSolicitada::class);
    expect(SolicitudMedicion::where('estado', 'abierta')->count())->toBe(1);

    $this->actingAs($this->laura)->post(route('responder.solicitar', $this->medicion))->assertSessionHasErrors('solicitud');
    $this->actingAs($this->laura)->get(route('inicio.empresa'))
        ->assertInertia(fn (Assert $p) => $p->where('pendiente.solicitud_abierta', true));
});

it('sin pendiente muestra el último resultado, la variación y las 3 áreas más débiles', function () {
    $this->medicion->update(['estado' => 'terminada', 'terminada_en' => now()->subMonths(6)]);
    Resultado::create(['medicion_id' => $this->medicion->id, 'puntaje_total' => 41, 'nivel' => 'mejorar', 'por_categoria' => [], 'publicado_en' => now()]);
    $segunda = Medicion::factory()->create(['empresa_id' => $this->empresa->id, 'version_diagnostico_id' => $this->version->id, 'numero' => 2, 'estado' => 'terminada', 'terminada_en' => now()]);
    Resultado::create(['medicion_id' => $segunda->id, 'puntaje_total' => 57, 'nivel' => 'mejorar', 'publicado_en' => now(), 'por_categoria' => [
        ...array_map(fn (array $c) => ['ref' => 'c'.$c[1], 'nombre' => $c[0], 'puntaje' => $c[1], 'importancia' => 25.0, 'nivel' => Niveles::de($c[1]), 'recomendacion' => ['titulo' => 'Haz algo', 'detalle' => '…']],
            [['KPIs', 45], ['Automatización', 22], ['Contenido', 38], ['Cliente', 81]]),
    ]]);

    $this->actingAs($this->laura)->get(route('inicio.empresa'))
        ->assertInertia(fn (Assert $p) => $p->where('pendiente', null)
            ->where('realizadas', 2)
            ->where('ultima.puntaje', 57)
            ->where('ultima.variacion', 16)
            ->has('mediciones', 2)
            ->where('areas.0.nombre', 'Automatización')
            ->where('areas.0.nivel', 'critico')
            ->where('areas.0.recomendacion', 'Haz algo')
            ->has('areas', 3));
});

it('no deja marcar "Ninguna" junto con otras opciones', function () {
    $contenido = $this->version->contenido;
    $contenido['categorias'][0]['preguntas'][0] = [
        'ref' => 'p900', 'texto' => '¿Qué canales usas?', 'tipo' => 'seleccion_multiple', 'indicacion' => 'Marca todos.', 'obligatoria' => true, 'orden' => 0,
        'opciones' => [
            ['ref' => 'o901', 'texto' => 'Instagram', 'puntaje' => 100, 'exclusiva' => false],
            ['ref' => 'o902', 'texto' => 'Ninguno', 'puntaje' => 0, 'exclusiva' => true],
        ],
    ];
    $this->version->forceFill(['contenido' => $contenido])->save();

    $ruta = route('responder.guardar', $this->medicion);
    $this->actingAs($this->laura)->putJson($ruta, ['pregunta_ref' => 'p900', 'opciones' => ['o901', 'o902']])->assertUnprocessable();
    $this->actingAs($this->laura)->putJson($ruta, ['pregunta_ref' => 'p900', 'opciones' => ['o902']])->assertOk();

    // La empresa recibe cuál es la excluyente.
    $this->actingAs($this->laura)->get(route('responder.mostrar', $this->medicion))
        ->assertInertia(fn (Assert $p) => $p->where('categorias.0.preguntas.0.opciones.1.exclusiva', true));
});

it('con la opción "Otro" guarda lo que escribió y sin ella lo descarta', function () {
    $contenido = $this->version->contenido;
    $contenido['categorias'][0]['preguntas'][0] = [
        'ref' => 'p900', 'texto' => '¿Qué canal usas más?', 'tipo' => 'opcion_unica', 'indicacion' => 'Elige uno.', 'obligatoria' => true, 'orden' => 0,
        'opciones' => [
            ['ref' => 'o901', 'texto' => 'Instagram', 'puntaje' => 100, 'exclusiva' => false, 'con_texto' => false],
            ['ref' => 'o902', 'texto' => 'Otro', 'puntaje' => 50, 'exclusiva' => false, 'con_texto' => true],
        ],
    ];
    $this->version->forceFill(['contenido' => $contenido])->save();
    $ruta = route('responder.guardar', $this->medicion);

    $this->actingAs($this->laura)->putJson($ruta, ['pregunta_ref' => 'p900', 'opciones' => ['o902'], 'texto' => ' TikTok '])->assertOk();
    expect(Respuesta::where('pregunta_ref', 'p900')->value('texto'))->toBe('TikTok')
        ->and(CalculadoraPuntajes::textoRespuesta($contenido['categorias'][0]['preguntas'][0], ['o902'], 'TikTok'))->toBe('Otro: TikTok');

    $this->actingAs($this->laura)->putJson($ruta, ['pregunta_ref' => 'p900', 'opciones' => ['o901'], 'texto' => 'TikTok'])->assertOk();
    expect(Respuesta::where('pregunta_ref', 'p900')->value('texto'))->toBeNull();

    $this->actingAs($this->laura)->get(route('responder.mostrar', $this->medicion))
        ->assertInertia(fn (Assert $p) => $p->where('categorias.0.preguntas.0.opciones.1.con_texto', true));
});
