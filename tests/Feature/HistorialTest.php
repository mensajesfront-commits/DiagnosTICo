<?php

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Resultado;
use App\Models\Sector;
use App\Models\User;
use App\Services\Mediciones\AsignarMedicion;
use App\Support\DatosHistorial;
use App\Support\Plazos;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * E8 · Mi historial (HU-067) y A3.4 · Historial de una empresa (HU-039).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $sector = Sector::factory()->create(['nombre' => 'Comidas']);
    $version = Diagnostico::factory()->publicado()->create(['sector_id' => $sector->id])->versiones()->firstOrFail();
    $this->empresa = Empresa::factory()->create(['nombre' => 'La Esquina', 'sector_id' => $sector->id]);
    $this->laura = User::factory()->create(['empresa_id' => $this->empresa->id])->assignRole('Empresa');
    $this->admin = User::factory()->create()->assignRole('Administrador');

    $medicion = fn (int $n, string $estado, array $extra = []) => Medicion::factory()->create([
        'empresa_id' => $this->empresa->id, 'version_diagnostico_id' => $version->id, 'numero' => $n, 'estado' => $estado, ...$extra,
    ]);

    foreach ([[1, 41], [2, 49]] as [$n, $puntaje]) {
        $m = $medicion($n, 'terminada', ['terminada_en' => now()->subMonths(6 - $n)]);
        Resultado::create(['medicion_id' => $m->id, 'puntaje_total' => $puntaje, 'nivel' => 'mejorar', 'publicado_en' => now(), 'por_categoria' => [
            ['ref' => 'c1', 'nombre' => 'Redes sociales', 'puntaje' => $puntaje + 10],
        ]]);
    }

    $this->pendiente = $medicion(3, 'en_curso', ['fecha_limite' => today()->addDays(7)]);
    $medicion(4, 'cancelada');
});

it('muestra a la empresa sus mediciones terminadas, la evolución y la pendiente', function () {
    $this->actingAs($this->laura)->get(route('historial.mio'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('historial/Historial')
            ->where('interno', false)
            ->where('empresa.nombre', 'La Esquina')
            ->has('mediciones', 2)
            ->where('mediciones.0.numero', 2)
            ->where('mediciones.0.variacion', 8)
            ->where('mediciones.1.variacion', null)
            ->has('evolucion', 2)
            ->where('evolucion_categorias.0.nombre', 'Redes sociales')
            ->where('evolucion_categorias.0.puntos.1.puntaje', 59)
            ->where('pendiente.numero', 3)
            ->where('pendiente.estado', 'en_curso')
            ->missing('pendiente.ultimo_aviso')
            // Cifras de arriba: la última (49), +8 desde la primera (41) y la mejor.
            ->where('resumen.cantidad', 2)
            ->where('resumen.ultima.numero', 2)
            ->where('resumen.cambio', 8)
            ->where('resumen.mejor.puntaje', 49));
});

it('el Administrador ve el mismo historial de cualquier empresa; la empresa no ve el de otra', function () {
    $this->actingAs($this->admin)->get(route('empresas.historial', $this->empresa))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('historial/Historial')
            ->where('interno', true)
            ->has('mediciones', 2)
            ->where('pendiente.id', $this->pendiente->id));

    $this->actingAs($this->laura)->get(route('empresas.historial', $this->empresa))->assertForbidden();
    $this->actingAs($this->admin)->get(route('historial.mio'))->assertNotFound();
});

it('sin mediciones terminadas el resumen viene vacío', function () {
    Resultado::query()->delete();
    Medicion::where('estado', 'terminada')->update(['estado' => 'cancelada']);

    $this->actingAs($this->laura)->get(route('historial.mio'))
        ->assertInertia(fn (Assert $p) => $p->where('resumen', null)->has('mediciones', 0));
});

// --- Vencidas canceladas (pedido del 10 de noviembre de 2026) --------------------

it('una vencida que se cancela sale en el historial como prueba de que se asignó', function () {
    $this->pendiente->forceFill([
        'estado' => 'vencida',
        'fecha_limite' => today()->subDays(3),
        'ultimo_aviso_en' => now()->subDays(10),
        'asignada_por' => $this->admin->id,
    ])->save();
    DB::table('respuestas')->insert([
        'medicion_id' => $this->pendiente->id, 'pregunta_ref' => 'p1', 'texto' => 'Algo', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->actingAs($this->admin)->post(route('mediciones.cancelar', $this->pendiente))->assertSessionHasNoErrors();

    expect($this->pendiente->fresh())
        ->estado->toBe('cancelada')
        ->estado_al_cancelar->toBe('vencida')
        ->cancelada_por->toBe($this->admin->id);

    // La empresa la ve, sin nombres de quién la asignó o la canceló.
    $this->actingAs($this->laura)->get(route('historial.mio'))
        ->assertInertia(fn (Assert $p) => $p
            ->has('vencidas', 1)
            ->where('vencidas.0.numero', 3)
            ->where('vencidas.0.vence', today()->subDays(3)->toDateString())
            ->where('vencidas.0.respondidas', 1)
            ->where('vencidas.0.ultimo_aviso', now()->subDays(10)->setTimezone(Plazos::zona($this->empresa->id))->toDateString())
            ->where('vencidas.0.asignada_por', null)
            ->where('vencidas.0.cancelada_por', null)
            // Las terminadas y sus cifras no cambian.
            ->has('mediciones', 2)
            ->where('resumen.cantidad', 2));

    // El equipo de NuevasTIC (A3.4) ve también quién la asignó y quién la canceló.
    $this->actingAs($this->admin)->get(route('empresas.historial', $this->empresa))
        ->assertInertia(fn (Assert $p) => $p
            ->has('vencidas', 1)
            ->where('vencidas.0.asignada_por', $this->admin->name)
            ->where('vencidas.0.cancelada_por', $this->admin->name));
});

it('no salen las canceladas antes de vencer; sí las antiguas cuya fecha límite ya había pasado', function () {
    // Cancelada a tiempo (la reemplazaron antes de su fecha límite): no es vencida.
    $this->pendiente->forceFill(['estado' => 'cancelada', 'cancelada_en' => now(), 'estado_al_cancelar' => 'en_curso'])->save();
    // Cancelada antes de guardar el estado previo, pero ya había vencido: sí.
    $antigua = Medicion::factory()->create([
        'empresa_id' => $this->empresa->id, 'version_diagnostico_id' => $this->pendiente->version_diagnostico_id,
        'numero' => 5, 'estado' => 'cancelada', 'fecha_limite' => today()->subDays(20), 'cancelada_en' => now()->subDays(15),
    ]);

    $this->actingAs($this->admin)->get(route('empresas.historial', $this->empresa))
        ->assertInertia(fn (Assert $p) => $p
            ->has('vencidas', 1)
            ->where('vencidas.0.id', $antigua->id));
});

it('reemplazar una vencida la deja en el historial con quién la reemplazó', function () {
    $this->pendiente->forceFill(['estado' => 'vencida', 'fecha_limite' => today()->subDay()])->save();
    $diagnostico = $this->pendiente->version->diagnostico;

    app(AsignarMedicion::class)
        ->asignar($this->empresa, $diagnostico, today()->addDays(10)->toDateString(), null, false, $this->admin, reemplazar: true);

    expect($this->pendiente->fresh())
        ->estado_al_cancelar->toBe('vencida')
        ->cancelada_por->toBe($this->admin->id);
    expect(DatosHistorial::vencidas($this->empresa))->toHaveCount(1);
});
