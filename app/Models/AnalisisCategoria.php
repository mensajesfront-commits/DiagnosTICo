<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Análisis de la IA de una categoría de una medición (RN-021).
 *
 * @property int $id
 * @property int $medicion_id
 * @property string $categoria_ref
 * @property string $estado pendiente · terminado · fallido
 * @property int $intentos
 * @property array<string, mixed>|null $respuesta_ia
 */
#[Table('analisis_categoria')]
#[Fillable(['medicion_id', 'categoria_ref', 'estado', 'intentos', 'prompt', 'respuesta_ia'])]
class AnalisisCategoria extends Model
{
    protected function casts(): array
    {
        return ['respuesta_ia' => 'array'];
    }

    /** @return BelongsTo<Medicion, $this> */
    public function medicion(): BelongsTo
    {
        return $this->belongsTo(Medicion::class);
    }
}
