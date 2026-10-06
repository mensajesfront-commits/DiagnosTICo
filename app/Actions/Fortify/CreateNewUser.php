<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Empresa;
use App\Models\User;
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
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'empresa_nombre' => ['required', 'string', 'max:255'],
            'sector_id' => ['required', 'integer', Rule::exists('sectores', 'id')->where('activo', true)],
            'ciudad' => ['required', 'string', 'max:255'],
            'pais' => ['required', 'string', 'max:255'],
            ...$this->profileRules(),
            'cargo' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'password' => $this->passwordRules(),
            'terminos' => ['accepted'],
        ], [], [
            'empresa_nombre' => 'nombre de la empresa',
            'sector_id' => 'sector',
            'name' => 'nombre del usuario',
            'email' => 'correo',
            'terminos' => 'términos de uso',
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $empresa = Empresa::create([
                'nombre' => $input['empresa_nombre'],
                'sector_id' => (int) $input['sector_id'],
                'ciudad' => $input['ciudad'],
                'pais' => $input['pais'],
            ]);

            $usuario = User::create([
                'empresa_id' => $empresa->id,
                'name' => $input['name'],
                'email' => $input['email'],
                'cargo' => $input['cargo'] ?? null,
                'telefono' => $input['telefono'] ?? null,
                'password' => $input['password'],
            ]);

            $usuario->assignRole('Empresa');

            return $usuario;
        });
    }
}
