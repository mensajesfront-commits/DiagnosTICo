<?php

use App\Models\AnalisisCategoria;
use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Prompt;
use App\Models\Respuesta;
use App\Models\Sector;
use App\Models\User;
use App\Services\Ia\Analizador;
use App\Services\Ia\AnalizadorDePrueba;
use App\Services\Ia\ArmadorPrompt;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * A4 · Configuración de la IA (HU-041 a HU-045, HU-080).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $this->admin = User::factory()->create(['name' => 'Cristian'])->assignRole('Administrador');
    $this->sector = Sector::factory()->create(['nombre' => 'Abogados', 'descripcion' => 'Bufetes que atienden empresas.']);
    $this->otroSector = Sector::factory()->create(['nombre' => 'Comidas']);
    $diagnostico = Diagnostico::factory()->publicado()->create(['sector_id' => $this->sector->id]);
    $this->version = $diagnostico->versiones()->firstOrFail();
    $this->empresa = Empresa::factory()->create(['nombre' => 'Rojas & Asociados', 'sector_id' => $this->sector->id]);
    Empresa::factory()->create(['nombre' => 'Gómez Abogados', 'sector_id' => $this->sector->id]);

    $this->medicion = Medicion::factory()->create([
        'empresa_id' => $this->empresa->id, 'version_diagnostico_id' => $this->version->id, 'numero' => 1, 'estado' => 'terminada',
    ]);
    $this->categoria = $this->version->contenido['categorias'][0];
    $pregunta = $this->categoria['preguntas'][0];
    Respuesta::create(['medicion_id' => $this->medicion->id, 'pregunta_ref' => $pregunta['ref'], 'opciones_elegidas' => [$pregunta['opciones'][0]['ref']]]);
});

function borradorSector($test, array $cambios = []): array
{
    return array_replace_recursive([
        'alcance' => 'sector',
        'id' => $test->sector->id,
        'modos' => ['contexto' => 'usar', 'tarea' => 'usar', 'detalles' => 'agregar', 'ejemplos' => 'usar'],
        'textos' => ['detalles' => 'No premies prometer resultados.'],
    ], $cambios);
}

