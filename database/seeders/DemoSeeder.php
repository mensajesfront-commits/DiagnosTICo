<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Cuentas de demostración para probar el acceso en una presentación:
 *
 * - Restaurante La Esquina (activa): cuenta principal, un colaborador activo
 *   y uno desactivado (RN-004).
 * - Hostal Casa Verde (desactivada): su cuenta no puede entrar (RN-025).
 *
 * Uso: php artisan db:seed --class=DemoSeeder
 * No corre en producción. La contraseña sale de DEMO_PASSWORD.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->warn('DemoSeeder no corre en producción.');

            return;
        }

        $this->call([RolesYPermisosSeeder::class, SectoresSeeder::class]);

        $definida = (string) config('diagnostico.demo.password');
        $contrasena = $definida !== '' ? $definida : 'Demo-'.Str::password(8, symbols: false).'!';

        $comidas = Sector::where('nombre', 'Comidas')->firstOrFail();
        $alojamientos = Sector::where('nombre', 'Alojamientos')->firstOrFail();

        $esquina = Empresa::updateOrCreate(['nombre' => 'Restaurante La Esquina'], [
            'sector_id' => $comidas->id,
            'ciudad' => 'Cali',
            'pais' => 'Colombia',
            'activa' => true,
        ]);

        $casaVerde = Empresa::updateOrCreate(['nombre' => 'Hostal Casa Verde'], [
            'sector_id' => $alojamientos->id,
            'ciudad' => 'Salento',
            'pais' => 'Colombia',
            'activa' => false,
        ]);

        $cuentas = [
            ['laura@laesquina.co', 'Laura Gómez', 'Administradora', $esquina, 'Empresa', true],
            ['andres@laesquina.co', 'Andrés Pérez', 'Mesero', $esquina, 'Colaborador', true],
            ['diego@laesquina.co', 'Diego Torres', 'Cocinero', $esquina, 'Colaborador', false],
            ['info@casaverde.co', 'Julián Mora', 'Dueño', $casaVerde, 'Empresa', true],
        ];

        foreach ($cuentas as [$correo, $nombre, $cargo, $empresa, $rol, $activo]) {
            $usuario = User::updateOrCreate(['email' => $correo], [
                'name' => $nombre,
                'cargo' => $cargo,
                'empresa_id' => $empresa->id,
                'password' => $contrasena,
                'activo' => $activo,
            ]);
            $usuario->forceFill(['terminos_aceptados_en' => $usuario->terminos_aceptados_en ?? now()])->save();
            $usuario->syncRoles([$rol]);
        }

        $this->command->table(
            ['Correo', 'Rol', 'Empresa', 'Puede entrar'],
            array_map(fn (array $c) => [$c[0], $c[4], $c[3]->nombre, $c[5] && $c[3]->activa ? 'Sí' : 'No'], $cuentas),
        );
        $this->command->info('Contraseña de las cuentas de demostración: '.($definida !== '' ? '(la de DEMO_PASSWORD)' : $contrasena));
    }
}
