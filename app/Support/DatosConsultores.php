<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\User;

/**
 * Datos de la pestaña "Consultores" de A3.1 (HU-085, RN-030): los
 * consultores vinculados a la empresa y los que se le pueden vincular.
 */
class DatosConsultores
{
    /**
     * @return array{vinculados: array<int, array<string, mixed>>, disponibles: array<int, array<string, mixed>>}
     */
    public static function deEmpresa(Empresa $empresa): array
    {
        $vinculados = $empresa->consultores()
            ->withCount('empresasAsesoradas')
            ->orderBy('name')
            ->get();

        $disponibles = User::role('Consultor')
            ->whereNull('empresa_id')
            ->whereNotIn('id', $vinculados->modelKeys())
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'activo', 'password']);

        return [
            'vinculados' => $vinculados->map(fn (User $u) => [
                ...self::fila($u),
                'empresas' => (int) $u->getAttribute('empresas_asesoradas_count'),
                'vinculado_en' => $u->getRelation('pivot')?->getAttribute('created_at')?->toDateString(),
            ])->values()->all(),
            'disponibles' => $disponibles->map(fn (User $u) => self::fila($u))->values()->all(),
        ];
    }

    /** @return array{id: int, nombre: string, correo: string, activo: bool, invitacion_pendiente: bool} */
    private static function fila(User $u): array
    {
        return [
            'id' => $u->id,
            'nombre' => $u->name,
            'correo' => $u->email,
            'activo' => (bool) $u->activo,
            'invitacion_pendiente' => $u->invitacionPendiente(),
        ];
    }
}
