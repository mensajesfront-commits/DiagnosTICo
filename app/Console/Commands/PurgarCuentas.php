<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\User;
use App\Support\Anonimizar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Borra para siempre, cada día (routes/console.php):
 * - las cuentas y empresas eliminadas hace más de 90 días (DEC-017): sus
 *   filas, sus roles, sus sesiones, su foto y su logo;
 * - los registros que no verificaron el correo en 7 días (L2b, DEC-020):
 *   la cuenta y su empresa, si la empresa no tiene otra cuenta verificada.
 *   Las invitaciones (sin contraseña) no cuentan como registro.
 *
 * Antes de borrar una empresa, sus mediciones terminadas se conservan sin
 * nombre para las estadísticas (Anonimizar, decisión de NuevasTIC).
 */
class PurgarCuentas extends Command
{
    protected $signature = 'cuentas:purgar';

    protected $description = 'Borra para siempre las cuentas eliminadas hace más de 90 días y los registros sin verificar de más de 7 días';

    public function handle(): int
    {
        $limite = now()->subDays((int) config('diagnostico.eliminacion.dias'));
        $sinVerificar = now()->subDays((int) config('diagnostico.sin_verificar.dias'));
        $archivos = [];
        $cuentas = 0;
        $empresas = 0;
        $registros = 0;

        DB::transaction(function () use ($limite, $sinVerificar, &$archivos, &$cuentas, &$empresas, &$registros): void {
            // Registros sin verificar (L2b): se borran con su empresa.
            $pendientes = User::withTrashed()
                ->whereNull('email_verified_at')
                ->whereNotNull('password')
                ->where('created_at', '<=', $sinVerificar)
                ->get();

            foreach ($pendientes as $usuario) {
                $empresa = $usuario->empresa_id ? Empresa::withTrashed()->find($usuario->empresa_id) : null;
                $archivos[] = $usuario->foto_ruta;
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
                DB::table('password_reset_tokens')->where('email', $usuario->email)->delete();
                $usuario->forceDelete();
                $registros++;

                if ($empresa && ! $empresa->usuarios()->withTrashed()->exists()) {
                    $archivos[] = $empresa->logo_ruta;
                    array_push($archivos, ...Anonimizar::medicionesDe($empresa));
                    $empresa->forceDelete();
                    $empresas++;
                }
            }

            foreach (User::onlyTrashed()->where('deleted_at', '<=', $limite)->get() as $usuario) {
                $archivos[] = $usuario->foto_ruta;
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
                DB::table('password_reset_tokens')->where('email', $usuario->email)->delete();
                // forceDelete también quita sus roles (HasRoles).
                $usuario->forceDelete();
                $cuentas++;
            }

            $vencidas = Empresa::onlyTrashed()
                ->where('deleted_at', '<=', $limite)
                ->whereDoesntHave('usuarios', fn ($q) => $q->withTrashed())
                ->get();

            foreach ($vencidas as $empresa) {
                $archivos[] = $empresa->logo_ruta;
                array_push($archivos, ...Anonimizar::medicionesDe($empresa));
                $empresa->forceDelete();
                $empresas++;
            }
        });

        Storage::disk('local')->delete(array_values(array_filter($archivos)));

        if ($cuentas + $empresas + $registros > 0) {
            Log::info('Cuentas borradas para siempre', ['cuentas' => $cuentas, 'empresas' => $empresas, 'registros_sin_verificar' => $registros]);
        }

        $this->info("Borradas para siempre: {$cuentas} cuentas, {$registros} registros sin verificar y {$empresas} empresas.");

        return self::SUCCESS;
    }
}
