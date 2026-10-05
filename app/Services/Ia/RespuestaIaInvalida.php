<?php

namespace App\Services\Ia;

use RuntimeException;

/**
 * La IA respondió algo que el sistema no puede usar. Quien llama reintenta la
 * categoría (hasta 3 veces, RN-021).
 */
class RespuestaIaInvalida extends RuntimeException {}
