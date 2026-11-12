<?php

use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\Correo;
use App\Notifications\DiagnosticoEnviado;
use App\Notifications\EmpresaDesactivada;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Cache\RateLimiter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\LoginRateLimiter;
use Spatie\Permission\Models\Role;

/*
 * Regresión de los hallazgos de la auditoría QA del 4 de noviembre de 2026
 * (ERR-01 a ERR-12). Cada prueba reproduce el caso que falló.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    Notification::fake();
    $this->admin = User::factory()->create()->assignRole('Administrador');
});

// --- ERR-02 · Correos sin distinguir mayúsculas -------------------------------------

it('no deja invitar un correo que ya existe con otras mayúsculas', function () {
    User::factory()->create(['email' => 'sofia@eltrigal.co']);

    $this->actingAs($this->admin)
        ->post(route('usuarios.invitar'), ['name' => 'Otra', 'email' => 'SOFIA@ElTrigal.co', 'rol_id' => Role::findByName('Consultor')->id])
        ->assertSessionHasErrors('email');

    expect(User::query()->whereRaw('lower(email) = ?', ['sofia@eltrigal.co'])->count())->toBe(1);
});

it('guarda los correos en minúsculas y sin espacios', function () {
    $this->actingAs($this->admin)
        ->post(route('usuarios.invitar'), ['name' => 'Valeria', 'email' => '  Valeria.Rios@Ejemplo.CO ', 'rol_id' => Role::findByName('Consultor')->id])
        ->assertSessionHasNoErrors();

    expect(User::where('email', 'valeria.rios@ejemplo.co')->exists())->toBeTrue();
});

it('la base no acepta dos correos que solo difieren en mayúsculas', function () {
    User::factory()->create(['email' => 'unico@ejemplo.co']);

    expect(fn () => DB::table('users')->insert(['name' => 'X', 'email' => 'UNICO@ejemplo.co', 'password' => 'x', 'created_at' => now(), 'updated_at' => now()]))
        ->toThrow(QueryException::class);
});

// --- ERR-01 · Login con un correo muy largo ----------------------------------------

it('un correo de 300 caracteres en el login no da error 500', function () {
    // Con la caché en la base, como en producción: cache.key es varchar(255).
    // Los limitadores se crean de nuevo para que usen esa caché.
    $limitadores = new ReflectionProperty(RateLimiter::class, 'limiters');
    $conBase = new RateLimiter(Cache::store('database'));
    $limitadores->setValue($conBase, $limitadores->getValue(app(RateLimiter::class)));
    $this->app->instance(RateLimiter::class, $conBase);
    $this->app->forgetInstance(LoginRateLimiter::class);

    $this->post(route('login.store'), ['email' => str_repeat('a', 300).'@x', 'password' => 'x'])
        ->assertRedirect()
        ->assertSessionHasErrors();
});

// --- ERR-03 · Markdown en los correos ----------------------------------------------

it('el nombre de la empresa no se vuelve un enlace en el correo', function () {
    $empresa = Empresa::factory()->create(['nombre' => '[Haz clic aquí](https://phishing.example/login)']);
    $medicion = Medicion::factory()->create(['empresa_id' => $empresa->id]);

    $html = (string) (new DiagnosticoEnviado($medicion))->toMail($this->admin)->render();

    expect($html)->not->toContain('href="https://phishing.example/login"')
        ->and($html)->toContain('[Haz clic aquí](https://phishing.example/login)');
});

it('el motivo de desactivación tampoco arma enlaces ni negritas', function () {
    $empresa = Empresa::factory()->create();
    $html = (string) (new EmpresaDesactivada($empresa, '**urgente** [aquí](https://x.example)'))->toMail($this->admin)->render();

    expect($html)->not->toContain('<strong>urgente</strong>')
        ->and($html)->not->toContain('href="https://x.example"');
});

it('escapa los marcadores de Markdown y deja el resto igual', function () {
    expect(Correo::escapar('[a](b)'))->toBe('\[a\](b)')
        ->and(Correo::escapar('1. Empresa'))->toBe('1\. Empresa')
        ->and(Correo::escapar('# Título'))->toBe('\# Título')
        ->and(Correo::escapar('Panadería (centro)'))->toBe('Panadería (centro)');
});

// --- ERR-06 · Cabeceras de seguridad ----------------------------------------------

it('manda las cabeceras de seguridad', function () {
    $this->get(route('login'))
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        // Fuera de «local» (producción y pruebas) los scripts solo salen del
        // propio sitio; en «local» solo se limita quién puede enmarcarla.
        ->assertHeader('Content-Security-Policy', "script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
});

// --- ERR-04 · Sectores y categorías sin distinguir mayúsculas ----------------------

it('no deja crear un sector que ya existe con otras mayúsculas o espacios', function () {
    Sector::factory()->create(['nombre' => 'Comidas']);

    $this->actingAs($this->admin)
        ->post(route('sectores.store'), ['nombre' => '  comidas '])
        ->assertSessionHasErrors(['nombre' => 'Ya existe un sector con ese nombre.']);

    expect(Sector::count())->toBe(1);
});

it('no deja crear una categoría que ya existe con otras mayúsculas', function () {
    Categoria::create(['nombre' => 'Presencia en línea']);

    $this->actingAs($this->admin)
        ->post(route('categorias.store'), ['nombre' => 'PRESENCIA EN LÍNEA'])
        ->assertSessionHasErrors(['nombre' => 'Ya existe una categoría con ese nombre.']);
});

it('renombrar una categoría con su mismo nombre en otras mayúsculas sí se puede', function () {
    $categoria = Categoria::create(['nombre' => 'Presencia en línea']);

    $this->actingAs($this->admin)
        ->put(route('categorias.update', $categoria), ['nombre' => 'Presencia En Línea'])
        ->assertSessionHasNoErrors();
});

// --- ERR-05 · Mensajes en español -------------------------------------------------

it('el mensaje de un valor repetido sale en español', function () {
    $empresa = Empresa::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('usuarios.invitar'), ['name' => 'X', 'email' => 'x@ejemplo.co', 'rol_id' => Role::findByName('Consultor')->id, 'empresas' => [$empresa->id, $empresa->id]])
        ->assertSessionHasErrors(['empresas.0' => 'El campo empresa tiene un valor repetido.']);
});

// --- ERR-12 · Rol inactivo --------------------------------------------------------

it('una cuenta activa con el rol inactivo recibe su propio mensaje', function () {
    $rol = Role::create(['name' => 'Rol en pausa', 'guard_name' => 'web']);
    $rol->forceFill(['activo' => false])->save();
    $cuenta = User::factory()->create(['password' => 'Clave-Segura-2026'])->assignRole($rol);

    $this->post(route('login.store'), ['email' => $cuenta->email, 'password' => 'Clave-Segura-2026'])
        ->assertSessionHasErrors(['rol_inactivo' => trans('auth.rol_inactivo')])
        ->assertSessionDoesntHaveErrors('cuenta_desactivada');
});

it('una cuenta desactivada sigue recibiendo "cuenta desactivada"', function () {
    $cuenta = User::factory()->create(['password' => 'Clave-Segura-2026', 'activo' => false])->assignRole('Consultor');

    $this->post(route('login.store'), ['email' => $cuenta->email, 'password' => 'Clave-Segura-2026'])
        ->assertSessionHasErrors('cuenta_desactivada');
});

// --- ERR-09 · Enlaces de invitación aparte ----------------------------------------

it('la migración pasa las invitaciones pendientes a su tabla y deja la recuperación', function () {
    $migracion = require database_path('migrations/2026_11_04_000002_enlaces_de_invitacion_aparte.php');
    $migracion->down();

    $invitada = User::factory()->create(['password' => null]);
    $conClave = User::factory()->create();
    DB::table('password_reset_tokens')->insert([
        ['email' => $invitada->email, 'token' => 'x', 'created_at' => now()],
        ['email' => $conClave->email, 'token' => 'y', 'created_at' => now()],
    ]);

    $migracion->up();

    expect(DB::table('invitaciones_tokens')->pluck('email')->all())->toBe([$invitada->email])
        ->and(DB::table('password_reset_tokens')->pluck('email')->all())->toBe([$conClave->email]);
});

// --- Correo de soporte (4 de noviembre) ----------------------------------------------

it('todas las pantallas reciben el correo de soporte de SOPORTE_EMAIL', function () {
    config(['diagnostico.soporte_email' => 'contacto@captter.com']);

    $this->get(route('login'))
        ->assertInertia(fn ($p) => $p->where('soporte', 'contacto@captter.com'));
});
