<?php

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Respuesta;
use App\Models\Resultado;
use App\Models\Sector;
use App\Models\User;
use App\Support\DatosInicio;
use Database\Seeders\RolesYPermisosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * T-048 · Redirección después del login según el rol.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

it('manda al login a quien no ha iniciado sesión', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('inicio.administrador'))->assertRedirect(route('login'));
    $this->get(route('inicio.empresa'))->assertRedirect(route('login'));
});

it('lleva al Administrador a A1', function () {
    $admin = User::factory()->create()->assignRole('Administrador');

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('inicio.administrador'));
    $this->actingAs($admin)->get(route('inicio.administrador'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('inicio/Administrador'));
});

it('lleva a la Empresa y al Colaborador a E1', function (string $rol) {
    $cuenta = User::factory()->create()->assignRole($rol);

    $this->actingAs($cuenta)->get(route('dashboard'))->assertRedirect(route('inicio.empresa'));
    $this->actingAs($cuenta)->get(route('inicio.empresa'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('inicio/Empresa'));
})->with(['Empresa', 'Colaborador']);

it('no deja a cada cuenta entrar al inicio de la otra', function () {
    $admin = User::factory()->create()->assignRole('Administrador');
    $empresa = User::factory()->create()->assignRole('Empresa');

    $this->actingAs($empresa)->get(route('inicio.administrador'))->assertRedirect(route('inicio.empresa'));
    $this->actingAs($admin)->get(route('inicio.empresa'))->assertRedirect(route('inicio.administrador'));
});

it('después del login va a /dashboard', function () {
    $empresa = User::factory()->create()->assignRole('Empresa');

    $this->post(route('login.store'), ['email' => $empresa->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
});

it('A1 muestra indicadores, mediciones, empresas por nivel y diagnósticos por sector', function () {
    $admin = User::factory()->create()->assignRole('Administrador');
    $sector = Sector::factory()->create(['nombre' => 'Comidas']);
    $version = Diagnostico::factory()->publicado()->create(['sector_id' => $sector->id])->versiones()->firstOrFail();
    $refs = collect($version->contenido['categorias'])->map(fn ($c) => $c['preguntas'][0]['ref']);

    $nueva = fn (string $nombre) => Empresa::factory()->create(['nombre' => $nombre, 'sector_id' => $sector->id]);
    $medir = fn (Empresa $e, array $datos = []) => Medicion::factory()->create(['empresa_id' => $e->id, 'version_diagnostico_id' => $version->id, ...$datos]);

    // En curso con 1 de 2 categorías completas.
    $enCurso = $medir($nueva('La Esquina'), ['estado' => 'en_curso', 'fecha_limite' => now()->addWeek()]);
    Respuesta::create(['medicion_id' => $enCurso->id, 'pregunta_ref' => $refs[0], 'puntaje' => 100]);
    // No iniciada que ya pasó su fecha: se ve vencida.
    $medir($nueva('Don Luigi'), ['estado' => 'no_iniciada', 'fecha_limite' => now()->subDay()]);
    // Terminada este mes con resultado.
    $hecha = $medir($nueva('Gómez'), ['estado' => 'terminada']);
    $hecha->forceFill(['terminada_en' => now()])->save();
    Resultado::create(['medicion_id' => $hecha->id, 'puntaje_total' => 83, 'nivel' => 'sigue', 'por_categoria' => [], 'publicado_en' => now()]);
    // Cancelada: no aparece. Empresa sin mediciones: "sin diagnóstico".
    $medir($nueva('Cancelada SAS'), ['estado' => 'cancelada']);

    $this->actingAs($admin)->get(route('inicio.administrador'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('inicio/Administrador')
            ->where('indicadores.empresas', 4)
            ->where('indicadores.sectores_con_empresas', 1)
            ->where('indicadores.pendientes', 2)
            ->where('indicadores.pendientes_por_estado', ['no_iniciada' => 0, 'en_curso' => 1, 'vencida' => 1])
            ->where('indicadores.completados_mes', 1)
            ->where('indicadores.puntaje_promedio', 83)
            ->has('mediciones', 3)
            ->where('mediciones.0.estado', 'vencida')
            ->where('mediciones.0.empresa', 'Don Luigi')
            ->where('mediciones.1.estado', 'en_curso')
            ->where('mediciones.1.avance', ['hechas' => 1, 'total' => 2])
            ->where('mediciones.2.resultado', ['puntaje' => 83, 'nivel' => 'sigue'])
            ->where('niveles.sigue', 1)
            ->where('niveles.sin', 3)
            ->where('sectores', fn ($s) => collect($s)->firstWhere('nombre', 'Comidas')['version'] === 1
                && collect($s)->firstWhere('nombre', 'Comidas')['empresas'] === 4));
});

it('cuenta como completa la categoría con todas sus preguntas obligatorias', function () {
    $contenido = ['categorias' => [
        ['preguntas' => [['ref' => 'p1'], ['ref' => 'p2', 'obligatoria' => false]]],
        ['preguntas' => [['ref' => 'p3'], ['ref' => 'p4']]],
    ]];

    expect(DatosInicio::avance($contenido, ['p1']))->toBe(['hechas' => 1, 'total' => 2])
        ->and(DatosInicio::avance($contenido, ['p1', 'p3', 'p4']))->toBe(['hechas' => 2, 'total' => 2]);
});
