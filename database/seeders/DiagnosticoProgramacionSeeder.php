<?php

namespace Database\Seeders;

use App\Models\Diagnostico;
use App\Models\Sector;
use App\Support\CatalogoCiiu;
use Illuminate\Database\Seeder;

/**
 * Diagnóstico DE PRUEBA para el sector "Programación y consultoría
 * informática" (CIIU 62). Sirve para responder una medición completa y
 * probar el análisis de la IA y el informe PDF con una empresa de software,
 * por ejemplo una app que ayuda a organizar el tiempo según los hobbies y las
 * responsabilidades de cada persona.
 *
 * 6 categorías del catálogo, 30 preguntas de los tres tipos, opciones "Otro"
 * y "Ninguna", "No sé" con indicación para la IA y criterios para las
 * abiertas. Las preguntas no son de NuevasTIC: los diagnósticos reales los
 * carga NuevasTIC.
 *
 * No lo corre DatabaseSeeder. Se carga a propósito:
 *   php artisan db:seed --class=DiagnosticoProgramacionSeeder
 * Crea el sector si no existe (con las actividades CIIU de la división 62).
 * Se puede correr de nuevo: si el diagnóstico ya existe, no hace nada.
 */
class DiagnosticoProgramacionSeeder extends Seeder
{
    use CreaDiagnosticoPublicado;

    public const string SECTOR = 'Programación y consultoría informática';

    public const string NOMBRE = 'Diagnóstico de prueba · Software y apps';

    private const string NO_SE = 'La empresa no conoce este dato: dilo en "faltante" y recomienda cómo medirlo.';

