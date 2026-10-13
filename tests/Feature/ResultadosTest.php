<?php

use App\Jobs\AnalizarMedicion;
use App\Models\AnalisisCategoria;
use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Prompt;
use App\Models\Respuesta;
use App\Models\Resultado;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\AnalisisFallido;
use App\Notifications\ResultadoListo;
use App\Services\Ia\Analizador;
use App\Services\Ia\AnalizadorDePrueba;
use App\Services\Ia\ArmadorPrompt;
use App\Services\Resultados\CalculadoraPuntajes;
use App\Support\DatosResultado;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\LaravelPdf\Facades\Pdf;

/*
 * HU-068 puntajes, HU-069 análisis por categoría, HU-084 publicar, E4 avance
 * y reintento (HU-063), E6 resultado, E9 respuestas y PDF (HU-064 a HU-066).
 * La IA es AnalizadorDePrueba (IA_DE_PRUEBA=true en phpunit.xml): la abierta
 * vale 20 + largo/4.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    Storage::fake('local');
    Notification::fake();

    $this->sector = Sector::factory()->create(['nombre' => 'Comidas']);
    $diagnostico = Diagnostico::factory()->publicado()->create(['sector_id' => $this->sector->id, 'nombre' => 'Diagnóstico general']);
    $this->version = $diagnostico->versiones()->firstOrFail();
    $this->version->update(['contenido' => [
        'nombre' => 'Diagnóstico general',
        'descripcion' => null,
        'categorias' => [
            ['ref' => 'c1', 'categoria_id' => 1, 'nombre' => 'Contenido', 'importancia' => 60.0, 'orden' => 0, 'preguntas' => [
                ['ref' => 'p1', 'texto' => '¿Publicas seguido?', 'tipo' => 'opcion_unica', 'indicacion' => 'Elige una.', 'criterio_ia' => null, 'obligatoria' => true, 'orden' => 0, 'opciones' => [
                    ['ref' => 'o1', 'texto' => 'Cada semana', 'puntaje' => 80, 'ten_en_cuenta' => 'Publica, pero sin plan.', 'exclusiva' => false, 'orden' => 0],
                    ['ref' => 'o2', 'texto' => 'Nunca', 'puntaje' => 20, 'ten_en_cuenta' => 'No tiene redes activas.', 'exclusiva' => false, 'orden' => 1],
                ]],
                ['ref' => 'p2', 'texto' => '¿Qué formatos usas?', 'tipo' => 'seleccion_multiple', 'indicacion' => 'Marca todos.', 'criterio_ia' => null, 'obligatoria' => true, 'orden' => 1, 'opciones' => [
                    ['ref' => 'o3', 'texto' => 'Fotos', 'puntaje' => 60, 'ten_en_cuenta' => null, 'exclusiva' => false, 'orden' => 0],
                    ['ref' => 'o4', 'texto' => 'Videos', 'puntaje' => 70, 'ten_en_cuenta' => null, 'exclusiva' => false, 'orden' => 1],
                ]],
                ['ref' => 'p3', 'texto' => 'Describe cómo decides qué publicar.', 'tipo' => 'abierta', 'indicacion' => 'Cuéntanos.', 'criterio_ia' => 'Más puntaje si planea.', 'obligatoria' => true, 'orden' => 2, 'opciones' => []],
                ['ref' => 'p4', 'texto' => '¿Algo más?', 'tipo' => 'abierta', 'indicacion' => 'Opcional.', 'criterio_ia' => 'Lo que sea.', 'obligatoria' => false, 'orden' => 3, 'opciones' => []],
            ]],
            ['ref' => 'c2', 'categoria_id' => 2, 'nombre' => 'Automatización', 'importancia' => 40.0, 'orden' => 1, 'preguntas' => [
                ['ref' => 'p5', 'texto' => '¿Automatizas mensajes?', 'tipo' => 'opcion_unica', 'indicacion' => 'Elige una.', 'criterio_ia' => null, 'obligatoria' => true, 'orden' => 0, 'opciones' => [
                    ['ref' => 'o5', 'texto' => 'Sí', 'puntaje' => 100, 'ten_en_cuenta' => null, 'exclusiva' => false, 'orden' => 0],
                    ['ref' => 'o6', 'texto' => 'No', 'puntaje' => 20, 'ten_en_cuenta' => null, 'exclusiva' => false, 'orden' => 1],
                ]],
            ]],
        ],
    ]]);

    $this->empresa = Empresa::factory()->create(['nombre' => 'La Esquina', 'sector_id' => $this->sector->id]);
    $this->laura = User::factory()->create(['name' => 'Laura', 'empresa_id' => $this->empresa->id])->assignRole('Empresa');
    $this->admin = User::factory()->create()->assignRole('Administrador');
    $this->medicion = nuevaMedicion($this, 1);
});

function nuevaMedicion($test, int $numero): Medicion
{
    return Medicion::factory()->create([
        'empresa_id' => $test->empresa->id,
        'version_diagnostico_id' => $test->version->id,
        'numero' => $numero,
        'fecha_limite' => today()->addDays(10),
    ]);
}

/** Responde y envía. La abierta de 80 caracteres vale 40 con la IA de prueba. */
function responderYEnviar($test, Medicion $medicion, string $unica = 'o1', string $automatiza = 'o6'): void
{
    $guardar = fn (array $datos) => $test->actingAs($test->laura)->putJson(route('responder.guardar', $medicion), $datos)->assertOk();
    $guardar(['pregunta_ref' => 'p1', 'opciones' => [$unica]]);
    $guardar(['pregunta_ref' => 'p2', 'opciones' => ['o3', 'o4']]);
    $guardar(['pregunta_ref' => 'p3', 'texto' => str_repeat('a', 80)]);
    $guardar(['pregunta_ref' => 'p5', 'opciones' => [$automatiza]]);

    $test->actingAs($test->laura)->post(route('responder.enviar', $medicion));
}

