<?php

namespace App\Models;

use App\Notifications\ContrasenaLaCambiaTuEmpresa;
use App\Services\Acceso\CodigoVerificacion;
use App\Support\OpcionesPerfil;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property int|null $empresa_id
 * @property string $name
 * @property string $email
 * @property string|null $cargo
 * @property string|null $telefono
 * @property string|null $ciudad
 * @property string|null $departamento
 * @property string|null $pais
 * @property string $zona_horaria
 * @property string $idioma
 * @property array<string, bool>|null $avisos
 * @property string|null $foto_ruta
 * @property Carbon|null $ultimo_acceso_en
 * @property Carbon|null $contrasena_actualizada_en
 * @property bool $activo
 * @property bool $administrador_principal Cuenta del Administrador creada al instalar: no se le quita el rol
 * @property Carbon|null $terminos_aceptados_en
 * @property Carbon|null $invitacion_enviada_en
 * @property Carbon|null $email_verified_at
 * @property string|null $codigo_verificacion Código de verificación cifrado (L2b)
 * @property string|null $email_pendiente Correo nuevo de Mi perfil, sin confirmar
 * @property string|null $telefono_pendiente Teléfono nuevo de Mi perfil, sin confirmar
 * @property Carbon|null $codigo_vence_en
 * @property int $codigo_intentos
 * @property string|null $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'email', 'password', 'empresa_id', 'cargo', 'telefono', 'activo',
    'ciudad', 'departamento', 'pais', 'zona_horaria', 'idioma', 'avisos',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'foto_ruta', 'codigo_verificacion'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * El correo se guarda siempre en minúsculas y sin espacios (ERR-02): dos
     * cuentas no pueden diferir solo en mayúsculas.
     *
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $valor) => $valor === null ? null : Str::lower(trim($valor)),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'codigo_vence_en' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'administrador_principal' => 'boolean',
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
    /**
     * ¿Quiere este aviso por correo? Lo elige en Mi perfil (A6, E11); si no
     * ha elegido, vale el valor inicial de `OpcionesPerfil`.
     */
    /**
     * L2b · En lugar del enlace de Laravel, un código de 6 dígitos. Lo usa
     * el evento `Registered` al crear la cuenta.
     */
    public function sendEmailVerificationNotification(): void
    {
        app(CodigoVerificacion::class)->enviar($this);
    }

    /**
     * L3 · Al colaborador no le llega el enlace: su contraseña la cambia la
     * cuenta principal de su empresa (E12.3).
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        if ($this->hasRole('Colaborador')) {
            $this->notify(new ContrasenaLaCambiaTuEmpresa($this->empresa?->nombre));

            return;
        }

        parent::sendPasswordResetNotification($token);
    }

    public function quiereAviso(string $clave): bool
    {
        return OpcionesPerfil::avisosDe($this)[$clave] ?? true;
    }

    public function puedeEntrar(): bool
    {
        if ($this->activo === false) {
            return false;
        }

        // Rol desactivado (A5.1c): sus cuentas no entran hasta tener otro rol.
        if ($this->rolInactivo()) {
            return false;
        }

        return $this->empresa_id === null || $this->empresa?->activa !== false;
    }

    /**
     * Error y mensaje de L1 cuando no puede entrar: rol inactivo (la cuenta
     * sigue activa) o cuenta/empresa desactivada (ERR-12).
     *
     * @return array<string, string>
     */
    public function errorSinAcceso(): array
    {
        if ($this->activo !== false && $this->rolInactivo()) {
            return ['rol_inactivo' => trans('auth.rol_inactivo')];
        }

        return ['cuenta_desactivada' => trans('auth.desactivada')];
    }

    /** Su rol está desactivado (A5.1c). */
    public function rolInactivo(): bool
    {
        return $this->roles->contains(fn (Model $rol): bool => ! (bool) ($rol->getAttribute('activo') ?? true));
    }

    /** Invitada que todavía no crea su contraseña (A5). */
    public function invitacionPendiente(): bool
    {
        return $this->password === null;
    }

    /** Consultor externo (RN-030): solo mira las empresas que tiene vinculadas. */
    public function esConsultor(): bool
    {
        return $this->empresa_id === null && $this->hasRole('Consultor');
    }

    /**
     * Empresas que acompaña como consultor (RN-030). Las vincula el
     * Administrador en A3.1 o al invitarlo.
     *
     * @return BelongsToMany<Empresa, $this>
     */
    public function empresasAsesoradas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'consultor_empresa')->withTimestamps();
    }

    /**
     * Si la cuenta puede ver una empresa, sus resultados y su historial:
     * - de una empresa: solo la suya (RN-007);
     * - interna: con `empresas.ver`; el consultor, solo las vinculadas.
     */
    public function puedeVerEmpresa(int $empresaId): bool
    {
        if ($this->empresa_id !== null) {
            return $this->empresa_id === $empresaId;
        }

        if (! $this->can('empresas.ver')) {
            return false;
        }

        return ! $this->esConsultor()
            || $this->empresasAsesoradas()->whereKey($empresaId)->exists();
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
