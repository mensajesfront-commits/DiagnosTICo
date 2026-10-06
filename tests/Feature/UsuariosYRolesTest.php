<?php

use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\InvitacionCuenta;
use App\Notifications\RolCambiado;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

/*
 * A5 · Usuarios y roles (HU-046 a HU-052). docs/15_BACKEND.md.
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
    $this->admin = User::factory()->create(['name' => 'Cristian A.'])->assignRole('Administrador');
});

function cuentaPrincipal(): User
{
    $empresa = Empresa::create([
        'nombre' => 'Restaurante La Esquina',
        'sector_id' => Sector::factory()->create()->id,
        'ciudad' => 'Cali',
        'pais' => 'Colombia',
    ]);

    return User::factory()->create(['empresa_id' => $empresa->id])->assignRole('Empresa');
}

// --- Acceso a la sección ----------------------------------------------------

it('muestra la lista de cuentas al Administrador', function () {
    $laura = cuentaPrincipal();
    User::factory()->create(['empresa_id' => $laura->empresa_id])->assignRole('Colaborador');

    $this->actingAs($this->admin)
        ->get(route('usuarios.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('usuarios/Index')
            ->has('cuentas', 3)
            ->where('cuentas.0.es_tuya', true)
            ->where('cuentas.0.rol', 'Administrador')
            ->has('roles', 4));

    $cuentas = collect($this->get(route('usuarios.index'))->viewData('page')['props']['cuentas']);
    $principal = $cuentas->firstWhere('id', $laura->id);
    expect($principal['es_principal'])->toBeTrue()
        ->and($principal['colaboradores_activos'])->toBe(1)
        ->and($principal['empresa'])->toBe('Restaurante La Esquina');
});

it('no deja entrar a una cuenta de empresa (RN-007)', function () {
    $this->actingAs(cuentaPrincipal())
        ->get(route('usuarios.index'))
        ->assertForbidden();
});

it('muestra los roles con sus bloques de permisos', function () {
    $this->actingAs($this->admin)
        ->get(route('usuarios.roles', ['rol' => Role::findByName('Consultor')->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('usuarios/Roles')
            ->has('roles', 4)
            ->where('roles.0.nombre', 'Administrador')
            ->where('roles.0.del_sistema', true)
            ->where('roles.3.nombre', 'Consultor')
            ->where('roles.3.activo', false)
            ->has('bloques', 6)
            ->where('rolId', Role::findByName('Consultor')->id));
});

// --- Invitar ----------------------------------------------------------------

it('invita a una cuenta interna sin contraseña y le envía el enlace', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post(route('usuarios.invitar'), [
            'name' => 'Mateo Herrera',
            'email' => 'mateo@nuevastic.co',
            'rol_id' => Role::findByName('Administrador')->id,
            'mensaje' => 'Te invito a apoyar la revisión de diagnósticos.',
        ])
        ->assertSessionHasNoErrors();

    $mateo = User::where('email', 'mateo@nuevastic.co')->firstOrFail();
    expect($mateo->password)->toBeNull()
        ->and($mateo->hasRole('Administrador'))->toBeTrue()
        ->and($mateo->invitacion_enviada_en)->not->toBeNull();

    Notification::assertSentTo($mateo, InvitacionCuenta::class, function (InvitacionCuenta $aviso) use ($mateo) {
        // La persona crea su contraseña con el enlace y ya puede entrar.
        auth()->logout();
        test()->post(route('password.update'), [
            'token' => $aviso->token,
            'email' => $mateo->email,
            'password' => 'Clave123!',
            'password_confirmation' => 'Clave123!',
        ])->assertSessionHasNoErrors();

        return true;
    });

    $this->post(route('login.store'), ['email' => 'mateo@nuevastic.co', 'password' => 'Clave123!']);
    $this->assertAuthenticatedAs($mateo);
});

it('no invita con un rol inactivo ni con un rol de empresa', function (string $rol) {
    $this->actingAs($this->admin)
        ->post(route('usuarios.invitar'), [
            'name' => 'Mateo',
            'email' => 'mateo@nuevastic.co',
            'rol_id' => Role::findByName($rol)->id,
        ])
        ->assertSessionHasErrors('rol_id');
})->with(['Consultor', 'Empresa']);

it('una invitación pendiente no puede iniciar sesión', function () {
    $invitada = User::factory()->create(['password' => null])->assignRole('Administrador');

    $this->post(route('login.store'), ['email' => $invitada->email, 'password' => '']);

    $this->assertGuest();
});

// --- Desactivar y reactivar (A5.3b) ------------------------------------------

it('desactiva la cuenta principal y con ella la empresa (RN-025)', function () {
    $laura = cuentaPrincipal();

    $this->actingAs($this->admin)
        ->post(route('usuarios.desactivar', $laura))
        ->assertSessionHasNoErrors();

    expect($laura->fresh()->activo)->toBeFalse()
        ->and($laura->empresa->fresh()->activa)->toBeFalse();

    $this->post(route('usuarios.reactivar', $laura));

    expect($laura->fresh()->activo)->toBeTrue()
        ->and($laura->empresa->fresh()->activa)->toBeTrue();
});

it('no deja desactivar ni cambiar el rol de la propia cuenta', function () {
    $this->actingAs($this->admin)
        ->post(route('usuarios.desactivar', $this->admin))
        ->assertForbidden();

    $this->put(route('usuarios.cambiar-rol', $this->admin), [
        'rol_id' => Role::findByName('Empresa')->id,
    ])->assertForbidden();
});

// --- Cambiar y asignar rol (A5.5) ---------------------------------------------

it('cambia el rol y avisa por correo', function () {
    Notification::fake();
    $luis = User::factory()->create()->assignRole('Administrador');
    $rol = Role::create(['name' => 'Revisor', 'guard_name' => 'web']);
    $rol->forceFill(['activo' => true])->save();

    $this->actingAs($this->admin)
        ->put(route('usuarios.cambiar-rol', $luis), ['rol_id' => $rol->id, 'avisar' => true])
        ->assertSessionHasNoErrors();

    expect($luis->fresh()->getRoleNames()->all())->toBe(['Revisor']);
    Notification::assertSentTo($luis, RolCambiado::class);
});

it('no asigna Colaborador, ni Empresa a una cuenta interna, ni un rol inactivo', function () {
    $luis = User::factory()->create()->assignRole('Administrador');

    $this->actingAs($this->admin);

    foreach (['Colaborador', 'Empresa', 'Consultor'] as $rol) {
        $this->put(route('usuarios.cambiar-rol', $luis), ['rol_id' => Role::findByName($rol)->id])
            ->assertSessionHasErrors('rol_id');
    }

    $this->post(route('roles.asignar', Role::findByName('Consultor')), ['cuenta_id' => $luis->id])
        ->assertSessionHasErrors('cuenta_id');

    expect($luis->fresh()->getRoleNames()->all())->toBe(['Administrador']);
});

// --- Roles (A5.1c, A5.1d, A5.2) ------------------------------------------------

it('crea un rol con permisos y se lo asigna a una cuenta', function () {
    $luis = User::factory()->create()->assignRole('Administrador');

    $this->actingAs($this->admin)
        ->post(route('roles.store'), [
            'nombre' => 'Revisor de resultados',
            'descripcion' => 'Ve empresas, resultados y respuestas, sin editar nada.',
            'activo' => true,
            'permisos' => ['diagnosticos.ver', 'empresas.ver', 'resultados.ver'],
            'cuentas' => [$luis->id],
        ])
        ->assertRedirect();

    $rol = Role::findByName('Revisor de resultados');
    expect($rol->permissions->pluck('name')->all())->toHaveCount(3)
        ->and((bool) $rol->del_sistema)->toBeFalse()
        ->and($luis->fresh()->hasRole('Revisor de resultados'))->toBeTrue();
});

it('no crea un rol con nombre repetido ni con permisos que no existen', function () {
    $this->actingAs($this->admin)
        ->post(route('roles.store'), ['nombre' => 'Administrador', 'activo' => true, 'permisos' => ['inventado']])
        ->assertSessionHasErrors(['nombre', 'permisos.0']);
});

it('edita un rol creado pero no los del sistema', function () {
    $consultor = Role::findByName('Consultor');

    $this->actingAs($this->admin)
        ->put(route('roles.update', $consultor), [
            'nombre' => 'Consultor',
            'descripcion' => 'Acompaña a sus empresas.',
            'activo' => true,
            'permisos' => ['empresas.ver'],
        ])
        ->assertSessionHasNoErrors();

    expect((bool) $consultor->fresh()->activo)->toBeTrue()
        ->and($consultor->fresh()->permissions)->toHaveCount(1);

    $this->put(route('roles.update', Role::findByName('Empresa')), [
        'nombre' => 'Empresa',
        'activo' => true,
        'permisos' => [],
    ])->assertForbidden();
});

it('elimina un rol creado solo si no tiene cuentas', function () {
    $rol = Role::create(['name' => 'Temporal', 'guard_name' => 'web']);
    $rol->forceFill(['activo' => true])->save();
    $cuenta = User::factory()->create()->assignRole('Temporal');

    $this->actingAs($this->admin)
        ->delete(route('roles.destroy', $rol))
        ->assertStatus(422);

    $cuenta->syncRoles(['Administrador']);

    $this->delete(route('roles.destroy', $rol))->assertRedirect(route('usuarios.roles'));
    expect(Role::where('name', 'Temporal')->exists())->toBeFalse();

    $this->delete(route('roles.destroy', Role::findByName('Administrador')))->assertForbidden();
});
