<?php

namespace App\Support;

use App\Models\Medicion;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Fecha límite de las mediciones en la hora de cada empresa (pedido del
 * equipo, 22 de octubre de 2026).
 *
 * - El día se cuenta en la zona horaria de la cuenta principal de la empresa
 *   (Mi perfil). La fecha límite vale hasta las 11:59 p. m. de ese día, en
 *   esa zona. Sin cuenta principal, se usa `diagnostico.zona_horaria`.
 * - Al asignar o cambiar la fecha, la más cercana que se acepta es dentro de
 *   `diagnostico.plazo_minimo_dias` días (3), para que la empresa alcance a
 *   responder aunque se le asigne de noche.
 *
 * Se registra como `scoped`: la caché de zonas dura una petición.
 */
class Plazos
{
    /** @var array<int, string> empresa_id → zona horaria */
    private array $zonas = [];

    public static function instancia(): self
    {
        return app(self::class);
    }

    /**
     * Carga de una vez la zona de varias empresas (listas de A1, A3…).
     *
     * @param  iterable<int|string|null>  $empresaIds
     */
    public static function precargar(iterable $empresaIds): void
    {
        $yo = self::instancia();
        $ids = [];

        foreach ($empresaIds as $id) {
            $ids[] = (int) $id;
        }

        $faltan = array_values(array_diff(array_unique($ids), array_keys($yo->zonas)));

        if ($faltan === []) {
            return;
        }

        $principales = User::query()->whereIn('empresa_id', $faltan)->role('Empresa')
            ->orderBy('id')->get(['id', 'empresa_id', 'zona_horaria'])->unique('empresa_id')->keyBy('empresa_id');

        foreach ($faltan as $id) {
            $yo->zonas[$id] = self::valida($principales->get($id)?->zona_horaria);
        }
    }

    public static function zona(?int $empresaId): string
    {
        if ($empresaId === null) {
            return self::valida(null);
        }

        self::precargar([$empresaId]);

        return self::instancia()->zonas[$empresaId];
    }

    /** Hoy en la zona de la empresa, "2026-10-22". */
    public static function hoy(?int $empresaId): string
    {
        return CarbonImmutable::now(self::zona($empresaId))->toDateString();
    }

    /** La fecha límite más cercana que se puede elegir. */
    public static function fechaMinima(?int $empresaId): string
    {
        return CarbonImmutable::parse(self::hoy($empresaId))
            ->addDays(self::diasMinimos())->toDateString();
    }

    public static function diasMinimos(): int
    {
        return max(0, (int) config('diagnostico.plazo_minimo_dias', 3));
    }

    /** Ya pasó el último día para responder, en la hora de la empresa. */
    public static function vencio(Medicion $m): bool
    {
        return $m->fecha_limite !== null
            && $m->fecha_limite->toDateString() < self::hoy($m->empresa_id);
    }

    /** Días que le quedan a la empresa (0 = vence hoy; negativo = venció). */
    public static function diasRestantes(Medicion $m): ?int
    {
        if ($m->fecha_limite === null) {
            return null;
        }

        return (int) CarbonImmutable::parse(self::hoy($m->empresa_id))
            ->diffInDays(CarbonImmutable::parse($m->fecha_limite->toDateString()), false);
    }

    /**
     * Regla para `fecha_limite` al asignar o cambiar la fecha.
     *
     * @return list<string>
     */
    public static function reglas(?int $empresaId): array
    {
        return ['nullable', 'date', 'after_or_equal:'.self::fechaMinima($empresaId)];
    }

    public static function mensaje(?int $empresaId): string
    {
        $dias = self::diasMinimos();
        /** @var CarbonImmutable $fecha */
        $fecha = CarbonImmutable::parse(self::fechaMinima($empresaId))->locale('es');
        $texto = $fecha->isoFormat('D [de] MMMM');

        return "La fecha límite debe dar al menos {$dias} días para responder: elige el {$texto} o después.";
    }

    private static function valida(?string $zona): string
    {
        $porDefecto = (string) config('diagnostico.zona_horaria', 'America/Bogota');

        return $zona !== null && in_array($zona, timezone_identifiers_list(), true) ? $zona : $porDefecto;
    }
}
