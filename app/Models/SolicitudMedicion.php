<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * La empresa pide una medición nueva porque la suya venció (HU-082).
 *
 * @property int $id
 * @property int $empresa_id
 * @property string $estado abierta · atendida
 */
#[Table('solicitudes_medicion')]
#[Fillable(['empresa_id', 'medicion_vencida_id', 'estado', 'solicitada_por', 'atendida_en'])]
class SolicitudMedicion extends Model {}
