<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Versión publicada y congelada de un diagnóstico (RN-010). No se edita.
 *
 * @property int $id
 * @property int $diagnostico_id
 * @property int $numero
 * @property string|null $nota_cambios
 * @property array<string, mixed> $contenido
 * @property int|null $publicada_por
 * @property Carbon $publicada_en
 */
#[Table('versiones_diagnostico')]
#[Fillable(['diagnostico_id', 'numero', 'nota_cambios', 'contenido', 'publicada_por', 'publicada_en'])]
class VersionDiagnostico extends Model
{
    protected function casts(): array
    {
        return ['contenido' => 'array', 'publicada_en' => 'datetime'];
    }

    /** @return BelongsTo<Diagnostico, $this> */
    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class);
    }

    /** @return HasMany<Medicion, $this> */
    public function mediciones(): HasMany
    {
        return $this->hasMany(Medicion::class);
    }
}