it('muestra las instrucciones generales por defecto; solo con ia.ver', function () {
    $this->actingAs($this->admin)->get(route('ia.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('configuracion-ia/Index')
            ->where('alcance', 'general')
            ->where('general.textos.tarea', ArmadorPrompt::GENERAL['tarea'])
            ->has('variables', 4)
            ->where('resumen.sectores_con_ajuste', 0)
            ->has('ejemplos', 1)
            ->where('ejemplos.0.empresa', 'Rojas & Asociados'));

    $empresa = User::factory()->create(['empresa_id' => $this->empresa->id])->assignRole('Empresa');
    $this->actingAs($empresa)->get(route('ia.index'))->assertForbidden();
    $this->actingAs($empresa)->put(route('ia.guardar'), ['alcance' => 'general'])->assertForbidden();
});

it('guarda las instrucciones generales; contexto y tarea son obligatorios', function () {
    $this->actingAs($this->admin)->put(route('ia.guardar'), ['alcance' => 'general', 'textos' => ['contexto' => '', 'tarea' => '']])
        ->assertSessionHasErrors(['textos.contexto', 'textos.tarea']);

    $this->actingAs($this->admin)->put(route('ia.guardar'), [
        'alcance' => 'general',
        'textos' => ['contexto' => 'Eres consultor del sector {sector}.', 'tarea' => 'Analiza {respuestas_categoria}.', 'detalles' => '', 'ejemplos' => null],
    ])->assertSessionHasNoErrors();

    expect(ArmadorPrompt::textosGenerales())->toBe(['contexto' => 'Eres consultor del sector {sector}.', 'tarea' => 'Analiza {respuestas_categoria}.', 'detalles' => '', 'ejemplos' => ''])
        ->and(Prompt::where('alcance', 'general')->value('actualizado_por'))->toBe($this->admin->id);

    $this->actingAs($this->admin)->get(route('ia.index'))
        ->assertInertia(fn (Assert $p) => $p->where('ultima_edicion.por', 'Cristian'));
});

it('guarda el ajuste de un sector y avisa qué empresas tienen el suyo', function () {
    $this->actingAs($this->admin)->put(route('ia.guardar'), borradorSector($this, ['modos' => ['contexto' => 'reemplazar'], 'textos' => ['contexto' => '']]))
        ->assertSessionHasErrors('textos.contexto');

    $this->actingAs($this->admin)->put(route('ia.guardar'), borradorSector($this))->assertSessionHasNoErrors();
    Prompt::create(['etapa' => ArmadorPrompt::ETAPA, 'alcance' => 'empresa', 'empresa_id' => $this->empresa->id, 'modo_contexto' => 'agregar', 'contexto' => 'Solo atiende empresas.']);

    $this->actingAs($this->admin)->get(route('ia.index', ['alcance' => 'sector', 'sector' => $this->sector->id]))
        ->assertInertia(fn (Assert $p) => $p->where('alcance', 'sector')
            ->where('sector.nombre', 'Abogados')
            ->where('sector.empresas', 2)
            ->where('sector.empresas_con_ajuste', ['Rojas & Asociados'])
            ->where('sector.ajuste.modos.detalles', 'agregar')
            ->where('resumen.sectores_con_ajuste', 1)
            ->where('resumen.empresas_con_ajuste', 1));

    // La empresa hereda el detalle del sector.
    $this->actingAs($this->admin)->get(route('ia.index', ['alcance' => 'empresa', 'empresa' => $this->empresa->id]))
        ->assertInertia(fn (Assert $p) => $p->where('empresa.sector_con_ajuste', true)
            ->where('empresa.ajuste.modos.contexto', 'agregar')
            ->where('origen_base.detalles', 'sector')
            ->where('origen_base.tarea', 'general'));

    $this->actingAs($this->admin)->delete(route('ia.quitar'), ['alcance' => 'sector', 'id' => $this->sector->id])->assertSessionHasNoErrors();
    expect(ArmadorPrompt::ajuste('sector', $this->sector->id))->toBeNull()
        ->and(ArmadorPrompt::ajuste('empresa', $this->empresa->id))->not->toBeNull();
});

it('muestra el prompt completo de un ejemplo con el borrador, los datos insertados y el origen de cada parte', function () {
    $respuesta = $this->actingAs($this->admin)->postJson(route('ia.vista-previa'), [
        'medicion_id' => $this->medicion->id,
        'categoria_ref' => $this->categoria['ref'],
        'borrador' => borradorSector($this),
    ])->assertOk()->json();

    $contexto = collect($respuesta['partes'])->firstWhere('elemento', 'contexto')['piezas'];
    $detalles = collect($respuesta['partes'])->firstWhere('elemento', 'detalles')['piezas'];

    expect(collect($contexto)->where('dato', true)->pluck('texto')->all())->toBe(['Abogados', 'Bufetes que atienden empresas.', $this->categoria['nombre']])
        ->and(collect($detalles)->last())->toBe(['texto' => 'No premies prometer resultados.', 'origen' => 'sector', 'dato' => false])
        ->and($respuesta['texto_exacto'])->toContain('## Preguntas con respuestas')->not->toContain('{sector}')
        ->and($respuesta['preguntas'])->toHaveCount(1)
        ->and($respuesta['ejemplo']['empresa'])->toBe('Rojas & Asociados');

    // El borrador no se guardó.
    expect(Prompt::count())->toBe(0);
});

it('prueba el prompt con un ejemplo sin guardar nada; si la IA falla, muestra el error', function () {
    $this->actingAs($this->admin)->postJson(route('ia.probar'), [
        'medicion_id' => $this->medicion->id,
        'categoria_ref' => $this->categoria['ref'],
        'borrador' => ['alcance' => 'general', 'textos' => ['contexto' => 'Prueba {categoria}', 'tarea' => 'Analiza']],
    ])->assertOk()
        ->assertJsonPath('resultado.preguntas.0.id', $this->categoria['preguntas'][0]['ref'])
        ->assertJsonStructure(['preguntas', 'resultado' => ['observacion_categoria', 'recomendacion' => ['titulo', 'detalle']]]);

    expect(Prompt::count())->toBe(0)->and(AnalisisCategoria::count())->toBe(0);

    app()->bind(Analizador::class, fn () => new class extends AnalizadorDePrueba
    {
        public function analizar(string $instrucciones, array $categoria): array
        {
            throw new RuntimeException('Sin conexión');
        }
    });

    $this->actingAs($this->admin)->postJson(route('ia.probar'), [
        'medicion_id' => $this->medicion->id, 'categoria_ref' => $this->categoria['ref'],
    ])->assertUnprocessable()->assertJsonPath('message', 'La IA no respondió bien: Sin conexión No se guardó nada.');
});

it('reemplaza los datos automáticos al armar el prompt del análisis', function () {
    $armado = app(ArmadorPrompt::class)->armar($this->medicion, $this->categoria, []);

    expect($armado['instrucciones'])
        ->toContain('del sector Abogados. Bufetes que atienden empresas. Estás revisando')
        ->toContain('"'.$this->categoria['nombre'].'"')
        ->toContain(ArmadorPrompt::RESPUESTAS_EN_TEXTO)
        ->not->toContain('{');
});

it('permite 100 pruebas al día entre todos y dice cuántas quedan (PA-003)', function () {
    $datos = ['medicion_id' => $this->medicion->id, 'categoria_ref' => $this->categoria['ref']];
    $llave = 'ia_pruebas:'.now(config('diagnostico.zona_horaria'))->toDateString();

    $this->actingAs($this->admin)->postJson(route('ia.probar'), $datos)
        ->assertOk()->assertJsonPath('restantes', 99);

    Cache::put($llave, 100, now()->addDay());

    $this->actingAs($this->admin)->postJson(route('ia.probar'), $datos)
        ->assertStatus(429)
        ->assertJsonPath('message', 'Ya se hicieron las 100 pruebas de hoy. Mañana se puede volver a probar.');
});
