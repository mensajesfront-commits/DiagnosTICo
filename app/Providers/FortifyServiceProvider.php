<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\AvisoRecuperacionResponse;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // RN-005: mismo aviso exista o no el correo.
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, AvisoRecuperacionResponse::class);
        $this->app->bind(SuccessfulPasswordResetLinkRequestResponse::class, AvisoRecuperacionResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureLogin();
        $this->configureResetEmail();
    }

    /**
     * L1 · Solo entran las cuentas activas de empresas activas (RN-004,
     * RN-025).
     *
     * Correo inexistente o contraseña equivocada: el mismo mensaje, para no
     * revelar qué cuentas existen (RN-005). Solo quien escribe la contraseña
     * correcta de una cuenta desactivada recibe el error
     * `cuenta_desactivada`, que la pantalla muestra en un modal.
     */
    private function configureLogin(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $usuario = User::where('email', Str::lower($request->string(Fortify::username())->value()))->first();

            if (! $usuario || ! Hash::check($request->string('password')->value(), $usuario->password ?? '')) {
                return null;
            }

            if (! $usuario->puedeEntrar()) {
                throw ValidationException::withMessages(['cuenta_desactivada' => trans('auth.desactivada')]);
            }

            return $usuario;
        });
    }

    /**
     * Correo de "¿Olvidaste tu contraseña?" en español y con el nombre del
     * sistema. El enlace no vence por tiempo (RN-006, config/auth.php).
     */
    private function configureResetEmail(): void
    {
        ResetPassword::toMailUsing(function (object $usuario, string $token): MailMessage {
            /** @var User $usuario */
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $usuario->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Crea una contraseña nueva para '.config('app.name'))
                ->greeting("Hola, {$usuario->name}:")
                ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta.')
                ->action('Crear contraseña nueva', $url)
                ->line('El enlace sirve hasta que guardes la contraseña nueva. Si pides otro, solo funciona el último.')
                ->line('Si no pediste el cambio, ignora este correo: tu contraseña sigue igual.')
                ->salutation('El equipo de NuevasTIC');
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            // Solo los sectores activos se ofrecen al registrarse (RN-003),
            // cada uno con sus actividades económicas (CIIU) activas.
            'sectores' => $this->sectoresParaRegistro(),
            // [INFORMACIÓN PENDIENTE] Lista de países.
            'paises' => ['Colombia'],
        ]));

    }

    /**
     * @return list<array{id: int, nombre: string, actividades: list<array{id: int, codigo: string, nombre: string}>}>
     */
    private function sectoresParaRegistro(): array
    {
        $sectores = Sector::activos()
            ->with(['actividades' => fn ($q) => $q->where('activo', true)->orderBy('codigo')])
            ->orderBy('nombre')
            ->get();

        $lista = [];

        foreach ($sectores as $sector) {
            $actividades = [];

            foreach ($sector->actividades as $actividad) {
                $actividades[] = ['id' => $actividad->id, 'codigo' => $actividad->codigo, 'nombre' => $actividad->nombre];
            }

            $lista[] = ['id' => $sector->id, 'nombre' => $sector->nombre, 'actividades' => $actividades];
        }

        return $lista;
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {

        // 5 intentos por minuto por correo e IP. Al pasarse, vuelve a L1 con
        // el error `bloqueo` (segundos que faltan), que la pantalla muestra en
        // un modal con la cuenta regresiva.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey)->response(
                fn (Request $request, array $headers) => redirect()->route('login')
                    ->withInput($request->only(Fortify::username()))
                    ->withErrors(['bloqueo' => (string) ($headers['Retry-After'] ?? 60)]),
            );
        });

    }
}
