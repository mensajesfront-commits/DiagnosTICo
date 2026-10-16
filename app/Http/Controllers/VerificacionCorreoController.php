<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Acceso\CodigoVerificacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * L2b · Verificación del correo con un código de 6 dígitos (DEC-020).
 *
 * Después de "Crear cuenta" (o al intentar entrar sin verificar) se abre la
 * ventana "Verifica tu correo" en la misma pantalla. La persona escribe el
 * código que le llegó y pasa a Iniciar sesión con el correo ya escrito.
 *
 * El correo que se verifica es el de la sesión de quien se registró o de
 * quien escribió la contraseña correcta: nadie puede pedir códigos para
 * otra cuenta.
 */
class VerificacionCorreoController extends Controller
{
    /** Correo por verificar, en la sesión de quien se registró. */
    public const string LLAVE = 'verificar_correo';

    /** Correo recién verificado, para escribirlo solo en Iniciar sesión. */
    public const string VERIFICADO = 'correo_verificado';

    public function __construct(private readonly CodigoVerificacion $codigos) {}

    public function verificar(Request $request): RedirectResponse
    {
        $datos = $request->validate(
            ['codigo' => ['required', 'digits:6']],
            ['codigo.required' => 'Escribe el código de 6 dígitos.', 'codigo.digits' => 'El código tiene 6 dígitos.'],
        );

        $usuario = $this->pendiente($request);

        if ($usuario === null) {
            return to_route('login');
        }

        $error = $this->codigos->verificar($usuario, $datos['codigo']);

        if ($error !== null) {
            return back()->withErrors(['codigo' => $error]);
        }

        $request->session()->forget(self::LLAVE);
        $request->session()->flash(self::VERIFICADO, $usuario->email);

        return to_route('login')->with('status', 'Tu correo quedó verificado. Escribe tu contraseña para entrar.');
    }

    public function reenviar(Request $request): RedirectResponse
    {
        $usuario = $this->pendiente($request);

        if ($usuario === null) {
            return to_route('login');
        }

        $this->codigos->enviar($usuario);

        return back()->with('status', 'Te enviamos un código nuevo. Revisa también la carpeta de spam.');
    }

    /** "Usar otro correo": cierra la ventana; la cuenta sigue sin verificar. */
    public function cancelar(Request $request): RedirectResponse
    {
        $request->session()->forget(self::LLAVE);

        return to_route('login');
    }

    private function pendiente(Request $request): ?User
    {
        $correo = $request->session()->get(self::LLAVE);
        $usuario = is_string($correo) ? User::where('email', $correo)->first() : null;

        if ($usuario?->hasVerifiedEmail()) {
            $request->session()->forget(self::LLAVE);

            return null;
        }

        return $usuario;
    }
}
