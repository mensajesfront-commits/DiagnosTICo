<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Resultado publicado de una medición. Inmutable (RN-023).
 *
 * @property int $id
 * @property int $medicion_id
 * @property int $puntaje_total
 * @property string $nivel
 * @property int|null $variacion
 * @property array<string, mixed> $por_categoria
 * @property string|null $pdf_ruta
 * @property Carbon $publicado_en
 */
#[Table('resultados')]
#[Fillable(['medicion_id', 'puntaje_total', 'nivel', 'variacion', 'por_categoria', 'pdf_ruta', 'publicado_en'])]
class Resultado extends Model
{
    protected function casts(): array
    {
        return ['por_categoria' => 'array', 'publicado_en' => 'datetime'];
    }

    /** @return BelongsTo<Medicion, $this> */
    public function medicion(): BelongsTo
    {
        return $this->belongsTo(Medicion::class);
    }
}
