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
use App\Services\Ia\ArmadorPrompt;
use App\Services\Resultados\CalculadoraPuntajes;
use App\Services\Resultados\InformePdf;
use App\Support\DatosResultado;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\Support\AnalizadorFalso;

/*
 * HU-068 puntajes, HU-069 análisis por categoría, HU-084 publicar, E4 avance
 * y reintento (HU-063), E6 resultado, E9 respuestas y PDF (HU-064 a HU-066).
 * La IA es AnalizadorFalso (tests/Pest.php): la abierta
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
        ->and($resultado->por_categoria[1]['recomendacion']['titulo'])->toBe('Ordena lo básico de automatización')
        // La IA de prueba elige el método por el nombre de la categoría.
        ->and($resultado->por_categoria[0]['metodo']['clave'])->toBe('VRIO')
        ->and($resultado->por_categoria[0]['metodo']['nombre'])->toBe('VRIO')
        ->and($resultado->por_categoria[1]['metodo']['clave'])->toBe('DOFA')
        // Lo que encontró el método va en los grupos de ese método.
        ->and(array_column($resultado->por_categoria[1]['hallazgos'], 'grupo'))->toBe(['Fortalezas', 'Oportunidades', 'Debilidades', 'Amenazas'])
        ->and($resultado->por_categoria[1]['impacto'])->not->toBe('')
        ->and($resultado->por_categoria[1]['faltante'])->toHaveCount(1)
        ->and($resultado->por_categoria[1]['fortalezas'])->toHaveCount(1)
        ->and($resultado->por_categoria[1]['recomendaciones'])->toHaveCount(2)
        ->and($resultado->por_categoria[1]['recomendaciones'][0]['pasos'])->toHaveCount(3);

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
    Pdf::assertSaved(fn ($pdf, string $ruta) => $pdf->viewName === 'pdf.resumen' && $ruta === 'informes/'.$this->empresa->id.'/resumen_la_esquina_m1.pdf');
    expect($resultado->fresh()->pdf_ruta)->toBe('informes/'.$this->empresa->id.'/informe_la_esquina_m1.pdf')
        ->and($resultado->fresh()->pdf_resumen_ruta)->toBe('informes/'.$this->empresa->id.'/resumen_la_esquina_m1.pdf');
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
    app()->bind(Analizador::class, fn () => new class extends AnalizadorFalso
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
            ->where('analisis.fallidas', ['Automatización'])
            ->where('analisis.categorias.0.estado', 'terminado')
            ->where('analisis.categorias.1', ['nombre' => 'Automatización', 'estado' => 'fallido']));

    app()->bind(Analizador::class, fn () => new AnalizadorFalso);
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

    Storage::disk('local')->put($resultado->pdf_ruta, '%PDF-1.7 completo');
    Storage::disk('local')->put($resultado->pdf_resumen_ruta, '%PDF-1.7 resumen');

    // La empresa recibe el resumen, también si pide el completo en la URL.
    $this->actingAs($this->laura)->get(route('resultados.pdf', $this->medicion))
        ->assertOk()
        ->assertDownload('resumen_la_esquina_m1.pdf');
    $this->actingAs($this->laura)->get(route('resultados.pdf', [$this->medicion, 'tipo' => 'completo']))
        ->assertDownload('resumen_la_esquina_m1.pdf');
});

it('el Administrador descarga el informe completo y, si lo pide, el resumen de la empresa', function () {
    responderYEnviar($this, $this->medicion);
    $resultado = Resultado::where('medicion_id', $this->medicion->id)->firstOrFail();
    Storage::disk('local')->put($resultado->pdf_ruta, '%PDF-1.7 completo');
    Storage::disk('local')->put($resultado->pdf_resumen_ruta, '%PDF-1.7 resumen');

    $this->actingAs($this->admin)->get(route('resultados.pdf', $this->medicion))
        ->assertDownload('informe_la_esquina_m1.pdf');
    $this->actingAs($this->admin)->get(route('resultados.pdf', [$this->medicion, 'tipo' => 'resumen']))
        ->assertDownload('resumen_la_esquina_m1.pdf');
});

it('el resumen de la empresa copia lo que dijo la IA, en orden y sin el detalle técnico', function () {
    responderYEnviar($this, $this->medicion);
    $resultado = Resultado::where('medicion_id', $this->medicion->id)->firstOrFail();
    $categoria = collect($resultado->por_categoria)->firstWhere('ref', 'c2');

    $html = view('pdf.resumen', ['informe' => DatosResultado::informe($resultado)])->render();

    expect($html)
        ->toContain('Resumen de tu diagnóstico de marketing digital')
        ->toContain('Tus resultados por categoría')
        ->toContain('Por dónde empezar')
        ->toContain('Tu diagnóstico por categoría')
        ->toContain('Qué encontramos')
        ->toContain('Qué hacer')
        ->toContain('Próximos pasos')
        ->toContain(e($categoria['observacion']))
        ->toContain(e($categoria['recomendaciones'][0]['titulo']))
        ->toContain(e($categoria['recomendaciones'][0]['pasos'][0]))
        ->toContain('Se puede mejorar')
        ->not->toContain('Metodología y criterios')
        ->not->toContain('Análisis estratégico DOFA')
        ->not->toContain('Cronograma de implementación')
        ->not->toContain('Indicadores de seguimiento (KPI)')
        ->not->toContain('new Chart(')
        ->not->toContain('cdn.');

    // De la categoría más débil a la más fuerte.
    $ordenadas = collect($resultado->por_categoria)->sortBy('puntaje')->pluck('nombre')->all();
    expect(strpos($html, '<h3>'.$ordenadas[0].'</h3>'))->toBeLessThan(strpos($html, '<h3>'.$ordenadas[1].'</h3>'));
});

it('el informe PDF sigue la estructura de la propuesta: ficha, resumen, metodología, categorías, DOFA, plan, cronograma y KPI', function () {
    responderYEnviar($this, $this->medicion);
    $resultado = Resultado::where('medicion_id', $this->medicion->id)->firstOrFail();

    $html = view('pdf.informe', ['informe' => DatosResultado::informe($resultado)])->render();

    expect($html)
        ->toContain('Informe de Diagnóstico y Estrategia de Marketing Digital')
        ->toContain('Ficha técnica y alcance')
        ->toContain('Resumen ejecutivo')
        ->toContain('Metodología y criterios')
        ->toContain('5 Fuerzas de Porter')
        ->toContain('Comparación y priorización')
        ->toContain('Diagnóstico por categoría')
        ->toContain('Hallazgos del diagnóstico')
        ->toContain('Análisis estratégico DOFA')
        ->toContain('Plan estratégico de marketing')
        ->toContain('Cronograma de implementación')
        ->toContain('Indicadores de seguimiento (KPI)')
        ->toContain('Requiere mejora')
        ->toContain('Ordena lo básico de automatización')
        ->not->toContain('IA real')
        ->not->toContain('Informe de ejemplo:')
        ->toContain('new Chart(')
        ->not->toContain('cdn.');
});

// --- Decisiones de NuevasTIC (23 de octubre de 2026) ------------------------------

it('una opcional respondida es un dato extra: la IA la lee, pero no cuenta en el puntaje', function () {
    $this->actingAs($this->laura)->putJson(route('responder.guardar', $this->medicion), ['pregunta_ref' => 'p4', 'texto' => str_repeat('b', 200)])->assertOk();
    responderYEnviar($this, $this->medicion);

    $resultado = Resultado::where('medicion_id', $this->medicion->id)->firstOrFail();
    $p4 = $resultado->por_categoria[0]['preguntas'][3];

    // Igual que sin responderla: 73 en la categoría y 52 en total.
    expect($resultado->por_categoria[0]['puntaje'])->toBe(73)
        ->and($resultado->puntaje_total)->toBe(52)
        ->and($p4['respuesta'])->toBe(str_repeat('b', 200))
        ->and($p4['extra'])->toBeTrue()
        ->and($p4['puntaje'])->toBeNull()
        ->and(AnalisisCategoria::where('medicion_id', $this->medicion->id)->where('categoria_ref', 'c1')->value('prompt'))
        ->toContain('dato_extra');
});

it('al borrar para siempre la empresa conserva sus mediciones terminadas sin nombre', function () {
    responderYEnviar($this, $this->medicion);
    $pendiente = nuevaMedicion($this, 2);
    $resultado = Resultado::where('medicion_id', $this->medicion->id)->firstOrFail();
    $pdf = $resultado->pdf_ruta;
    $resumen = $resultado->pdf_resumen_ruta;

    $this->laura->delete();
    $this->empresa->delete();
    $this->travel(91)->days();
    $this->artisan('cuentas:purgar')->assertSuccessful();

    $medicion = $this->medicion->fresh();
    $resultado->refresh();
    expect(Empresa::withTrashed()->find($this->empresa->id))->toBeNull()
        ->and($medicion)->not->toBeNull()
        ->and($medicion->empresa_id)->toBeNull()
        ->and($pendiente->fresh())->toBeNull()
        ->and($resultado->puntaje_total)->toBe(52)
        ->and($resultado->pdf_ruta)->toBeNull()
        ->and($resultado->pdf_resumen_ruta)->toBeNull()
        ->and($resultado->por_categoria[0]['preguntas'][2]['respuesta'])->toBeNull()
        ->and($resultado->por_categoria[0]['preguntas'][0]['respuesta'])->toBe('Cada semana')
        ->and(Respuesta::where('medicion_id', $medicion->id)->whereNotNull('texto')->exists())->toBeFalse()
        ->and(AnalisisCategoria::where('medicion_id', $medicion->id)->whereNotNull('prompt')->exists())->toBeFalse();
    Storage::disk('local')->assertMissing($pdf);
    Storage::disk('local')->assertMissing($resumen);

    // Nadie la ve como resultado de una empresa.
    $this->actingAs($this->admin)->get(route('resultados.ver', $medicion))->assertNotFound();
});

it('las fichas del PDF ponen primero la principal de las 3 categorías más débiles', function () {
    responderYEnviar($this, $this->medicion);
    $informe = DatosResultado::informe(Resultado::where('medicion_id', $this->medicion->id)->firstOrFail());

    $primeras = array_column(array_slice($informe['prioridades'], 0, 3), 'nombre');

    $prioritarias = array_values(array_filter($informe['fichas'], fn ($f) => $f['tipo'] === 'prioritaria'));

    expect(array_column($prioritarias, 'categoria'))->toBe($primeras)
        ->and(collect($informe['fichas'])->where('tipo', 'seguimiento'))->not->toBeEmpty()
        ->and($informe['total_exacto'])->toEqualWithDelta($informe['puntaje'], 0.5)
        ->and($informe['cronograma']['filas'][0]['inicio'])->toBe(1)
        ->and($informe['dofa']['fortalezas'])->not->toBeEmpty();
});

it('el PDF lleva encabezado y número de página en cada hoja', function () {
    responderYEnviar($this, $this->medicion);
    $resultado = Resultado::where('medicion_id', $this->medicion->id)->firstOrFail();

    $pdf = InformePdf::construir($resultado);

    expect($pdf->footerViewName)->toBe('pdf.pie')
        ->and($pdf->headerViewName)->toBe('pdf.encabezado')
        ->and(view('pdf.pie')->render())->toContain('class="pageNumber"')->toContain('class="totalPages"');
});

it('un resultado publicado antes del informe nuevo se sigue viendo en el PDF, sin inventar lo que no tiene', function () {
    responderYEnviar($this, $this->medicion);
    $resultado = Resultado::where('medicion_id', $this->medicion->id)->firstOrFail();

    // Como se guardaban antes: sin impacto, DOFA, faltante ni fichas.
    $viejo = array_map(function (array $c) {
        unset($c['impacto'], $c['debilidades'], $c['oportunidades'], $c['amenazas'], $c['faltante'], $c['hallazgos'], $c['fortalezas'], $c['recomendaciones']);

        return $c;
    }, $resultado->por_categoria);
    Resultado::whereKey($resultado->id)->update(['por_categoria' => json_encode($viejo)]);

    $html = view('pdf.informe', ['informe' => DatosResultado::informe($resultado->fresh())])->render();

    expect($html)
        ->toContain('Ordena lo básico de automatización')
        ->toContain('Por definir cuando se mida la línea base.')
        ->toContain('Sin información suficiente en las respuestas.')
        ->toContain('acciones no tienen plazo en semanas');

    // El resumen de la empresa muestra lo que haya y salta lo que falta.
    expect(view('pdf.resumen', ['informe' => DatosResultado::informe($resultado->fresh())])->render())
        ->toContain('Ordena lo básico de automatización')
        ->toContain('Qué encontramos')
        ->not->toContain('Por qué importa')
        ->not->toContain('Lo que ya haces bien');
});

it('el PDF del resultado de ejemplo lo dice en la portada', function () {
    responderYEnviar($this, $this->medicion);
    $informe = DatosResultado::informeDe(['id' => null, 'numero' => 1, 'empresa' => ['id' => null, 'nombre' => 'Empresa de ejemplo'], 'sector' => 'Comidas', 'diagnostico' => 'D', 'version' => 1], DatosResultado::resultado(Resultado::firstOrFail()), null, now(), null, true);

    expect(view('pdf.informe', ['informe' => $informe])->render())->toContain('Informe de ejemplo: los datos y textos son ilustrativos');
});

it('el resumen de E6 trae cuántas categorías hay por nivel y la más fuerte', function () {
    $categorias = [
        ['ref' => 'c1', 'nombre' => 'Uno', 'puntaje' => 20, 'importancia' => 50.0, 'nivel' => 'critico', 'recomendacion' => ['titulo' => 'A', 'detalle' => '']],
        ['ref' => 'c2', 'nombre' => 'Dos', 'puntaje' => 85, 'importancia' => 30.0, 'nivel' => 'sigue', 'recomendacion' => ['titulo' => 'B', 'detalle' => '']],
        ['ref' => 'c3', 'nombre' => 'Tres', 'puntaje' => 25, 'importancia' => 20.0, 'nivel' => 'critico', 'recomendacion' => ['titulo' => 'C', 'detalle' => '']],
    ];

    $pagina = DatosResultado::pagina(['puntaje_total' => 41, 'nivel' => 'mejorar', 'variacion' => null, 'por_categoria' => $categorias], null, now(), false);

    expect(collect($pagina['por_nivel'])->pluck('cantidad', 'clave')->all())->toBe(['critico' => 2, 'mejorar' => 0, 'camino' => 0, 'sigue' => 1])
        ->and($pagina['mas_fuerte']['nombre'])->toBe('Dos')
        ->and($pagina['prioridades'][0]['nombre'])->toBe('Uno');
});
