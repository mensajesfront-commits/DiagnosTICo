<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Prompt;
use App\Models\Sector;
use App\Services\Ia\AnalizadorDeCategoria;
use App\Services\Ia\ArmadorPrompt;

/**
 * A4 · Configuración de la IA, etapa "Analizar categoría" (HU-041 a
 * HU-045), con la forma de resources/js/types/configuracionIa.ts.
 */
class DatosConfiguracionIa
{
    /**
     * @return array<string, mixed>
     */
    public static function pantalla(string $alcance, ?int $sectorId, ?int $empresaId): array
    {
        $ajustes = Prompt::where('etapa', ArmadorPrompt::ETAPA)->whereIn('alcance', ['sector', 'empresa'])->get();
        $sectoresConAjuste = $ajustes->where('alcance', 'sector')->pluck('sector_id')->all();
        $empresasConAjuste = $ajustes->where('alcance', 'empresa')->pluck('empresa_id')->all();

        $sectores = Sector::query()->orderBy('nombre')->withCount('empresas')->get(['id', 'nombre'])
            ->map(fn (Sector $s) => [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'empresas' => (int) $s->getAttribute('empresas_count'),
                'con_ajuste' => in_array($s->id, $sectoresConAjuste, true),
            ])->values()->all();

        $empresas = Empresa::query()->with('sector:id,nombre')->orderBy('nombre')->get(['id', 'nombre', 'sector_id'])
            ->map(fn (Empresa $e) => [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'sector_id' => $e->sector_id,
                'sector' => $e->sector->nombre ?? null,
                'con_ajuste' => in_array($e->id, $empresasConAjuste, true),
            ])->values()->all();

        $datos = [
            'alcance' => $alcance,
            'general' => ['textos' => ArmadorPrompt::textosGenerales(), 'originales' => ArmadorPrompt::GENERAL],
            'sector' => null,
            'empresa' => null,
            'base' => ArmadorPrompt::textosGenerales(),
            'origen_base' => array_fill_keys(ArmadorPrompt::ELEMENTOS, 'general'),
            'ultima_edicion' => self::ultimaEdicion('general', null),
            'sectores' => $sectores,
            'empresas' => $empresas,
            'resumen' => ['sectores_con_ajuste' => count($sectoresConAjuste), 'empresas_con_ajuste' => count($empresasConAjuste)],
            'variables' => collect(ArmadorPrompt::VARIABLES)->map(fn (array $v, string $codigo) => ['codigo' => $codigo, 'nombre' => $v[0], 'elemento' => $v[1]])->values()->all(),
            'formato' => ['texto' => ArmadorPrompt::FORMATO, 'esquema' => json_encode(AnalizadorDeCategoria::esquema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
            'ejemplos' => self::ejemplos(),
        ];

        $armador = app(ArmadorPrompt::class);

        if ($alcance === 'sector' && $sectorId !== null) {
            $sector = collect($sectores)->firstWhere('id', $sectorId);

            if ($sector !== null) {
                $propias = collect($empresas)->where('sector_id', $sectorId)->where('con_ajuste', true)->pluck('nombre')->values()->all();
                $datos['sector'] = [...$sector, 'empresas_con_ajuste' => $propias, 'ajuste' => ArmadorPrompt::ajuste('sector', $sectorId)];
                $datos['ultima_edicion'] = self::ultimaEdicion('sector', $sectorId);
            }
        }

        if ($alcance === 'empresa' && $empresaId !== null) {
            $empresa = collect($empresas)->firstWhere('id', $empresaId);

            if ($empresa !== null) {
                $datos['empresa'] = [
                    ...$empresa,
                    'sector_con_ajuste' => in_array($empresa['sector_id'], $sectoresConAjuste, true),
                    'ajuste' => ArmadorPrompt::ajuste('empresa', $empresaId),
                ];
                // Lo que la empresa hereda: general más el ajuste de su sector.
                $segmentos = $armador->segmentos($empresa['sector_id'], null);
                $datos['base'] = array_map(fn (array $s) => implode("\n", array_column($s, 'texto')), $segmentos);
                $datos['origen_base'] = array_map(fn (array $s) => in_array('sector', array_column($s, 'origen'), true) ? 'sector' : 'general', $segmentos);
                $datos['ultima_edicion'] = self::ultimaEdicion('empresa', $empresaId);
            }
        }

        return $datos;
    }

    /** @return array{fecha: string, por: string|null}|null */
    public static function ultimaEdicion(string $alcance, ?int $id): ?array
    {
        $consulta = Prompt::with('actualizadoPor:id,name')->where('etapa', ArmadorPrompt::ETAPA)->where('alcance', $alcance);

        if ($id !== null) {
            $consulta->where("{$alcance}_id", $id);
        }

        $fila = $consulta->first();

        return $fila?->updated_at === null ? null : [
            'fecha' => $fila->updated_at->toIso8601String(),
            'por' => $fila->actualizadoPor->name ?? null,
        ];
    }

    /**
     * Mediciones con respuestas para "Ver instrucciones completas" y "Probar
     * con un ejemplo": la más reciente de cada empresa.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function ejemplos(): array
    {
        return Medicion::query()
            ->whereIn('estado', ['en_curso', 'enviada', 'terminada', 'vencida'])
            ->whereHas('respuestas')
            ->with(['empresa:id,nombre', 'version:id,diagnostico_id,contenido', 'version.diagnostico:id,sector_id', 'version.diagnostico.sector:id,nombre'])
            ->orderByDesc('numero')
            ->get()
            ->unique('empresa_id')
            ->sortBy(fn (Medicion $m) => $m->empresa->nombre ?? '')
            ->take(200)
            ->map(function (Medicion $m) {
                /** @var list<array<string, mixed>> $categorias */
                $categorias = $m->version->contenido['categorias'] ?? [];

                return [
                    'medicion_id' => $m->id,
                    'numero' => $m->numero,
                    'empresa_id' => $m->empresa_id,
                    'empresa' => $m->empresa->nombre ?? '',
                    // El sector del diagnóstico: el mismo que usa ArmadorPrompt.
                    'sector_id' => $m->version->diagnostico->sector_id ?? null,
                    'sector' => $m->version->diagnostico->sector->nombre ?? '',
                    'categorias' => array_map(fn (array $c) => ['ref' => $c['ref'], 'nombre' => $c['nombre']], $categorias),
                ];
            })
            ->values()
            ->all();
    }
}
