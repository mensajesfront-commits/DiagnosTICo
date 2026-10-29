<?php

use App\Models\User;
use App\Notifications\InvitacionCuenta;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Support\Facades\Notification;

/*
 * Cuenta principal del Administrador: la crea el seeder sin contraseña y le
 * llega el correo para crearla (RN-006). `admin:invitar` lo reenvía.
 */

it('el seeder crea la cuenta principal sin contraseña y le envía la invitación', function () {
    Notification::fake();
    config(['diagnostico.admin.email' => 'gerencia@nuevastic.co']);

    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'gerencia@nuevastic.co')->firstOrFail();
    expect($admin->hasRole('Administrador'))->toBeTrue()
        ->and($admin->administrador_principal)->toBeTrue()
        ->and($admin->password)->toBeNull()
        ->and($admin->invitacion_enviada_en)->not->toBeNull();
    Notification::assertSentTo($admin, InvitacionCuenta::class, fn (InvitacionCuenta $n) => $n->rol === 'Administrador');

    // Correrlo otra vez no duplica ni reenvía.
    Notification::fake();
    $this->seed(DatabaseSeeder::class);
    expect(User::where('email', 'gerencia@nuevastic.co')->count())->toBe(1);
    Notification::assertNothingSent();
});

it('admin:invitar reenvía la invitación mientras no haya creado su contraseña', function () {
    Notification::fake();
    $this->seed(RolesYPermisosSeeder::class);
    $admin = User::factory()->create(['email' => 'gerencia@nuevastic.co', 'password' => null])->assignRole('Administrador');
    $admin->forceFill(['administrador_principal' => true])->save();

    $this->artisan('admin:invitar')
        ->expectsOutput('Enviamos a gerencia@nuevastic.co el correo para crear su contraseña.')
        ->assertSuccessful();
    Notification::assertSentTo($admin, InvitacionCuenta::class);

    $admin->forceFill(['password' => 'Clave-Segura-2026'])->save();
    Notification::fake();
    $this->artisan('admin:invitar')
        ->expectsOutputToContain('ya creó su contraseña')
        ->assertSuccessful();
    Notification::assertNothingSent();
});

it('si el correo falla, admin:invitar muestra el enlace para crear la contraseña', function () {
    $this->seed(RolesYPermisosSeeder::class);
    $admin = User::factory()->create(['email' => 'gerencia@nuevastic.co', 'password' => null])->assignRole('Administrador');
    $admin->forceFill(['administrador_principal' => true])->save();
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);

    $this->artisan('admin:invitar')
        ->expectsOutputToContain('No se pudo enviar el correo')
        ->expectsOutputToContain('/reset-password/')
        ->assertSuccessful();
});
