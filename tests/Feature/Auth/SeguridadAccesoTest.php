<?php

use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;

/*
 * Seguridad del acceso (L1–L4): docs/17_SEGURIDAD.md.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

function empresaDePrueba(bool $activa = true): Empresa
{
    return Empresa::create([
        'nombre' => 'Restaurante La Esquina',
        'sector_id' => Sector::factory()->create()->id,
        'ciudad' => 'Cali',
        'pais' => 'Colombia',
        'activa' => $activa,
    ]);
}

function intentarEntrar(User $usuario, string $contrasena = 'password'): TestResponse
{
    return test()->post(route('login.store'), ['email' => $usuario->email, 'password' => $contrasena]);
}

// --- L1 · Iniciar sesión -------------------------------------------------

it('no deja entrar a una cuenta desactivada y lo avisa en el modal (RN-004)', function () {
    $usuario = User::factory()->create(['activo' => false]);

    intentarEntrar($usuario)->assertSessionHasErrors([
        'cuenta_desactivada' => 'Su cuenta ha sido desactivada. Diríjase a Captter para saber más detalles.',
    ]);

    $this->assertGuest();
});

it('no deja entrar a las cuentas de una empresa desactivada (RN-025)', function () {
    $empresa = empresaDePrueba(activa: false);
    $principal = User::factory()->create(['empresa_id' => $empresa->id])->assignRole('Empresa');
    $colaborador = User::factory()->create(['empresa_id' => $empresa->id])->assignRole('Colaborador');

    intentarEntrar($principal)->assertSessionHasErrors('cuenta_desactivada');
    intentarEntrar($colaborador)->assertSessionHasErrors('cuenta_desactivada');

    $this->assertGuest();
});

it('sin la contraseña correcta, una cuenta desactivada responde como un correo inexistente (RN-005)', function () {
    $desactivada = User::factory()->create(['activo' => false]);

    $mensaje = ['email' => 'El correo o la contraseña no son correctos.'];

    intentarEntrar($desactivada, 'equivocada')->assertSessionHasErrors($mensaje)
        ->assertSessionDoesntHaveErrors('cuenta_desactivada');

    $this->flushSession();
    $this->post(route('login.store'), ['email' => 'nadie@ejemplo.co', 'password' => 'password'])
        ->assertSessionHasErrors($mensaje);
});

it('deja entrar a la cuenta de una empresa activa', function () {
    $usuario = User::factory()->create(['empresa_id' => empresaDePrueba()->id])->assignRole('Empresa');

    intentarEntrar($usuario)->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($usuario);
});

it('cierra la sesión si desactivan la cuenta mientras está adentro', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    $usuario->forceFill(['activo' => false])->save();

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('cuenta_desactivada');
    $this->assertGuest();
});

it('acepta el correo con mayúsculas', function () {
    $usuario = User::factory()->create(['email' => 'laura@laesquina.co']);

    $this->post(route('login.store'), ['email' => 'Laura@LaEsquina.co', 'password' => 'password']);

    $this->assertAuthenticatedAs($usuario);
});

// --- L2 · Contraseña fuerte (RN-001) --------------------------------------

it('exige la contraseña fuerte al registrarse', function (string $contrasena, string $falta) {
    $sector = Sector::factory()->create();

    $this->post(route('register.store'), [
        'empresa_nombre' => 'Rojas & Asociados',
        'sector_id' => $sector->id,
        'ciudad' => 'Bogotá',
        'pais' => 'Colombia',
        'descripcion' => 'Bufete de derecho laboral.',
        'cargo' => 'Socia',
        'name' => 'Laura Gómez',
        'email' => 'laura@rojas.co',
        'password' => $contrasena,
        'password_confirmation' => $contrasena,
        'terminos' => '1',
    ])->assertSessionHasErrors('password');

    expect(session('errors')->get('password'))->toContain(...array_filter(
        session('errors')->get('password'),
        fn (string $mensaje) => str_contains($mensaje, $falta),
    ) ?: ["(ningún mensaje dice «{$falta}»)"]);
    $this->assertGuest();
})->with([
    'corta' => ['Ab1!', 'al menos 8 caracteres'],
    'sin mayúscula' => ['clave123!', 'mayúscula'],
    'sin número' => ['ClaveSegura!', 'número'],
    'sin carácter especial' => ['Clave1234', 'carácter especial'],
]);

it('guarda cuándo se aceptaron los términos', function () {
    $sector = Sector::factory()->create();

    $this->post(route('register.store'), [
        'empresa_nombre' => 'Rojas & Asociados',
        'sector_id' => $sector->id,
        'ciudad' => 'Bogotá',
        'pais' => 'Colombia',
        'descripcion' => 'Bufete de derecho laboral.',
        'cargo' => 'Socia',
        'name' => 'Laura Gómez',
        'email' => 'laura@rojas.co',
        'password' => 'Clave123!',
        'password_confirmation' => 'Clave123!',
        'terminos' => '1',
    ])->assertSessionHasNoErrors();

    expect(User::where('email', 'laura@rojas.co')->first()->terminos_aceptados_en)->not->toBeNull();
});

it('no deja registrar dos cuentas con el mismo correo (RN-002)', function () {
    User::factory()->create(['email' => 'laura@rojas.co']);
    $sector = Sector::factory()->create();

    $this->post(route('register.store'), [
        'empresa_nombre' => 'Otra',
        'sector_id' => $sector->id,
        'ciudad' => 'Bogotá',
        'pais' => 'Colombia',
        'descripcion' => 'Bufete de derecho laboral.',
        'cargo' => 'Socia',
        'name' => 'Laura',
        'email' => 'laura@rojas.co',
        'password' => 'Clave123!',
        'password_confirmation' => 'Clave123!',
        'terminos' => '1',
    ])->assertSessionHasErrors(['email' => 'Ya existe una cuenta con este correo.']);

    expect(Empresa::count())->toBe(0);
});

// --- L3 · ¿Olvidaste tu contraseña? (RN-005) ------------------------------

it('responde lo mismo exista o no el correo', function () {
    Notification::fake();
    $usuario = User::factory()->create();

    $existe = $this->from(route('password.request'))->post(route('password.email'), ['email' => $usuario->email]);
    $noExiste = $this->from(route('password.request'))->post(route('password.email'), ['email' => 'nadie@ejemplo.co']);
    // Un segundo pedido seguido tampoco revela nada.
    $repetido = $this->from(route('password.request'))->post(route('password.email'), ['email' => $usuario->email]);

    foreach ([$existe, $noExiste, $repetido] as $respuesta) {
        $respuesta->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Si el correo está registrado, te enviamos un enlace para crear una contraseña nueva.');
    }

    Notification::assertSentToTimes($usuario, ResetPassword::class, 1);
});

it('envía el correo de recuperación en español', function () {
    Notification::fake();
    $usuario = User::factory()->create(['name' => 'Laura']);

    $this->post(route('password.email'), ['email' => $usuario->email]);

    Notification::assertSentTo($usuario, ResetPassword::class, function (ResetPassword $aviso) use ($usuario) {
        $correo = $aviso->toMail($usuario);

        expect($correo->subject)->toStartWith('Crea una contraseña nueva para')
            ->and($correo->greeting)->toBe('Hola, Laura:')
            ->and($correo->actionText)->toBe('Crear contraseña nueva');

        return true;
    });
});

it('acepta el enlace de recuperación aunque hayan pasado días (RN-006)', function () {
    $usuario = User::factory()->create();
    $token = Password::createToken($usuario);

    $this->travel(3)->days();

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $usuario->email,
        'password' => 'NuevaClave1!',
        'password_confirmation' => 'NuevaClave1!',
    ])->assertSessionHasNoErrors()->assertRedirect(route('login'));
});

// --- Límite de intentos ----------------------------------------------------

it('limita los intentos de registro y de recuperación', function (string $ruta) {
    for ($i = 0; $i < 5; $i++) {
        $this->post(route($ruta), ['email' => "intento{$i}@ejemplo.co"]);
    }

    $this->post(route($ruta), ['email' => 'otro@ejemplo.co'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toStartWith('Demasiados intentos');
})->with(['register.store', 'password.email']);

it('limita los intentos de inicio de sesión', function () {
    $usuario = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        intentarEntrar($usuario, 'equivocada');
    }

    intentarEntrar($usuario, 'equivocada')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('bloqueo');

    expect((int) session('errors')->first('bloqueo'))->toBeGreaterThan(0)->toBeLessThanOrEqual(60);

    // Bloqueado, ni la contraseña correcta deja entrar.
    intentarEntrar($usuario)->assertSessionHasErrors('bloqueo');
    $this->assertGuest();
});

// --- Español ---------------------------------------------------------------

it('muestra los errores de validación en español', function () {
    $this->post(route('login.store'), [])
        ->assertSessionHasErrors(['email' => 'El campo correo es obligatorio.']);
});
