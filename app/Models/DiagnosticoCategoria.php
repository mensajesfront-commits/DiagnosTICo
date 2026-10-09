<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una categoría dentro de un diagnóstico, con su importancia (RN-011) y sus
 * preguntas.
 *
 * @property int $id
 * @property int $diagnostico_id
 * @property int $categoria_id
 * @property string $importancia
 * @property int $orden
 */
#[Table('diagnostico_categoria')]
#[Fillable(['diagnostico_id', 'categoria_id', 'importancia', 'orden'])]
class DiagnosticoCategoria extends Model
{
    protected function casts(): array
    {
        return ['importancia' => 'decimal:2'];
    }

    /** @return BelongsTo<Diagnostico, $this> */
    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class);
    }

    /** @return BelongsTo<Categoria, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /** @return HasMany<Pregunta, $this> */
    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class)->orderBy('orden');
    }
}
