<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Constancia de cada uso de "Ver como" (RN-026, A5.4).
 *
 * @property int $id
 * @property int $administrador_id
 * @property int $cuenta_id
 */
#[Table('registros_ver_como')]
#[Fillable(['administrador_id', 'cuenta_id', 'inicio', 'fin'])]
class RegistroVerComo extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['inicio' => 'datetime', 'fin' => 'datetime'];
    }
}
