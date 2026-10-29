<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Diagnostico;
use App\Models\DiagnosticoCategoria;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Sector;
use App\Models\VersionDiagnostico;
use App\Services\Diagnosticos\ContenidoDiagnostico;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Diagnóstico de EJEMPLO para ver cómo se responde (E2): sector Comidas, 3
 * categorías y una con 7 preguntas de los tres tipos. Las preguntas no son
 * de NuevasTIC: los diagnósticos reales los carga NuevasTIC a mano.
 *
 * No lo corre DatabaseSeeder. Se carga a propósito:
 *   php artisan db:seed --class=DiagnosticoEjemploSeeder
 * Se puede correr de nuevo: si ya existe, no hace nada.
 */
class DiagnosticoEjemploSeeder extends Seeder
{
    public const string NOMBRE = 'Diagnóstico de ejemplo';

    /**
     * Categoría del catálogo => [importancia, preguntas]. Cada pregunta:
     * [texto, tipo, obligatoria, indicación ("Cómo responder"), opciones]. Cada opción:
     * [texto, puntaje, marca] con marca 'otro' (pide escribir cuál) o
     * 'ninguna' (excluye a las demás).
     *
     * @var array<string, array{0: int, 1: list<array{0: string, 1: string, 2: bool, 3: string, 4: list<array{0: string, 1: int, 2?: string}>}>}>
     */
    private const array CONTENIDO = [
        'Presencia en línea' => [40, [
            ['¿Tu negocio tiene página web propia?', 'opcion_unica', true, 'Cuenta solo una página con dominio propio, no un perfil de red social.', [
                ['Sí, y la actualizamos seguido', 100], ['Sí, pero está desactualizada', 50], ['No tenemos', 0],
            ]],
            ['¿En qué redes sociales tiene perfil tu negocio?', 'seleccion_multiple', true, 'Marca solo las que usan para el negocio, no las personales.', [
                ['Facebook', 20], ['Instagram', 20], ['TikTok', 20], ['WhatsApp Business', 20], ['Google Mi Negocio', 20], ['Otra', 10, 'otro'], ['Ninguna', 0, 'ninguna'],
            ]],
            ['¿Con qué frecuencia publican en redes sociales?', 'opcion_unica', true, 'Piensa en los últimos tres meses.', [
                ['Varias veces por semana', 100], ['Una vez por semana', 70], ['Una o dos veces al mes', 40], ['Casi nunca', 0],
            ]],
            ['¿Tu negocio aparece en Google Maps con horario, fotos y teléfono?', 'opcion_unica', true, 'Busca el nombre de tu negocio en Google Maps antes de responder.', [
                ['Sí, con todo completo', 100], ['Sí, pero le falta información', 50], ['No aparece', 0], ['No sé', 0],
            ]],
            ['¿En cuánto tiempo responden los mensajes y comentarios de los clientes?', 'opcion_unica', true, 'Elige lo que pasa la mayoría de las veces.', [
                ['El mismo día', 100], ['En dos o tres días', 50], ['Pocas veces respondemos', 20], ['No respondemos', 0],
            ]],
            ['¿Por qué medios pueden pedir o reservar en línea tus clientes?', 'seleccion_multiple', false, 'Marca todos los que funcionan hoy.', [
                ['WhatsApp', 25], ['Página web', 25], ['Apps de domicilios (Rappi, iFood…)', 25], ['Redes sociales', 25], ['Ninguno', 0, 'ninguna'],
            ]],
            ['Cuéntanos qué te gustaría mejorar de la presencia en línea de tu negocio.', 'abierta', false, 'Escribe con tus palabras; no hay respuestas buenas o malas.', []],
        ]],
        'Contenido y publicaciones' => [30, [
            ['¿Quién crea el contenido que publican?', 'opcion_unica', true, 'Elige quién lo hace la mayoría de las veces.', [
                ['Una persona o agencia dedicada a eso', 100], ['Alguien del equipo, cuando tiene tiempo', 50], ['Nadie en particular', 0],
            ]],
            ['¿Qué tipo de contenido publican?', 'seleccion_multiple', true, 'Marca todos los que publicaron en el último mes.', [
                ['Fotos de los productos', 25], ['Videos cortos', 25], ['Promociones y descuentos', 25], ['Opiniones de clientes', 25], ['Otro', 10, 'otro'],
            ]],
            ['¿Tienen un calendario de publicaciones?', 'opcion_unica', false, 'Un calendario dice qué se publica y cuándo.', [
                ['Sí, lo seguimos', 100], ['Lo tenemos, pero no lo seguimos', 40], ['No', 0],
            ]],
        ]],
        'Análisis del cliente' => [30, [
            ['¿Saben quiénes son sus clientes más frecuentes?', 'opcion_unica', true, 'Por ejemplo, su nombre y qué compran.', [
                ['Sí, tenemos una base de datos', 100], ['Los conocemos de vista', 50], ['No', 0],
            ]],
            ['¿Cómo recogen las opiniones de sus clientes?', 'seleccion_multiple', true, 'Marca todas las que usan.', [
                ['Encuestas', 30], ['Reseñas en Google', 30], ['Comentarios en redes', 30], ['No las recogemos', 0, 'ninguna'],
            ]],
            ['Describe a tu cliente ideal: quién es y qué busca.', 'abierta', false, 'Por ejemplo: edad, dónde vive, qué le gusta y por qué te compra.', []],
        ]],
    ];

