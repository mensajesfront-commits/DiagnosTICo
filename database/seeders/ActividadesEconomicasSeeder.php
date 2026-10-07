<?php

namespace Database\Seeders;

use App\Models\ActividadEconomica;
use App\Models\Sector;
use Illuminate\Database\Seeder;

/**
 * Actividades económicas de cada sector, con su código CIIU Rev. 4 A.C.
 * (clasificación que usa la DIAN en el RUT).
 *
 * [INFORMACIÓN PENDIENTE] Qué códigos van en cada sector lo propuso el
 * equipo de desarrollo; NuevasTIC debe confirmarlo. Un sector sin
 * actividades no pide la actividad en el registro.
 */
class ActividadesEconomicasSeeder extends Seeder
{
    /** @var array<string, array<int, string>> sector → [código → nombre] */
    public const ACTIVIDADES = [
        'Abogados' => [
            '6910' => 'Actividades jurídicas',
        ],
        'Inmobiliarias' => [
            '6810' => 'Actividades inmobiliarias realizadas con bienes propios o arrendados',
            '6820' => 'Actividades inmobiliarias realizadas a cambio de una retribución o por contrata',
        ],
        'Médicos' => [
            '8610' => 'Actividades de hospitales y clínicas, con internación',
            '8621' => 'Actividades de la práctica médica, sin internación',
            '8622' => 'Actividades de la práctica odontológica',
            '8691' => 'Actividades de apoyo diagnóstico',
            '8692' => 'Actividades de apoyo terapéutico',
            '8699' => 'Otras actividades de atención de la salud humana',
        ],
        'Comidas' => [
            '5611' => 'Expendio a la mesa de comidas preparadas',
            '5612' => 'Expendio por autoservicio de comidas preparadas',
            '5613' => 'Expendio de comidas preparadas en cafeterías',
            '5619' => 'Otros tipos de expendio de comidas preparadas n.c.p.',
            '5621' => 'Catering para eventos',
            '5629' => 'Actividades de otros servicios de comidas',
            '5630' => 'Expendio de bebidas alcohólicas para el consumo dentro del establecimiento',
        ],
        'Alojamientos' => [
            '5511' => 'Alojamiento en hoteles',
            '5512' => 'Alojamiento en apartahoteles',
            '5513' => 'Alojamiento en centros vacacionales',
            '5514' => 'Alojamiento rural',
            '5519' => 'Otros tipos de alojamientos para visitantes',
            '5520' => 'Actividades de zonas de camping y parques para vehículos recreacionales',
            '5590' => 'Otros tipos de alojamiento n.c.p.',
        ],
        'Turismo' => [
            '7911' => 'Actividades de las agencias de viaje',
            '7912' => 'Actividades de operadores turísticos',
            '7990' => 'Otros servicios de reserva y actividades relacionadas',
        ],
        'Talleres' => [
            '4520' => 'Mantenimiento y reparación de vehículos automotores',
            '4542' => 'Mantenimiento y reparación de motocicletas y de sus partes y piezas',
            '3311' => 'Mantenimiento y reparación especializado de productos elaborados en metal',
            '3312' => 'Mantenimiento y reparación especializado de maquinaria y equipo',
            '9511' => 'Mantenimiento y reparación de computadores y de equipo periférico',
            '9521' => 'Mantenimiento y reparación de aparatos electrónicos de consumo',
        ],
    ];

    public function run(): void
    {
        foreach (self::ACTIVIDADES as $nombreSector => $actividades) {
            $sector = Sector::where('nombre', $nombreSector)->first();

            if ($sector === null) {
                continue;
            }

            foreach ($actividades as $codigo => $nombre) {
                ActividadEconomica::updateOrCreate(
                    ['sector_id' => $sector->id, 'codigo' => (string) $codigo],
                    ['nombre' => $nombre],
                );
            }
        }
    }
}
