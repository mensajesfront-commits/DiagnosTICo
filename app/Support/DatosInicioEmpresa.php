<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\SolicitudMedicion;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * E1 · Inicio de la empresa y del colaborador (HU-057 a HU-059, HU-082),
 * con la forma de resources/js/types/inicioEmpresa.ts. Puntajes, niveles,
 * variación y avance llegan calculados.
 */
class DatosInicioEmpresa
{
    /** @return array<string, mixed> */
    public static function datos(User $usuario): array
    {
        /** @var Empresa $empresa */
        $empresa = Empresa::with('sector:id,nombre')->findOrFail($usuario->empresa_id);

        $mediciones = Medicion::query()
            ->where('empresa_id', $empresa->id)
            ->where('estado', '!=', 'cancelada')
            ->with(['resultado', 'version:id,diagnostico_id,numero,contenido', 'version.diagnostico:id,nombre'])
            ->orderBy('numero')
            ->get();

        $terminadas = $mediciones->filter(fn (Medicion $m) => $m->estado === 'terminada' && $m->resultado !== null)->values();
        $pendiente = $mediciones->first(fn (Medicion $m) => in_array($m->estado, DatosEmpresas::PENDIENTES, true));

        // Variación de cada terminada frente a la anterior (RN-020).
        $filas = [];
        $anterior = null;

        foreach ($terminadas as $m) {
            $puntaje = (int) $m->resultado?->puntaje_total;
            $filas[] = [
                'id' => $m->id,
                'numero' => $m->numero,
                'fecha' => ($m->terminada_en ?? $m->created_at)?->toDateString(),
                'puntaje' => $puntaje,
                'nivel' => $m->resultado?->nivel,
                'variacion' => $anterior === null ? null : $puntaje - $anterior,
            ];
            $anterior = $puntaje;
        }

        $ultima = $filas === [] ? null : $filas[count($filas) - 1];

        return [
            'nombre' => $usuario->name,
            'empresa' => ['nombre' => $empresa->nombre, 'sector' => $empresa->sector->nombre ?? ''],
            'realizadas' => count($filas),
            'pendiente' => $pendiente ? self::pendiente($pendiente, $filas === []) : null,
            'ultima' => $ultima,
            'mediciones' => array_reverse($filas),
            'areas' => $ultima ? self::areas($terminadas->last()) : [],
            'soporte' => config('diagnostico.soporte_email'),
        ];
    }

    /** @return array<string, mixed> */
    private static function pendiente(Medicion $m, bool $primera): array
    {
        $estado = DatosEmpresas::estadoVisible($m);
        $avance = DatosResponder::avanceDe($m);
        $limite = $m->fecha_limite;

        return [
            'id' => $m->id,
            'numero' => $m->numero,
            'estado' => $estado,
            'primera' => $primera,
            'diagnostico' => $m->version->diagnostico->nombre ?? '',
            'categorias' => count($avance['categorias']),
            'preguntas' => $avance['total'],
            'mensaje' => $m->mensaje,
            'fecha_limite' => $limite?->toDateString(),
            'dias_restantes' => $limite ? (int) Carbon::today()->diffInDays($limite, false) : null,
            'avance' => [
                'respondidas' => $avance['respondidas'],
                'total' => $avance['total'],
                'porcentaje' => $avance['porcentaje'],
                'categoria' => $avance['categorias'][$avance['actual']]['nombre'] ?? null,
                'categoria_numero' => $avance['actual'] + 1,
            ],
            // HU-082: ya pidió una medición nueva y espera respuesta.
            'solicitud_abierta' => SolicitudMedicion::where('empresa_id', $m->empresa_id)->where('estado', 'abierta')->exists(),
        ];
    }

    /**
     * "Tus 3 áreas a trabajar": las 3 categorías con menor puntaje del último
     * resultado, con su primera recomendación si la hay.
     *
     * @return list<array<string, mixed>>
     */
    private static function areas(?Medicion $m): array
    {
        /** @var list<array<string, mixed>> $categorias */
        $categorias = $m->resultado->por_categoria ?? [];
        usort($categorias, fn (array $a, array $b) => ($a['puntaje'] ?? 0) <=> ($b['puntaje'] ?? 0));

        return array_map(fn (array $c) => [
            'nombre' => $c['nombre'] ?? '',
            'puntaje' => (int) ($c['puntaje'] ?? 0),
            'nivel' => $c['nivel'] ?? Niveles::de((int) ($c['puntaje'] ?? 0)),
            'recomendacion' => $c['recomendacion'] ?? null,
        ], array_slice($categorias, 0, 3));
    }
}
