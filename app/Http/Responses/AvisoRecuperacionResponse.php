<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

/**
 * L3 · RN-005: "¿Olvidaste tu contraseña?" responde lo mismo exista o no el
 * correo, y también si ya se pidió un enlace hace poco. Así nadie puede saber
 * qué correos están registrados.
 *
 * Solo un correo mal escrito (validación del formulario) muestra un error.
 */
class AvisoRecuperacionResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public function __construct(protected string $status = '') {}

    /**
     * @param  Request  $request
     *
     * @throws ValidationException
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $mensaje = trans('passwords.sent');

        return $request->wantsJson()
            ? new JsonResponse(['message' => $mensaje], 200)
            : back()->with('status', $mensaje);
    }
}
