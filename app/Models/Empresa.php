<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nombre
 * @property int $sector_id
 * @property string $ciudad
 * @property string $pais
 * @property string|null $telefono
 * @property string|null $sitio_web
 * @property string|null $numero_empleados
 * @property string|null $logo_ruta
 * @property bool $activa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nombre', 'sector_id', 'ciudad', 'pais', 'telefono', 'sitio_web', 'numero_empleados', 'activa'])]
class Empresa extends Model
{
    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }

    /** @return BelongsTo<Sector, $this> */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /** @return HasMany<User, $this> */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
