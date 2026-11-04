<?php

namespace App\Console\Commands;

use App\Support\AdministradorPrincipal;
use Illuminate\Console\Command;

/**
 * Reenvía a la cuenta principal del Administrador el correo para crear su
 * contraseña, por si no le llegó al instalar. Si el correo falla, muestra
 * el enlace.
 */
class InvitarAdministrador extends Command
{
    protected $signature = 'admin:invitar';

    protected $description = 'Reenvía al Administrador principal el correo para crear su contraseña';

    public function handle(): int
    {
        $admin = AdministradorPrincipal::cuenta();

        if ($admin === null) {
            $this->error('No hay cuenta de Administrador principal. Revisa ADMIN_EMAIL en .env y corre php artisan db:seed.');

            return self::FAILURE;
        }

        if ($admin->password !== null) {
            $this->info("{$admin->email} ya creó su contraseña. Si la olvidó, que use \"¿Olvidaste tu contraseña?\" en el inicio de sesión.");

            return self::SUCCESS;
        }

        $enlace = AdministradorPrincipal::invitar($admin);

        if ($enlace === null) {
            $this->info("Enviamos a {$admin->email} el correo para crear su contraseña.");
        } else {
            $this->warn('No se pudo enviar el correo (revisa MAIL_* en .env). Dale este enlace para crear la contraseña:');
            $this->line($enlace);
        }

        return self::SUCCESS;
    }
}
