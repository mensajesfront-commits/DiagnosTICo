<?php

namespace App\Services\Mediciones;

use App\Models\Medicion;
use App\Notifications\AvisoMedicion;
use App\Support\CorreoAviso;
use App\Support\DatosEmpresas;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Envía el correo de aviso de una medición al usuario principal de su
 * empresa, con el asunto y el cuerpo de la medición (o los de la plantilla),
 * y guarda cuándo se envió (HU-008 CA-003).
 */
class EnviarAviso
{
    /**
     * @param  bool  $alAsignar  Si es el aviso de una medición recién asignada:
     *                           respeta "Avisarme cuando me asignen una medición" (E11).
     *                           Los recordatorios los pide el Administrador y salen siempre.
     */
    public function enviar(Medicion $medicion, bool $alAsignar = false): void
    {
        $medicion->loadMissing('empresa');
        $principal = $medicion->empresa ? DatosEmpresas::principal($medicion->empresa) : null;

        if ($principal === null) {
            throw ValidationException::withMessages(['envio' => 'La empresa no tiene usuario principal a quien enviarle el correo.']);
        }

        if ($alAsignar && ! $principal->quiereAviso('medicion_asignada')) {
            throw ValidationException::withMessages(['envio' => 'La empresa apagó el aviso por correo de mediciones nuevas en su perfil.']);
        }

        $correo = CorreoAviso::deMedicion($medicion);
        $variables = CorreoAviso::variables($medicion, $principal);

        try {
            $principal->notify(new AvisoMedicion(
                CorreoAviso::rellenar($correo['asunto'], $variables),
                CorreoAviso::rellenar($correo['cuerpo'], $variables),
            ));
        } catch (Throwable $e) {
            // Solo llega aquí si no se pudo poner en la cola (o, sin cola,
            // si el servidor de correo falló): se avisa y se puede reintentar.
            Log::error('No se pudo enviar el aviso de la medición', ['medicion' => $medicion->id, 'error' => $e->getMessage()]);

            throw ValidationException::withMessages(['envio' => 'No pudimos enviar el correo. Intenta de nuevo en un momento.']);
        }

        $medicion->forceFill(['ultimo_aviso_en' => now()])->save();
    }
}
