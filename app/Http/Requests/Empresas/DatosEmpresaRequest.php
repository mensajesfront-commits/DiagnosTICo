<?php

namespace App\Http\Requests\Empresas;

use App\Rules\TelefonoValido;
use App\Models\ActividadEconomica;
use App\Models\Empresa;
use App\Models\User;
use App\Rules\DepartamentoDelPais;
use App\Support\DatosEmpresas;
use App\Support\OpcionesPerfil;
use App\Support\Ubicaciones;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos de la empresa y de su usuario principal: Registrar empresa (A3.2,
 * HU-032) y Datos de la cuenta (A3.1d, HU-034).
 *
 * - Al registrar, el sector debe estar activo (RN-003). Al editar también
 *   vale el sector que ya tiene (CA-002: cambiarlo no toca las mediciones).
 * - La actividad económica debe ser del sector elegido; obligatoria si el
 *   sector tiene actividades.
 * - El correo del usuario principal no puede ser de otra cuenta (RN-002).
 * - El Administrador no escribe contraseñas: el usuario recibe un correo
 *   para crearla (RN-006).
 */
class DatosEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Empresa|null $empresa */
        $empresa = $this->route('empresa');
        $principal = $empresa ? DatosEmpresas::principal($empresa) : null;
        $sectorId = is_numeric($this->input('sector_id')) ? (int) $this->input('sector_id') : null;
        $conActividades = $sectorId !== null && ActividadEconomica::where('sector_id', $sectorId)->where('activo', true)->exists();

        $reglas = [
            'nombre' => ['required', 'string', 'max:255'],
            'sector_id' => ['required', 'integer', Rule::exists('sectores', 'id')->where(
                fn ($q) => $empresa ? $q->where('activo', true)->orWhere('id', $empresa->sector_id) : $q->where('activo', true),
            )],
            'actividad_economica_id' => [
                Rule::requiredIf($conActividades),
                'nullable',
                'integer',
                Rule::exists('actividades_economicas', 'id')->where('sector_id', $sectorId)
                    ->where(fn ($q) => $q->where('activo', true)->orWhere('id', $empresa->actividad_economica_id ?? 0)),
            ],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'pais' => ['required', 'string', Rule::in(Ubicaciones::nombresDePaises())],
            'departamento' => ['required', 'string', new DepartamentoDelPais($this->string('pais')->value())],
            'ciudad' => ['required', 'string', 'min:2', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30', new TelefonoValido],
            'sitio_web' => ['nullable', 'string', 'url', 'max:255'],
            'numero_empleados' => ['nullable', 'string', Rule::in(OpcionesPerfil::RANGOS_EMPLEADOS)],
            'usuario_nombre' => ['required', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($principal?->id)->withoutTrashed()],
            'usuario_telefono' => ['nullable', 'string', 'max:30', new TelefonoValido],
        ];

        if ($empresa === null) {
            $reglas += [
                'asignar_medicion' => ['boolean'],
                'diagnostico_id' => ['required_if_accepted:asignar_medicion', 'nullable', 'integer'],
                'fecha_limite' => ['nullable', 'date', 'after_or_equal:today'],
            ];
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Ese correo ya es de otra cuenta.',
            'diagnostico_id.required_if_accepted' => 'Elige el diagnóstico de la primera medición.',
            'fecha_limite.after_or_equal' => 'La fecha límite no puede ser anterior a hoy.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la empresa',
            'sector_id' => 'sector',
            'actividad_economica_id' => 'actividad económica',
            'sitio_web' => 'sitio web',
            'numero_empleados' => 'número de empleados',
            'usuario_nombre' => 'nombre del usuario',
            'email' => 'correo',
            'usuario_telefono' => 'teléfono del usuario',
            'diagnostico_id' => 'diagnóstico',
            'fecha_limite' => 'fecha límite',
        ];
    }

    /**
     * Los datos de la tabla `empresas`.
     *
     * @return array<string, mixed>
     */
    public function datosEmpresa(): array
    {
        $d = $this->validated();

        return [
            'nombre' => trim((string) $d['nombre']),
            'sector_id' => (int) $d['sector_id'],
            'actividad_economica_id' => $d['actividad_economica_id'] ?? null,
            'descripcion' => $d['descripcion'] ?? null,
            'pais' => $d['pais'],
            'departamento' => $d['departamento'],
            'ciudad' => trim((string) $d['ciudad']),
            'telefono' => $d['telefono'] ?? null,
            'sitio_web' => $d['sitio_web'] ?? null,
            'numero_empleados' => $d['numero_empleados'] ?? null,
        ];
    }

    /**
     * Los datos del usuario principal.
     *
     * @return array<string, mixed>
     */
    public function datosUsuario(): array
    {
        $d = $this->validated();

        return [
            'name' => trim((string) $d['usuario_nombre']),
            'cargo' => $d['cargo'] ?? null,
            'email' => mb_strtolower(trim((string) $d['email'])),
            'telefono' => $d['usuario_telefono'] ?? null,
        ];
    }
}
