<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $diagnostico_categoria_id
 * @property string $texto
 * @property string $tipo abierta · opcion_unica · seleccion_multiple
 * @property string $indicacion
 * @property string|null $criterio_ia
 * @property bool $obligatoria
 * @property int $orden
 */
#[Table('preguntas')]
#[Fillable(['diagnostico_categoria_id', 'texto', 'tipo', 'indicacion', 'criterio_ia', 'obligatoria', 'orden'])]
class Pregunta extends Model
{
    public const array TIPOS = ['abierta', 'opcion_unica', 'seleccion_multiple'];

    protected function casts(): array
    {
        return ['obligatoria' => 'boolean'];
    }

    /** @return BelongsTo<DiagnosticoCategoria, $this> */
    public function parte(): BelongsTo
    {
        return $this->belongsTo(DiagnosticoCategoria::class, 'diagnostico_categoria_id');
    }

    /** @return HasMany<Opcion, $this> */
    public function opciones(): HasMany
    {
        return $this->hasMany(Opcion::class)->orderBy('orden');
    }
}
