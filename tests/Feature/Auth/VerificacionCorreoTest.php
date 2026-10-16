<?php

use App\Models\ActividadEconomica;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * L2b · Al registrarse, la cuenta no entra hasta verificar el correo. El
 * enlace del correo la verifica y lleva a Iniciar sesión con el correo escrito.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    Notification::fake();
    $this->sector = Sector::factory()->create();
    ActividadEconomica::create(['sector_id' => $this->sector->id, 'codigo' => '6910', 'nombre' => 'Actividades jurídicas']);
});

function registrar($test)
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
    ]);
}

function enlaceDe(User $usuario, int $minutos = 60): string
{
    return URL::temporarySignedRoute('verificacion.confirmar', now()->addMinutes($minutos), [
        'id' => $usuario->id,
        'hash' => sha1($usuario->email),
    ]);
}

it('al crear la cuenta no entra, manda el correo y muestra "Verifica tu correo"', function () {
    registrar($this)->assertRedirect(route('verificacion.aviso'));

    $this->assertGuest();
    $laura = User::where('email', 'laura@rojas.co')->firstOrFail();
    expect($laura->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($laura, VerifyEmail::class);

    $this->get(route('verificacion.aviso'))
        ->assertInertia(fn (Assert $p) => $p->component('auth/VerificarCorreo')->where('correo', 'laura@rojas.co'));
});

it('sin verificar no puede iniciar sesión, aunque la contraseña sea correcta', function () {
    registrar($this);

    $this->post(route('login.store'), ['email' => 'laura@rojas.co', 'password' => 'Clave123!'])
        ->assertSessionHasErrors('correo_sin_verificar');
    $this->assertGuest();

    // Con contraseña equivocada no revela nada (RN-005).
    $this->post(route('login.store'), ['email' => 'laura@rojas.co', 'password' => 'Otra123!'])
        ->assertSessionHasErrors('email')
        ->assertSessionDoesntHaveErrors('correo_sin_verificar');
});

it('el enlace verifica el correo y lleva a Iniciar sesión con el correo escrito', function () {
    registrar($this);
    $laura = User::where('email', 'laura@rojas.co')->firstOrFail();

    $this->get(enlaceDe($laura))->assertRedirect(route('login'));
    expect($laura->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->get(route('login'))
        ->assertInertia(fn (Assert $p) => $p->where('correo', 'laura@rojas.co')->where('status', 'Tu correo quedó verificado. Escribe tu contraseña para entrar.'));

    $this->post(route('login.store'), ['email' => 'laura@rojas.co', 'password' => 'Clave123!'])->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($laura);
});

it('un enlace vencido o alterado no verifica', function () {
    registrar($this);
    $laura = User::where('email', 'laura@rojas.co')->firstOrFail();

    $this->get(enlaceDe($laura, -5))->assertRedirect(route('verificacion.aviso'));
    $this->get(route('verificacion.confirmar', ['id' => $laura->id, 'hash' => sha1('otro@correo.co')]))->assertRedirect(route('login'));

    expect($laura->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('reenvía el correo solo a la cuenta que se registró en esta sesión', function () {
    registrar($this);
    $laura = User::where('email', 'laura@rojas.co')->firstOrFail();

    $this->post(route('verificacion.reenviar'))->assertSessionHasNoErrors();
    Notification::assertSentToTimes($laura, VerifyEmail::class, 2);

    // Otra sesión, sin registro: no reenvía nada.
    $this->flushSession();
    $this->post(route('verificacion.reenviar'))->assertRedirect(route('login'));
    Notification::assertSentToTimes($laura, VerifyEmail::class, 2);
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