    public function run(): void
    {
        $sector = Sector::where('nombre', 'Comidas')->first();

        if ($sector === null) {
            $this->command->warn('No existe el sector Comidas: corre primero php artisan db:seed.');

            return;
        }

        if (Diagnostico::where('nombre', self::NOMBRE)->where('sector_id', $sector->id)->exists()) {
            $this->command->info('El diagnóstico de ejemplo ya existe.');

            return;
        }

        DB::transaction(function () use ($sector): void {
            $diagnostico = Diagnostico::create([
                'sector_id' => $sector->id,
                'nombre' => self::NOMBRE,
                'descripcion' => 'Ejemplo para ver cómo responde una empresa. No es un diagnóstico de NuevasTIC.',
                'estado' => Diagnostico::PUBLICADO,
                'version_borrador' => null,
            ]);

            $orden = 0;

            foreach (self::CONTENIDO as $nombre => [$importancia, $preguntas]) {
                $categoria = Categoria::firstOrCreate(['nombre' => $nombre]);
                $parte = DiagnosticoCategoria::create([
                    'diagnostico_id' => $diagnostico->id,
                    'categoria_id' => $categoria->id,
                    'importancia' => $importancia,
                    'orden' => $orden++,
                ]);

                foreach ($preguntas as $i => [$texto, $tipo, $obligatoria, $indicacion, $opciones]) {
                    $pregunta = Pregunta::create([
                        'diagnostico_categoria_id' => $parte->id,
                        'texto' => $texto,
                        'tipo' => $tipo,
                        'indicacion' => $indicacion,
                        'obligatoria' => $obligatoria,
                        'orden' => $i,
                    ]);

                    foreach ($opciones as $j => $opcion) {
                        Opcion::create([
                            'pregunta_id' => $pregunta->id,
                            'texto' => $opcion[0],
                            'puntaje' => $opcion[1],
                            'con_texto' => ($opcion[2] ?? null) === 'otro',
                            'exclusiva' => ($opcion[2] ?? null) === 'ninguna',
                            'orden' => $j,
                        ]);
                    }
                }
            }

            VersionDiagnostico::create([
                'diagnostico_id' => $diagnostico->id,
                'numero' => 1,
                'contenido' => app(ContenidoDiagnostico::class)->congelar($diagnostico),
                'publicada_en' => now(),
            ]);
        });

        $this->command->info('Diagnóstico de ejemplo publicado en el sector Comidas. Asígnalo a una empresa de ese sector para responderlo.');
    }
}
