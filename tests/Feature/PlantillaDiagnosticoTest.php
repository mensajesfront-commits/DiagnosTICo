<?php

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use App\Services\Diagnosticos\LectorPlantilla;
use App\Support\EstadoDiagnostico;
use Database\Seeders\CategoriasSeeder;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * A2.5 · Pegar el contenido del diagnóstico en la plantilla (HU-020 CA-005).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $this->seed(CategoriasSeeder::class);
    $this->admin = User::factory()->create()->assignRole('Administrador');
    $this->sector = Sector::factory()->create(['nombre' => 'Médicos']);
    $this->leer = fn (string $texto) => app(LectorPlantilla::class)
        ->leer($texto, DB::table('categorias')->whereNull('archivado_en')->get(['id', 'nombre']));
});

it('lee la plantilla de ejemplo sin errores ni avisos', function () {
    $lectura = ($this->leer)(LectorPlantilla::EJEMPLO);

    expect($lectura['errores'])->toBe([])
        ->and($lectura['avisos'])->toBe([])
        ->and($lectura['resumen']['categorias'])->toHaveCount(2)
        ->and($lectura['resumen']['preguntas'])->toBe(4)
        ->and($lectura['resumen']['opciones'])->toBe(8)
        ->and(EstadoDiagnostico::calcular($lectura['contenido'])['publicable'])->toBeTrue();
});

it('entiende tipos, marcas, «Ten en cuenta» y textos de varias líneas sin mirar mayúsculas ni tildes', function () {
    $lectura = ($this->leer)(<<<'TXT'
    categoria: presencia EN linea
    Importancia: 100 %

    Pregunta: ¿Dónde te encuentran?
    tipo: opcion multiple
    obligatoria: no
    Indicacion: Marca todas
    las que uses.
    opciones:
    * 60 | Google
    - 40 | Otro | otro | Ten en cuenta: Pregunta cuál.
    - 0 | Ninguno | NINGUNO
    TXT);

    expect($lectura['errores'])->toBe([]);

    $pregunta = $lectura['contenido']['categorias'][0]['preguntas'][0];
    expect($pregunta['tipo'])->toBe('seleccion_multiple')
        ->and($pregunta['obligatoria'])->toBeFalse()
        ->and($pregunta['indicacion'])->toBe("Marca todas\nlas que uses.")
        ->and($pregunta['opciones'][1])->toMatchArray(['texto' => 'Otro', 'puntaje' => 40, 'con_texto' => true, 'ten_en_cuenta' => 'Pregunta cuál.'])
        ->and($pregunta['opciones'][2]['exclusiva'])->toBeTrue()
        ->and($lectura['contenido']['categorias'][0]['nombre'])->toBe('Presencia en línea');
});

it('reparte la importancia en partes iguales si ninguna categoría la trae', function () {
    $lectura = ($this->leer)(<<<'TXT'
    CATEGORÍA: Presencia en línea
    PREGUNTA: ¿Tienes web?
    TIPO: Opción única
    INDICACIÓN: Responde sí o no.
    OPCIONES:
    - 100 | Sí
    - 0 | No

    CATEGORÍA: Análisis del cliente
    PREGUNTA: Describe a tu cliente.
    TIPO: Abierta
    INDICACIÓN: Con tus palabras.
    CATEGORÍA: Recursos humanos
    PREGUNTA: ¿Quién hace el marketing?
    TIPO: Abierta
    INDICACIÓN: Con tus palabras.
    TXT);

    expect($lectura['errores'])->toBe([])
        ->and(array_column($lectura['contenido']['categorias'], 'importancia'))->toBe([33.33, 33.33, 33.34])
        ->and($lectura['avisos'])->toBe([]);
});

it('da cada error con su línea: categoría fuera del catálogo, tipo, puntaje y opciones mal puestas', function () {
    $lectura = ($this->leer)(<<<'TXT'
    CATEGORÍA: Marketing de influencers
    IMPORTANCIA: 50
    CATEGORÍA: Presencia en línea
    IMPORTANCIA: 50
    PREGUNTA: ¿Tienes web?
    TIPO: Verdadero o falso
    INDICACIÓN: Responde.
    OPCIONES:
    - 150 | Sí
    - 0 | No | NINGUNA
    - 10 Sin barra
    PREGUNTA: Cuéntanos.
    TIPO: Abierta
    INDICACIÓN: Libre.
    OPCIONES:
    - 50 | Esto sobra
    TXT);

    $lineas = array_column($lectura['errores'], 'linea');

    expect($lineas)->toContain(1, 6, 9, 11, 12)
        ->and($lectura['errores'][0]['mensaje'])->toContain('no está en el catálogo')
        ->and(collect($lectura['errores'])->pluck('mensaje')->join(' '))
        ->toContain('Tipo «Verdadero o falso» no reconocido')
        ->toContain('puntaje de 0 a 100')
        ->toContain('no lleva opciones')
        // Con errores no se calculan los avisos de publicación.
        ->and($lectura['avisos'])->toBe([]);
});