it('calcula puntajes: única, múltiple con tope 100, promedio y total ponderado', function () {
    $multiple = ['tipo' => 'seleccion_multiple', 'opciones' => [['ref' => 'a', 'puntaje' => 60], ['ref' => 'b', 'puntaje' => 70]]];
    $unica = ['tipo' => 'opcion_unica', 'opciones' => [['ref' => 'a', 'puntaje' => 80], ['ref' => 'b', 'puntaje' => 20]]];

    expect(CalculadoraPuntajes::cerrada($multiple, ['a', 'b']))->toBe(100)
        ->and(CalculadoraPuntajes::cerrada($multiple, ['a']))->toBe(60)
        ->and(CalculadoraPuntajes::cerrada($unica, ['b']))->toBe(20)
        ->and(CalculadoraPuntajes::cerrada($unica, []))->toBeNull()
        ->and(CalculadoraPuntajes::categoria([80, 100, 40, null]))->toBe(73)
        ->and(CalculadoraPuntajes::categoria([null]))->toBeNull()
        ->and(CalculadoraPuntajes::total([['puntaje' => 73, 'importancia' => 60], ['puntaje' => 20, 'importancia' => 40]]))->toBe(52);
});

it('al enviar analiza cada categoría, publica el resultado inmutable y avisa a la empresa', function () {
    responderYEnviar($this, $this->medicion);

    $this->medicion->refresh();
    expect($this->medicion->estado)->toBe('terminada')->and($this->medicion->terminada_en)->not->toBeNull();

    $resultado = Resultado::where('medicion_id', $this->medicion->id)->firstOrFail();
    expect($resultado->puntaje_total)->toBe(52)
        ->and($resultado->nivel)->toBe('mejorar')
        ->and($resultado->variacion)->toBeNull()
        ->and($resultado->por_categoria[0]['puntaje'])->toBe(73)
        ->and($resultado->por_categoria[0]['nivel'])->toBe('camino')
        ->and($resultado->por_categoria[0]['preguntas'][1]['puntaje'])->toBe(100)
        ->and($resultado->por_categoria[0]['preguntas'][2]['puntaje'])->toBe(40)
        ->and($resultado->por_categoria[0]['preguntas'][3]['respuesta'])->toBeNull()
        ->and($resultado->por_categoria[1]['recomendacion']['titulo'])->toBe('Mejora Automatización');

    // Puntajes y observaciones guardados por respuesta.
    expect(Respuesta::where('medicion_id', $this->medicion->id)->where('pregunta_ref', 'p3')->value('puntaje'))->toBe(40)
        ->and(Respuesta::where('medicion_id', $this->medicion->id)->where('pregunta_ref', 'p1')->value('observacion_ia'))->not->toBeNull();

    // Una etapa por categoría, con el prompt exacto guardado.
    $analisis = AnalisisCategoria::where('medicion_id', $this->medicion->id)->get();
    expect($analisis)->toHaveCount(2)
        ->and($analisis->pluck('estado')->unique()->all())->toBe(['terminado'])
        ->and($analisis->firstWhere('categoria_ref', 'c1')->prompt)->toContain('Publica, pero sin plan.')->not->toContain('No tiene redes activas.')->not->toContain('¿Algo más?');

    Notification::assertSentTo($this->laura, ResultadoListo::class);
    Pdf::assertSaved(fn ($pdf, string $ruta) => $pdf->viewName === 'pdf.informe' && $ruta === 'informes/'.$this->empresa->id.'/informe_la_esquina_m1.pdf');
    expect($resultado->fresh()->pdf_ruta)->toBe('informes/'.$this->empresa->id.'/informe_la_esquina_m1.pdf');
});

