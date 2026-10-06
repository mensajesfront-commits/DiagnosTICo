<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property int|null $empresa_id
 * @property string $name
 * @property string $email
 * @property string|null $cargo
 * @property string|null $telefono
 * @property string|null $ciudad
 * @property string|null $pais
 * @property string $zona_horaria
 * @property string $idioma
 * @property array<string, bool>|null $avisos
 * @property string|null $foto_ruta
 * @property Carbon|null $ultimo_acceso_en
 * @property Carbon|null $contrasena_actualizada_en
 * @property bool $activo
 * @property Carbon|null $terminos_aceptados_en
 * @property Carbon|null $invitacion_enviada_en
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'email', 'password', 'empresa_id', 'cargo', 'telefono', 'activo',
    'ciudad', 'pais', 'zona_horaria', 'idioma', 'avisos',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'foto_ruta'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'avisos' => 'array',
            'ultimo_acceso_en' => 'datetime',
            'contrasena_actualizada_en' => 'datetime',
            'terminos_aceptados_en' => 'datetime',
            'invitacion_enviada_en' => 'datetime',
        ];
    }

    /**
     * La cuenta puede iniciar sesión si está activa (RN-004) y, si es de una
     * empresa, la empresa también lo está (RN-025).
     */
    public function puedeEntrar(): bool
    {
        if ($this->activo === false) {
            return false;
        }

        return $this->empresa_id === null || $this->empresa?->activa !== false;
    }

    /** Invitada que todavía no crea su contraseña (A5). */
    public function invitacionPendiente(): bool
    {
        return $this->password === null;
    }

    /** Cuenta principal de su empresa (rol Empresa). */
    public function esPrincipal(): bool
    {
        return $this->empresa_id !== null && $this->hasRole('Empresa');
    }

    /**
     * Empresa de la cuenta; null para el Administrador.
     *
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
