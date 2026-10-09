<?php

namespace App\Models;

use Database\Factories\MedicionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Una vez que una empresa responde una versión de un diagnóstico (RN-015).
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $version_diagnostico_id
 * @property int $numero
 * @property string $estado
 * @property Carbon|null $fecha_limite
 * @property Carbon|null $enviada_en
 * @property Carbon|null $terminada_en
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('mediciones')]
#[Fillable([
    'empresa_id', 'version_diagnostico_id', 'numero', 'estado', 'fecha_limite', 'mensaje',
    'aviso_por_correo', 'correo_asunto', 'correo_cuerpo', 'asignada_por',
])]
class Medicion extends Model
{
    /** @use HasFactory<MedicionFactory> */
    use HasFactory;

    public const array ESTADOS = ['no_iniciada', 'en_curso', 'enviada', 'terminada', 'vencida', 'cancelada'];

    protected function casts(): array
    {
        return [
            'fecha_limite' => 'date',
            'aviso_por_correo' => 'boolean',
            'cancelada_en' => 'datetime',
            'enviada_en' => 'datetime',
            'terminada_en' => 'datetime',
        ];
    }

    /** @return BelongsTo<Empresa, $this> */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** @return BelongsTo<VersionDiagnostico, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(VersionDiagnostico::class, 'version_diagnostico_id');
    }

    /** @return HasMany<Respuesta, $this> */
    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class);
    }

    /** @return HasMany<AnalisisCategoria, $this> */
    public function analisis(): HasMany
    {
        return $this->hasMany(AnalisisCategoria::class);
    }

    /** @return HasOne<Resultado, $this> */
    public function resultado(): HasOne
    {
        return $this->hasOne(Resultado::class);
    }
}
