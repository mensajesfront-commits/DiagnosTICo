<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use App\Support\DatosHistorial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * E8 · Mi historial (HU-067), para la empresa y su colaborador
 * (`resultados.ver`, solo su empresa), y A3.4 · Historial de una empresa
 * (HU-039) para el equipo interno (`empresas.ver`). Es la misma pantalla.
 */
class HistorialController extends Controller
{
    public function mio(Request $request): Response
    {
        /** @var User $yo */
        $yo = $request->user();
        abort_if($yo->empresa_id === null, 404);

        return Inertia::render('historial/Historial', [
            ...DatosHistorial::de(Empresa::findOrFail($yo->empresa_id)),
            'interno' => false,
        ]);
    }

    public function empresa(Empresa $empresa): Response
    {
        return Inertia::render('historial/Historial', [
            ...DatosHistorial::de($empresa),
            'interno' => true,
        ]);
    }
}
