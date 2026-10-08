<?php

namespace App\Models;

use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Tema que se evalúa en un diagnóstico (catálogo A2.3).
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property Carbon|null $archivado_en
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('categorias')]
#[Fillable(['nombre', 'descripcion'])]
class Categoria extends Model
{
    /** @use HasFactory<CategoriaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['archivado_en' => 'datetime'];
    }

    /** @return BelongsToMany<Diagnostico, $this> */
    public function diagnosticos(): BelongsToMany
    {
        return $this->belongsToMany(Diagnostico::class, 'diagnostico_categoria')
            ->withPivot(['id', 'importancia', 'orden'])
            ->withTimestamps();
    }

    /** @return HasMany<DiagnosticoCategoria, $this> */
    public function usos(): HasMany
    {
        return $this->hasMany(DiagnosticoCategoria::class);
    }

    /** @param Builder<Categoria> $query */
    public function scopeActivas(Builder $query): void
    {
        $query->whereNull('archivado_en');
    }

    public function archivada(): bool
    {
        return $this->archivado_en !== null;
    }
}
