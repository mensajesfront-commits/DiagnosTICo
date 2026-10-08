<?php

namespace App\Support;

use App\Models\Categoria;
use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Sector;
use App\Models\VersionDiagnostico;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Arma los datos de Diagnósticos (A2, A2·T, A2b), del catálogo de
 * categorías (A2.3) y de Crear diagnóstico (A2.5) con la forma que esperan
 * las pantallas (resources/js/types/diagnosticos.ts, docs/14_FRONTEND.md).
 */
class DatosDiagnosticos
{
    /** Empresas que se muestran en A2 (el total va en el resumen). */
    public const int EMPRESAS_EN_LISTA = 8;

    /** @return list<array<string, mixed>> */
    public static function sectores(bool $soloActivos = false): array
    {
        $sectores = Sector::query()
            ->when($soloActivos, fn (Builder $q) => $q->where('activo', true))
            ->withCount(['diagnosticos' => fn (Builder $q) => $q->where('estado', '!=', Diagnostico::ARCHIVADO)])
            ->orderBy('nombre')
            ->get();

        $lista = [];

        foreach ($sectores as $sector) {
            $lista[] = self::sector($sector);
        }

        return $lista;
    }

    /** @return array<string, mixed> */
    public static function sector(Sector $sector): array
    {
        return [
            'id' => $sector->id,
            'nombre' => $sector->nombre,
            'descripcion' => $sector->descripcion,
            'activo' => $sector->activo,
            'diagnosticos' => (int) ($sector->diagnosticos_count ?? $sector->diagnosticos()->where('estado', '!=', Diagnostico::ARCHIVADO)->count()),
        ];
    }

    /** @return array<string, mixed> */
    public static function resumen(?Sector $sector): array
    {
        $empresas = Empresa::query()->when($sector, fn (Builder $q) => $q->where('sector_id', $sector?->id));
        $mediciones = Medicion::query()
            ->where('estado', '!=', 'cancelada')
            ->when($sector, fn (Builder $q) => $q->whereHas('empresa', fn (Builder $e) => $e->where('sector_id', $sector?->id)));
        $diagnosticos = Diagnostico::query()->when($sector, fn (Builder $q) => $q->where('sector_id', $sector?->id));

        $resumen = [
            'empresas' => (clone $empresas)->count(),
            'mediciones' => (clone $mediciones)->count(),
            'publicados' => (clone $diagnosticos)->where('estado', Diagnostico::PUBLICADO)->count(),
            'borradores' => (clone $diagnosticos)->where(fn (Builder $q) => $q
                ->where('estado', Diagnostico::BORRADOR)
                ->orWhere(fn (Builder $p) => $p->where('estado', Diagnostico::PUBLICADO)->whereNotNull('version_borrador')))
                ->count(),
            'ultima_medicion' => self::porEstado(self::ultimasMediciones($sector)),
        ];

        if ($sector === null) {
            $resumen['sectores_activos'] = Sector::where('activo', true)->count();
        } else {
            $resumen['mediciones_por_estado'] = self::porEstado((clone $mediciones)->pluck('estado')->all());
        }

        return $resumen;
    }

    /** @return list<array<string, mixed>> */
    public static function diagnosticos(?Sector $sector): array
    {
        $diagnosticos = Diagnostico::query()
            ->activos()
            ->when($sector, fn (Builder $q) => $q->where('sector_id', $sector?->id))
            ->with('sector:id,nombre')
            ->withCount(['partes', 'preguntas', 'mediciones'])
            ->withMax('versiones', 'numero')
            ->orderByDesc('updated_at')
            ->get();

        $lista = [];

        foreach ($diagnosticos as $d) {
            $maximo = $d->getAttribute('versiones_max_numero');
            $publicada = $maximo !== null ? (int) $maximo : null;

            $lista[] = [
                'id' => $d->id,
                'nombre' => $d->nombre,
                'sector_id' => $d->sector_id,
                'sector_nombre' => $d->sector?->nombre,
                'categorias' => (int) $d->partes_count,
                'preguntas' => (int) $d->preguntas_count,
                'empresas' => $d->mediciones()->distinct()->count('mediciones.empresa_id'),
                'mediciones' => (int) $d->mediciones_count,
                'estado' => $publicada !== null ? 'publicado' : 'borrador',
                'version_publicada' => $publicada,
                'borrador_pendiente' => $publicada !== null ? $d->version_borrador : null,
                'actualizado_en' => $d->updated_at?->toIso8601String(),
            ];
        }

        return $lista;
    }

