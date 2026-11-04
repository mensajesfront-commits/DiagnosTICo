<?php

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Respuesta;
use App\Models\Resultado;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\AvisoMedicion;
use App\Notifications\EmpresaDesactivada;
use App\Notifications\InvitacionCuenta;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * A3 · Empresas (HU-031 a HU-036): lista, registrar, ficha, datos de la
 * cuenta, desactivar y asignar mediciones.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $this->admin = User::factory()->create()->assignRole('Administrador');
    $this->sector = Sector::factory()->create(['nombre' => 'Comidas']);
    $this->diagnostico = Diagnostico::factory()->publicado()->create(['sector_id' => $this->sector->id, 'nombre' => 'Diagnóstico general']);
    Notification::fake();
});

function empresaCon(Sector $sector, string $nombre = 'Restaurante La Esquina'): Empresa
{
    $empresa = Empresa::factory()->create(['nombre' => $nombre, 'sector_id' => $sector->id]);
    User::factory()->create(['empresa_id' => $empresa->id, 'email' => 'laura@laesquina.co'])->assignRole('Empresa');

    return $empresa;
}

/** @return array<string, mixed> */
function datosEmpresa(Sector $sector, array $cambios = []): array
{
    return [
        'nombre' => 'Pizzería Don Luigi',
        'sector_id' => $sector->id,
        'pais' => 'Colombia',
        'departamento' => 'Valle del Cauca',
        'ciudad' => 'Cali',
        'usuario_nombre' => 'Marco Rossi',
        'email' => 'Marco@DonLuigi.co',
        ...$cambios,
    ];
}

it('lista las empresas con su último puntaje, variación y medición pendiente', function () {
    $empresa = empresaCon($this->sector);
    $version = $this->diagnostico->versiones()->firstOrFail();
    foreach ([[1, 49], [2, 57]] as [$numero, $puntaje]) {
        $m = Medicion::factory()->create(['empresa_id' => $empresa->id, 'version_diagnostico_id' => $version->id, 'numero' => $numero, 'estado' => 'terminada']);
        Resultado::create(['medicion_id' => $m->id, 'puntaje_total' => $puntaje, 'nivel' => 'mejorar', 'por_categoria' => [], 'publicado_en' => now()]);
    }
    Medicion::factory()->create(['empresa_id' => $empresa->id, 'version_diagnostico_id' => $version->id, 'numero' => 3, 'estado' => 'no_iniciada']);

    $this->actingAs($this->admin)->get(route('empresas.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('empresas/Index')
            ->has('empresas', 1)
            ->where('empresas.0.puntaje', 57)
            ->where('empresas.0.variacion', 8)
            ->where('empresas.0.mediciones', 2)
            ->where('empresas.0.pendiente.numero', 3)
            ->where('empresas.0.pendiente.estado', 'no_iniciada'));
});

it('registra una empresa con su usuario principal, le envía la invitación y le asigna la primera medición', function () {
    $this->actingAs($this->admin)->post(route('empresas.guardar'), datosEmpresa($this->sector, [
        'asignar_medicion' => true,
        'diagnostico_id' => $this->diagnostico->id,
    ]))->assertSessionHasNoErrors();

    $empresa = Empresa::where('nombre', 'Pizzería Don Luigi')->firstOrFail();
    $usuario = User::where('email', 'marco@donluigi.co')->firstOrFail();

    expect($empresa->getAttribute('registrada_por'))->toBe($this->admin->id)
        ->and($usuario->empresa_id)->toBe($empresa->id)
        ->and($usuario->hasRole('Empresa'))->toBeTrue()
        ->and($usuario->password)->toBeNull()
        ->and($empresa->mediciones()->first()->numero)->toBe(1);
    Notification::assertSentTo($usuario, InvitacionCuenta::class);
});

it('no registra con un correo en uso ni con un sector inactivo', function () {
    empresaCon($this->sector);
    $inactivo = Sector::factory()->create(['activo' => false]);

    $this->actingAs($this->admin)->post(route('empresas.guardar'), datosEmpresa($this->sector, ['email' => 'laura@laesquina.co']))
        ->assertSessionHasErrors('email');
    $this->actingAs($this->admin)->post(route('empresas.guardar'), datosEmpresa($inactivo))
        ->assertSessionHasErrors('sector_id');
    expect(Empresa::count())->toBe(1);
});

it('muestra la ficha y guarda los datos de la cuenta, también el sector', function () {
    $empresa = empresaCon($this->sector);
    $otro = Sector::factory()->create(['nombre' => 'Turismo']);

    $this->actingAs($this->admin)->get(route('empresas.ver', $empresa))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('empresas/Ver')
            ->where('empresa.nombre', 'Restaurante La Esquina')
            ->where('principal.email', 'laura@laesquina.co')
            ->has('diagnosticos', 1));

    $this->actingAs($this->admin)->put(route('empresas.actualizar', $empresa), datosEmpresa($otro, [
        'nombre' => 'La Esquina',
        'usuario_nombre' => 'Laura Gómez',
        'email' => 'laura@laesquina.co',
    ]))->assertSessionHasNoErrors();

    expect($empresa->fresh()->nombre)->toBe('La Esquina')
        ->and($empresa->fresh()->sector_id)->toBe($otro->id);

    // Correo de otra cuenta: no se guarda.
    User::factory()->create(['email' => 'otra@correo.co']);
    $this->actingAs($this->admin)->put(route('empresas.actualizar', $empresa), datosEmpresa($otro, ['email' => 'otra@correo.co']))
        ->assertSessionHasErrors('email');
});

