<?php

namespace App\Console\Commands;

use App\Support\Ubicaciones;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Genera resources/ubicaciones/{ISO}.json (departamentos y ciudades de los
 * países de App\Support\Ubicaciones) a partir del archivo
 * countries+states+cities.json de countries-states-cities-database.
 *
 * Uso: php artisan ubicaciones:generar ruta/countries+states+cities.json
 */
class GenerarUbicaciones extends Command
{
    protected $signature = 'ubicaciones:generar {archivo : countries+states+cities.json descargado}';

    protected $description = 'Genera los departamentos y ciudades de los países hispanohablantes';

    /** Nombres que la fuente trae en inglés o con otra forma. */
    private const array NOMBRES = [
        'AR' => ['Autonomous City of Buenos Aires' => 'Ciudad Autónoma de Buenos Aires'],
        'CU' => ['Havana' => 'La Habana'],
        'NI' => [
            'North Caribbean Coast' => 'Región Autónoma de la Costa Caribe Norte',
            'South Caribbean Coast' => 'Región Autónoma de la Costa Caribe Sur',
        ],
        'PA' => [
            'Chiriquí Province' => 'Chiriquí',
            'Emberá-Wounaan Comarca' => 'Comarca Emberá-Wounaan',
            'Guna' => 'Comarca Guna Yala',
            'Naso Tjër Di' => 'Comarca Naso Tjër Di',
            'Ngöbe-Buglé Comarca' => 'Comarca Ngäbe-Buglé',
        ],
        'PY' => ['Asuncion' => 'Asunción'],
        'PE' => [
            'Huanuco' => 'Huánuco',
            'Municipalidad Metropolitana de Lima' => 'Lima Metropolitana',
        ],
        'VE' => ['Federal Dependencies' => 'Dependencias Federales'],
    ];

    /** Ciudades que la fuente trae en inglés. */
    private const array CIUDADES = [
        'AR' => ['Iriondo Department' => 'Departamento Iriondo'],
        'CU' => ['Havana' => 'La Habana'],
        'GT' => ['Guatemala City' => 'Ciudad de Guatemala'],
        'MX' => ['Mexico City' => 'Ciudad de México'],
        'PA' => ['Panama City' => 'Ciudad de Panamá'],
    ];

    /** Divisiones que no son de primer nivel (regiones de desarrollo de R. Dominicana). */
    private const array TIPOS_EXCLUIDOS = ['region' => ['DO']];

    public function handle(): int
    {
        $archivo = (string) $this->argument('archivo');

        if (! File::exists($archivo)) {
            $this->error("No existe {$archivo}.");

            return self::FAILURE;
        }

        /** @var list<array{iso2: string, states: list<array{name: string, type: ?string, cities: list<array{name: string}>}>}> $paises */
        $paises = json_decode((string) File::get($archivo), true, flags: JSON_THROW_ON_ERROR);
        File::ensureDirectoryExists(resource_path('ubicaciones'));

        foreach ($paises as $pais) {
            $codigo = $pais['iso2'];

            if (! isset(Ubicaciones::PAISES[$codigo])) {
                continue;
            }

            $departamentos = [];

            foreach ($pais['states'] as $estado) {
                if (in_array($codigo, self::TIPOS_EXCLUIDOS[$estado['type'] ?? ''] ?? [], true)) {
                    continue;
                }

                $nombre = trim($estado['name']);
                $nombre = self::NOMBRES[$codigo][$nombre] ?? $nombre;

                $ciudades = array_values(array_unique(array_map(
                    fn (array $c): string => $this->ciudad($codigo, $c['name']),
                    $estado['cities'],
                )));
                usort($ciudades, fn (string $a, string $b): int => self::comparar($a, $b));

                $departamentos[] = ['nombre' => $nombre, 'ciudades' => $ciudades];
            }

            usort($departamentos, fn (array $a, array $b): int => self::comparar($a['nombre'], $b['nombre']));

            File::put(
                resource_path("ubicaciones/{$codigo}.json"),
                json_encode($departamentos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            );

            $total = array_sum(array_map(fn (array $d): int => count($d['ciudades']), $departamentos));
            $this->line(sprintf('%s · %d divisiones · %d ciudades', Ubicaciones::PAISES[$codigo][0], count($departamentos), $total));
        }

        return self::SUCCESS;
    }

    private function ciudad(string $codigo, string $nombre): string
    {
        $nombre = trim($nombre);
        $nombre = self::CIUDADES[$codigo][$nombre] ?? $nombre;

        // "Ameca Municipality" → "Ameca" (México).
        return (string) preg_replace('/ Municipality( \w+)?$/u', '', $nombre);
    }

    /** Orden alfabético sin que las tildes manden al final ("Áncash" junto a la A). */
    public static function comparar(string $a, string $b): int
    {
        return strcmp(Str::lower(Str::ascii($a)), Str::lower(Str::ascii($b))) ?: strcmp($a, $b);
    }
}
