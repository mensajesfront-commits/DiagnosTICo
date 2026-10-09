<?php

namespace App\Support;

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Sector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Datos de A1 · Inicio del Administrador (HU-006, HU-007): los cuatro
 * indicadores, la tabla de mediciones y los paneles "Empresas por nivel" y
 * "Diagnósticos por sector". Todo llega calculado; la pantalla solo lo
 * muestra (docs/10_ARQUITECTURA.md).
 *
 * Estado que se muestra (RN-015):
 * - "vencida" si la medición quedó vencida o si pasó su fecha límite sin
 *   enviarse (antes de que corra la tarea diaria que la marca);
 * - "enviada" cuenta como "En curso" en las pestañas (HU-007).
 * Las canceladas o reemplazadas no aparecen.
 */
class DatosInicio
{
    /** Orden de la tabla: primero lo que necesita atención. */
    private const array ORDEN = ['vencida' => 0, 'en_curso' => 1, 'enviada' => 1, 'no_iniciada' => 2, 'terminada' => 3];

    /** @return array<string, mixed> */
    public static function administrador(): array
    {
        $mediciones = self::mediciones();

        return [
            'indicadores' => self::indicadores($mediciones),
            'mediciones' => $mediciones,
            'niveles' => self::empresasPorNivel(),
            'sectores' => self::diagnosticosPorSector(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $mediciones
     * @return array<string, mixed>
     */
    private static function indicadores(array $mediciones): array
    {
        $empresas = Empresa::query();
        $pendientes = ['no_iniciada' => 0, 'en_curso' => 0, 'vencida' => 0];

        foreach ($mediciones as $m) {
            $clave = $m['estado'] === 'enviada' ? 'en_curso' : $m['estado'];

            if (isset($pendientes[$clave])) {
                $pendientes[$clave]++;
            }
        }

        $promedio = DB::table('resultados as r')
            ->join('mediciones as m', 'm.id', '=', 'r.medicion_id')
            ->join('empresas as e', 'e.id', '=', 'm.empresa_id')
            ->whereNull('e.deleted_at')
            ->whereRaw('m.numero = (select max(m2.numero) from mediciones m2 join resultados r2 on r2.medicion_id = m2.id where m2.empresa_id = m.empresa_id)')
            ->avg('r.puntaje_total');

        return [
            'empresas' => (clone $empresas)->count(),
            'sectores_con_empresas' => (clone $empresas)->distinct()->count('sector_id'),
            'pendientes' => array_sum($pendientes),
            'pendientes_por_estado' => $pendientes,
            'completados_mes' => Medicion::where('estado', 'terminada')
                ->whereBetween('terminada_en', [now()->startOfMonth(), now()->endOfMonth()])
                ->whereHas('empresa')
                ->count(),
            'puntaje_promedio' => $promedio !== null ? (int) round((float) $promedio) : null,
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function mediciones(): array
    {
        $mediciones = Medicion::query()
            ->whereNotIn('estado', ['cancelada'])
            ->whereHas('empresa')
            ->with(['empresa:id,nombre,sector_id', 'empresa.sector:id,nombre', 'version:id,contenido', 'resultado:id,medicion_id,puntaje_total,nivel'])
            ->get();

        // Medición pendiente de cada empresa, para "Tiene la medición 3 en curso".
        $pendientePorEmpresa = $mediciones
            ->filter(fn (Medicion $m) => in_array($m->estado, ['no_iniciada', 'en_curso', 'enviada'], true))
            ->mapWithKeys(fn (Medicion $m) => [$m->empresa_id => $m->numero]);

        $respuestas = DB::table('respuestas')
            ->whereIn('medicion_id', $mediciones->modelKeys())
            ->get(['medicion_id', 'pregunta_ref', 'updated_at'])
            ->groupBy('medicion_id');

        $hoy = Carbon::today();
        $lista = [];

        foreach ($mediciones as $m) {
            $estado = $m->estado;

            if (in_array($estado, ['no_iniciada', 'en_curso'], true) && $m->fecha_limite !== null && $m->fecha_limite->lt($hoy)) {
                $estado = 'vencida';
            }

            $deEsta = $respuestas->get($m->id, collect());
            $respondidas = $deEsta->pluck('pregunta_ref')->map(fn ($r) => (string) $r)->values()->all();
            $ultimoAvance = $deEsta->max('updated_at');

            $lista[] = [
                'id' => $m->id,
                'numero' => $m->numero,
                'empresa_id' => $m->empresa_id,
                'empresa' => $m->empresa?->nombre,
                'sector' => $m->empresa?->sector?->nombre,
                'asignada' => $m->created_at?->toDateString(),
                'vence' => $m->fecha_limite?->toDateString(),
                'estado' => $estado,
                'avance' => $estado !== 'no_iniciada' && $estado !== 'terminada' && $m->version
                    ? self::avance($m->version->contenido, $respondidas)
                    : null,
                'ultimo_avance' => $ultimoAvance ? Carbon::parse((string) $ultimoAvance)->toIso8601String() : null,
                // [FUNCIONALIDAD POR DEFINIR] Aún no se registran los avisos
                // reenviados (PA-004): se muestra el aviso de la asignación.
                'ultimo_aviso' => $m->aviso_por_correo ? $m->created_at?->toIso8601String() : null,
                'resultado' => $m->resultado ? ['puntaje' => $m->resultado->puntaje_total, 'nivel' => $m->resultado->nivel] : null,
                'pendiente_numero' => $estado === 'terminada' ? $pendientePorEmpresa->get($m->empresa_id) : null,
            ];
        }

        usort($lista, function (array $a, array $b): int {
            $orden = self::ORDEN[$a['estado']] <=> self::ORDEN[$b['estado']];

            if ($orden !== 0) {
                return $orden;
            }

            // Pendientes: la más antigua primero. Terminadas: la más reciente primero.
            return $a['estado'] === 'terminada'
                ? strcmp((string) $b['asignada'], (string) $a['asignada'])
                : strcmp((string) $a['asignada'], (string) $b['asignada']);
        });

        return $lista;
    }

    /**
     * Categorías completas (todas sus preguntas obligatorias respondidas) de
     * la versión que se responde, para "En curso · 6/10".
     *
     * @param  array<string, mixed>  $contenido
     * @param  array<int, string>  $respondidas
     * @return array{hechas: int, total: int}
     */
    public static function avance(array $contenido, array $respondidas): array
    {
        /** @var list<array{preguntas?: list<array{ref: string, obligatoria?: bool}>}> $categorias */
        $categorias = $contenido['categorias'] ?? [];
        $hechas = 0;

        foreach ($categorias as $categoria) {
            $refs = array_map(
                fn (array $p) => $p['ref'],
                array_filter($categoria['preguntas'] ?? [], fn (array $p) => $p['obligatoria'] ?? true),
            );

            if ($refs !== [] && array_diff($refs, $respondidas) === []) {
                $hechas++;
            }
        }

        return ['hechas' => $hechas, 'total' => count($categorias)];
    }

    /**
     * Nivel del último resultado de cada empresa; "sin" si no tiene.
     *
     * @return array<string, int>
     */
    private static function empresasPorNivel(): array
    {
        $niveles = DB::table('empresas as e')
            ->whereNull('e.deleted_at')
            ->leftJoin('mediciones as m', function ($join) {
                $join->on('m.empresa_id', '=', 'e.id')
                    ->whereRaw('m.numero = (select max(m2.numero) from mediciones m2 join resultados r2 on r2.medicion_id = m2.id where m2.empresa_id = e.id)');
            })
            ->leftJoin('resultados as r', 'r.medicion_id', '=', 'm.id')
            ->pluck('r.nivel');

        $cuenta = array_fill_keys([...array_keys(Niveles::NIVELES), 'sin'], 0);

        foreach ($niveles as $nivel) {
            $cuenta[$nivel !== null && isset($cuenta[$nivel]) ? (string) $nivel : 'sin']++;
        }

        return $cuenta;
    }

    /**
     * Cada sector activo con sus empresas y su versión publicada.
     *
     * @return list<array<string, mixed>>
     */
    private static function diagnosticosPorSector(): array
    {
        $lista = [];

        $sectores = Sector::where('activo', true)
            ->withCount('empresas')
            ->with(['diagnosticos' => fn ($q) => $q->where('estado', Diagnostico::PUBLICADO)->withMax('versiones', 'numero')])
            ->orderBy('nombre')
            ->get();

        foreach ($sectores as $sector) {
            $publicados = $sector->diagnosticos;

            $lista[] = [
                'id' => $sector->id,
                'nombre' => $sector->nombre,
                'empresas' => (int) $sector->empresas_count,
                'publicados' => $publicados->count(),
                // Con un solo diagnóstico publicado, su versión ("v2").
                'version' => $publicados->count() === 1 ? (int) $publicados->first()?->getAttribute('versiones_max_numero') : null,
            ];
        }

        return $lista;
    }
}
