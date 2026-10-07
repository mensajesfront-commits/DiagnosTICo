<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property int $sector_id
 * @property int|null $actividad_economica_id
 * @property string $ciudad
 * @property string|null $departamento
 * @property string $pais
 * @property string|null $telefono
 * @property string|null $sitio_web
 * @property string|null $numero_empleados
 * @property string|null $logo_ruta
 * @property bool $activa
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre', 'descripcion', 'sector_id', 'actividad_economica_id', 'ciudad', 'departamento', 'pais', 'telefono', 'sitio_web', 'numero_empleados', 'activa'])]
class Empresa extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }

    /** @return BelongsTo<Sector, $this> */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /** @return BelongsTo<ActividadEconomica, $this> */
    public function actividadEconomica(): BelongsTo
    {
        return $this->belongsTo(ActividadEconomica::class);
    }

    /** @return HasMany<User, $this> */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