it('calcula la variación contra la medición anterior, total y por categoría', function () {
    responderYEnviar($this, $this->medicion);
    $segunda = nuevaMedicion($this, 2);
    responderYEnviar($this, $segunda, unica: 'o2', automatiza: 'o5');

    $resultado = Resultado::where('medicion_id', $segunda->id)->firstOrFail();
    // c1: (20 + 100 + 40) / 3 = 53; c2: 100; total = 53·0.6 + 100·0.4 = 72.
    expect($resultado->puntaje_total)->toBe(72)
        ->and($resultado->variacion)->toBe(20)
        ->and($resultado->por_categoria[0]['anterior'])->toBe(73)
        ->and($resultado->por_categoria[0]['variacion'])->toBe(-20)
        ->and($resultado->por_categoria[1]['variacion'])->toBe(80);

    $this->actingAs($this->laura)->get(route('resultados.ver', $segunda))
        ->assertInertia(fn (Assert $p) => $p->component('resultados/Resultado')
            ->where('resultado.anterior.numero', 1)
            ->where('resultado.anterior.puntaje', 52)
            ->where('resultado.prioridades.0.nombre', 'Contenido'));
});

it('arma el prompt por capas: general, sector y empresa, con el formato fijo al final', function () {
    Prompt::create(['etapa' => ArmadorPrompt::ETAPA, 'alcance' => 'sector', 'sector_id' => $this->sector->id, 'modo_detalles' => 'agregar', 'detalles' => 'Piensa en restaurantes.', 'modo_tarea' => 'usar', 'tarea' => 'Esto no se usa.']);
    Prompt::create(['etapa' => ArmadorPrompt::ETAPA, 'alcance' => 'empresa', 'empresa_id' => $this->empresa->id, 'modo_ejemplos' => 'reemplazar', 'ejemplos' => 'Ejemplo propio de La Esquina.']);

    $textos = app(ArmadorPrompt::class)->capas($this->sector->id, $this->empresa->id);

    expect($textos['contexto'])->toBe(ArmadorPrompt::GENERAL['contexto'])
        ->and($textos['tarea'])->toBe(ArmadorPrompt::GENERAL['tarea'])
        ->and($textos['detalles'])->toStartWith(ArmadorPrompt::GENERAL['detalles'])->toEndWith('Piensa en restaurantes.')
        ->and($textos['ejemplos'])->toBe('Ejemplo propio de La Esquina.')
        ->and(ArmadorPrompt::instrucciones($textos))->toEndWith(ArmadorPrompt::FORMATO);
});

it('si una categoría falla queda fallida, avisa al administrador y la empresa la reintenta sola', function () {
    // Falla solo "Automatización".
    app()->bind(Analizador::class, fn () => new class extends AnalizadorDePrueba
    {
        public function analizar(string $instrucciones, array $categoria): array
        {
            throw_if($categoria['nombre'] === 'Automatización', new RuntimeException('Sin conexión'));

            return parent::analizar($instrucciones, $categoria);
        }
    });

    try {
        responderYEnviar($this, $this->medicion);
    } catch (RuntimeException) {
        // La cola síncrona de las pruebas deja pasar el error del trabajo.
    }

    expect($this->medicion->refresh()->estado)->toBe('enviada')
        ->and(AnalisisCategoria::where('categoria_ref', 'c1')->value('estado'))->toBe('terminado')
        ->and(AnalisisCategoria::where('categoria_ref', 'c2')->value('estado'))->toBe('fallido');
    Notification::assertSentTo($this->admin, AnalisisFallido::class);

    $this->actingAs($this->laura)->get(route('responder.enviado', $this->medicion))
        ->assertInertia(fn (Assert $p) => $p->component('responder/Enviado')
            ->where('analisis.total', 2)
            ->where('analisis.terminadas', 1)
            ->where('analisis.fallidas', ['Automatización']));

    app()->bind(Analizador::class, fn () => new AnalizadorDePrueba);
    $this->actingAs($this->laura)->post(route('responder.reintentar', $this->medicion))
        ->assertRedirect(route('responder.enviado', $this->medicion));

    expect($this->medicion->refresh()->estado)->toBe('terminada');
    $this->actingAs($this->laura)->get(route('responder.enviado', $this->medicion))->assertRedirect(route('resultados.ver', $this->medicion));
});

