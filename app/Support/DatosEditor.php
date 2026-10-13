<?php

namespace App\Support;

use App\Models\Categoria;
use App\Models\Diagnostico;
use App\Models\DiagnosticoCategoria;
use App\Models\Medicion;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\VersionDiagnostico;
use App\Services\Diagnosticos\ContenidoDiagnostico;

/**
 * Datos del editor de diagnóstico (A2.1), de Editar importancia (A2.1c) y de
 * la vista previa (A2.4), con la forma de resources/js/types/editor.ts. El
 * estado (qué falta para publicar) lo calcula `EstadoDiagnostico`.
 */
class DatosEditor
{
    /** @return array<string, mixed> */
    public static function editor(Diagnostico $diagnostico): array
    {
        $diagnostico->load(['sector:id,nombre', 'partes.categoria:id,nombre', 'partes.preguntas.opciones', 'versiones']);
        $contenido = app(ContenidoDiagnostico::class)->congelar($diagnostico);
        $estado = EstadoDiagnostico::calcular($contenido);
        $enDiagnostico = $diagnostico->partes->pluck('categoria_id')->all();

        return [
            'diagnostico' => self::cabecera($diagnostico, $estado),
            'partes' => $diagnostico->partes->map(fn (DiagnosticoCategoria $parte) => [
                'id' => $parte->id,
                'categoria_id' => $parte->categoria_id,
                'nombre' => $parte->categoria->nombre ?? '',
                'importancia' => (float) $parte->importancia,
                'problemas' => $estado['categorias']['c'.$parte->categoria_id] ?? [],
                'preguntas' => $parte->preguntas->map(fn (Pregunta $p) => [
                    'id' => $p->id,
                    'texto' => $p->texto,
                    'tipo' => $p->tipo,
                    'indicacion' => $p->indicacion,
                    'criterio_ia' => $p->criterio_ia,
                    'obligatoria' => $p->obligatoria,
                    'problemas' => $estado['preguntas']['p'.$p->id] ?? [],
                    'opciones' => $p->opciones->map(fn (Opcion $o) => [
                        'id' => $o->id,
                        'texto' => $o->texto,
                        'puntaje' => $o->puntaje,
                        'ten_en_cuenta' => $o->ten_en_cuenta,
                        'exclusiva' => $o->exclusiva,
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
            'estado' => self::estado($estado),
            'catalogo' => Categoria::query()->orderBy('nombre')->get(['id', 'nombre', 'archivado_en'])
                ->map(fn (Categoria $c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'archivada' => $c->archivado_en !== null,
                    'en_diagnostico' => in_array($c->id, $enDiagnostico, true),
                ])->values()->all(),
            'versiones' => self::versiones($diagnostico),
            'pendientes' => self::pendientesConUltima($diagnostico),
        ];
    }

    /**
     * Encabezado: nombre, sector, versión del borrador y estado.
     *
     * @param  array<string, mixed>  $estado
     * @return array<string, mixed>
     */
    public static function cabecera(Diagnostico $diagnostico, array $estado): array
    {
        $ultima = $diagnostico->versiones->max('numero');

        return [
            'id' => $diagnostico->id,
            'nombre' => $diagnostico->nombre,
            'sector' => ['id' => $diagnostico->sector_id, 'nombre' => $diagnostico->sector->nombre ?? ''],
            'estado' => $diagnostico->estado,
            // El borrador que se edita: el pendiente o el siguiente al publicado.
            'version' => $diagnostico->version_borrador ?? ((int) $ultima + 1),
            'hay_borrador' => $diagnostico->version_borrador !== null,
            'ultima_publicada' => $ultima !== null ? (int) $ultima : null,
            'publicable' => $estado['publicable'],
            'guardado_en' => $diagnostico->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $estado
     * @return array<string, mixed>
     */
    public static function estado(array $estado): array
    {
        return [
            'publicable' => $estado['publicable'],
            'requisitos' => $estado['requisitos'],
            'completas' => $estado['completas'],
            'total' => $estado['total'],
            'por_corregir' => count($estado['categorias']),
            'importancia' => $estado['importancia'],
        ];
    }

    /**
     * Historial (A2.1d): el borrador, la publicada vigente y las reemplazadas,
     * con cuántas empresas la usan.
     *
     * @return list<array<string, mixed>>
     */
    public static function versiones(Diagnostico $diagnostico): array
    {
        $lista = [];
        $ultima = $diagnostico->versiones->max('numero');

        if ($diagnostico->version_borrador !== null && $diagnostico->estado !== Diagnostico::ARCHIVADO) {
            $lista[] = ['numero' => $diagnostico->version_borrador, 'estado' => 'borrador', 'publicada_en' => null, 'nota' => null, 'empresas' => 0, 'mediciones' => 0];
        }

        foreach ($diagnostico->versiones->sortByDesc('numero') as $v) {
            /** @var VersionDiagnostico $v */
            $usos = Medicion::query()->where('version_diagnostico_id', $v->id)->where('estado', '!=', 'cancelada');

            $lista[] = [
                'numero' => $v->numero,
                'estado' => $v->numero === $ultima ? 'publicada' : 'reemplazada',
                'publicada_en' => $v->publicada_en->toIso8601String(),
                'nota' => $v->nota_cambios,
                'empresas' => (clone $usos)->distinct()->count('empresa_id'),
                'mediciones' => (clone $usos)->where('estado', 'terminada')->count(),
            ];
        }

        return $lista;
    }

    /**
     * Empresas con una medición pendiente sin empezar en versiones
     * anteriores: las que se pueden pasar a la nueva al publicar (HU-029 CA-004).
     */
    public static function pendientesConUltima(Diagnostico $diagnostico): int
    {
        return Medicion::query()
            ->whereIn('version_diagnostico_id', $diagnostico->versiones->modelKeys())
            ->whereIn('estado', DatosEmpresas::PENDIENTES)
            ->distinct()
            ->count('empresa_id');
    }
}
