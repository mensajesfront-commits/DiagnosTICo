<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $medicion_id
 * @property string $pregunta_ref
 * @property list<int|string>|null $opciones_elegidas
 * @property string|null $texto
 * @property int|null $puntaje
 */
#[Table('respuestas')]
#[Fillable(['medicion_id', 'pregunta_ref', 'opciones_elegidas', 'texto', 'puntaje', 'observacion_ia', 'respondida_por'])]
class Respuesta extends Model
{
    protected function casts(): array
    {
        return ['opciones_elegidas' => 'array'];
    }

    /** @return BelongsTo<Medicion, $this> */
    public function medicion(): BelongsTo
    {
        return $this->belongsTo(Medicion::class);
    }
}
