<?php

namespace App\Support;

use App\Models\ActividadEconomica;
use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de Empresas (A3), la ficha (A3.1, A3.1d) y Registrar empresa (A3.2)
 * con la forma que esperan las pantallas (resources/js/types/empresas.ts).
 * Puntajes, niveles, variación y avance llegan calculados.
 */
class DatosEmpresas
{
    /** Estados de una medición pendiente (la que la empresa debe responder). */
    public const array PENDIENTES = ['no_iniciada', 'en_curso', 'enviada', 'vencida'];

    /**
     * A3 · Todas las empresas (no eliminadas), por nombre.
     *
     * @return list<array<string, mixed>>
     */
    public static function lista(): array
    {
        $empresas = Empresa::query()
            ->with(['sector:id,nombre', 'mediciones' => fn ($q) => $q->where('estado', '!=', 'cancelada')->with(['resultado', 'version:id,contenido'])])
            ->orderBy('nombre')
            ->get();

        $respuestas = self::respuestasDe($empresas->flatMap(fn (Empresa $e) => $e->mediciones->modelKeys())->all());
        $lista = [];

        foreach ($empresas as $empresa) {
            [$ultimo, $variacion] = self::ultimoResultado($empresa->mediciones);
            $pendiente = $empresa->mediciones->first(fn (Medicion $m) => in_array($m->estado, self::PENDIENTES, true));

            $lista[] = [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'sector_id' => $empresa->sector_id,
                'sector' => $empresa->sector?->nombre,
                'puntaje' => $ultimo?->resultado?->puntaje_total,
                'nivel' => $ultimo->resultado->nivel ?? 'sin',
                'variacion' => $variacion,
                'mediciones' => $empresa->mediciones->where('estado', 'terminada')->count(),
                'pendiente' => $pendiente ? self::pendiente($pendiente, $respuestas) : null,
                'activa' => $empresa->activa,
            ];
        }

        return $lista;
    }

