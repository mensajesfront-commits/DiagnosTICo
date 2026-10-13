<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Support\CatalogoCiiu;
use Illuminate\Database\Seeder;

/**
 * Subsectores (actividades económicas) de los sectores iniciales, tomados
 * del catálogo CIIU Rev. 5 A.C. del DANE (DEC-018). Los nombres salen del
 * catálogo; aquí solo se dice qué división o qué clases usa cada sector.
 *
 * Las actividades que un sector tenía con la CIIU Rev. 4 y ya no existen en
 * la Rev. 5 (6820, 5514, 5519, 5520) quedan desactivadas, no se borran.
 *
 * [INFORMACIÓN PENDIENTE] NuevasTIC debe confirmar qué va en cada sector.
 */
class ActividadesEconomicasSeeder extends Seeder
{
    /** Sector → división CIIU entera (todas sus clases). */
    public const array DIVISIONES = [
        'Alojamientos' => '55',
        'Comidas' => '56',
        'Inmobiliarias' => '68',
        'Turismo' => '79',
        'Médicos' => '86',
    ];

    /** Sector → solo algunas clases (la división 69 incluye contabilidad). */
    public const array CLASES = [
        'Abogados' => ['6910'],
    ];

    public function run(): void
    {
        $this->call(CiiuSeeder::class);

        foreach (self::DIVISIONES as $nombre => $division) {
            $sector = Sector::where('nombre', $nombre)->first();

            if ($sector !== null) {
                CatalogoCiiu::asignarDivision($sector, $division);
            }
        }

        foreach (self::CLASES as $nombre => $clases) {
            $sector = Sector::where('nombre', $nombre)->first();

            if ($sector !== null) {
                CatalogoCiiu::asignarClases($sector, $clases);
            }
        }
    }
}