    /** @var array<string, array{0: int, 1: list<array<int, mixed>>}> */
    private const array CONTENIDO = [
        'Presencia en línea' => [20, [
            ['¿Tu producto tiene una página web o landing propia?', 'opcion_unica', true, 'Cuenta solo un sitio con dominio propio, no el perfil en una tienda de apps.', [
                ['Sí, explica el producto y tiene un botón claro para descargar o registrarse', 100],
                ['Sí, pero no explica bien qué hace el producto', 50],
                ['Solo una página en construcción', 20],
                ['No tenemos', 0],
            ]],
            ['¿Dónde se puede descargar o usar tu producto?', 'seleccion_multiple', true, 'Marca todos los canales disponibles hoy.', [
                ['Google Play', 30], ['App Store', 30], ['Versión web', 30], ['Extensión de navegador o escritorio', 10], ['Otro', 10, 'otro'],
            ]],
            ['¿Cómo está la ficha de tu app en las tiendas (capturas, video, descripción)?', 'opcion_unica', true, 'Revisa la ficha de Google Play o App Store antes de responder.', [
                ['Completa y la actualizamos con cada versión', 100],
                ['Completa, pero no la hemos vuelto a revisar', 60],
                ['Le faltan capturas o la descripción es corta', 30],
                ['No tenemos app en tiendas', 0, null, 'No hay app en tiendas: evalúa solo la versión web.'],
            ]],
            ['Si alguien busca en Google "app para organizar mi tiempo", ¿aparece tu producto?', 'opcion_unica', true, 'Haz la búsqueda en una ventana de incógnito.', [
                ['Sí, en la primera página', 100], ['Sí, pero después de la primera página', 50], ['No aparece', 0], ['No sé', 0, null, self::NO_SE],
            ]],
            ['¿En qué redes sociales tiene perfil el producto?', 'seleccion_multiple', true, 'Marca solo las que usan para el producto, no las personales.', [
                ['Instagram', 25], ['TikTok', 25], ['LinkedIn', 20], ['YouTube', 20], ['X (Twitter)', 10], ['Otra', 10, 'otro'], ['Ninguna', 0, 'ninguna'],
            ]],
            ['¿Responden las reseñas de los usuarios en las tiendas de apps?', 'opcion_unica', true, 'Piensa en las reseñas de los últimos tres meses.', [
                ['Todas, en menos de 48 horas', 100], ['Solo las negativas', 50], ['Casi nunca', 10], ['No tenemos reseñas todavía', 0, null, 'El producto aún no tiene reseñas: es una oportunidad para pedirlas.'],
            ]],
            ['Cuéntanos qué ve una persona que encuentra tu producto por primera vez y qué la convence de probarlo.', 'abierta', true, 'Describe el camino: dónde lo encuentra, qué lee y qué hace después.', [],
                'Califica alto si el camino es claro (dónde lo encuentran, qué mensaje ven y qué acción toman) y hay algo que lo hace distinto. Califica bajo si no saben cómo llegan los usuarios.'],
        ]],
        'Análisis del cliente' => [20, [
            ['¿Tienen definidos los tipos de usuario de tu producto (por ejemplo, estudiantes, profesionales, padres)?', 'opcion_unica', true, 'Un tipo de usuario definido tiene edad, situación y una necesidad concreta.', [
                ['Sí, por escrito y los usamos para decidir', 100], ['Los tenemos en mente, pero no por escrito', 50], ['No', 0],
            ]],
            ['¿Cómo conocen las necesidades de sus usuarios?', 'seleccion_multiple', true, 'Marca todo lo que hicieron en los últimos seis meses.', [
                ['Entrevistas con usuarios', 30], ['Encuestas dentro de la app', 25], ['Análisis de reseñas', 20], ['Datos de uso de la app', 25], ['Ninguna', 0, 'ninguna'],
            ]],
            ['Al registrarse, ¿la app le pregunta al usuario por sus hobbies y responsabilidades para personalizar su plan?', 'opcion_unica', true, 'Piensa en el primer uso de la app.', [
                ['Sí, y lo usamos para recomendar cómo organizar su tiempo', 100], ['Sí, pero todavía no lo usamos', 50], ['No', 0],
            ]],
            ['¿Qué porcentaje de usuarios sigue usando la app 30 días después de registrarse?', 'opcion_unica', true, 'Es la retención a 30 días. Usa el dato de tu herramienta de analítica.', [
                ['Más del 40 %', 100], ['Entre 20 % y 40 %', 70], ['Menos del 20 %', 30], ['No lo medimos', 0, null, self::NO_SE],
            ]],
            ['¿Cómo recogen las opiniones de los usuarios después de usar la app?', 'seleccion_multiple', false, 'Marca todos los que funcionan hoy.', [
                ['Botón de comentarios en la app', 30], ['Encuesta de satisfacción (NPS)', 30], ['Comunidad o grupo de usuarios', 30], ['Correo de soporte', 10], ['No las recogemos', 0, 'ninguna'],
            ]],
            ['Describe a tu usuario ideal: quién es, cómo es su semana y qué problema le resuelve tu app.', 'abierta', true, 'Por ejemplo: edad, ocupación, hobbies, qué le cuesta organizar y por qué usaría la app.', [],
                'Califica alto si describe un usuario concreto (edad, ocupación, hobbies y responsabilidades) y el problema que le resuelve. Califica bajo si dice "todo el mundo" o no menciona el problema.'],
        ]],
        'Contenido y publicaciones' => [15, [
            ['¿Quién crea el contenido del producto?', 'opcion_unica', true, 'Elige quién lo hace la mayoría de las veces.', [
                ['Una persona o agencia dedicada a eso', 100], ['Alguien del equipo de desarrollo, cuando tiene tiempo', 40], ['Nadie en particular', 0],
            ]],
            ['¿Qué tipo de contenido publican?', 'seleccion_multiple', true, 'Marca todos los que publicaron en el último mes.', [
                ['Consejos para organizar el tiempo', 25], ['Tutoriales de la app', 25], ['Historias de usuarios', 25], ['Novedades de cada versión', 15], ['Retos o planes semanales', 10], ['Otro', 10, 'otro'],
            ]],
            ['¿Con qué frecuencia publican?', 'opcion_unica', true, 'Piensa en los últimos tres meses.', [
                ['Varias veces por semana', 100], ['Una vez por semana', 70], ['Una o dos veces al mes', 40], ['Casi nunca', 0],
            ]],
            ['¿Tienen un blog o artículos que respondan búsquedas como "cómo organizar mi semana"?', 'opcion_unica', false, 'Cuenta solo artículos publicados en su propio sitio.', [
                ['Sí, publicamos al menos uno al mes', 100], ['Tenemos algunos, pero no seguimos publicando', 40], ['No', 0],
            ]],
            ['¿Cuál es el mensaje principal que quieren que la gente recuerde de tu producto?', 'abierta', true, 'Escríbelo como lo dirías en una frase.', [],
                'Califica alto si el mensaje es una frase clara que dice para quién es y qué beneficio da. Califica bajo si es genérico ("la mejor app") o no tienen uno.'],
        ]],
        'Análisis de la competencia' => [10, [
            ['¿Tienen identificados a sus competidores directos?', 'opcion_unica', true, 'Piensa en apps de calendario, listas de tareas, hábitos o productividad.', [
                ['Sí, sabemos cuáles son y qué ofrecen', 100], ['Conocemos algunos de nombre', 50], ['No', 0],
            ]],
            ['¿Qué revisan de la competencia?', 'seleccion_multiple', true, 'Marca lo que revisaron en los últimos seis meses.', [
                ['Precios y planes', 25], ['Funciones nuevas', 25], ['Reseñas de sus usuarios', 25], ['Su publicidad y contenido', 25], ['Nada', 0, 'ninguna'],
            ]],
            ['¿Qué tiene tu producto que no tengan Google Calendar, Notion, Todoist u otras apps parecidas?', 'abierta', true, 'Sé concreto: una función, una forma de uso o un tipo de usuario.', [],
                'Califica alto si nombra una diferencia concreta y verificable (por ejemplo, que el plan se arma según los hobbies). Califica bajo si no hay diferencia clara o solo habla de precio.'],
        ]],
        'Herramientas de automatización' => [15, [
            ['¿Envían notificaciones o correos automáticos según lo que hace cada usuario?', 'opcion_unica', true, 'Por ejemplo, un recordatorio si alguien no abre la app en una semana.', [
                ['Sí, según su comportamiento', 100], ['Sí, pero los mismos para todos', 50], ['No', 0],
            ]],
            ['¿Qué herramientas usan?', 'seleccion_multiple', true, 'Marca las que usan hoy para marketing o relación con usuarios.', [
                ['Correo automático (Mailchimp, Brevo…)', 25], ['Notificaciones push (Firebase, OneSignal…)', 25], ['CRM', 20], ['Chat de soporte', 15], ['Programador de publicaciones', 15], ['Otra', 10, 'otro'], ['Ninguna', 0, 'ninguna'],
            ]],
            ['¿El primer uso de la app (onboarding) guía al usuario hasta crear su primer plan?', 'opcion_unica', true, 'El primer plan es el momento en que el usuario ve el valor de la app.', [
                ['Sí, paso a paso y lo medimos', 100], ['Sí, pero no medimos cuántos lo terminan', 60], ['No, el usuario lo descubre solo', 0],
            ]],
            ['¿La app se conecta con otras herramientas (calendario, correo, recordatorios)?', 'opcion_unica', false, 'Cuenta solo integraciones que funcionan hoy.', [
                ['Sí, con varias', 100], ['Con una', 50], ['No', 0],
            ]],
        ]],
        'Medición de KPIs' => [20, [
            ['¿Qué datos miden con regularidad?', 'seleccion_multiple', true, 'Marca los que revisan al menos una vez al mes.', [
                ['Descargas o registros', 20], ['Usuarios activos', 20], ['Retención', 20], ['Fuente de cada usuario (de dónde llegó)', 20], ['Costo por usuario conseguido', 20], ['Ninguno', 0, 'ninguna'],
            ]],
            ['¿Cuántos usuarios activos tiene la app al mes?', 'opcion_unica', true, 'Usuarios que abrieron la app al menos una vez en los últimos 30 días.', [
                ['Más de 5.000', 100], ['Entre 500 y 5.000', 70], ['Menos de 500', 40], ['No lo sabemos', 0, null, self::NO_SE],
            ]],
            ['¿Con qué frecuencia revisan esos datos para tomar decisiones?', 'opcion_unica', true, 'Revisar es mirar los datos y decidir algo con ellos.', [
                ['Cada semana', 100], ['Cada mes', 70], ['Cuando hay un problema', 30], ['Nunca', 0],
            ]],
            ['¿Invierten en publicidad digital?', 'opcion_unica', true, 'Piensa en los últimos tres meses.', [
                ['Sí, y medimos cuántos usuarios trae cada campaña', 100],
                ['Sí, pero no medimos el resultado', 30, null, 'Invierte sin medir: prioriza medir el costo por usuario.'],
                ['No invertimos todavía', 50, null, 'No hay publicidad: no recomiendes metas de inversión sin datos.'],
            ]],
            ['¿Qué dato te gustaría conocer y hoy no tienes?', 'abierta', false, 'Por ejemplo: por qué se van los usuarios o qué función usan más.', [],
                'Califica alto si nombra un dato concreto y para qué lo usaría. Califica bajo si no sabe qué medir.'],
        ]],
    ];

    public function run(): void
    {
        $sector = Sector::firstOrCreate(
            ['nombre' => self::SECTOR],
            ['descripcion' => 'Desarrollo de software, apps y consultoría informática.', 'activo' => true],
        );

        if ($sector->wasRecentlyCreated) {
            CatalogoCiiu::asignarDivision($sector, '62');
        }

        if (Diagnostico::where('nombre', self::NOMBRE)->where('sector_id', $sector->id)->exists()) {
            $this->command->info('El diagnóstico de prueba ya existe.');

            return;
        }

        $this->crearDiagnostico($sector, self::NOMBRE, 'Diagnóstico de prueba para empresas de software y apps. No es un diagnóstico de NuevasTIC.', self::CONTENIDO);

        $this->command->info('Diagnóstico de prueba publicado en el sector «'.self::SECTOR.'». Registra una empresa de ese sector (A3.2) y asígnale una medición.');
    }
}
