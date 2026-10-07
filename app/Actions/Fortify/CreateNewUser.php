<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\ActividadEconomica;
use App\Models\Empresa;
use App\Models\User;
use App\Rules\DepartamentoDelPais;
use App\Support\Ubicaciones;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * L2 · Registro propio de una empresa (HU-001).
 *
 * Crea la empresa y su usuario principal con el rol Empresa en una sola
 * operación. El sector debe estar activo (RN-003) y el correo no puede
 * repetirse (RN-002).
 *
 * Paso «Mi empresa»: la actividad económica debe ser del sector elegido
 * (obligatoria si el sector tiene actividades) y la descripción corta es
 * obligatoria, de máximo 300 caracteres. País → departamento → ciudad: el
 * departamento debe ser del país; la ciudad puede escribirse (DEC-016). Paso «Tu usuario»: el cargo es
 * obligatorio para saber quién registra la empresa.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $sectorId = is_numeric($input['sector_id'] ?? null) ? (int) $input['sector_id'] : null;
        $sectorConActividades = $sectorId !== null
            && ActividadEconomica::where('sector_id', $sectorId)->where('activo', true)->exists();

        Validator::make($input, [
            'empresa_nombre' => ['required', 'string', 'max:255'],
            'sector_id' => ['required', 'integer', Rule::exists('sectores', 'id')->where('activo', true)],
            'actividad_economica_id' => [
                Rule::requiredIf($sectorConActividades),
                'nullable',
                'integer',
                Rule::exists('actividades_economicas', 'id')->where('sector_id', $sectorId)->where('activo', true),
            ],
            'descripcion' => ['required', 'string', 'max:300'],
            'pais' => ['required', 'string', Rule::in(Ubicaciones::nombresDePaises())],
            'departamento' => ['required', 'string', new DepartamentoDelPais($input['pais'] ?? null)],
            // La ciudad puede no estar en la lista: se acepta escrita (DEC-016).
            'ciudad' => ['required', 'string', 'max:255'],
            ...$this->profileRules(),
            'cargo' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'password' => $this->passwordRules(),
            'terminos' => ['accepted'],
        ], [], [
            'empresa_nombre' => 'nombre de la empresa',
            'sector_id' => 'sector',
            'actividad_economica_id' => 'actividad económica',
            'descripcion' => 'descripción corta',
            'pais' => 'país',
            'name' => 'nombre del usuario',
            'email' => 'correo',
            'terminos' => 'los términos de uso y la política de tratamiento de datos',
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $empresa = Empresa::create([
                'nombre' => $input['empresa_nombre'],
                'descripcion' => $input['descripcion'],
                'sector_id' => (int) $input['sector_id'],
                'actividad_economica_id' => isset($input['actividad_economica_id']) && $input['actividad_economica_id'] !== ''
                    ? (int) $input['actividad_economica_id']
                    : null,
                'departamento' => $input['departamento'],
                'ciudad' => trim($input['ciudad']),
                'pais' => $input['pais'],
            ]);

            $usuario = User::create([
                'empresa_id' => $empresa->id,
                'name' => $input['name'],
                'email' => $input['email'],
                'cargo' => $input['cargo'],
                'telefono' => $input['telefono'] ?? null,
                'password' => $input['password'],
            ]);

            // Constancia de que aceptó los términos y la política de datos.
            $usuario->forceFill(['terminos_aceptados_en' => now()])->save();

            $usuario->assignRole('Empresa');

            return $usuario;
        });
    }
}
