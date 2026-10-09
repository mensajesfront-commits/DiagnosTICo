<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Roles, sectores con sus actividades, las 10 categorías y la cuenta del
     * Administrador (T-046). Los niveles son fijos (RN-019) y están en
     * App\Support\Niveles.
     *
     * El correo y la contraseña del Administrador salen de ADMIN_EMAIL y
     * ADMIN_PASSWORD en .env (config/diagnostico.php). Sin contraseña, se
     * genera una y se muestra.
     */
    public function run(): void
    {
        $this->call([RolesYPermisosSeeder::class, SectoresSeeder::class, CategoriasSeeder::class]);

        $correo = (string) config('diagnostico.admin.email');

        if (User::where('email', $correo)->exists()) {
            return;
        }

        $definida = (string) config('diagnostico.admin.password');
        $contrasena = $definida !== '' ? $definida : Str::password(12, symbols: false);

        User::create([
            'name' => 'Administrador',
            'email' => $correo,
            'password' => $contrasena,
        ])->assignRole('Administrador');

        $this->command->info("Administrador creado: {$correo}".($definida !== '' ? '' : " · contraseña: {$contrasena}"));
    }
}
