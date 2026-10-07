<?php

namespace App\Http\Requests\Perfil;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Rules\DepartamentoDelPais;
use App\Support\OpcionesPerfil;
use App\Support\Ubicaciones;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos que se guardan desde "Mi perfil" (A6 y E11).
 *
 * Los datos de la empresa solo los cambia la cuenta principal (rol Empresa);
 * el sector solo lo cambia el equipo de NuevasTIC (RN-025, E11).
 */
class ActualizarPerfilRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $usuario */
        $usuario = $this->user();

        $reglas = [
            ...$this->profileRules($usuario->id),
            'cargo' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'pais' => ['nullable', 'string', Rule::in(Ubicaciones::nombresDePaises())],
            'departamento' => ['nullable', 'string', new DepartamentoDelPais($this->string('pais')->value())],
            'ciudad' => ['nullable', 'string', 'max:255'],
            'zona_horaria' => ['required', 'string', Rule::in(array_keys(OpcionesPerfil::ZONAS_HORARIAS))],
            'idioma' => ['required', 'string', Rule::in(array_keys(OpcionesPerfil::IDIOMAS))],
            'avisos' => ['array'],
        ];

        foreach (array_keys(OpcionesPerfil::avisosPara($usuario)) as $clave) {
            $reglas["avisos.{$clave}"] = ['boolean'];
        }

        if ($this->editaEmpresa()) {
            $reglas += [
                'empresa.nombre' => ['required', 'string', 'max:255'],
                'empresa.pais' => ['required', 'string', Rule::in(Ubicaciones::nombresDePaises())],
                'empresa.departamento' => ['required', 'string', new DepartamentoDelPais($this->string('empresa.pais')->value())],
                'empresa.ciudad' => ['required', 'string', 'max:255'],
                'empresa.sitio_web' => ['nullable', 'string', 'max:255'],
                'empresa.numero_empleados' => ['nullable', 'string', Rule::in(OpcionesPerfil::RANGOS_EMPLEADOS)],
            ];
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'empresa.nombre' => 'nombre de la empresa',
            'empresa.ciudad' => 'ciudad',
            'empresa.pais' => 'país',
            'empresa.departamento' => 'departamento',
            'empresa.sitio_web' => 'sitio web',
            'empresa.numero_empleados' => 'número de empleados',
        ];
    }

    /** Solo la cuenta principal de la empresa cambia sus datos. */
    public function editaEmpresa(): bool
    {
        /** @var User $usuario */
        $usuario = $this->user();

        return $usuario->empresa_id !== null && $usuario->hasRole('Empresa');
    }
}
