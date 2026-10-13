<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Borra para siempre las cuentas y empresas eliminadas hace más de 90 días
 * (DEC-017): sus filas, sus roles, sus sesiones, su foto y su logo. Corre
 * cada día (routes/console.php).
 */
class PurgarCuentas extends Command
{
    protected $signature = 'cuentas:purgar';

    protected $description = 'Borra para siempre las cuentas eliminadas hace más de 90 días';

    public function handle(): int
    {
        $limite = now()->subDays((int) config('diagnostico.eliminacion.dias'));
        $archivos = [];
        $cuentas = 0;
        $empresas = 0;

        DB::transaction(function () use ($limite, &$archivos, &$cuentas, &$empresas): void {
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
                $empresa->forceDelete();
                $empresas++;
            }
        });

        Storage::disk('local')->delete(array_values(array_filter($archivos)));

        if ($cuentas + $empresas > 0) {
            Log::info('Cuentas borradas para siempre', ['cuentas' => $cuentas, 'empresas' => $empresas]);
        }

        $this->info("Borradas para siempre: {$cuentas} cuentas y {$empresas} empresas.");

        return self::SUCCESS;
    }
}
