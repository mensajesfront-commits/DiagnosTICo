<?php

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Resultado;
use App\Models\Sector;
use App\Models\User;
use App\Services\Resultados\ResultadoEjemplo;
use App\Support\Niveles;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

/*
 * Rol Consultor (HU-085, HU-086, RN-030): externo e invitado, solo mira las
 * empresas que el Administrador le vincula. No responde ni toca
 * diagnósticos.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    Notification::fake();

    $sector = Sector::factory()->create(['nombre' => 'Comidas']);
    $diagnostico = Diagnostico::factory()->publicado()->create(['sector_id' => $sector->id]);
    $this->version = $diagnostico->versiones()->firstOrFail();

    $this->suya = Empresa::factory()->create(['nombre' => 'La Esquina', 'sector_id' => $sector->id]);
    $this->otra = Empresa::factory()->create(['nombre' => 'El Trigal', 'sector_id' => $sector->id]);

    $this->admin = User::factory()->create()->assignRole('Administrador');
    $this->consultor = User::factory()->create(['name' => 'Valeria Consultora'])->assignRole('Consultor');
    $this->consultor->empresasAsesoradas()->attach($this->suya->id);

    $this->medicionSuya = medicionTerminada($this, $this->suya);
    $this->medicionOtra = medicionTerminada($this, $this->otra);
});

function medicionTerminada($test, Empresa $empresa): Medicion
{
    $medicion = Medicion::factory()->create([
        'empresa_id' => $empresa->id,
        'version_diagnostico_id' => $test->version->id,
        'numero' => 1,
        'estado' => 'terminada',
    ]);
    $medicion->forceFill(['enviada_en' => now(), 'terminada_en' => now()])->save();

    $datos = ResultadoEjemplo::calcular($test->version->contenido, 'medio');
    Resultado::create([
        'medicion_id' => $medicion->id,
        'puntaje_total' => $datos['puntaje_total'],
        'nivel' => Niveles::de($datos['puntaje_total']),
        'variacion' => null,
        'por_categoria' => $datos['por_categoria'],
        'publicado_en' => now(),
    ]);

    return $medicion;
}

// --- Qué ve el consultor --------------------------------------------------------

it('entra a su propio inicio con solo sus empresas, no al del Administrador', function () {
    $this->actingAs($this->consultor)
        ->get(route('inicio.administrador'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p
            ->component('inicio/Consultor')
            ->has('empresas', 1)
            ->where('empresas.0.nombre', 'La Esquina'));
});

it('en Empresas solo ve las que tiene vinculadas', function () {
    $this->actingAs($this->consultor)
        ->get(route('empresas.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->has('empresas', 1)->where('empresas.0.id', $this->suya->id));
});

it('ve la ficha, el historial y los resultados de su empresa', function () {
    $this->actingAs($this->consultor);

    $this->get(route('empresas.ver', $this->suya))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('empresas/Ver')->where('consultores', null));
    $this->get(route('empresas.historial', $this->suya))->assertOk();
    $this->get(route('resultados.ver', $this->medicionSuya))->assertOk();
    $this->get(route('resultados.respuestas', $this->medicionSuya))->assertOk();
});

it('no ve la ficha, el historial ni los resultados de otra empresa', function () {
    $this->actingAs($this->consultor);

    $this->get(route('empresas.ver', $this->otra))->assertNotFound();
    $this->get(route('empresas.historial', $this->otra))->assertNotFound();
    $this->get(route('resultados.ver', $this->medicionOtra))->assertNotFound();
    $this->get(route('resultados.respuestas', $this->medicionOtra))->assertNotFound();
    $this->get(route('resultados.pdf', $this->medicionOtra))->assertNotFound();
});

it('no responde, no asigna, no edita empresas ni toca diagnósticos', function () {
    $this->actingAs($this->consultor);

    $this->get(route('diagnosticos.index'))->assertForbidden();
    $this->get(route('mediciones.crear'))->assertForbidden();
    $this->get(route('empresas.crear'))->assertForbidden();
    $this->put(route('empresas.actualizar', $this->suya), [])->assertForbidden();
    $this->get(route('usuarios.index'))->assertForbidden();
    $this->get(route('ia.index'))->assertForbidden();
    $this->post(route('empresas.consultores.vincular', $this->otra), ['consultor_id' => $this->consultor->id])->assertForbidden();

    expect($this->consultor->can('diagnostico.responder'))->toBeFalse();
});

it('sin empresas vinculadas no ve ninguna', function () {
    $this->consultor->empresasAsesoradas()->detach();

    $this->actingAs($this->consultor)
        ->get(route('empresas.index'))
        ->assertInertia(fn (Assert $p) => $p->has('empresas', 0));
    $this->get(route('empresas.ver', $this->suya))->assertNotFound();
});

it('el Administrador sigue viendo todas las empresas', function () {
    $this->actingAs($this->admin)
        ->get(route('empresas.index'))
        ->assertInertia(fn (Assert $p) => $p->has('empresas', 2));
    $this->get(route('empresas.ver', $this->otra))->assertOk();
});

// --- Vincular (HU-085) --------------------------------------------------------------

it('el Administrador ve la pestaña Consultores con los vinculados y los disponibles', function () {
    $otroConsultor = User::factory()->create(['name' => 'Bruno'])->assignRole('Consultor');

    $this->actingAs($this->admin)
        ->get(route('empresas.ver', $this->suya))
        ->assertInertia(fn (Assert $p) => $p
            ->has('consultores.vinculados', 1)
            ->where('consultores.vinculados.0.nombre', 'Valeria Consultora')
            ->where('consultores.vinculados.0.empresas', 1)
            ->has('consultores.disponibles', 1)
            ->where('consultores.disponibles.0.id', $otroConsultor->id));
});

it('vincula un consultor a varias empresas y lo quita', function () {
    $this->actingAs($this->admin)
        ->post(route('empresas.consultores.vincular', $this->otra), ['consultor_id' => $this->consultor->id])
        ->assertSessionHasNoErrors();

    expect($this->consultor->empresasAsesoradas()->pluck('empresas.id')->sort()->values()->all())
        ->toBe([$this->suya->id, $this->otra->id]);

    $this->delete(route('empresas.consultores.quitar', [$this->otra, $this->consultor]))->assertRedirect();

    expect($this->consultor->empresasAsesoradas()->pluck('empresas.id')->all())->toBe([$this->suya->id]);
});

it('solo vincula cuentas con el rol Consultor', function () {
    $empresa = User::factory()->create()->assignRole('Administrador');

    $this->actingAs($this->admin)
        ->post(route('empresas.consultores.vincular', $this->otra), ['consultor_id' => $empresa->id])
        ->assertSessionHasErrors('consultor_id');

    expect($this->otra->consultores()->count())->toBe(0);
});

it('invita un consultor con sus empresas', function () {
    $this->actingAs($this->admin)
        ->post(route('usuarios.invitar'), [
            'name' => 'Camilo',
            'email' => 'camilo@asesores.co',
            'rol_id' => Role::findByName('Consultor')->id,
            'empresas' => [$this->suya->id, $this->otra->id],
        ])
        ->assertSessionHasNoErrors();

    $camilo = User::where('email', 'camilo@asesores.co')->firstOrFail();

    expect($camilo->esConsultor())->toBeTrue()
        ->and($camilo->empresasAsesoradas()->count())->toBe(2);
});

it('al invitar con otro rol no vincula empresas', function () {
    $this->actingAs($this->admin)
        ->post(route('usuarios.invitar'), [
            'name' => 'Ana',
            'email' => 'ana@nuevastic.co',
            'rol_id' => Role::findByName('Administrador')->id,
            'empresas' => [$this->suya->id],
        ])
        ->assertSessionHasNoErrors();

    expect(User::where('email', 'ana@nuevastic.co')->firstOrFail()->empresasAsesoradas()->count())->toBe(0);
});

it('al dejar de ser Consultor pierde sus empresas', function () {
    $this->actingAs($this->admin)
        ->put(route('usuarios.cambiar-rol', $this->consultor), ['rol_id' => Role::findByName('Administrador')->id])
        ->assertSessionHasNoErrors();

    expect($this->consultor->fresh()->empresasAsesoradas()->count())->toBe(0);
});
