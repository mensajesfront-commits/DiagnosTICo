<?php

namespace App\Support;

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Sector;
use App\Models\SolicitudMedicion;
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
            // HU-083: empresas con una solicitud de medición nueva abierta.
            'solicitudes' => count(array_filter($mediciones, fn (array $m) => $m['solicitud'] !== null)),
            // Revisión antes de publicar: resultados que esperan aprobación.
            'porRevisar' => count(array_filter($mediciones, fn (array $m) => $m['por_revisar'])),
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

        // Medición pendiente de cada empresa, para "Tiene la medición 3 sin iniciar".
        $pendientePorEmpresa = $mediciones
            ->filter(fn (Medicion $m) => in_array($m->estado, ['no_iniciada', 'en_curso', 'enviada'], true))
            ->mapWithKeys(fn (Medicion $m) => [$m->empresa_id => ['numero' => $m->numero, 'estado' => $m->estado]]);
        $solicitudes = SolicitudMedicion::abiertasDe($mediciones->pluck('empresa_id')->unique()->values()->all());

        $respuestas = DB::table('respuestas')
            ->whereIn('medicion_id', $mediciones->modelKeys())
            ->get(['medicion_id', 'pregunta_ref', 'updated_at'])
            ->groupBy('medicion_id');

        Plazos::precargar($mediciones->pluck('empresa_id')->all());
        $lista = [];

        foreach ($mediciones as $m) {
            $estado = $m->estado;

            if (in_array($estado, ['no_iniciada', 'en_curso'], true) && Plazos::vencio($m)) {
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
                // "Nueva fecha": la más cercana que se acepta (Plazos).
                'fecha_minima' => Plazos::fechaMinima($m->empresa_id),
                'estado' => $estado,
                'avance' => $estado !== 'no_iniciada' && $estado !== 'terminada' && $m->version
                    ? self::avance($m->version->contenido, $respondidas)
                    : null,
                'ultimo_avance' => $ultimoAvance ? Carbon::parse((string) $ultimoAvance)->toIso8601String() : null,
                // Al asignar con aviso, "Recordatorio" o "Reenviar aviso" (HU-008).
                'ultimo_aviso' => $m->ultimo_aviso_en?->toIso8601String(),
                'resultado' => $m->resultado ? ['puntaje' => $m->resultado->puntaje_total, 'nivel' => $m->resultado->nivel] : null,
                'pendiente' => $estado === 'terminada' ? $pendientePorEmpresa->get($m->empresa_id) : null,
                // HU-083: en la vencida, la fecha (ISO) en que la empresa pidió una nueva.
                'solicitud' => $estado === 'vencida' ? ($solicitudes[$m->empresa_id] ?? null) : null,
                // La IA terminó y el resultado espera que un administrador lo apruebe.
                'por_revisar' => $estado === 'enviada' && $m->revision_desde !== null,
            ];
        }

        usort($lista, function (array $a, array $b): int {
            $orden = self::ORDEN[$a['estado']] <=> self::ORDEN[$b['estado']];

            if ($orden !== 0) {
                return $orden;
            }

            // Vencidas: primero las que tienen una solicitud abierta (HU-083).
            $solicitud = ($b['solicitud'] !== null) <=> ($a['solicitud'] !== null);

            if ($solicitud !== 0) {
                return $solicitud;
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