    /**
     * Empresas de un sector con su diagnóstico asignado, su última medición
     * y su puntaje. Ya no se muestra en A2 (se quitó para ganar espacio);
     * queda para la lista de Empresas filtrada por sector (A3, semanas 4–5).
     *
     * @return list<array<string, mixed>>
     */
    public static function empresas(Sector $sector): array
    {
        $empresas = Empresa::where('sector_id', $sector->id)
            ->orderBy('nombre')
            ->limit(self::EMPRESAS_EN_LISTA)
            ->get();

        $lista = [];

        foreach ($empresas as $empresa) {
            /** @var Medicion|null $ultima */
            $ultima = $empresa->mediciones()
                ->where('estado', '!=', 'cancelada')
                ->with(['version.diagnostico:id,nombre', 'resultado'])
                ->withCount('respuestas')
                ->orderByDesc('numero')
                ->first();
            $version = $ultima?->version;

            $lista[] = [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'diagnostico' => $version ? ($version->diagnostico?->nombre.' v'.$version->numero) : null,
                'medicion' => $ultima ? [
                    'numero' => $ultima->numero,
                    'estado' => $ultima->estado,
                    'avance' => $ultima->estado === 'en_curso' && $version
                        ? ['hechas' => (int) $ultima->respuestas_count, 'total' => self::preguntasDe($version)]
                        : null,
                ] : null,
                'puntaje' => $empresa->mediciones()
                    ->join('resultados', 'resultados.medicion_id', '=', 'mediciones.id')
                    ->orderByDesc('mediciones.numero')
                    ->value('resultados.puntaje_total'),
            ];
        }

        return $lista;
    }

    /**
     * Catálogo de categorías (A2.3).
     *
     * @return list<array<string, mixed>>
     */
    public static function categorias(): array
    {
        $categorias = Categoria::query()
            ->with(['usos.diagnostico.sector:id,nombre', 'usos.diagnostico.versiones:id,diagnostico_id,numero'])
            ->withCount('usos')
            ->orderByRaw('archivado_en is not null')
            ->orderBy('nombre')
            ->get();

        $lista = [];

        foreach ($categorias as $categoria) {
            $activos = 0;
            $preguntas = 0;
            $borradores = [];

            foreach ($categoria->usos as $uso) {
                $diagnostico = $uso->diagnostico;

                if ($diagnostico === null || $diagnostico->estado === Diagnostico::ARCHIVADO) {
                    continue;
                }

                $activos++;
                $preguntas += $uso->preguntas()->count();

                if (self::esBorrador($diagnostico)) {
                    $borradores[] = self::nombreBorrador($diagnostico);
                }
            }

            $versiones = VersionDiagnostico::query()
                ->whereJsonContains('contenido->categorias', ['categoria_id' => $categoria->id]);

            $lista[] = [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'descripcion' => $categoria->descripcion,
                'diagnosticos' => $activos,
                'preguntas' => $preguntas,
                'versiones_publicadas' => (clone $versiones)->count(),
                'borradores' => $borradores,
                // Alguna empresa ya respondió una versión que la incluye:
                // se archiva en vez de borrarse (RN-009).
                'tiene_respuestas' => Medicion::query()
                    ->whereIn('version_diagnostico_id', (clone $versiones)->select('id'))
                    ->whereIn('estado', ['en_curso', 'enviada', 'terminada', 'vencida'])
                    ->exists(),
                'archivada' => $categoria->archivada(),
            ];
        }

        return $lista;
    }

