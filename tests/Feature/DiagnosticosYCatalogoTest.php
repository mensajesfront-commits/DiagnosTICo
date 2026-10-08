<?php

use App\Models\Categoria;
use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\SectorReasignado;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * A2 · Diagnósticos, sectores y catálogo de categorías (T-049, T-050).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $this->admin = User::factory()->create()->assignRole('Administrador');
});

// --- A2 / A2·T · Lista ------------------------------------------------------------

it('muestra Todos y un sector con su resumen, diagnósticos y empresas', function () {
    $abogados = Sector::factory()->create(['nombre' => 'Abogados']);
    $publicado = Diagnostico::factory()->publicado()->create(['sector_id' => $abogados->id, 'nombre' => 'Diagnóstico general']);
    Diagnostico::factory()->conCategorias(3)->create(['sector_id' => $abogados->id]);
    Diagnostico::factory()->create(['sector_id' => $abogados->id, 'estado' => Diagnostico::ARCHIVADO]);
    $empresa = Empresa::factory()->create(['sector_id' => $abogados->id]);
    Medicion::factory()->create([
        'empresa_id' => $empresa->id,
        'version_diagnostico_id' => $publicado->versiones()->first()->id,
        'estado' => 'en_curso',
    ]);

    $this->actingAs($this->admin)->get(route('diagnosticos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('diagnosticos/Index')
            ->where('sector', null)
            ->has('diagnosticos', 2)
            ->where('resumen.sectores_activos', 1));

    $this->actingAs($this->admin)->get(route('diagnosticos.index', ['sector' => $abogados->id]))
        ->assertInertia(fn (Assert $p) => $p
            ->where('sector.nombre', 'Abogados')
            ->where('sector.diagnosticos', 2)
            ->where('resumen.empresas', 1)
            ->where('resumen.publicados', 1)
            ->where('resumen.borradores', 1)
            ->where('resumen.ultima_medicion.en_curso', 1)
            ->has('empresas', 1)
            ->where('empresas.0.medicion.estado', 'en_curso')
            ->where('empresas.0.medicion.avance.total', 2)
            ->where('diagnosticos', fn ($filas) => collect($filas)->firstWhere('id', $publicado->id)['version_publicada'] === 1));
});

it('no deja ver ni editar diagnósticos a una cuenta de empresa', function () {
    $empresa = User::factory()->create(['empresa_id' => Empresa::factory()->create()->id])->assignRole('Empresa');

    $this->actingAs($empresa)->get(route('diagnosticos.index'))->assertForbidden();
    $this->actingAs($empresa)->post(route('sectores.store'), ['nombre' => 'X'])->assertForbidden();
});

// --- A2.5 · Crear, A2.7 · Duplicar, archivar y eliminar ------------------------------

it('crea un borrador en blanco con importancias que suman 100', function () {
    $sector = Sector::factory()->create();
    $categorias = Categoria::factory()->count(3)->create();

    $this->actingAs($this->admin)->post(route('diagnosticos.guardar'), [
        'nombre' => 'Diagnóstico de redes',
        'sector_id' => $sector->id,
        'punto_partida' => 'blanco',
        'categorias' => $categorias->pluck('id')->all(),
    ])->assertRedirect(route('diagnosticos.index', ['sector' => $sector->id]));

    $diagnostico = Diagnostico::where('nombre', 'Diagnóstico de redes')->firstOrFail();
    expect($diagnostico->estado)->toBe(Diagnostico::BORRADOR)
        ->and($diagnostico->partes)->toHaveCount(3)
        ->and(round((float) $diagnostico->partes->sum('importancia'), 2))->toBe(100.0);
});

it('no deja repetir el nombre de un diagnóstico en el mismo sector, sin mirar mayúsculas ni espacios', function () {
    $sector = Sector::factory()->create();
    $otroSector = Sector::factory()->create();
    $existente = Diagnostico::factory()->create(['nombre' => 'Diagnóstico general', 'sector_id' => $sector->id]);
    $categoria = Categoria::factory()->create();
    $crear = fn (string $nombre, int $sectorId) => $this->actingAs($this->admin)->post(route('diagnosticos.guardar'), [
        'nombre' => $nombre,
        'sector_id' => $sectorId,
        'punto_partida' => 'blanco',
        'categorias' => [$categoria->id],
    ]);

    // Mismo sector, escrito distinto: no.
    $crear('  diagnóstico   GENERAL ', $sector->id)
        ->assertSessionHasErrors(['nombre' => 'Este sector ya tiene un diagnóstico con ese nombre. Elige otro.']);

    // Duplicar al mismo sector con el mismo nombre: no.
    $this->actingAs($this->admin)->post(route('diagnosticos.duplicar', $existente), [
        'nombre' => 'Diagnóstico General',
        'sector_id' => $sector->id,
    ])->assertSessionHasErrors('nombre');

    // Un archivado del sector también cuenta.
    $existente->update(['estado' => Diagnostico::ARCHIVADO, 'archivado_en' => now()]);
    $crear('Diagnóstico general', $sector->id)->assertSessionHasErrors('nombre');

    // En otro sector sí se puede, y se guarda sin espacios de más.
    $crear('  Diagnóstico   general ', $otroSector->id)->assertSessionHasNoErrors();

    expect(Diagnostico::where('sector_id', $otroSector->id)->where('nombre', 'Diagnóstico general')->exists())->toBeTrue()
        ->and(Diagnostico::count())->toBe(2);
});

it('crea copiando la última versión publicada y duplica un diagnóstico', function () {
    $origen = Diagnostico::factory()->publicado()->create();
    $sector = Sector::factory()->create();

    $this->actingAs($this->admin)->post(route('diagnosticos.guardar'), [
        'nombre' => 'Copia',
        'sector_id' => $sector->id,
        'punto_partida' => 'copia',
        'copiar_de' => $origen->id,
    ])->assertSessionHasNoErrors();
    $copia = Diagnostico::where('nombre', 'Copia')->firstOrFail();
    expect($copia->preguntas()->count())->toBe(2);

    $this->actingAs($this->admin)->post(route('diagnosticos.duplicar', $origen), [
        'nombre' => 'Duplicado',
        'sector_id' => $sector->id,
    ])->assertSessionHasNoErrors();
    expect(Diagnostico::where('nombre', 'Duplicado')->firstOrFail()->partes()->count())->toBe(2);
});

it('archiva un publicado, elimina un borrador y no elimina uno con versiones', function () {
    $publicado = Diagnostico::factory()->publicado()->create();
    $borrador = Diagnostico::factory()->create();

    $this->actingAs($this->admin)->delete(route('diagnosticos.eliminar', $publicado))->assertSessionHasErrors('diagnostico');
    $this->actingAs($this->admin)->post(route('diagnosticos.archivar', $publicado))->assertRedirect();
    $this->actingAs($this->admin)->delete(route('diagnosticos.eliminar', $borrador))->assertRedirect();

    expect($publicado->refresh()->estado)->toBe(Diagnostico::ARCHIVADO)
        ->and(Diagnostico::find($borrador->id))->toBeNull();
});

it('descarta el borrador pendiente y vuelve a la versión publicada', function () {
    $diagnostico = Diagnostico::factory()->publicado()->create();
    $diagnostico->forceFill(['version_borrador' => 2])->save();
    $diagnostico->partes()->first()->delete();
    expect($diagnostico->partes()->count())->toBe(1);

    $this->actingAs($this->admin)->delete(route('diagnosticos.eliminar-borrador', $diagnostico))->assertRedirect();

    expect($diagnostico->refresh()->version_borrador)->toBeNull()
        ->and($diagnostico->partes()->count())->toBe(2);
});

// --- A2.2 · Sectores -------------------------------------------------------------

it('crea, edita, desactiva y reactiva un sector', function () {
    $this->actingAs($this->admin)->post(route('sectores.store'), ['nombre' => 'Veterinarias', 'descripcion' => 'Clínicas'])
        ->assertSessionHasNoErrors();
    $sector = Sector::where('nombre', 'Veterinarias')->firstOrFail();

    $this->actingAs($this->admin)->post(route('sectores.store'), ['nombre' => 'Veterinarias'])
        ->assertSessionHasErrors(['nombre' => 'Ya existe un sector con ese nombre.']);

    $this->actingAs($this->admin)->put(route('sectores.update', $sector), ['nombre' => 'Mascotas'])->assertSessionHasNoErrors();
    $this->actingAs($this->admin)->post(route('sectores.desactivar', $sector));
    expect($sector->refresh()->activo)->toBeFalse()->and($sector->nombre)->toBe('Mascotas');

    $this->actingAs($this->admin)->post(route('sectores.reactivar', $sector));
    expect($sector->refresh()->activo)->toBeTrue();
});

it('reasigna las empresas con aviso y luego deja eliminar el sector vacío (RN-008)', function () {
    Notification::fake();
    $origen = Sector::factory()->create();
    $destino = Sector::factory()->create(['nombre' => 'Destino']);
    $empresa = Empresa::factory()->create(['sector_id' => $origen->id]);
    $principal = User::factory()->create(['empresa_id' => $empresa->id])->assignRole('Empresa');

    $this->actingAs($this->admin)->delete(route('sectores.destroy', $origen))->assertSessionHasErrors('sector');

    $this->actingAs($this->admin)->post(route('sectores.reasignar', $origen), [
        'sector_destino_id' => $destino->id,
        'avisar' => true,
    ])->assertSessionHasNoErrors();

    expect($empresa->refresh()->sector_id)->toBe($destino->id);
    Notification::assertSentTo($principal, SectorReasignado::class);

    $this->actingAs($this->admin)->delete(route('sectores.destroy', $origen))->assertRedirect(route('diagnosticos.index'));
    expect(Sector::find($origen->id))->toBeNull();
});

// --- A2.3 · Catálogo de categorías -------------------------------------------------

it('lista el catálogo y crea una categoría agregándola a un borrador', function () {
    $borrador = Diagnostico::factory()->conCategorias(2)->create();

    $this->actingAs($this->admin)->post(route('categorias.store'), [
        'nombre' => 'Ventas en línea',
        'diagnosticos' => [$borrador->id],
    ])->assertSessionHasNoErrors();

    expect($borrador->partes()->count())->toBe(3);

    $this->actingAs($this->admin)->get(route('categorias.index'))
        ->assertInertia(fn (Assert $p) => $p->component('categorias/Index')
            ->has('categorias', 3)
            ->has('borradores', 1)
            ->where('totalDiagnosticos', 1));
});

it('archiva una categoría (sale de los borradores) y la restaura', function () {
    $borrador = Diagnostico::factory()->conCategorias(2)->create();
    $categoria = $borrador->partes()->first()->categoria;

    $this->actingAs($this->admin)->post(route('categorias.archivar', $categoria))->assertRedirect();
    expect($categoria->refresh()->archivada())->toBeTrue()
        ->and($borrador->partes()->count())->toBe(1);

    $this->actingAs($this->admin)->post(route('categorias.restaurar', $categoria));
    expect($categoria->refresh()->archivada())->toBeFalse();
});

it('no elimina una categoría que ya tiene respuestas (RN-009)', function () {
    $diagnostico = Diagnostico::factory()->publicado()->create();
    $categoria = $diagnostico->partes()->first()->categoria;
    Medicion::factory()->create(['version_diagnostico_id' => $diagnostico->versiones()->first()->id, 'estado' => 'terminada']);

    $this->actingAs($this->admin)->delete(route('categorias.destroy', $categoria))->assertSessionHasErrors('categoria');

    $libre = Categoria::factory()->create();
    $this->actingAs($this->admin)->delete(route('categorias.destroy', $libre))->assertRedirect();
    expect(Categoria::find($libre->id))->toBeNull();
});
