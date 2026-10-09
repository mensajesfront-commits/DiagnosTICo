<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pregunta_id
 * @property string $texto
 * @property int $puntaje 0 a 100
 * @property string|null $ten_en_cuenta
 * @property int $orden
 */
#[Table('opciones')]
#[Fillable(['pregunta_id', 'texto', 'puntaje', 'ten_en_cuenta', 'orden'])]
class Opcion extends Model
{
    /** @return BelongsTo<Pregunta, $this> */
    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }
}
