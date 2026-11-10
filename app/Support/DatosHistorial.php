<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * E8 · Mi historial (HU-067) y A3.4 · Historial de una empresa (HU-039): lo
 * mismo para la empresa y para el Administrador. Sale de la ficha de A3.1
 * (`DatosEmpresas::ficha`) para que los números coincidan.
 *
 * Lista las mediciones terminadas con resultado; la pendiente va aparte.
 * También las vencidas que se cancelaron (`vencidas`, pedido del 10 de
 * noviembre de 2026): prueban que la empresa recibió el diagnóstico y no lo
 * respondió a tiempo, por si dice que nunca le llegó.
 */
class DatosHistorial
{
    /** @return array<string, mixed> */
    public static function de(Empresa $empresa, bool $interno = false): array
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
            'vencidas' => self::vencidas($empresa, $interno),
            'resumen' => self::resumen($terminadas),
            'evolucion' => $ficha['evolucion'],
            'evolucion_categorias' => $ficha['evolucion_categorias'],
        ];
    }

    /**
     * Mediciones que vencieron sin enviarse y luego se cancelaron (o se
     * reemplazaron por otra), de la más reciente a la más antigua. Cuentan
     * como vencidas si lo estaban al cancelarlas (`estado_al_cancelar`) o, en
     * las canceladas antes de guardar ese dato, si la fecha límite ya había
     * pasado ese día en la hora de la empresa (como `Plazos::vencio`).
     *
     * Quién la asignó y quién la canceló solo va en la vista interna (A3.4).
     *
     * @return list<array<string, mixed>>
     */
    public static function vencidas(Empresa $empresa, bool $interno = false): array
    {
        $canceladas = $empresa->mediciones()
            ->where('estado', 'cancelada')
            ->whereNotNull('fecha_limite')
            ->with(['version:id,diagnostico_id,numero,contenido', 'version.diagnostico:id,nombre'])
            ->orderByDesc('cancelada_en')
            ->orderByDesc('id')
            ->get();

        if ($canceladas->isEmpty()) {
            return [];
        }

        $zona = Plazos::zona($empresa->id);
        $respondidas = DB::table('respuestas')
            ->whereIn('medicion_id', $canceladas->modelKeys())
            ->selectRaw('medicion_id, count(distinct pregunta_ref) as total')
            ->groupBy('medicion_id')
            ->pluck('total', 'medicion_id');
        $nombres = $interno
            ? User::withTrashed()
                ->whereIn('id', $canceladas->pluck('asignada_por')->merge($canceladas->pluck('cancelada_por'))->filter()->unique()->values())
                ->pluck('name', 'id')
            : collect();
        $lista = [];

        foreach ($canceladas as $m) {
            /** @var Medicion $m */
            $cancelada = $m->cancelada_en?->setTimezone($zona)->toDateString();
            $vencio = $m->estado_al_cancelar === 'vencida'
                || ($m->estado_al_cancelar !== 'enviada' && $cancelada !== null
                    && $m->fecha_limite !== null && $m->fecha_limite->toDateString() < $cancelada);

            if (! $vencio) {
                continue;
            }

            /** @var list<array<string, mixed>> $categorias */
            $categorias = $m->version?->contenido['categorias'] ?? [];
            $asignadaPor = $m->getAttribute('asignada_por');

            $lista[] = [
                'id' => $m->id,
                'numero' => $m->numero,
                'diagnostico' => $m->version?->diagnostico?->nombre,
                'version' => $m->version?->numero,
                'asignada' => $m->created_at?->setTimezone($zona)->toDateString(),
                'vence' => $m->fecha_limite?->toDateString(),
                'cancelada' => $cancelada,
                'respondidas' => (int) ($respondidas[$m->id] ?? 0),
                'preguntas' => array_sum(array_map(fn (array $c) => count($c['preguntas'] ?? []), $categorias)),
                // Fecha del último correo de aviso o recordatorio que salió (EnviarAviso).
                'ultimo_aviso' => $m->ultimo_aviso_en?->setTimezone($zona)->toDateString(),
                'asignada_por' => $interno && $asignadaPor ? ($nombres[$asignadaPor] ?? null) : null,
                'cancelada_por' => $interno && $m->cancelada_por ? ($nombres[$m->cancelada_por] ?? null) : null,
            ];
        }

        return $lista;
    }

    /**
     * A3.4 · "Por categoría": cada categoría en su primera y su última
     * medición y cuánto cambió, de la que más bajó a la que más subió (las
     * que solo tienen una medición, al final).
     *
     * @param  list<array{nombre: string, puntos: list<array{numero: int, fecha: string|null, puntaje: int}>}>  $series
     * @return list<array{nombre: string, primera: array{numero: int, puntaje: int}, ultima: array{numero: int, puntaje: int}, cambio: int|null, nivel: string}>
     */
    public static function cambiosPorCategoria(array $series): array
    {
        $filas = [];

        foreach ($series as $serie) {
            $puntos = $serie['puntos'];

            if ($puntos === []) {
                continue;
            }

            $primera = $puntos[0];
            $ultima = $puntos[count($puntos) - 1];

            $filas[] = [
                'nombre' => $serie['nombre'],
                'primera' => ['numero' => $primera['numero'], 'puntaje' => $primera['puntaje']],
                'ultima' => ['numero' => $ultima['numero'], 'puntaje' => $ultima['puntaje']],
                'cambio' => count($puntos) > 1 ? $ultima['puntaje'] - $primera['puntaje'] : null,
                'nivel' => Niveles::de($ultima['puntaje']),
            ];
        }

        usort($filas, fn (array $a, array $b) => [$a['cambio'] === null, $a['cambio'] ?? 0, $a['nombre']]
            <=> [$b['cambio'] === null, $b['cambio'] ?? 0, $b['nombre']]);

        return $filas;
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
