<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Actividad económica de una empresa, con su código CIIU (Rev. 4 A.C.).
 * Cada sector ofrece las suyas en el registro (L2).
 *
 * @property int $id
 * @property int $sector_id
 * @property string $codigo
 * @property string $nombre
 * @property bool $activo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('actividades_economicas')]
#[Fillable(['sector_id', 'codigo', 'nombre', 'activo'])]
class ActividadEconomica extends Model
{
    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /** @return BelongsTo<Sector, $this> */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
