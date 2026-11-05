<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ERR-02 (auditoría QA): dos cuentas no pueden tener el mismo correo con
 * distintas mayúsculas. Pasa los correos a minúsculas y crea un índice único
 * sobre lower(email). Si ya hay correos repetidos así, se detiene y los lista:
 * decidir cuál cuenta queda es de una persona, no de la migración.
 */
return new class extends Migration
{
    public function up(): void
    {
        $repetidos = DB::table('users')
            ->selectRaw('lower(trim(email)) as correo, count(*) as total')
            ->groupByRaw('lower(trim(email))')
            ->havingRaw('count(*) > 1')
            ->pluck('correo');

        if ($repetidos->isNotEmpty()) {
            throw new RuntimeException('Hay cuentas con el mismo correo en distintas mayúsculas; resuélvelas antes de migrar: '.$repetidos->implode(', '));
        }

        DB::table('users')->whereRaw('email <> lower(trim(email))')->update(['email' => DB::raw('lower(trim(email))')]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_email_lower_unique ON users (lower(email))');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS users_email_lower_unique');
        }
    }
};
