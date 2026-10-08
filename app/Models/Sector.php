<?php

namespace App\Models;

use Database\Factories\SectorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property bool $activo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('sectores')]
#[Fillable(['nombre', 'descripcion', 'activo'])]
class Sector extends Model
{
    /** @use HasFactory<SectorFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /** @return HasMany<ActividadEconomica, $this> */
    public function actividades(): HasMany
    {
        return $this->hasMany(ActividadEconomica::class);
    }

    /** @return HasMany<Diagnostico, $this> */
    public function diagnosticos(): HasMany
    {
        return $this->hasMany(Diagnostico::class);
    }

    /** @return HasMany<Empresa, $this> */
    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class);
    }

    /**
     * Sectores que se ofrecen al registrarse (RN-003).
     *
     * @param  Builder<Sector>  $query
     */
    public function scopeActivos(Builder $query): void
    {
        $query->where('activo', true);
    }
}
