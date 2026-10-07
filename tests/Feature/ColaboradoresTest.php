<?php

use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * E12 · Colaboradores de la empresa (HU-076 a HU-079, RN-025).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

function empresaConPrincipal(string $nombre = 'Restaurante La Esquina'): User
{
    $empresa = Empresa::create([
        'nombre' => $nombre,
        'sector_id' => Sector::factory()->create()->id,
        'ciudad' => 'Cali',
        'pais' => 'Colombia',
    ]);

    return User::factory()->create(['name' => 'Laura Gómez', 'empresa_id' => $empresa->id])->assignRole('Empresa');
}

function colaboradorDe(User $principal, array $datos = []): User
{
    return User::factory()->create(['empresa_id' => $principal->empresa_id, ...$datos])->assignRole('Colaborador');
}

// --- HU-076 · Ver ------------------------------------------------------------

it('muestra la cuenta principal primero y luego sus colaboradores', function () {
    $laura = empresaConPrincipal();
    colaboradorDe($laura, ['name' => 'Camila Rojas']);
    colaboradorDe($laura, ['name' => 'Andrés Pérez', 'activo' => false]);
    colaboradorDe(empresaConPrincipal('Otra empresa'), ['name' => 'De otra empresa']);

    $this->actingAs($laura)
        ->get(route('colaboradores.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('colaboradores/Index')
            ->where('empresa', 'Restaurante La Esquina')
            ->has('colaboradores', 3)
            ->where('colaboradores.0.es_principal', true)
            ->where('colaboradores.0.nombre', 'Laura Gómez')
            ->where('colaboradores.1.nombre', 'Andrés Pérez')
            ->where('colaboradores.1.activo', false)
            ->where('colaboradores.2.nombre', 'Camila Rojas'));
});

it('solo deja entrar a la cuenta principal (HU-076 CA-001)', function () {
    $laura = empresaConPrincipal();

    $this->actingAs(colaboradorDe($laura))->get(route('colaboradores.index'))->assertForbidden();
    $this->actingAs(User::factory()->create()->assignRole('Administrador'))->get(route('colaboradores.index'))->assertForbidden();
    $this->actingAs(User::factory()->create()->assignRole('Empresa'))->get(route('colaboradores.index'))->assertForbidden();
});

// --- HU-077 · Crear ----------------------------------------------------------

it('crea un colaborador que puede iniciar sesión', function () {
    $laura = empresaConPrincipal();

    $this->actingAs($laura)
        ->post(route('colaboradores.store'), [
            'name' => 'Mariana Díaz',
            'cargo' => 'Marketing',
            'email' => ' Mariana@LaEsquina.co ',
            'password' => 'Mesa-47-Sol',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $mariana = User::where('email', 'mariana@laesquina.co')->firstOrFail();
    expect($mariana->hasRole('Colaborador'))->toBeTrue()
        ->and($mariana->empresa_id)->toBe($laura->empresa_id)
        ->and($mariana->activo)->toBeTrue()
        ->and($mariana->cargo)->toBe('Marketing')
        ->and(Hash::check('Mesa-47-Sol', (string) $mariana->password))->toBeTrue();

    auth()->logout();
    $this->post(route('login.store'), ['email' => 'mariana@laesquina.co', 'password' => 'Mesa-47-Sol']);
    $this->assertAuthenticatedAs($mariana);
});

it('no crea con un correo registrado ni con una contraseña débil', function () {
    $laura = empresaConPrincipal();

    $this->actingAs($laura)
        ->post(route('colaboradores.store'), ['name' => 'Otra', 'cargo' => 'Ventas', 'email' => $laura->email, 'password' => 'Mesa-47-Sol'])
        ->assertSessionHasErrors(['email' => 'Ya hay una cuenta con este correo.']);

    $this->actingAs($laura)
        ->post(route('colaboradores.store'), ['name' => 'Otra', 'cargo' => 'Ventas', 'email' => 'otra@laesquina.co', 'password' => 'mesa1234'])
        ->assertSessionHasErrors('password');

    expect(User::where('email', 'otra@laesquina.co')->exists())->toBeFalse();
});

it('no deja a un colaborador crear colaboradores', function () {
    $this->actingAs(colaboradorDe(empresaConPrincipal()))
        ->post(route('colaboradores.store'), ['name' => 'X', 'email' => 'x@x.co', 'password' => 'Mesa-47-Sol'])
        ->assertForbidden();
});

// --- HU-078 · Editar (datos y contraseña) ----------------------------------------

function datosDe(User $u, array $cambios = []): array
{
    return ['name' => $u->name, 'cargo' => $u->cargo ?? 'Ventas', 'email' => $u->email, 'password' => '', ...$cambios];
}

it('edita nombre, cargo y correo sin tocar la contraseña', function () {
    $laura = empresaConPrincipal();
    $andres = colaboradorDe($laura, ['cargo' => 'Ventas']);
    $clave = $andres->password;

    $this->actingAs($laura)
        ->put(route('colaboradores.actualizar', $andres), datosDe($andres, [
            'name' => 'Andrés Felipe Pérez',
            'cargo' => 'Marketing',
            'email' => 'Andres.Felipe@LaEsquina.co',
        ]))
        ->assertSessionHasNoErrors();

    $andres->refresh();
    expect($andres->name)->toBe('Andrés Felipe Pérez')
        ->and($andres->cargo)->toBe('Marketing')
        ->and($andres->email)->toBe('andres.felipe@laesquina.co')
        ->and($andres->password)->toBe($clave);
});

it('no deja poner el correo de otra cuenta', function () {
    $laura = empresaConPrincipal();
    $andres = colaboradorDe($laura);

    $this->actingAs($laura)
        ->put(route('colaboradores.actualizar', $andres), datosDe($andres, ['email' => $laura->email]))
        ->assertSessionHasErrors(['email' => 'Ya hay una cuenta con este correo.']);
});

it('cambia la contraseña y cierra las sesiones del colaborador', function () {
    $laura = empresaConPrincipal();
    $andres = colaboradorDe($laura);
    config(['session.driver' => 'database']); // el de la aplicación (.env.example)
    DB::table('sessions')->insert([
        'id' => 'sesion-de-andres', 'user_id' => $andres->id, 'ip_address' => null,
        'user_agent' => null, 'payload' => '', 'last_activity' => time(),
    ]);

    $this->actingAs($laura)
        ->put(route('colaboradores.actualizar', $andres), datosDe($andres, ['password' => 'Nueva-12-Clave']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $andres->refresh();
    expect(Hash::check('Nueva-12-Clave', (string) $andres->password))->toBeTrue()
        ->and($andres->contrasena_actualizada_en)->not->toBeNull()
        ->and(DB::table('sessions')->where('user_id', $andres->id)->exists())->toBeFalse();
});

it('pide una contraseña fuerte al cambiarla', function () {
    $laura = empresaConPrincipal();

    $this->actingAs($laura)
        ->put(route('colaboradores.actualizar', $c = colaboradorDe($laura)), datosDe($c, ['password' => 'corta']))
        ->assertSessionHasErrors('password');
});

// --- HU-079 · Desactivar y reactivar -----------------------------------------

it('desactiva y reactiva un colaborador', function () {
    $laura = empresaConPrincipal();
    $andres = colaboradorDe($laura);

    $this->actingAs($laura)->post(route('colaboradores.desactivar', $andres))->assertRedirect();
    expect($andres->refresh()->activo)->toBeFalse()
        ->and($andres->puedeEntrar())->toBeFalse();

    $this->actingAs($laura)->post(route('colaboradores.reactivar', $andres))->assertRedirect();
    expect($andres->refresh()->activo)->toBeTrue();
});

// --- Límites -----------------------------------------------------------------

it('no toca colaboradores de otra empresa ni la cuenta principal', function () {
    $laura = empresaConPrincipal();
    $ajeno = colaboradorDe(empresaConPrincipal('Otra empresa'));

    $this->actingAs($laura)->post(route('colaboradores.desactivar', $ajeno))->assertNotFound();
    $this->actingAs($laura)->put(route('colaboradores.actualizar', $ajeno), datosDe($ajeno, ['password' => 'Nueva-12-Clave']))->assertNotFound();
    $this->actingAs($laura)->post(route('colaboradores.desactivar', $laura))->assertForbidden();

    expect($ajeno->refresh()->activo)->toBeTrue()
        ->and($laura->refresh()->activo)->toBeTrue();
});

it('pide el cargo del colaborador', function () {
    $this->actingAs(empresaConPrincipal())
        ->post(route('colaboradores.store'), ['name' => 'Sin cargo', 'email' => 'sin@cargo.co', 'password' => 'Mesa-47-Sol'])
        ->assertSessionHasErrors('cargo');

    expect(User::where('email', 'sin@cargo.co')->exists())->toBeFalse();
});
