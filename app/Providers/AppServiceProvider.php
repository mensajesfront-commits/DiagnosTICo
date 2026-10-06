<?php

namespace App\Providers;

use App\Models\User;
use App\Rules\TieneMayuscula;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // "Último acceso" en Mi perfil, Empresas y Usuarios (A6, A3.1, A5).
        Event::listen(Login::class, function (Login $evento): void {
            if ($evento->user instanceof User) {
                $evento->user->forceFill(['ultimo_acceso_en' => now()])->saveQuietly();
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // RN-001, igual en todos los entornos: mínimo 8 caracteres, una
        // mayúscula, un número y un carácter especial. La pantalla muestra
        // los mismos requisitos (resources/js/lib/contrasena.ts).
        Password::defaults(fn (): Password => Password::min(8)
            ->numbers()
            ->symbols()
            ->rules([new TieneMayuscula]));
    }
}
