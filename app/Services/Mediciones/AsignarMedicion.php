<?php

namespace App\Services\Mediciones;

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\User;
use App\Support\DatosEmpresas;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Asignar una medición a una empresa (HU-036, A3.1b; también al registrarla,
 * A3.2). Reglas:
 *
 * - Solo diagnósticos publicados del sector de la empresa; se responde la
 *   última versión publicada (RN-010, RN-014).
 * - Una sola medición pendiente por empresa (RN-014). Si ya hay una, solo se
 *   asigna con `reemplazar`: la anterior queda cancelada y la nueva ocupa su
 *   número ("la nueva reemplaza a la medición 4", A3.1c).
 * - Con `aviso`, se envía el correo de aviso al usuario principal
 *   (`EnviarAviso`). `correo` trae el asunto y el cuerpo editados para esta
 *   medición (A3.1e); sin él, se usa la plantilla general.
 */
class AsignarMedicion
{
    public function __construct(private readonly EnviarAviso $enviarAviso) {}

    /**
     * @param  array{asunto: string, cuerpo: string}|null  $correo
     */
    public function asignar(
        Empresa $empresa,
        Diagnostico $diagnostico,
        ?string $fechaLimite,
        ?string $mensaje,
        bool $aviso,
        ?User $por,
        bool $reemplazar = false,
        ?array $correo = null,
    ): Medicion {
        $diagnostico->loadMissing('ultimaVersion');

        if ($diagnostico->sector_id !== $empresa->sector_id || $diagnostico->estado !== Diagnostico::PUBLICADO || $diagnostico->ultimaVersion === null) {
            throw ValidationException::withMessages(['diagnostico_id' => 'Elige un diagnóstico publicado del sector de la empresa.']);
        }

        if (! $empresa->activa) {
            throw ValidationException::withMessages(['empresa_id' => 'La empresa está desactivada: reactívala para asignarle una medición.']);
        }

        $medicion = DB::transaction(function () use ($empresa, $diagnostico, $fechaLimite, $mensaje, $aviso, $por, $reemplazar, $correo): Medicion {
            $pendiente = $empresa->mediciones()->whereIn('estado', DatosEmpresas::PENDIENTES)->lockForUpdate()->first();

            if ($pendiente && ! $reemplazar) {
                throw ValidationException::withMessages([
                    'reemplazar' => "La empresa ya tiene la medición {$pendiente->numero} pendiente. Confirma para reemplazarla.",
                ]);
            }

            if ($pendiente) {
                $pendiente->forceFill(['estado' => 'cancelada', 'cancelada_en' => now()])->save();
            }

            $numero = $pendiente->numero
                ?? ((int) $empresa->mediciones()->where('estado', '!=', 'cancelada')->max('numero')) + 1;

            $medicion = Medicion::create([
                'empresa_id' => $empresa->id,
                'version_diagnostico_id' => $diagnostico->ultimaVersion->id,
                'numero' => $numero,
                'estado' => 'no_iniciada',
                'fecha_limite' => $fechaLimite,
                'mensaje' => $mensaje,
                'aviso_por_correo' => $aviso,
                'correo_asunto' => $correo['asunto'] ?? null,
                'correo_cuerpo' => $correo['cuerpo'] ?? null,
                'asignada_por' => $por?->id,
            ]);

            $pendiente?->forceFill(['reemplazada_por' => $medicion->id])->save();

            // Una solicitud de nueva medición abierta queda atendida (HU-083).
            DB::table('solicitudes_medicion')->where('empresa_id', $empresa->id)->where('estado', 'abierta')
                ->update(['estado' => 'atendida', 'atendida_en' => now(), 'updated_at' => now()]);

            return $medicion;
        });

        // Si el correo no sale, la medición queda asignada igual: sin
        // `ultimo_aviso_en`, y la pantalla ofrece "Reenviar aviso".
        if ($aviso) {
            try {
                $this->enviarAviso->enviar($medicion);
            } catch (ValidationException) {
            }
        }

        return $medicion;
    }
}