it('desactiva la empresa (nadie entra, se conservan las mediciones) y la reactiva', function () {
    $empresa = empresaCon($this->sector);
    $principal = $empresa->usuarios()->firstOrFail();

    $this->actingAs($this->admin)->post(route('empresas.desactivar', $empresa), ['motivo' => 'Pidió pausar', 'avisar' => true])
        ->assertSessionHasNoErrors();

    expect($empresa->fresh()->activa)->toBeFalse()
        ->and($empresa->fresh()->motivo_desactivacion)->toBe('Pidió pausar')
        ->and($principal->fresh()->puedeEntrar())->toBeFalse();
    Notification::assertSentTo($principal, EmpresaDesactivada::class);

    $this->actingAs($this->admin)->post(route('empresas.reactivar', $empresa));
    expect($empresa->fresh()->activa)->toBeTrue()->and($principal->fresh()->puedeEntrar())->toBeTrue();
});

it('asigna una medición con aviso y solo reemplaza la pendiente si se confirma', function () {
    $empresa = empresaCon($this->sector);
    $datos = ['empresa_id' => $empresa->id, 'diagnostico_id' => $this->diagnostico->id, 'aviso' => true];

    $this->actingAs($this->admin)->post(route('mediciones.guardar'), $datos)->assertSessionHasNoErrors();
    $primera = $empresa->mediciones()->firstOrFail();
    Notification::assertSentTo($empresa->usuarios()->firstOrFail(), AvisoMedicion::class);

    // Ya hay una pendiente: pide confirmar.
    $this->actingAs($this->admin)->post(route('mediciones.guardar'), $datos)->assertSessionHasErrors('reemplazar');
    expect($empresa->mediciones()->count())->toBe(1);

    $this->actingAs($this->admin)->post(route('mediciones.guardar'), [...$datos, 'reemplazar' => true])->assertSessionHasNoErrors();
    $nueva = $empresa->mediciones()->where('estado', 'no_iniciada')->firstOrFail();

    expect($primera->fresh()->estado)->toBe('cancelada')
        ->and($primera->fresh()->reemplazada_por)->toBe($nueva->id)
        ->and($nueva->numero)->toBe(1);
});

it('no asigna diagnósticos de otro sector ni borradores', function () {
    $empresa = empresaCon($this->sector);
    $otroSector = Diagnostico::factory()->publicado()->create();
    $borrador = Diagnostico::factory()->conCategorias()->create(['sector_id' => $this->sector->id]);

    foreach ([$otroSector, $borrador] as $d) {
        $this->actingAs($this->admin)->post(route('mediciones.guardar'), ['empresa_id' => $empresa->id, 'diagnostico_id' => $d->id])
            ->assertSessionHasErrors('diagnostico_id');
    }
});

it('cambia la fecha de una vencida (vuelve a pendiente) y cancela una pendiente', function () {
    $empresa = empresaCon($this->sector);
    $version = $this->diagnostico->versiones()->firstOrFail();
    $vencida = Medicion::factory()->create(['empresa_id' => $empresa->id, 'version_diagnostico_id' => $version->id, 'estado' => 'vencida']);
    Respuesta::create(['medicion_id' => $vencida->id, 'pregunta_ref' => 'p1']);

    $this->actingAs($this->admin)->put(route('mediciones.fecha', $vencida), ['fecha_limite' => now()->addWeek()->toDateString()])
        ->assertSessionHasNoErrors();
    expect($vencida->fresh()->estado)->toBe('en_curso');

    $this->actingAs($this->admin)->post(route('mediciones.cancelar', $vencida));
    expect($vencida->fresh()->estado)->toBe('cancelada');
});

