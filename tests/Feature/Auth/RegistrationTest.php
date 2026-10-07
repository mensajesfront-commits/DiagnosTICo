<?php

namespace Tests\Feature\Auth;

use App\Models\ActividadEconomica;
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
            'descripcion' => 'Bufete de derecho laboral para pequeñas empresas.',
            'sector_id' => $sector->id,
            'actividad_economica_id' => $sector->actividades()->value('id'),
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

    private function sectorConActividad(string $codigo = '6910'): Sector
    {
        $sector = Sector::factory()->create();
        ActividadEconomica::create(['sector_id' => $sector->id, 'codigo' => $codigo, 'nombre' => 'Actividades jurídicas']);

        return $sector;
    }

    public function test_registration_screen_offers_only_active_sectors()
    {
        $activo = Sector::factory()->create(['nombre' => 'Abogados']);
        ActividadEconomica::create(['sector_id' => $activo->id, 'codigo' => '6910', 'nombre' => 'Actividades jurídicas']);
        ActividadEconomica::create(['sector_id' => $activo->id, 'codigo' => '6999', 'nombre' => 'Retirada', 'activo' => false]);
        Sector::factory()->inactivo()->create(['nombre' => 'Talleres']);

        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/Register')
                ->has('sectores', 1)
                ->where('sectores.0.id', $activo->id)
                ->where('sectores.0.nombre', 'Abogados')
                ->has('sectores.0.actividades', 1)
                ->where('sectores.0.actividades.0.codigo', '6910'));
    }

    public function test_new_companies_can_register()
    {
        $sector = $this->sectorConActividad();

        $response = $this->post(route('register.store'), $this->datos($sector));

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $usuario = User::where('email', 'laura@rojas.co')->firstOrFail();
        $this->assertTrue($usuario->hasRole('Empresa'));
        $this->assertSame('Gerente', $usuario->cargo);

        $empresa = Empresa::findOrFail($usuario->empresa_id);
        $this->assertSame('Rojas & Asociados', $empresa->nombre);
        $this->assertSame($sector->id, $empresa->sector_id);
        $this->assertSame($sector->actividades()->value('id'), $empresa->actividad_economica_id);
        $this->assertSame('Bufete de derecho laboral para pequeñas empresas.', $empresa->descripcion);
    }

    public function test_activity_must_belong_to_the_chosen_sector()
    {
        $sector = $this->sectorConActividad();
        $otro = $this->sectorConActividad('5611');

        $this->post(route('register.store'), $this->datos($sector, ['actividad_economica_id' => $otro->actividades()->value('id')]))
            ->assertSessionHasErrors('actividad_economica_id');

        $this->post(route('register.store'), $this->datos($sector, ['actividad_economica_id' => null]))
            ->assertSessionHasErrors('actividad_economica_id');

        $this->assertGuest();
        $this->assertSame(0, Empresa::count());
    }

    public function test_sector_without_activities_does_not_ask_for_one()
    {
        $sector = Sector::factory()->create();

        $this->post(route('register.store'), $this->datos($sector, ['actividad_economica_id' => null]))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    public function test_description_and_job_title_are_required()
    {
        $sector = $this->sectorConActividad();

        $this->post(route('register.store'), $this->datos($sector, ['descripcion' => '', 'cargo' => '']))
            ->assertSessionHasErrors(['descripcion', 'cargo']);

        $this->post(route('register.store'), $this->datos($sector, ['descripcion' => str_repeat('a', 301)]))
            ->assertSessionHasErrors('descripcion');

        $this->assertGuest();
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
