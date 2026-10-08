<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Instrucciones de la IA por capas: general, sector o empresa (RN-022).
 *
 * @property int $id
 * @property string $etapa
 * @property string $alcance general · sector · empresa
 * @property int|null $sector_id
 * @property int|null $empresa_id
 */
#[Table('prompts')]
#[Fillable([
    'etapa', 'alcance', 'sector_id', 'empresa_id', 'modo_contexto', 'modo_tarea', 'modo_detalles',
    'modo_ejemplos', 'contexto', 'tarea', 'detalles', 'ejemplos', 'actualizado_por',
])]
class Prompt extends Model {}