it('no deja entrar a Empresas a una cuenta de empresa', function () {
    $empresa = empresaCon($this->sector);
    $principal = $empresa->usuarios()->firstOrFail();

    $this->actingAs($principal)->get(route('empresas.index'))->assertForbidden();
    $this->actingAs($principal)->post(route('mediciones.guardar'), ['empresa_id' => $empresa->id, 'diagnostico_id' => $this->diagnostico->id])->assertForbidden();
});

it('abre Registrar empresa con los sectores, sus subsectores y diagnósticos publicados', function () {
    $this->actingAs($this->admin)->get(route('empresas.crear'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('empresas/Registrar')
            ->where('opciones.sectores.0.nombre', 'Comidas')
            ->has('opciones.sectores.0.diagnosticos', 1)
            ->has('opciones.paises', 18));
});

it('abre Asignar medición con la empresa elegida, su pendiente y los borradores sin poder elegirlos', function () {
    $empresa = empresaCon($this->sector);
    $pendiente = Medicion::factory()->create([
        'empresa_id' => $empresa->id,
        'version_diagnostico_id' => $this->diagnostico->versiones()->firstOrFail()->id,
        'numero' => 1,
        'estado' => 'en_curso',
    ]);
    Diagnostico::factory()->create(['sector_id' => $this->sector->id, 'nombre' => 'Borrador nuevo']);

    $this->actingAs($this->admin)->get(route('mediciones.crear', ['empresa' => $empresa->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('mediciones/Asignar')
            ->where('empresaInicial', $empresa->id)
            ->where('empresas.0.pendiente.id', $pendiente->id)
            ->where('empresas.0.siguiente', 2)
            ->has("diagnosticos.{$this->sector->id}.publicados", 1)
            ->where("diagnosticos.{$this->sector->id}.borradores.0.nombre", 'Borrador nuevo'));
});

// --- Empresa sin cuenta principal (su cuenta pasó al equipo) ------------------

function empresaSinCuenta(Sector $sector): Empresa
{
    $empresa = Empresa::factory()->create(['nombre' => 'Restaurante La Esquina', 'sector_id' => $sector->id, 'activa' => false]);

    return $empresa;
}

it('da una cuenta principal nueva a una empresa que se quedó sin ella y le envía la invitación', function () {
    Notification::fake();
    $empresa = empresaSinCuenta($this->sector);

    // Sin cuenta principal no se reactiva.
    $this->actingAs($this->admin)->post(route('empresas.reactivar', $empresa))
        ->assertSessionHasErrors(['reactivar' => 'Esta empresa no tiene cuenta principal: dale una antes de reactivarla.']);

    $this->actingAs($this->admin)->post(route('empresas.cuenta-principal', $empresa), [
        'usuario_nombre' => 'Sofía Rojas', 'email' => ' Sofia@LaEsquina.co ', 'cargo' => 'Gerente',
    ])->assertSessionHasNoErrors();

    $sofia = User::where('email', 'sofia@laesquina.co')->firstOrFail();
    expect($sofia->hasRole('Empresa'))->toBeTrue()
        ->and($sofia->empresa_id)->toBe($empresa->id)
        ->and($sofia->password)->toBeNull();
    Notification::assertSentTo($sofia, InvitacionCuenta::class);

    $this->actingAs($this->admin)->post(route('empresas.reactivar', $empresa))->assertSessionHasNoErrors();
    expect($empresa->fresh()->activa)->toBeTrue();

    // Ya tiene: no se le da otra.
    $this->actingAs($this->admin)->post(route('empresas.cuenta-principal', $empresa), [
        'usuario_nombre' => 'Otra', 'email' => 'otra@laesquina.co',
    ])->assertStatus(422);
});

it('un colaborador no pasa a ser la cuenta principal: se le da una cuenta nueva', function () {
    Notification::fake();
    $empresa = empresaSinCuenta($this->sector);
    $andres = User::factory()->create(['empresa_id' => $empresa->id])->assignRole('Colaborador');

    $this->actingAs($this->admin)->post(route('empresas.cuenta-principal', $empresa), ['colaborador_id' => $andres->id])
        ->assertSessionHasErrors(['usuario_nombre', 'email']);

    expect($andres->fresh()->hasRole('Colaborador'))->toBeTrue();
});

it('sin cuenta principal se pueden guardar los datos de la empresa sin los del usuario', function () {
    $empresa = empresaSinCuenta($this->sector);

    $this->actingAs($this->admin)
        ->put(route('empresas.actualizar', $empresa), datosEmpresa($this->sector, ['nombre' => 'La Esquina Gourmet', 'usuario_nombre' => '', 'email' => '']))
        ->assertSessionHasNoErrors();

    expect($empresa->fresh()->nombre)->toBe('La Esquina Gourmet');
});
