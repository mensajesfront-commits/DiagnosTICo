<?php

namespace App\Http\Controllers;

use App\Support\Ubicaciones;
use Illuminate\Http\JsonResponse;

/**
 * Departamentos y ciudades de un país para las listas de L2, A6 y E11. Es
 * pública porque el registro se hace sin sesión; los datos no son privados.
 */
class UbicacionesController extends Controller
{
    public function show(string $pais): JsonResponse
    {
        $codigo = strtoupper($pais);
        abort_unless(isset(Ubicaciones::PAISES[$codigo]), 404);

        return response()
            ->json(Ubicaciones::departamentos($codigo))
            ->setPublic()
            ->setMaxAge(86400);
    }
}
