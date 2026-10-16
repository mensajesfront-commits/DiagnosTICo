<?php

namespace App\Services\Acceso;

use App\Models\User;
use App\Notifications\CodigoVerificacionCorreo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

/**
 * Código de 6 dígitos para confirmar un correo (DEC-020): al registrarse
 * (L2b) y al cambiar el correo en Mi perfil (A6, E11). Se guarda cifrado;
 * vence en 15 minutos y admite 5 intentos. Pedir otro reemplaza al anterior.
 */
class CodigoVerificacion
{
    public const int MINUTOS = 15;

    public const int INTENTOS = 5;

    /**
     * Crea un código nuevo y lo manda al correo de la cuenta o, al cambiarlo
     * en Mi perfil, al correo nuevo (`$destino`). `$motivo` cambia el texto
     * del correo: registro, correo o contrasena.
     */
    public function enviar(User $usuario, ?string $destino = null, string $motivo = 'registro'): void
    {
        $codigo = (string) random_int(100000, 999999);

        $usuario->forceFill([
            'codigo_verificacion' => Hash::make($codigo),
            'codigo_vence_en' => now()->addMinutes(self::MINUTOS),
            'codigo_intentos' => 0,
        ])->save();

        // Al momento, no en la cola: la persona lo está esperando.
        if ($destino !== null) {
            Notification::route('mail', $destino)->notifyNow(new CodigoVerificacionCorreo($codigo, $usuario->name, $motivo));
        } else {
            $usuario->notifyNow(new CodigoVerificacionCorreo($codigo, $usuario->name, $motivo));
        }
    }

    /** ¿Tiene un código que todavía sirve? */
    public function vigente(User $usuario): bool
    {
        return $usuario->codigo_verificacion !== null
            && $usuario->codigo_vence_en?->isFuture()
            && $usuario->codigo_intentos < self::INTENTOS;
    }

    /**
     * Comprueba el código. Devuelve null si sirvió (y verifica el correo) o
     * el mensaje de error para mostrar.
     */
    public function verificar(User $usuario, string $codigo): ?string
    {
        if ($usuario->hasVerifiedEmail()) {
            return null;
        }

        $error = $this->comprobar($usuario, $codigo);

        if ($error === null) {
            $usuario->forceFill(['email_verified_at' => now()])->save();
        }

        return $error;
    }

    /**
     * Comprueba el código sin decidir qué se confirma. Si sirve, lo borra y
     * devuelve null; si no, cuenta el intento y devuelve el mensaje.
     */
    public function comprobar(User $usuario, string $codigo): ?string
    {
        if ($usuario->codigo_verificacion === null || ! $usuario->codigo_vence_en?->isFuture()) {
            return 'El código venció. Pide uno nuevo con "Reenviar código".';
        }

        if ($usuario->codigo_intentos >= self::INTENTOS) {
            return 'Demasiados intentos. Pide un código nuevo con "Reenviar código".';
        }

        if (! Hash::check($codigo, $usuario->codigo_verificacion)) {
            $usuario->forceFill(['codigo_intentos' => $usuario->codigo_intentos + 1])->save();
            $quedan = self::INTENTOS - $usuario->codigo_intentos;

            return $quedan > 0
                ? "El código no coincide. Te quedan {$quedan} ".($quedan === 1 ? 'intento.' : 'intentos.')
                : 'Demasiados intentos. Pide un código nuevo con "Reenviar código".';
        }

        $usuario->forceFill([
            'codigo_verificacion' => null,
            'codigo_vence_en' => null,
            'codigo_intentos' => 0,
        ])->save();

        return null;
    }
}
