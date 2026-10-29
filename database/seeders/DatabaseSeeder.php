<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\AdministradorPrincipal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Roles, sectores con sus actividades, las 10 categorías y la cuenta del
     * Administrador (T-046). Los niveles son fijos (RN-019) y están en
     * App\Support\Niveles.
     *
     * La cuenta del Administrador es la cuenta principal: su correo sale de
     * ADMIN_EMAIL en .env (lo define NuevasTIC al lanzar). Se crea sin
     * contraseña y le llega el correo para crearla (RN-006): nadie más la
     * conoce. Si el correo falla, se muestra el enlace; se reenvía con
     * `php artisan admin:invitar`.
     */
    public function run(): void
    {
        $this->call([RolesYPermisosSeeder::class, SectoresSeeder::class, CategoriasSeeder::class]);

        $correo = (string) config('diagnostico.admin.email');

        $existente = User::where('email', $correo)->first();

        if ($existente !== null) {
            // Cuenta principal: la de ADMIN_EMAIL, si todavía no hay una.
            if ($existente->hasRole('Administrador') && ! User::where('administrador_principal', true)->exists()) {
                $existente->forceFill(['administrador_principal' => true])->save();
            }

            return;
        }

        $admin = User::create([
            'name' => 'Administrador',
            'email' => $correo,
            'password' => null,
        ]);
        // El enlace del correo también confirma el correo.
        $admin->forceFill(['email_verified_at' => now(), 'administrador_principal' => true])->save();
        $admin->assignRole('Administrador');

        $enlace = AdministradorPrincipal::invitar($admin);

        if ($enlace === null) {
            $this->command->info("Administrador principal creado: {$correo}. Le enviamos el correo para crear su contraseña.");
        } else {
            $this->command->warn("Administrador principal creado: {$correo}, pero no se pudo enviar el correo (revisa MAIL_* en .env).");
            $this->command->line("Enlace para crear la contraseña: {$enlace}");
            $this->command->line('Para reenviar el correo: php artisan admin:invitar');
        }
    }
}
