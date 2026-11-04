<?php

use App\Models\ActividadEconomica;
use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\CodigoVerificacionCorreo;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * L2b · Al registrarse, la cuenta no entra hasta verificar el correo con el
 * código de 6 dígitos que llega por correo (DEC-020). Antes, el captcha.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    Notification::fake();
    $this->sector = Sector::factory()->create();
    ActividadEconomica::create(['sector_id' => $this->sector->id, 'codigo' => '6910', 'nombre' => 'Actividades jurídicas']);
});

function registrar($test, array $extra = [])
{
    return $test->post(route('register.store'), [
        'empresa_nombre' => 'Rojas & Asociados',
        'descripcion' => 'Bufete de derecho laboral.',
        'sector_id' => $test->sector->id,
        'actividad_economica_id' => $test->sector->actividades()->value('id'),
        'ciudad' => 'Bogotá',
        'departamento' => 'Bogotá D.C.',
        'pais' => 'Colombia',
        'name' => 'Laura Gómez',
        'cargo' => 'Gerente',
        'email' => 'laura@rojas.co',
        'password' => 'Clave123!',
        'password_confirmation' => 'Clave123!',
        'terminos' => '1',
        ...$extra,
    ]);
}

/** El código que llegó por correo. */
function codigoDe(User $usuario): string
{
    $codigo = null;
    Notification::assertSentTo($usuario, CodigoVerificacionCorreo::class, function (CodigoVerificacionCorreo $n) use (&$codigo) {
        $codigo = $n->codigo;

        return true;
    });

    return (string) $codigo;
}

it('al crear la cuenta no entra, manda el código y abre la ventana en el registro', function () {
    registrar($this)->assertRedirect(route('register'));

    $this->assertGuest();
    $laura = User::where('email', 'laura@rojas.co')->firstOrFail();
    expect($laura->hasVerifiedEmail())->toBeFalse()
        ->and(codigoDe($laura))->toMatch('/^\d{6}$/');

    $this->get(route('register'))
        ->assertInertia(fn (Assert $p) => $p->component('auth/Register')->where('verificando', 'laura@rojas.co'));
});

it('el código correcto verifica y lleva a Iniciar sesión con el correo escrito', function () {
    registrar($this);
    $laura = User::where('email', 'laura@rojas.co')->firstOrFail();

    $this->post(route('verificacion.verificar'), ['codigo' => codigoDe($laura)])->assertRedirect(route('login'));
    expect($laura->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->get(route('login'))
        ->assertInertia(fn (Assert $p) => $p->where('correo', 'laura@rojas.co')->where('verificando', null));

    $this->post(route('login.store'), ['email' => 'laura@rojas.co', 'password' => 'Clave123!'])->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($laura);
});

it('un código equivocado cuenta los intentos y a los 5 hay que pedir otro', function () {
    registrar($this);
    $laura = User::where('email', 'laura@rojas.co')->firstOrFail();
    $bueno = codigoDe($laura);
    $malo = $bueno === '111111' ? '222222' : '111111';

    $this->post(route('verificacion.verificar'), ['codigo' => $malo])
        ->assertSessionHasErrors(['codigo' => 'El código no coincide. Te quedan 4 intentos.']);

    foreach (range(1, 4) as $_) {
        $this->post(route('verificacion.verificar'), ['codigo' => $malo]);
    }

    // Ni el correcto sirve después de 5 intentos.
    $this->post(route('verificacion.verificar'), ['codigo' => $bueno])->assertSessionHasErrors('codigo');
    expect($laura->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('un código vencido no sirve; reenviar manda uno nuevo', function () {
    registrar($this);
    $laura = User::where('email', 'laura@rojas.co')->firstOrFail();
    $viejo = codigoDe($laura);
    $laura->forceFill(['codigo_vence_en' => now()->subMinute()])->save();

    $this->post(route('verificacion.verificar'), ['codigo' => $viejo])
        ->assertSessionHasErrors(['codigo' => 'El código venció. Pide uno nuevo con "Reenviar código".']);

    $this->post(route('verificacion.reenviar'))->assertSessionHasNoErrors();
    Notification::assertSentToTimes($laura, CodigoVerificacionCorreo::class, 2);
});

it('sin verificar no puede iniciar sesión: abre la ventana; con contraseña equivocada no revela nada', function () {
    registrar($this);
    $this->flushSession();

    $this->post(route('login.store'), ['email' => 'laura@rojas.co', 'password' => 'Otra123!'])
        ->assertSessionHasErrors('email')
        ->assertSessionDoesntHaveErrors('correo_sin_verificar');

    $this->post(route('login.store'), ['email' => 'laura@rojas.co', 'password' => 'Clave123!'])
        ->assertSessionHasErrors('correo_sin_verificar');
    $this->assertGuest();

    $this->get(route('login'))->assertInertia(fn (Assert $p) => $p->where('verificando', 'laura@rojas.co'));
});

it('sin registro en esta sesión no se verifica ni se reenvía nada', function () {
    registrar($this);
    $this->flushSession();

    $this->post(route('verificacion.verificar'), ['codigo' => '123456'])->assertRedirect(route('login'));
    $this->post(route('verificacion.reenviar'))->assertRedirect(route('login'));
    Notification::assertSentToTimes(User::where('email', 'laura@rojas.co')->first(), CodigoVerificacionCorreo::class, 1);
});

it('con el captcha encendido, sin resolverlo no crea la cuenta', function () {
    config(['services.turnstile.activo' => true]);
    Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
        ->push(['success' => false])
        ->push(['success' => true])]);

    registrar($this)->assertSessionHasErrors('captcha');
    registrar($this, ['captcha' => 'token-falso'])->assertSessionHasErrors('captcha');
    expect(User::where('email', 'laura@rojas.co')->exists())->toBeFalse();

    registrar($this, ['captcha' => 'token-bueno'])->assertSessionHasNoErrors();
    expect(User::where('email', 'laura@rojas.co')->exists())->toBeTrue();
});

it('crear la contraseña desde una invitación también verifica el correo', function () {
    $invitada = User::factory()->unverified()->create(['password' => null]);
    $token = Password::broker()->createToken($invitada);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $invitada->email,
        'password' => 'Clave123!',
        'password_confirmation' => 'Clave123!',
    ])->assertSessionHasNoErrors();

    expect($invitada->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('borra a los 7 días el registro sin verificar con su empresa; el verificado y la invitación se quedan', function () {
    registrar($this);
    $laura = User::where('email', 'laura@rojas.co')->firstOrFail();
    $empresaId = $laura->empresa_id;
    $verificada = User::factory()->create(['email' => 'ok@empresa.co']);
    $invitada = User::factory()->unverified()->create(['password' => null]);

    $this->travel(6)->days();
    $this->artisan('cuentas:purgar')->assertSuccessful();
    expect(User::find($laura->id))->not->toBeNull();

    $this->travel(2)->days();
    $this->artisan('cuentas:purgar')->assertSuccessful();

    expect(User::withTrashed()->find($laura->id))->toBeNull()
        ->and(Empresa::withTrashed()->find($empresaId))->toBeNull()
        ->and(User::find($verificada->id))->not->toBeNull()
        ->and(User::find($invitada->id))->not->toBeNull();

    // El correo queda libre para registrarse otra vez.
    $this->flushSession();
    registrar($this)->assertSessionHasNoErrors();
});
