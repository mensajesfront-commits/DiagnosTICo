<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Plantilla general de un correo, como el aviso de medición (A3.1e).
 *
 * @property int $id
 * @property string $clave
 * @property string $asunto
 * @property string $cuerpo
 */
#[Table('plantillas_correo')]
#[Fillable(['clave', 'asunto', 'cuerpo', 'actualizado_por'])]
class PlantillaCorreo extends Model {}
