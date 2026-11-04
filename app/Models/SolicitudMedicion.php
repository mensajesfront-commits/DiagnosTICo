<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * La empresa pide una medición nueva porque la suya venció (HU-082). El
 * Administrador la ve en A1, en la ficha (A3.1) y en Asignar medición
 * (A3.1b), y la atiende (HU-083):
 * - asignando otra medición,
 * - dándole una nueva fecha a la vencida, o
 * - cancelando la vencida.
 * En los tres casos la solicitud queda "atendida". Si no hace nada, la
 * medición vencida queda como está (CA-004).
 *
 * @property int $id
 * @property int $empresa_id
 * @property int|null $medicion_vencida_id
 * @property string $estado abierta · atendida
 * @property Carbon|null $created_at
 */
#[Table('solicitudes_medicion')]
#[Fillable(['empresa_id', 'medicion_vencida_id', 'estado', 'solicitada_por', 'atendida_en'])]
class SolicitudMedicion extends Model
{
    /**
     * Fecha (ISO) de la solicitud abierta de cada empresa: [empresa_id => fecha].
     *
     * @param  array<int, int>  $empresas
     * @return array<int, string>
     */
    public static function abiertasDe(array $empresas): array
    {
        if ($empresas === []) {
            return [];
        }

        $abiertas = [];

        foreach (self::where('estado', 'abierta')->whereIn('empresa_id', $empresas)->orderBy('id')->get() as $s) {
            $abiertas[$s->empresa_id] = (string) $s->created_at?->toIso8601String();
        }

        return $abiertas;
    }

    /** La solicitud abierta de la empresa queda atendida (HU-083 CA-003). */
    public static function atender(int $empresa): void
    {
        self::where('empresa_id', $empresa)->where('estado', 'abierta')
            ->update(['estado' => 'atendida', 'atendida_en' => now()]);
    }
}
