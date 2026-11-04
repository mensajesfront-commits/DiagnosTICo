<?php

use App\Jobs\AnalizarCategoria;
use App\Models\AnalisisCategoria;
use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\AnalisisFallido;
use App\Notifications\AvisoMedicion;
use App\Notifications\DiagnosticoEnviado;
use App\Notifications\ResultadoListo;
use App\Notifications\ResumenSemanal;
use App\Services\Mediciones\AsignarMedicion;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

/*
 * Los avisos por correo que se eligen en Mi perfil (A6, E11) se respetan:
 * cada correo mira la preferencia de quien lo recibe.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    Notification::fake();
    $sector = Sector::factory()->create();
    $this->diagnostico = Diagnostico::factory()->publicado()->create(['sector_id' => $sector->id]);
    $this->empresa = Empresa::factory()->create(['sector_id' => $sector->id]);
    $this->laura = User::factory()->create(['empresa_id' => $this->empresa->id])->assignRole('Empresa');
    $this->admin = User::factory()->create()->assignRole('Administrador');
    $this->otroAdmin = User::factory()->create(['avisos' => ['empresa_envia' => false, 'ia_falla' => false, 'resumen_semanal' => true]])->assignRole('Administrador');
    $this->medicion = Medicion::factory()->create([
        'empresa_id' => $this->empresa->id,
        'version_diagnostico_id' => $this->diagnostico->versiones()->firstOrFail()->id,
        'numero' => 1,
        'estado' => 'en_curso',
    ]);
});

it('usa el valor inicial si la cuenta no eligió nada', function () {
    expect($this->admin->quiereAviso('empresa_envia'))->toBeTrue()
        ->and($this->admin->quiereAviso('resumen_semanal'))->toBeFalse()
        ->and($this->laura->quiereAviso('resultado_listo'))->toBeTrue();
});

it('avisa a los administradores que lo pidieron cuando una empresa envía', function () {
    Queue::fake();
    $notificacion = new DiagnosticoEnviado($this->medicion);

    expect($notificacion->via($this->admin))->toBe(['mail'])
        ->and($notificacion->via($this->otroAdmin))->toBe([]);
});

it('el resultado listo y la falla de la IA respetan la preferencia', function () {
    $this->laura->update(['avisos' => ['medicion_asignada' => true, 'resultado_listo' => false]]);
    $analisis = AnalisisCategoria::create(['medicion_id' => $this->medicion->id, 'categoria_ref' => 'c1', 'estado' => 'pendiente', 'intentos' => 3]);

    expect((new ResultadoListo($this->medicion))->via($this->laura->fresh()))->toBe([])
        ->and((new AnalisisFallido($analisis))->via($this->admin))->toBe(['mail'])
        ->and((new AnalisisFallido($analisis))->via($this->otroAdmin))->toBe([]);

    (new AnalizarCategoria($analisis->id))->failed(null);
    Notification::assertSentTo($this->admin, AnalisisFallido::class);
});

it('al asignar no manda el aviso si la empresa lo apagó, pero la medición queda asignada', function () {
    $this->medicion->update(['estado' => 'terminada']);
    $this->laura->update(['avisos' => ['medicion_asignada' => false, 'resultado_listo' => true]]);

    $nueva = app(AsignarMedicion::class)->asignar($this->empresa, $this->diagnostico, null, null, true, $this->admin);

    Notification::assertNotSentTo($this->laura, AvisoMedicion::class);
    expect($nueva->ultimo_aviso_en)->toBeNull();
});

it('el resumen semanal llega solo a quien lo encendió, con las vencidas', function () {
    $this->medicion->update(['estado' => 'vencida', 'fecha_limite' => today()->subDay()]);

    $this->artisan('mediciones:resumen')->assertSuccessful();

    Notification::assertSentTo($this->otroAdmin, ResumenSemanal::class, fn (ResumenSemanal $n) => $n->cuenta['vencida'] === 1 && count($n->vencidas) === 1);
    Notification::assertNotSentTo($this->admin, ResumenSemanal::class);
});
