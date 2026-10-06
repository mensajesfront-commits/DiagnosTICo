<?php

namespace Tests\Feature\Auth;

use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
        $this->seed(RolesYPermisosSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(Sector $sector, array $cambios = []): array
    {
        return [
            'empresa_nombre' => 'Rojas & Asociados',
            'sector_id' => $sector->id,
            'ciudad' => 'Bogotá',
            'pais' => 'Colombia',
            'name' => 'Laura Gómez',
            'cargo' => 'Gerente',
            'email' => 'laura@rojas.co',
            'telefono' => '+57 300 000 0000',
            'password' => 'Clave123!',
            'password_confirmation' => 'Clave123!',
            'terminos' => '1',
            ...$cambios,
        ];
    }

    public function test_registration_screen_offers_only_active_sectors()
    {
        $activo = Sector::factory()->create(['nombre' => 'Abogados']);
        Sector::factory()->inactivo()->create(['nombre' => 'Talleres']);

        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/Register')
                ->has('sectores', 1)
                ->where('sectores.0.id', $activo->id)
                ->where('sectores.0.nombre', 'Abogados'));
    }

    public function test_new_companies_can_register()
    {
        $sector = Sector::factory()->create();

        $response = $this->post(route('register.store'), $this->datos($sector));

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $usuario = User::where('email', 'laura@rojas.co')->firstOrFail();
        $this->assertTrue($usuario->hasRole('Empresa'));
        $this->assertSame('Gerente', $usuario->cargo);

        $empresa = Empresa::findOrFail($usuario->empresa_id);
        $this->assertSame('Rojas & Asociados', $empresa->nombre);
        $this->assertSame($sector->id, $empresa->sector_id);
    }

    public function test_inactive_sector_is_rejected()
    {
        $sector = Sector::factory()->inactivo()->create();

        $this->post(route('register.store'), $this->datos($sector))
            ->assertSessionHasErrors('sector_id');

        $this->assertGuest();
        $this->assertSame(0, Empresa::count());
    }

    public function test_terms_must_be_accepted()
    {
        $sector = Sector::factory()->create();

        $this->post(route('register.store'), $this->datos($sector, ['terminos' => null]))
            ->assertSessionHasErrors('terminos');

        $this->assertGuest();
    }
}
