<?php

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\PlantillaCorreo;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\AvisoMedicion;
use App\Support\CorreoAviso;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;

/*
 * A3.1e · Correo de aviso (HU-037), Recordatorio / Reenviar aviso (HU-008)
 * y la tarea diaria de vencidas (HU-070).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $this->admin = User::factory()->create(['email' => 'admin@nuevastic.co'])->assignRole('Administrador');
    $sector = Sector::factory()->create(['nombre' => 'Comidas']);
    $this->diagnostico = Diagnostico::factory()->publicado()->create(['sector_id' => $sector->id, 'nombre' => 'Diagnóstico general']);
    $this->empresa = Empresa::factory()->create(['nombre' => 'La Esquina', 'sector_id' => $sector->id]);
    $this->laura = User::factory()->create(['name' => 'Laura', 'empresa_id' => $this->empresa->id, 'email' => 'laura@laesquina.co'])->assignRole('Empresa');
    Notification::fake();
});

function medicionPendiente(Empresa $empresa, Diagnostico $diagnostico, array $datos = []): Medicion
{
    return Medicion::factory()->create([
        'empresa_id' => $empresa->id,
        'version_diagnostico_id' => $diagnostico->versiones()->firstOrFail()->id,
        'numero' => 4,
        'estado' => 'no_iniciada',
        ...$datos,
    ]);
}

it('rellena las variables y no deja líneas vacías si falta el mensaje', function () {
    $texto = CorreoAviso::rellenar(CorreoAviso::CUERPO, [
        'nombre_usuario' => 'Laura',
        'numero_medicion' => '4',
        'diagnostico' => 'Diagnóstico general',
        'mensaje_admin' => '',
        'fecha_limite' => 'sin fecha límite',
    ]);

    expect($texto)->toBe("Hola Laura,\n\nTe asignamos la medición 4 de tu diagnóstico de marketing digital: Diagnóstico general.\n\nFecha límite: sin fecha límite.")
        ->and(CorreoAviso::rellenar('{desconocida} {empresa}', ['empresa' => 'X']))->toBe('{desconocida} X');
});

it('asigna con el correo editado, lo guarda como plantilla y registra el envío', function () {
    $this->actingAs($this->admin)->post(route('mediciones.guardar'), [
        'empresa_id' => $this->empresa->id,
        'diagnostico_id' => $this->diagnostico->id,
        'fecha_limite' => now()->addDays(7)->toDateString(),
        'mensaje' => 'Nos vemos el jueves.',
        'aviso' => true,
        'correo_asunto' => 'Hola {empresa}: {diagnostico}',
        'correo_cuerpo' => "Hola {nombre_usuario}.\n\n{mensaje_admin}",
        'guardar_plantilla' => true,
    ])->assertRedirect();

    $medicion = Medicion::firstOrFail();
    expect($medicion->correo_asunto)->toBe('Hola {empresa}: {diagnostico}')
        ->and($medicion->ultimo_aviso_en)->not->toBeNull()
        ->and(CorreoAviso::plantilla()['asunto'])->toBe('Hola {empresa}: {diagnostico}');

    Notification::assertSentTo($this->laura, AvisoMedicion::class, fn (AvisoMedicion $n) => $n->asunto === 'Hola La Esquina: Diagnóstico general'
        && $n->cuerpo === "Hola Laura.\n\nNos vemos el jueves."
        && ! $n->prueba);
});

it('sin correo editado usa la plantilla general', function () {
    PlantillaCorreo::create(['clave' => CorreoAviso::CLAVE, 'asunto' => 'Plantilla {numero_medicion}', 'cuerpo' => 'Cuerpo']);

    $this->actingAs($this->admin)->post(route('mediciones.guardar'), [
        'empresa_id' => $this->empresa->id, 'diagnostico_id' => $this->diagnostico->id, 'aviso' => true,
    ])->assertRedirect();

    Notification::assertSentTo($this->laura, AvisoMedicion::class, fn (AvisoMedicion $n) => $n->asunto === 'Plantilla 1');
});

it('entrega los datos del correo de una medición pendiente para la vista previa', function () {
    $medicion = medicionPendiente($this->empresa, $this->diagnostico, ['fecha_limite' => '2026-10-20', 'mensaje' => 'Hola']);

    $this->actingAs($this->admin)->getJson(route('mediciones.correo', $medicion))
        ->assertOk()
        ->assertJsonPath('para.email', 'laura@laesquina.co')
        ->assertJsonPath('asunto', CorreoAviso::ASUNTO)
        ->assertJsonPath('variables.nombre_usuario', 'Laura')
        ->assertJsonPath('variables.numero_medicion', '4')
        ->assertJsonPath('variables.fecha_limite', '20 de octubre de 2026');
});

it('envía el recordatorio con el texto editado y registra cuándo', function () {
    $medicion = medicionPendiente($this->empresa, $this->diagnostico, ['estado' => 'en_curso']);

    $this->actingAs($this->admin)->post(route('mediciones.correo.enviar', $medicion), [
        'asunto' => 'Recordatorio: {diagnostico}',
        'cuerpo' => 'Te falta poco, {nombre_usuario}.',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $medicion->refresh();
    expect($medicion->correo_cuerpo)->toBe('Te falta poco, {nombre_usuario}.')
        ->and($medicion->ultimo_aviso_en)->not->toBeNull()
        ->and(PlantillaCorreo::count())->toBe(0);
    Notification::assertSentTo($this->laura, AvisoMedicion::class, fn (AvisoMedicion $n) => $n->cuerpo === 'Te falta poco, Laura.');
});

it('no envía avisos de una medición terminada ni sin asunto', function () {
    $terminada = medicionPendiente($this->empresa, $this->diagnostico, ['estado' => 'terminada']);
    $pendiente = medicionPendiente($this->empresa, $this->diagnostico, ['numero' => 5]);

    $this->actingAs($this->admin)->post(route('mediciones.correo.enviar', $terminada), ['asunto' => 'a', 'cuerpo' => 'b'])
        ->assertSessionHasErrors('medicion');
    $this->actingAs($this->admin)->post(route('mediciones.correo.enviar', $pendiente), ['asunto' => '', 'cuerpo' => 'b'])
        ->assertSessionHasErrors('asunto');

    Notification::assertNothingSent();
});

it('envía la prueba solo a quien la pide', function () {
    $this->actingAs($this->admin)->post(route('mediciones.correo.prueba'), [
        'asunto' => 'Medición {numero_medicion}',
        'cuerpo' => 'Hola {nombre_usuario}',
        'variables' => ['numero_medicion' => '4', 'nombre_usuario' => 'Laura'],
    ])->assertRedirect();

    Notification::assertSentTo($this->admin, AvisoMedicion::class, fn (AvisoMedicion $n) => $n->prueba && $n->asunto === 'Medición 4');
    Notification::assertNotSentTo($this->laura, AvisoMedicion::class);
});

it('no deja a una empresa ver ni enviar correos de mediciones', function () {
    $medicion = medicionPendiente($this->empresa, $this->diagnostico);

    $this->actingAs($this->laura)->getJson(route('mediciones.correo', $medicion))->assertForbidden();
    $this->actingAs($this->laura)->post(route('mediciones.correo.prueba'), ['asunto' => 'a', 'cuerpo' => 'b'])->assertForbidden();
});

it('marca como vencidas cada día las que pasaron su fecha límite, no las de hoy ni las enviadas', function () {
    $vencida = medicionPendiente($this->empresa, $this->diagnostico, ['fecha_limite' => today()->subDay(), 'estado' => 'en_curso']);
    $hoy = medicionPendiente($this->empresa, $this->diagnostico, ['numero' => 5, 'fecha_limite' => today()]);
    $enviada = medicionPendiente($this->empresa, $this->diagnostico, ['numero' => 6, 'fecha_limite' => today()->subDays(3), 'estado' => 'enviada']);
    $sinFecha = medicionPendiente($this->empresa, $this->diagnostico, ['numero' => 7]);

    $this->artisan('mediciones:vencer')->assertSuccessful();

    expect($vencida->refresh()->estado)->toBe('vencida')
        ->and($hoy->refresh()->estado)->toBe('no_iniciada')
        ->and($enviada->refresh()->estado)->toBe('enviada')
        ->and($sinFecha->refresh()->estado)->toBe('no_iniciada');
});
