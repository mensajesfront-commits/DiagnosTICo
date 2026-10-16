<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

/**
 * L2b · Verificación del correo al registrarse.
 *
 * 1. Después de "Crear cuenta", la pantalla "Verifica tu correo" muestra a
 *    qué correo se mandó el enlace y deja reenviarlo.
 * 2. El enlace del correo (firmado, vence en 24 horas) marca el correo como
 *    verificado y lleva a Iniciar sesión con el correo ya escrito.
 *
 * Sin verificar no se puede iniciar sesión (FortifyServiceProvider). Así no
 * se crean cuentas con correos que no existen.
 */
class VerificacionCorreoController extends Controller
{
    /** Correo recién registrado, en la sesión de quien se registró. */
    public const string LLAVE = 'verificar_correo';

    /** Correo verificado, para escribirlo solo en Iniciar sesión. */
    public const string VERIFICADO = 'correo_verificado';

    public function aviso(Request $request): Response|RedirectResponse
    {
        $correo = $request->session()->get(self::LLAVE);

        if (! is_string($correo) || $correo === '') {
            return to_route('login');
        }

        return Inertia::render('auth/VerificarCorreo', [
            'correo' => $correo,
            'status' => $request->session()->get('status'),
        ]);
    }

    /** "Reenviar el correo": solo a la cuenta que se registró en esta sesión. */
    public function reenviar(Request $request): RedirectResponse
    {
        $correo = $request->session()->get(self::LLAVE);
        $usuario = is_string($correo) ? User::where('email', $correo)->first() : null;

        if ($usuario === null) {
            return to_route('login');
        }

        if ($usuario->hasVerifiedEmail()) {
            $request->session()->flash(self::VERIFICADO, $usuario->email);

            return to_route('login')->with('status', 'Tu correo ya está verificado. Inicia sesión.');
        }

        $usuario->sendEmailVerificationNotification();

        return back()->with('status', 'Te enviamos otro correo. Revisa también la carpeta de spam.');
    }

    /** El enlace del correo. No pide sesión: se abre desde el correo, en cualquier navegador. */
    public function confirmar(Request $request, int $id, string $hash): RedirectResponse
    {
        $usuario = User::find($id);

        if ($usuario === null || ! hash_equals(sha1($usuario->getEmailForVerification()), $hash)) {
            return to_route('login')->withErrors(['email' => 'El enlace de verificación no es válido.']);
        }

        if (! URL::hasValidSignature($request)) {
            // Vencido: quien se registró puede pedir otro desde "Verifica tu correo".
            $request->session()->put(self::LLAVE, $usuario->email);

            return to_route('verificacion.aviso')->with('status', 'El enlace venció. Pide uno nuevo con "Reenviar el correo".');
        }

        if (! $usuario->hasVerifiedEmail()) {
            $usuario->markEmailAsVerified();
        }

        $request->session()->forget(self::LLAVE);
        $request->session()->flash(self::VERIFICADO, $usuario->email);

        return to_route('login')->with('status', 'Tu correo quedó verificado. Escribe tu contraseña para entrar.');
    }
}
