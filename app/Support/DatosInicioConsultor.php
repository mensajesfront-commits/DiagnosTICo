<?php

namespace App\Support;

use App\Models\Medicion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * C1 · "Qué atender" del inicio del Consultor (HU-086 CA-005, RN-030): avisos
 * de sus empresas vinculadas, de lo más urgente a lo menos.
 *
 * 1. Medición vencida: pasó la fecha límite sin enviarse.
 * 2. Bajó: el último resultado quedó por debajo del anterior (RN-020).
 * 3. Sin empezar: medición asignada hace DIAS_SIN_EMPEZAR días o más.
 * 4. Resultado nuevo: publicado en los últimos DIAS_NUEVO días (si no bajó,
 *    porque ese aviso ya lo cubre).
 *
 * Todo llega calculado; la pantalla solo lo muestra.
 */
class DatosInicioConsultor
{
    public const int DIAS_SIN_EMPEZAR = 7;

    public const int DIAS_NUEVO = 7;

    private const array PRIORIDAD = ['vencida' => 0, 'bajo' => 1, 'sin_empezar' => 2, 'nuevo' => 3];

    /**
     * @return list<array{tipo: string, empresa: array{id: int, nombre: string}, medicion: int, numero: int, fecha: string|null, puntos: int|null, puntaje: int|null, nivel: string|null, dias: int|null}>
     */
    public static function avisos(User $consultor): array
    {
        $ids = $consultor->empresasAsesoradas()->pluck('empresas.id');

        /** @var Collection<int, Collection<int, Medicion>> $porEmpresa */
        $porEmpresa = Medicion::query()
            ->whereIn('empresa_id', $ids)
            ->where('estado', '!=', 'cancelada')
            ->with(['resultado', 'empresa:id,nombre'])
            ->orderBy('numero')
            ->get()
            ->groupBy('empresa_id');

        $avisos = [];

        foreach ($porEmpresa as $mediciones) {
            $empresa = $mediciones->first()?->empresa;

            if ($empresa === null) {
                continue;
            }

            $datosEmpresa = ['id' => $empresa->id, 'nombre' => $empresa->nombre];

            // La medición pendiente: vencida o sin empezar.
            $pendiente = $mediciones->first(fn (Medicion $m) => in_array($m->estado, ['no_iniciada', 'en_curso'], true));

            if ($pendiente !== null) {
                if (Plazos::vencio($pendiente)) {
                    $avisos[] = self::aviso('vencida', $datosEmpresa, $pendiente, fecha: $pendiente->fecha_limite?->toDateString());
                } elseif ($pendiente->estado === 'no_iniciada' && $pendiente->created_at !== null) {
                    $dias = (int) $pendiente->created_at->startOfDay()->diffInDays(now()->startOfDay());

                    if ($dias >= self::DIAS_SIN_EMPEZAR) {
                        $avisos[] = self::aviso('sin_empezar', $datosEmpresa, $pendiente, fecha: $pendiente->created_at->toDateString(), dias: $dias);
                    }
                }
            }

            // El último resultado: bajó o es nuevo.
            $ultima = $mediciones->filter(fn (Medicion $m) => $m->estado === 'terminada' && $m->resultado !== null)->last();
            $resultado = $ultima?->resultado;

            if ($ultima === null || $resultado === null) {
                continue;
            }

            $comun = [
                'fecha' => $resultado->publicado_en->toDateString(),
                'puntaje' => (int) $resultado->puntaje_total,
                'nivel' => (string) $resultado->nivel,
            ];

            if ($resultado->variacion !== null && $resultado->variacion < 0) {
                $avisos[] = self::aviso('bajo', $datosEmpresa, $ultima, ...$comun, puntos: abs((int) $resultado->variacion));
            } elseif ($resultado->publicado_en->greaterThanOrEqualTo(now()->subDays(self::DIAS_NUEVO))) {
                $avisos[] = self::aviso('nuevo', $datosEmpresa, $ultima, ...$comun);
            }
        }

        // Lo más urgente primero; dentro de cada tipo, lo que más pesa.
        usort($avisos, fn (array $a, array $b) => [self::PRIORIDAD[$a['tipo']], -($a['puntos'] ?? $a['dias'] ?? 0), $a['empresa']['nombre']]
            <=> [self::PRIORIDAD[$b['tipo']], -($b['puntos'] ?? $b['dias'] ?? 0), $b['empresa']['nombre']]);

        return $avisos;
    }

    /**
     * @param  array{id: int, nombre: string}  $empresa
     * @return array{tipo: string, empresa: array{id: int, nombre: string}, medicion: int, numero: int, fecha: string|null, puntos: int|null, puntaje: int|null, nivel: string|null, dias: int|null}
     */
    private static function aviso(string $tipo, array $empresa, Medicion $medicion, ?string $fecha = null, ?int $puntos = null, ?int $puntaje = null, ?string $nivel = null, ?int $dias = null): array
    {
        return [
            'tipo' => $tipo,
            'empresa' => $empresa,
            'medicion' => $medicion->id,
            'numero' => $medicion->numero,
            'fecha' => $fecha,
            'puntos' => $puntos,
            'puntaje' => $puntaje,
            'nivel' => $nivel,
            'dias' => $dias,
        ];
    }
}
