<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

/**
 * Las 10 categorías del catálogo (T-046), tomadas del wireframe A2.3.
 *
 * [INFORMACIÓN PENDIENTE] NuevasTIC debe confirmar los nombres y completar
 * las descripciones que el wireframe deja vacías.
 */
class CategoriasSeeder extends Seeder
{
    /** @var array<string, string|null> */
    public const array CATEGORIAS = [
        'Análisis del mercado' => null,
        'Análisis de la competencia' => null,
        'Análisis del cliente' => 'Qué tan bien conoces a tus clientes y cómo te relacionas con ellos.',
        'Análisis de la marca' => null,
        'Presencia en línea' => 'Dónde te encuentran tus clientes en internet: sitio web, redes y buscadores.',
        'Contenido y publicaciones' => null,
        'Herramientas de automatización' => null,
        'Análisis de la inversión' => null,
        'Medición de KPIs' => null,
        'Recursos humanos' => 'Cómo está organizado tu equipo para hacer marketing digital: quién lo hace, con qué formación y con qué apoyo.',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIAS as $nombre => $descripcion) {
            Categoria::firstOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }
    }
}
