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
 * @property bool $exclusiva En selección múltiple, desmarca las demás ("Ninguna")
 * @property bool $con_texto "Otro": al elegirla, la empresa escribe cuál
 * @property int $orden
 */
#[Table('opciones')]
#[Fillable(['pregunta_id', 'texto', 'puntaje', 'ten_en_cuenta', 'exclusiva', 'con_texto', 'orden'])]
class Opcion extends Model
{
    protected function casts(): array
    {
        return ['exclusiva' => 'boolean', 'con_texto' => 'boolean'];
    }

    /** @return BelongsTo<Pregunta, $this> */
    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }
}
