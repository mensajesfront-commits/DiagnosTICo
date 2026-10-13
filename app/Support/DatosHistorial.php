<?php

namespace App\Support;

use App\Models\Empresa;

/**
 * E8 · Mi historial (HU-067) y A3.4 · Historial de una empresa (HU-039): lo
 * mismo para la empresa y para el Administrador. Sale de la ficha de A3.1
 * (`DatosEmpresas::ficha`) para que los números coincidan.
 *
 * Solo lista las mediciones terminadas con resultado; la pendiente va aparte.
 */
class DatosHistorial
{
    /** @return array<string, mixed> */
    public static function de(Empresa $empresa): array
    {
        $ficha = DatosEmpresas::ficha($empresa);
        /** @var array<string, mixed>|null $pendiente */
        $pendiente = $ficha['pendiente'];
        /** @var list<array<string, mixed>> $mediciones */
        $mediciones = $ficha['mediciones'];

        return [
            'empresa' => [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'sector' => $ficha['empresa']['sector'],
            ],
            'pendiente' => $pendiente === null ? null : array_intersect_key($pendiente, array_flip([
                'id', 'numero', 'estado', 'avance', 'diagnostico', 'version', 'asignada', 'vence', 'tiene_respuestas',
            ])),
            'mediciones' => array_values(array_filter($mediciones, fn (array $m) => $m['puntaje'] !== null)),
            'evolucion' => $ficha['evolucion'],
        ];
    }
}