it('el envío deja el análisis en la cola', function () {
    Queue::fake();
    responderYEnviar($this, $this->medicion);

    expect($this->medicion->refresh()->estado)->toBe('enviada');
    Queue::assertPushed(AnalizarMedicion::class, fn ($job) => $job->medicionId === $this->medicion->id);

    $this->actingAs($this->laura)->get(route('resultados.ver', $this->medicion))->assertRedirect(route('responder.enviado', $this->medicion));
    $this->actingAs($this->admin)->get(route('resultados.ver', $this->medicion))->assertNotFound();
});

it('muestra E6 y E9 a la empresa y al administrador; otra empresa no los ve', function () {
    responderYEnviar($this, $this->medicion);

    $this->actingAs($this->laura)->get(route('resultados.ver', $this->medicion))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('resultados/Resultado')
            ->where('interno', false)
            ->where('medicion.empresa.nombre', 'La Esquina')
            ->where('resultado.puntaje', 52)
            ->where('resultado.nivel', 'mejorar')
            ->where('resultado.variacion', null)
            ->where('resultado.anterior', null)
            ->has('resultado.categorias', 2)
            ->has('resultado.prioridades', 2)
            ->where('resultado.prioridades.0.nombre', 'Automatización'));

    $this->actingAs($this->laura)->get(route('resultados.respuestas', [$this->medicion, 'categoria' => 'c2']))
        ->assertInertia(fn (Assert $p) => $p->component('resultados/Respuestas')->where('inicial', 'c2')->has('categorias.0.preguntas', 4));

    $this->actingAs($this->admin)->get(route('resultados.ver', $this->medicion))
        ->assertInertia(fn (Assert $p) => $p->where('interno', true));

    $otra = User::factory()->create(['empresa_id' => Empresa::factory()->create()->id])->assignRole('Empresa');
    $this->actingAs($otra)->get(route('resultados.ver', $this->medicion))->assertNotFound();
    $this->actingAs($otra)->get(route('resultados.respuestas', $this->medicion))->assertNotFound();
    $this->actingAs($otra)->get(route('resultados.pdf', $this->medicion))->assertNotFound();
});

it('descarga el PDF guardado; si falta, lo vuelve a generar sin llamar a la IA', function () {
    responderYEnviar($this, $this->medicion);
    $resultado = Resultado::where('medicion_id', $this->medicion->id)->firstOrFail();

    // Con el PDF simulado el archivo no queda en disco: avisa y deja reintentar.
    $this->actingAs($this->laura)->get(route('resultados.pdf', $this->medicion))->assertRedirect();

    Storage::disk('local')->put($resultado->pdf_ruta, '%PDF-1.7 prueba');
    $this->actingAs($this->laura)->get(route('resultados.pdf', $this->medicion))
        ->assertOk()
        ->assertDownload('informe_la_esquina_m1.pdf');
});

it('la plantilla del PDF tiene 3 páginas con radar, tabla y recomendaciones', function () {
    responderYEnviar($this, $this->medicion);
    $resultado = Resultado::where('medicion_id', $this->medicion->id)->firstOrFail();

    $html = view('pdf.informe', ['informe' => DatosResultado::informe($resultado)])->render();

    expect($html)
        ->toContain('Marketing digital de La Esquina')
        ->toContain('Página 3 de 3')
        ->toContain('Recomendaciones priorizadas')
        ->toContain('Mejora Automatización')
        ->toContain('new Chart(')
        ->not->toContain('cdn.');
});
