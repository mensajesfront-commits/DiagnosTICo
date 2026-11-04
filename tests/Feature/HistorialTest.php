<?php

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Resultado;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
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
            ->missing('pendiente.ultimo_aviso'));
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