    /**
     * Diagnósticos con un borrador en curso (para A2.3b).
     *
     * @return list<array<string, mixed>>
     */
    public static function borradores(): array
    {
        $lista = [];

        foreach (Diagnostico::with('sector:id,nombre')->activos()->orderBy('nombre')->get() as $d) {
            if (self::esBorrador($d)) {
                $lista[] = [
                    'id' => $d->id,
                    'nombre' => $d->nombre,
                    'sector_nombre' => $d->sector?->nombre,
                    'version' => (int) $d->version_borrador,
                ];
            }
        }

        return $lista;
    }

    /**
     * Última versión publicada de cada diagnóstico activo (A2.5).
     *
     * @return list<array<string, mixed>>
     */
    public static function publicados(): array
    {
        $lista = [];
        $diagnosticos = Diagnostico::with(['sector:id,nombre', 'ultimaVersion'])
            ->where('estado', Diagnostico::PUBLICADO)
            ->orderBy('nombre')
            ->get();

        foreach ($diagnosticos as $d) {
            $version = $d->ultimaVersion;

            if ($version === null) {
                continue;
            }

            /** @var list<array{nombre?: string}> $categorias */
            $categorias = $version->contenido['categorias'] ?? [];

            $lista[] = [
                'id' => $d->id,
                'nombre' => $d->nombre,
                'sector_id' => $d->sector_id,
                'sector_nombre' => $d->sector?->nombre,
                'version' => $version->numero,
                'preguntas' => self::preguntasDe($version),
                'categorias' => array_values(array_filter(array_map(fn (array $c) => $c['nombre'] ?? null, $categorias))),
            ];
        }

        return $lista;
    }

    /** Borrador nunca publicado, o cambios pendientes sobre una versión publicada. */
    public static function esBorrador(Diagnostico $d): bool
    {
        return $d->estado === Diagnostico::BORRADOR
            || ($d->estado === Diagnostico::PUBLICADO && $d->version_borrador !== null);
    }

    /** "Diagnóstico general · Abogados (borrador v3)". */
    public static function nombreBorrador(Diagnostico $d): string
    {
        $nombre = $d->nombre.' · '.$d->sector?->nombre;

        return ($d->version_borrador ?? 1) > 1 ? $nombre.' (borrador v'.$d->version_borrador.')' : $nombre;
    }

    public static function preguntasDe(VersionDiagnostico $version): int
    {
        /** @var list<array{preguntas?: list<mixed>}> $categorias */
        $categorias = $version->contenido['categorias'] ?? [];

        return array_sum(array_map(fn (array $c) => count($c['preguntas'] ?? []), $categorias));
    }

    /**
     * Cuenta por estado (en_curso, no_iniciada, vencida, terminada). "Enviada"
     * cuenta como en curso hasta que se publique el resultado.
     *
     * @param  array<mixed>  $estados
     * @return array{en_curso: int, no_iniciada: int, vencida: int, terminada: int}
     */
    private static function porEstado(array $estados): array
    {
        $cuenta = ['en_curso' => 0, 'no_iniciada' => 0, 'vencida' => 0, 'terminada' => 0];

        foreach ($estados as $estado) {
            $clave = $estado === 'enviada' ? 'en_curso' : (string) $estado;

            if (isset($cuenta[$clave])) {
                $cuenta[$clave]++;
            }
        }

        return $cuenta;
    }

    /**
     * Estado de la última medición (no cancelada) de cada empresa.
     *
     * @return list<string>
     */
    private static function ultimasMediciones(?Sector $sector): array
    {
        $ultimas = DB::table('mediciones as m')
            ->join('empresas as e', 'e.id', '=', 'm.empresa_id')
            ->whereNull('e.deleted_at')
            ->where('m.estado', '!=', 'cancelada')
            ->when($sector, fn ($q) => $q->where('e.sector_id', $sector?->id))
            ->whereRaw('m.numero = (select max(m2.numero) from mediciones m2 where m2.empresa_id = m.empresa_id and m2.estado <> ?)', ['cancelada'])
            ->pluck('m.estado');

        /** @var list<string> */
        return $ultimas->map(fn ($e) => (string) $e)->values()->all();
    }
}
