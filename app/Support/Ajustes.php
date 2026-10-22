<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Opciones generales del sistema guardadas en la tabla `ajustes`.
 *
 * - `revision_resultados`: si está encendida, el resultado de una medición no
 *   se publica solo cuando la IA termina; queda "Por revisar" hasta que un
 *   administrador lo apruebe (supervisión humana, 23 de octubre de 2026).
 */
class Ajustes
{
    public const string REVISION = 'revision_resultados';

    public static function revisionActiva(): bool
    {
        return (bool) self::valor(self::REVISION, false);
    }

    public static function valor(string $clave, mixed $porDefecto = null): mixed
    {
        $fila = DB::table('ajustes')->where('clave', $clave)->value('valor');

        return $fila === null ? $porDefecto : json_decode((string) $fila, true);
    }

    public static function guardar(string $clave, mixed $valor, ?int $usuarioId = null): void
    {
        DB::table('ajustes')->updateOrInsert(
            ['clave' => $clave],
            ['valor' => json_encode($valor), 'actualizado_por' => $usuarioId, 'updated_at' => now(), 'created_at' => now()],
        );
    }

    /** @return array{activa: bool, actualizado_en: string|null, actualizado_por: string|null} */
    public static function revision(): array
    {
        $fila = DB::table('ajustes')->leftJoin('users', 'users.id', '=', 'ajustes.actualizado_por')
            ->where('clave', self::REVISION)->first(['ajustes.valor', 'ajustes.updated_at', 'users.name']);

        return [
            'activa' => $fila !== null && (bool) json_decode((string) $fila->valor, true),
            'actualizado_en' => $fila?->updated_at ? Carbon::parse((string) $fila->updated_at)->toIso8601String() : null,
            'actualizado_por' => $fila?->name,
        ];
    }
}
