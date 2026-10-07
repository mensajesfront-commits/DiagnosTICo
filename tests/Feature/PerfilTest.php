<?php

use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * A6 · Mi perfil del Administrador y E11 · Mi perfil de la empresa.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

function cuentaDeEmpresa(string $rol = 'Empresa'): User
{
    $empresa = Empresa::create([
        'nombre' => 'Restaurante La Esquina',
        'sector_id' => Sector::factory()->create(['nombre' => 'Comidas'])->id,
        'ciudad' => 'Cali',
        'pais' => 'Colombia',
    ]);

    return User::factory()->create(['empresa_id' => $empresa->id])->assignRole($rol);
}

/** @return array<string, mixed> */
function datosPerfil(User $usuario, array $cambios = []): array
{
    return [
        'name' => $usuario->name,
        'email' => $usuario->email,
        'zona_horaria' => 'America/Bogota',
        'idioma' => 'es',
        ...$cambios,
    ];
}

it('muestra Mi perfil del Administrador con sus avisos', function () {
    $admin = User::factory()->create()->assignRole('Administrador');

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('perfil/MiPerfil')
            ->where('rol', 'Administrador')
            ->where('empresa', null)
            ->where('usuario.avisos', ['empresa_envia' => true, 'ia_falla' => true, 'resumen_semanal' => false]));
});

it('guarda los datos personales y los avisos', function () {
    $admin = User::factory()->create()->assignRole('Administrador');

    $this->actingAs($admin)
        ->patch(route('profile.update'), datosPerfil($admin, [
            'name' => 'Cristian Andrés',
            'cargo' => 'Administrador del sistema',
            'ciudad' => 'Bogotá',
            'departamento' => 'Bogotá D.C.',
            'pais' => 'Colombia',
            'avisos' => ['resumen_semanal' => true, 'otro' => true],
        ]))
        ->assertRedirect(route('profile.edit'));

    $admin->refresh();
    expect($admin->name)->toBe('Cristian Andrés')
        ->and($admin->ciudad)->toBe('Bogotá')
        ->and($admin->avisos)->toBe(['resumen_semanal' => true]);
});

it('deja a la cuenta principal cambiar los datos de la empresa, salvo el sector', function () {
    $usuario = cuentaDeEmpresa();
    $sector = $usuario->empresa->sector_id;

    $this->actingAs($usuario)
        ->patch(route('profile.update'), datosPerfil($usuario, [
            'empresa' => [
                'nombre' => 'La Esquina Gourmet',
                'ciudad' => 'Cali',
                'departamento' => 'Valle del Cauca',
                'pais' => 'Colombia',
                'sitio_web' => 'https://laesquina.co',
                'numero_empleados' => '11 a 50',
                'sector_id' => 999,
            ],
        ]))
        ->assertSessionHasNoErrors();

    $empresa = $usuario->empresa->fresh();
    expect($empresa->nombre)->toBe('La Esquina Gourmet')
        ->and($empresa->numero_empleados)->toBe('11 a 50')
        ->and($empresa->sector_id)->toBe($sector);
});

it('no deja al colaborador cambiar los datos de la empresa (RN-025)', function () {
    $colaborador = cuentaDeEmpresa('Colaborador');

    $this->actingAs($colaborador)
        ->patch(route('profile.update'), datosPerfil($colaborador, [
            'name' => 'Andrés Pérez',
            'empresa' => ['nombre' => 'Otra', 'ciudad' => 'Otra', 'pais' => 'Colombia'],
        ]))
        ->assertSessionHasNoErrors();

    expect($colaborador->fresh()->name)->toBe('Andrés Pérez')
        ->and($colaborador->empresa->fresh()->nombre)->toBe('Restaurante La Esquina');
});

it('cambia la contraseña con la actual y guarda cuándo', function () {
    $usuario = User::factory()->create()->assignRole('Administrador');

    $this->actingAs($usuario)
        ->from(route('profile.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'NuevaClave1!',
            'password_confirmation' => 'NuevaClave1!',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $usuario->refresh();
    expect(Hash::check('NuevaClave1!', $usuario->password))->toBeTrue()
        ->and($usuario->contrasena_actualizada_en)->not->toBeNull();
});

it('pide la contraseña actual correcta', function () {
    $usuario = User::factory()->create();

    $this->actingAs($usuario)
        ->from(route('profile.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'equivocada',
            'password' => 'NuevaClave1!',
            'password_confirmation' => 'NuevaClave1!',
        ])
        ->assertSessionHasErrors('current_password');
});

it('guarda el logo de la empresa y la foto de las demás cuentas', function () {
    Storage::fake();
    $usuario = cuentaDeEmpresa();
    $admin = User::factory()->create()->assignRole('Administrador');

    $this->actingAs($usuario)->post(route('perfil.foto'), ['foto' => UploadedFile::fake()->create('logo.png', 20, 'image/png')]);
    $this->actingAs($admin)->post(route('perfil.foto'), ['foto' => UploadedFile::fake()->create('yo.jpg', 20, 'image/jpeg')]);

    $logo = $usuario->empresa->fresh()->logo_ruta;
    expect($logo)->toStartWith('logos/')
        ->and($admin->fresh()->foto_ruta)->toStartWith('fotos/');
    Storage::assertExists($logo);

    $this->actingAs($usuario)
        ->get(route('perfil.imagen', ['tipo' => 'empresa', 'id' => $usuario->empresa_id]))
        ->assertOk();
});

it('no muestra la imagen de otra empresa a una cuenta de empresa', function () {
    Storage::fake();
    $otra = cuentaDeEmpresa();
    $this->actingAs($otra)->post(route('perfil.foto'), ['foto' => UploadedFile::fake()->create('logo.png', 20, 'image/png')]);

    $intrusa = User::factory()->create()->assignRole('Colaborador');

    $this->actingAs($intrusa)
        ->get(route('perfil.imagen', ['tipo' => 'empresa', 'id' => $otra->empresa_id]))
        ->assertForbidden();
});

it('guarda el último acceso al iniciar sesión', function () {
    $usuario = User::factory()->create();

    $this->post(route('login.store'), ['email' => $usuario->email, 'password' => 'password']);

    expect($usuario->fresh()->ultimo_acceso_en)->not->toBeNull();
});

it('pide que el departamento sea del país elegido', function () {
    $admin = User::factory()->create()->assignRole('Administrador');

    $this->actingAs($admin)
        ->patch(route('profile.update'), datosPerfil($admin, [
            'pais' => 'México',
            'departamento' => 'Antioquia',
        ]))
        ->assertSessionHasErrors('departamento');

    $this->actingAs($admin)
        ->patch(route('profile.update'), datosPerfil($admin, [
            'pais' => 'México',
            'departamento' => 'Jalisco',
            'ciudad' => 'Un pueblo que no está en la lista',
        ]))
        ->assertSessionHasNoErrors();

    expect($admin->refresh()->departamento)->toBe('Jalisco');
});