    /**
     * A3.1 · Ficha de una empresa: pestaña Historial y Datos de la cuenta.
     *
     * @return array<string, mixed>
     */
    public static function ficha(Empresa $empresa): array
    {
        $empresa->load([
            'sector:id,nombre',
            'mediciones' => fn ($q) => $q->where('estado', '!=', 'cancelada')->orderBy('numero')
                ->with(['resultado', 'version:id,diagnostico_id,numero,contenido', 'version.diagnostico:id,nombre']),
        ]);

        $mediciones = $empresa->mediciones;
        $respuestas = self::respuestasDe($mediciones->modelKeys());
        [$ultimo, $variacion] = self::ultimoResultado($mediciones);
        $pendiente = $mediciones->first(fn (Medicion $m) => in_array($m->estado, self::PENDIENTES, true));
        $principal = self::principal($empresa);
        $registradaPor = $empresa->getAttribute('registrada_por');

        // Variación de cada medición frente a la terminada anterior (RN-020).
        $anterior = null;
        $filas = [];

        foreach ($mediciones as $m) {
            $puntaje = $m->resultado?->puntaje_total;
            $filas[] = [
                'id' => $m->id,
                'numero' => $m->numero,
                'asignada' => $m->created_at?->toDateString(),
                'terminada' => $m->terminada_en?->toDateString(),
                'version' => $m->version?->numero,
                'diagnostico' => $m->version?->diagnostico?->nombre,
                'estado' => self::estadoVisible($m),
                'puntaje' => $puntaje,
                'nivel' => $m->resultado?->nivel,
                'variacion' => $puntaje !== null && $anterior !== null ? $puntaje - $anterior : null,
            ];

            if ($puntaje !== null) {
                $anterior = $puntaje;
            }
        }

        $usuarios = $empresa->usuarios()->with('roles:id,name')->orderBy('name')->get();

        return [
            'empresa' => [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'descripcion' => $empresa->descripcion,
                'sector_id' => $empresa->sector_id,
                'sector' => $empresa->sector?->nombre,
                'actividad_economica_id' => $empresa->actividad_economica_id,
                'pais' => $empresa->pais,
                'departamento' => $empresa->departamento,
                'ciudad' => $empresa->ciudad,
                'telefono' => $empresa->telefono,
                'sitio_web' => $empresa->sitio_web,
                'numero_empleados' => $empresa->numero_empleados,
                'activa' => $empresa->activa,
                'desactivada_en' => $empresa->desactivada_en?->toIso8601String(),
                'motivo_desactivacion' => $empresa->getAttribute('motivo_desactivacion'),
                'registrada_en' => $empresa->created_at?->toIso8601String(),
                'registrada_por' => $registradaPor ? User::withTrashed()->whereKey($registradaPor)->value('name') : null,
            ],
            'principal' => $principal ? [
                'id' => $principal->id,
                'name' => $principal->name,
                'cargo' => $principal->cargo,
                'email' => $principal->email,
                'telefono' => $principal->telefono,
                'invitacion_pendiente' => $principal->password === null,
                'ultimo_acceso' => $principal->ultimo_acceso_en?->toIso8601String(),
            ] : null,
            'ultimo' => $ultimo ? [
                'numero' => $ultimo->numero,
                'puntaje' => $ultimo->resultado?->puntaje_total,
                'nivel' => $ultimo->resultado?->nivel,
            ] : null,
            'variacion' => $variacion,
            'pendiente' => $pendiente ? [
                ...self::pendiente($pendiente, $respuestas),
                'id' => $pendiente->id,
                'diagnostico' => $pendiente->version?->diagnostico?->nombre,
                'version' => $pendiente->version?->numero,
                'asignada' => $pendiente->created_at?->toDateString(),
                'vence' => $pendiente->fecha_limite?->toDateString(),
                'tiene_respuestas' => $respuestas->has($pendiente->id),
            ] : null,
            'mediciones' => array_reverse($filas),
            'evolucion' => array_values(array_filter(array_map(fn (array $f) => $f['puntaje'] === null ? null : [
                'numero' => $f['numero'],
                'fecha' => $f['terminada'] ?? $f['asignada'],
                'puntaje' => $f['puntaje'],
            ], $filas))),
            'colaboradores' => $usuarios
                ->filter(fn (User $u) => $u->hasRole('Colaborador'))
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'cargo' => $u->cargo, 'activo' => $u->activo])
                ->values()->all(),
            'diagnosticos' => self::diagnosticosPublicados($empresa->sector_id),
        ];
    }

    /**
     * Diagnósticos publicados de un sector, con su última versión, para
     * asignar una medición (RN-014).
     *
     * @return list<array<string, mixed>>
     */
    public static function diagnosticosPublicados(int $sectorId): array
    {
        return array_values(Diagnostico::query()
            ->where('sector_id', $sectorId)
            ->where('estado', Diagnostico::PUBLICADO)
            ->with('ultimaVersion')
            ->orderBy('nombre')
            ->get()
            ->filter(fn (Diagnostico $d) => $d->ultimaVersion !== null)
            ->map(function (Diagnostico $d): array {
                /** @var list<array{preguntas?: list<mixed>}> $categorias */
                $categorias = $d->ultimaVersion->contenido['categorias'] ?? [];

                return [
                    'id' => $d->id,
                    'nombre' => $d->nombre,
                    'version' => $d->ultimaVersion->numero,
                    'categorias' => count($categorias),
                    'preguntas' => array_sum(array_map(fn (array $c) => count($c['preguntas'] ?? []), $categorias)),
                ];
            })
            ->values()
            ->all());
    }

    /**
     * Opciones de los formularios de empresa (A3.2, A3.1d).
     *
     * @return array<string, mixed>
     */
    public static function opciones(): array
    {
        return [
            'sectores' => Sector::query()->orderBy('nombre')->get(['id', 'nombre', 'activo'])
                ->map(fn (Sector $s) => [
                    'id' => $s->id,
                    'nombre' => $s->nombre,
                    'activo' => $s->activo,
                    'actividades' => ActividadEconomica::where('sector_id', $s->id)->where('activo', true)->orderBy('codigo')
                        ->get(['id', 'codigo', 'nombre'])->toArray(),
                    'diagnosticos' => self::diagnosticosPublicados($s->id),
                ])->all(),
            'paises' => Ubicaciones::paises(),
            'empleados' => OpcionesPerfil::RANGOS_EMPLEADOS,
        ];
    }

    /** La cuenta principal (rol Empresa) de la empresa. */
    public static function principal(Empresa $empresa): ?User
    {
        return $empresa->usuarios()->role('Empresa')->orderBy('id')->first();
    }

    /** Estado que se muestra: vencida también si pasó la fecha límite sin enviarse. */
    public static function estadoVisible(Medicion $m): string
    {
        if (in_array($m->estado, ['no_iniciada', 'en_curso'], true) && $m->fecha_limite !== null && $m->fecha_limite->lt(Carbon::today())) {
            return 'vencida';
        }

        return $m->estado;
    }

    /**
     * @param  Collection<int|string, Collection<int|string, mixed>>  $respuestas
     * @return array{numero: int, estado: string, avance: array{hechas: int, total: int}|null}
     */
    private static function pendiente(Medicion $m, Collection $respuestas): array
    {
        $estado = self::estadoVisible($m);
        $refs = $respuestas->get($m->id, collect())->map(fn ($r) => (string) $r)->values()->all();

        return [
            'numero' => $m->numero,
            'estado' => $estado,
            'avance' => $estado !== 'no_iniciada' && $m->version ? DatosInicio::avance($m->version->contenido, $refs) : null,
        ];
    }

    /**
     * Última medición con resultado y la variación frente a la anterior
     * con resultado (RN-020); null en la primera.
     *
     * @param  Collection<int, Medicion>  $mediciones
     * @return array{0: Medicion|null, 1: int|null}
     */
    private static function ultimoResultado(Collection $mediciones): array
    {
        $conResultado = $mediciones->filter(fn (Medicion $m) => $m->resultado !== null)->sortBy('numero')->values();
        $ultimo = $conResultado->last();
        $anterior = $conResultado->count() > 1 ? $conResultado[$conResultado->count() - 2] : null;

        $variacion = $ultimo->resultado->variacion
            ?? ($ultimo && $anterior ? $ultimo->resultado->puntaje_total - $anterior->resultado->puntaje_total : null);

        return [$ultimo, $variacion];
    }

    /**
     * Preguntas respondidas por medición.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int|string, Collection<int|string, mixed>>
     */
    private static function respuestasDe(array $ids): Collection
    {
        return DB::table('respuestas')->whereIn('medicion_id', $ids)->get(['medicion_id', 'pregunta_ref'])
            ->groupBy('medicion_id')
            ->map(fn ($filas) => $filas->pluck('pregunta_ref'));
    }
}
