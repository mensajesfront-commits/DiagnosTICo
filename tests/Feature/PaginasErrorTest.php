<?php

use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * Páginas de error con el diseño de Captter (bootstrap/app.php,
 * resources/js/pages/errores/Error.vue).
 */

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    Route::middleware('web')->get('/prueba-error/{codigo}', fn (int $codigo) => abort($codigo));
});

it('muestra la página 404 de Captter con su código', function () {
    $this->get('/no-existe-esta-pagina')
        ->assertNotFound()
        ->assertInertia(fn (Assert $p) => $p->component('errores/Error')->where('codigo', 404));
});

it('muestra la página 403 cuando falta el permiso', function () {
    $empresa = User::factory()->create();
    $empresa->assignRole('Empresa');

    $this->actingAs($empresa)->get('/usuarios')
        ->assertForbidden()
        ->assertInertia(fn (Assert $p) => $p->component('errores/Error')->where('codigo', 403));
});

it('muestra la página 503 en mantenimiento', function () {
    $this->get('/prueba-error/503')
        ->assertStatus(503)
        ->assertInertia(fn (Assert $p) => $p->component('errores/Error')->where('codigo', 503));
});

it('solo cambia el 500 sin APP_DEBUG', function () {
    config(['app.debug' => true]);
    $this->get('/prueba-error/500')->assertStatus(500)->assertDontSee('errores/Error');

    config(['app.debug' => false]);
    $this->get('/prueba-error/500')
        ->assertStatus(500)
        ->assertInertia(fn (Assert $p) => $p->component('errores/Error')->where('codigo', 500));
});

it('deja los errores en JSON para quien los pide así', function () {
    $this->getJson('/no-existe-esta-pagina')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('con la página expirada (419) vuelve atrás con un aviso', function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    Route::middleware('web')->post('/prueba-419b', fn () => abort(419));

    $this->from('/login')->post('/prueba-419b')
        ->assertRedirect('/login');

    expect(session('inertia.flash_data.toast.message') ?? collect(session()->all())->flatten()->implode(' '))
        ->toContain('Inténtalo de nuevo');
});