it('avisa lo que falta para publicar sin impedir crearlo', function () {
    $lectura = ($this->leer)(<<<'TXT'
    CATEGORÍA: Presencia en línea
    IMPORTANCIA: 80
    PREGUNTA: ¿Dónde publicas?
    TIPO: Selección múltiple
    OPCIONES:
    - 50 | Instagram
    - 30 | Facebook
    TXT);

    expect($lectura['errores'])->toBe([])
        ->and(collect($lectura['avisos'])->pluck('mensaje')->join(' '))
        ->toContain('suma 80%')
        ->toContain('Falta la indicación')
        ->toContain('suma solo 80');
});

it('A2.5 trae la plantilla y revisar no guarda nada', function () {
    $this->actingAs($this->admin)->get(route('diagnosticos.crear'))
        ->assertInertia(fn (Assert $p) => $p->component('diagnosticos/Crear')->where('plantilla', LectorPlantilla::EJEMPLO));

    $this->actingAs($this->admin)
        ->postJson(route('diagnosticos.plantilla.revisar'), ['texto' => LectorPlantilla::EJEMPLO])
        ->assertOk()
        ->assertJsonPath('resumen.preguntas', 4)
        ->assertJsonPath('errores', [])
        ->assertJsonMissingPath('contenido');

    expect(Diagnostico::count())->toBe(0);
});

it('crea el borrador lleno desde el texto pegado y abre el editor', function () {
    $this->actingAs($this->admin)->post(route('diagnosticos.guardar'), [
        'nombre' => 'Diagnóstico para consultorios',
        'sector_id' => $this->sector->id,
        'punto_partida' => 'pegar',
        'texto' => LectorPlantilla::EJEMPLO,
    ])->assertSessionHasNoErrors()->assertRedirectContains('/editar');

    $diagnostico = Diagnostico::where('nombre', 'Diagnóstico para consultorios')->firstOrFail();
    $multiple = $diagnostico->preguntas()->where('tipo', 'seleccion_multiple')->with('opciones')->firstOrFail();

    expect($diagnostico->estado)->toBe(Diagnostico::BORRADOR)
        ->and($diagnostico->partes()->pluck('importancia')->map(fn ($i) => (float) $i)->all())->toBe([60.0, 40.0])
        ->and($diagnostico->preguntas()->count())->toBe(4)
        ->and($multiple->opciones)->toHaveCount(5)
        ->and($multiple->opciones->firstWhere('texto', 'Otra')->con_texto)->toBeTrue()
        ->and($multiple->opciones->firstWhere('texto', 'Ninguna')->exclusiva)->toBeTrue()
        ->and($diagnostico->preguntas()->where('tipo', 'abierta')->where('obligatoria', true)->value('criterio_ia'))->toContain('60: describe al cliente');
});

it('con errores en el texto no crea nada y dice cuáles', function () {
    $this->actingAs($this->admin)->post(route('diagnosticos.guardar'), [
        'nombre' => 'Con errores',
        'sector_id' => $this->sector->id,
        'punto_partida' => 'pegar',
        'texto' => "CATEGORÍA: No existe\nPREGUNTA: Hola",
    ])->assertSessionHasErrors('texto');

    expect(Diagnostico::where('nombre', 'Con errores')->exists())->toBeFalse();

    $this->actingAs($this->admin)->post(route('diagnosticos.guardar'), [
        'nombre' => 'Sin texto',
        'sector_id' => $this->sector->id,
        'punto_partida' => 'pegar',
    ])->assertSessionHasErrors(['texto' => 'Pega el contenido del diagnóstico.']);
});

it('solo quien edita diagnósticos puede revisar la plantilla', function () {
    $empresa = User::factory()->create(['empresa_id' => Empresa::factory()->create()->id])->assignRole('Empresa');

    $this->actingAs($empresa)
        ->postJson(route('diagnosticos.plantilla.revisar'), ['texto' => LectorPlantilla::EJEMPLO])
        ->assertForbidden();
});
