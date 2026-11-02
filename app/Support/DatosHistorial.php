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
        $terminadas = array_values(array_filter($mediciones, fn (array $m) => $m['puntaje'] !== null));

        return [
            'empresa' => [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'sector' => $ficha['empresa']['sector'],
            ],
            'pendiente' => $pendiente === null ? null : array_intersect_key($pendiente, array_flip([
                'id', 'numero', 'estado', 'avance', 'diagnostico', 'version', 'asignada', 'vence', 'fecha_minima', 'tiene_respuestas', 'solicitud',
            ])),
            'mediciones' => $terminadas,
            'resumen' => self::resumen($terminadas),
            'evolucion' => $ficha['evolucion'],
            'evolucion_categorias' => $ficha['evolucion_categorias'],
        ];
    }

    /**
     * Las cifras de arriba de E8: la última medición, el cambio desde la
     * primera y la mejor. Null si aún no hay mediciones terminadas.
     *
     * @param  list<array<string, mixed>>  $terminadas  de la más reciente a la primera
     * @return array{cantidad: int, ultima: array<string, mixed>, primera: array<string, mixed>, cambio: int|null, mejor: array<string, mixed>}|null
     */
    private static function resumen(array $terminadas): ?array
    {
        if ($terminadas === []) {
            return null;
        }

        $ultima = $terminadas[0];
        $primera = $terminadas[count($terminadas) - 1];
        $mejor = collect($terminadas)->sortByDesc('puntaje')->first();

        return [
            'cantidad' => count($terminadas),
            'ultima' => $ultima,
            'primera' => $primera,
            'cambio' => count($terminadas) > 1 ? (int) $ultima['puntaje'] - (int) $primera['puntaje'] : null,
            'mejor' => $mejor,
        ];
    }
}
