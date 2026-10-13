<?php

namespace App\Models;

use Database\Factories\DiagnosticoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Cuestionario de un sector (A2). El borrador se edita; al publicarlo se
 * guarda una versión congelada (RN-010).
 *
 * @property int $id
 * @property int $sector_id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string $estado borrador · publicado · archivado
 * @property int|null $version_borrador
 * @property Carbon|null $archivado_en
 * @property int|null $creado_por
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('diagnosticos')]
#[Fillable(['sector_id', 'nombre', 'descripcion', 'estado', 'version_borrador', 'creado_por'])]
class Diagnostico extends Model
{
    /** @use HasFactory<DiagnosticoFactory> */
    use HasFactory;

    public const string BORRADOR = 'borrador';

    public const string PUBLICADO = 'publicado';

    public const string ARCHIVADO = 'archivado';

    protected function casts(): array
    {
        return ['archivado_en' => 'datetime'];
    }

    /** @return BelongsTo<Sector, $this> */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /** @return BelongsToMany<Categoria, $this> */
    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'diagnostico_categoria')
            ->withPivot(['id', 'importancia', 'orden'])
            ->withTimestamps()
            ->orderByPivot('orden');
    }

    /** @return HasMany<DiagnosticoCategoria, $this> */
    public function partes(): HasMany
    {
        return $this->hasMany(DiagnosticoCategoria::class)->orderBy('orden');
    }

    /** @return HasManyThrough<Pregunta, DiagnosticoCategoria, $this> */
    public function preguntas(): HasManyThrough
    {
        return $this->hasManyThrough(Pregunta::class, DiagnosticoCategoria::class);
    }

    /** @return HasMany<VersionDiagnostico, $this> */
    public function versiones(): HasMany
    {
        return $this->hasMany(VersionDiagnostico::class)->orderBy('numero');
    }

    /** @return HasOne<VersionDiagnostico, $this> */
    public function ultimaVersion(): HasOne
    {
        return $this->hasOne(VersionDiagnostico::class)->ofMany('numero', 'max');
    }

    /** @return HasManyThrough<Medicion, VersionDiagnostico, $this> */
    public function mediciones(): HasManyThrough
    {
        return $this->hasManyThrough(Medicion::class, VersionDiagnostico::class);
    }

    /** @param Builder<Diagnostico> $query */
    public function scopeActivos(Builder $query): void
    {
        $query->where('estado', '!=', self::ARCHIVADO);
    }
}
