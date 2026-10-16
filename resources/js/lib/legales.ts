/**
 * Términos de uso y política de tratamiento de datos personales (L2).
 *
 * Se muestran en una ventana desde Crear cuenta y en las páginas públicas
 * /terminos-de-uso y /politica-de-datos. El texto describe lo que el sistema
 * hace de verdad (docs/17_SEGURIDAD.md, docs/06_REQUISITOS_NO_FUNCIONALES.md,
 * RNF-011) y sigue la Ley 1581 de 2012 y el Decreto 1074 de 2015.
 *
 * Lo que NuevasTIC no ha entregado queda marcado con [INFORMACIÓN PENDIENTE]
 * o [FUNCIONALIDAD POR DEFINIR] y se resalta en pantalla para que no pase
 * desapercibido. NuevasTIC (y su asesor legal) debe revisar el texto antes de
 * usarlo con empresas reales.
 */

export type SeccionLegal = {
    titulo: string;
    parrafos?: string[];
    lista?: string[];
    /** Párrafos que van después de la lista. */
    cierre?: string[];
};

export type DocumentoLegal = {
    clave: 'terminos' | 'politica';
    titulo: string;
    resumen: string;
    version: string;
    vigencia: string;
    secciones: SeccionLegal[];
};

const CORREO = 'soporte@nuevastic.co';
const PENDIENTE = '[INFORMACIÓN PENDIENTE]';

export const terminosDeUso: DocumentoLegal = {
    clave: 'terminos',
    titulo: 'Términos de uso',
    resumen:
        'Las reglas para usar Captter, el sistema de diagnóstico de marketing digital de NuevasTIC.',
    version: '1.0',
    vigencia: `${PENDIENTE} Fecha desde la que rige.`,
    secciones: [
        {
            titulo: '1. Quién presta el servicio',
            parrafos: [
                `Captter es un servicio de NuevasTIC (${PENDIENTE} razón social, NIT y domicilio) para medir el nivel de marketing digital de las empresas. En estos términos, "nosotros" es NuevasTIC y "tú" es la persona que crea o usa una cuenta en nombre de una empresa.`,
            ],
        },
        {
            titulo: '2. Aceptación',
            parrafos: [
                'Al marcar la casilla "Acepto los términos de uso y la política de tratamiento de datos" y crear la cuenta, aceptas estos términos. Guardamos la fecha y la hora en que los aceptaste.',
                'Quien registra la empresa declara que puede actuar en su nombre. Si no estás de acuerdo con estos términos, no crees la cuenta.',
            ],
        },
        {
            titulo: '3. Tu cuenta',
            lista: [
                'Cada empresa tiene una cuenta principal, la de quien la registró, y puede agregar colaboradores. La cuenta principal responde por lo que hagan sus colaboradores en el sistema.',
                'Los datos que registras deben ser verdaderos y estar al día. Puedes corregirlos en Mi perfil.',
                'Verificamos tu correo con un código. Si no lo verificas en 7 días, el registro se borra.',
                'Tu contraseña es personal. No la compartas: cada persona de la empresa debe tener su propia cuenta de colaborador.',
                'Si crees que alguien entró a tu cuenta, cambia la contraseña y escríbenos.',
            ],
        },
        {
            titulo: '4. Lo que no está permitido',
            lista: [
                'Hacerte pasar por otra persona o empresa.',
                'Entrar o intentar entrar a cuentas que no son tuyas, o a partes del sistema que tu rol no permite.',
                'Intentar vulnerar la seguridad, saturar el sistema o usar programas automáticos para usarlo.',
                'Escribir en las respuestas datos personales de otras personas, datos sensibles (salud, origen, creencias, datos biométricos y similares) o contenido ilegal u ofensivo.',
            ],
        },
        {
            titulo: '5. El diagnóstico y tu resultado',
            lista: [
                'El puntaje y el nivel se calculan con tus respuestas. Las observaciones y recomendaciones las escribe una inteligencia artificial (OpenAI) con las instrucciones que define NuevasTIC.',
                'La inteligencia artificial puede equivocarse. El resultado es una orientación y no reemplaza la asesoría de un profesional. Las decisiones que tomes con él son tuyas.',
                'El resultado depende de que respondas con la verdad.',
                'Un resultado publicado no se modifica. Para medirte de nuevo, se hace una medición nueva y así puedes ver cómo evolucionas.',
            ],
        },
        {
            titulo: '6. Lo que ve el equipo de NuevasTIC',
            parrafos: [
                'Los administradores de NuevasTIC ven los datos de tu empresa, tus respuestas y tus resultados para prestarte el servicio y acompañarte.',
                'Para ayudarte, un administrador puede "ver como" tu cuenta. Es solo de lectura: no puede cambiar nada, y queda registrado quién lo hizo y cuándo.',
                'Los administradores no conocen ni escriben tu contraseña.',
            ],
        },
        {
            titulo: '7. Propiedad intelectual',
            parrafos: [
                'El sistema, la marca, los diagnósticos, las preguntas y los textos son de NuevasTIC. No puedes copiarlos ni usarlos para otro servicio.',
                'Tus respuestas son tuyas. Puedes usar tu informe en PDF dentro de tu empresa como quieras.',
                '[FUNCIONALIDAD POR DEFINIR] Si NuevasTIC usará los resultados sin el nombre de la empresa para estadísticas, debe decirse aquí.',
            ],
        },
        {
            titulo: '8. Costo',
            parrafos: [
                `${PENDIENTE} Si el servicio es gratuito o tiene costo, y en qué condiciones.`,
            ],
        },
        {
            titulo: '9. Disponibilidad',
            parrafos: [
                'Hacemos lo posible para que el sistema funcione siempre, pero puede detenerse por mantenimiento o por fallas, propias o de proveedores como OpenAI. Si el análisis de una categoría falla, el sistema lo intenta de nuevo y puedes reintentarlo.',
            ],
        },
        {
            titulo: '10. Desactivación y eliminación de la cuenta',
            lista: [
                'Podemos desactivar una cuenta que incumpla estos términos. Una cuenta desactivada no puede entrar.',
                'Puedes pedir que eliminemos tu cuenta escribiendo a nuestro correo. Durante 90 días se puede recuperar; después se borra para siempre, con sus datos.',
                'Si se elimina la cuenta principal, también se eliminan las de los colaboradores de la empresa.',
            ],
        },
        {
            titulo: '11. Cambios en estos términos',
            parrafos: [
                'Si cambiamos estos términos, te avisaremos por correo o en el sistema antes de que rijan. La versión vigente siempre estará en esta página.',
            ],
        },
        {
            titulo: '12. Ley aplicable',
            parrafos: [
                `Estos términos se rigen por las leyes de la República de Colombia. ${PENDIENTE} Ciudad de los jueces competentes.`,
            ],
        },
        {
            titulo: '13. Contacto',
            parrafos: [
                `Para cualquier pregunta sobre estos términos, escribe a ${CORREO}.`,
            ],
        },
    ],
};

export const politicaDeDatos: DocumentoLegal = {
    clave: 'politica',
    titulo: 'Política de tratamiento de datos personales',
    resumen:
        'Qué datos guardamos, para qué, con quién los compartimos y cómo ejerces tus derechos (Ley 1581 de 2012).',
    version: '1.0',
    vigencia: `${PENDIENTE} Fecha desde la que rige.`,
    secciones: [
        {
            titulo: '1. Responsable del tratamiento',
            lista: [
                `Razón social: NuevasTIC (${PENDIENTE} razón social completa).`,
                `NIT: ${PENDIENTE}`,
                `Domicilio y dirección: ${PENDIENTE}`,
                `Teléfono: ${PENDIENTE}`,
                `Correo para temas de datos personales: ${CORREO} (${PENDIENTE} confirmar si es otro).`,
            ],
        },
        {
            titulo: '2. Marco legal',
            parrafos: [
                'Esta política cumple la Ley Estatutaria 1581 de 2012 y el Decreto 1074 de 2015 (capítulo 25, que recoge el Decreto 1377 de 2013), sobre protección de datos personales en Colombia.',
            ],
        },
        {
            titulo: '3. Qué datos guardamos',
            parrafos: ['De la persona que usa la cuenta:'],
            lista: [
                'Nombre, cargo, correo y teléfono.',
                'Contraseña, guardada cifrada: nadie puede leerla, ni siquiera NuevasTIC.',
                'Foto de perfil, si la subes.',
                'País, departamento, ciudad, zona horaria e idioma.',
                'Qué avisos por correo quieres recibir.',
                'Fecha en que aceptaste esta política y fecha del último acceso.',
            ],
            cierre: [
                'De la empresa: nombre, sector, actividad económica, descripción, ubicación, teléfono, sitio web, número de empleados y logo.',
                'Del diagnóstico: tus respuestas, los puntajes, las observaciones y recomendaciones, y el informe en PDF.',
                'Datos técnicos: la dirección IP y el navegador desde los que entras, para mantener la sesión y proteger la cuenta.',
                'No pedimos datos sensibles ni datos de niños, niñas o adolescentes.',
            ],
        },
        {
            titulo: '4. Para qué los usamos',
            lista: [
                'Crear y administrar tu cuenta y verificar tu correo.',
                'Asignarte mediciones, calcular tu puntaje y nivel, analizar tus respuestas y entregarte el resultado y el informe en PDF.',
                'Enviarte correos del servicio: código de verificación, medición asignada, recordatorios, resultado listo y resumen semanal, según lo que elijas en Mi perfil.',
                'Que el equipo de NuevasTIC te acompañe y resuelva tus solicitudes.',
                'Proteger el sistema: verificación contra robots, límite de intentos de inicio de sesión y registro de actividad.',
            ],
            cierre: [
                `${PENDIENTE} Si NuevasTIC enviará información comercial propia, debe agregarse aquí como una finalidad aparte y pedirse por separado.`,
            ],
        },
        {
            titulo: '5. Con quién los compartimos',
            parrafos: [
                'No vendemos tus datos. Los compartimos solo con los proveedores que necesitamos para prestar el servicio, que los tratan por cuenta nuestra:',
            ],
            lista: [
                'OpenAI (Estados Unidos), que analiza tus respuestas. Recibe el sector, la categoría, las preguntas con tus respuestas y las instrucciones de NuevasTIC. No recibe el nombre de la empresa, tu nombre ni tus datos de contacto, salvo que los escribas en una respuesta abierta. Por eso te pedimos no escribir datos personales en las respuestas.',
                'Cloudflare (Estados Unidos), que verifica que no seas un robot al crear la cuenta. Recibe datos técnicos de tu navegador.',
                `El servicio de envío de correos (${PENDIENTE} proveedor).`,
                `El servicio donde se aloja el sistema (${PENDIENTE} proveedor y país).`,
            ],
            cierre: [
                'Algunos de estos proveedores están fuera de Colombia, así que tus datos se transmiten a otros países. Al aceptar esta política autorizas expresamente esa transmisión, solo para las finalidades de esta política.',
                'También entregaríamos datos a una autoridad que los pida con base en la ley.',
            ],
        },
        {
            titulo: '6. Tus derechos',
            parrafos: ['Como titular de los datos tienes derecho a:'],
            lista: [
                'Conocer, actualizar y corregir tus datos. Buena parte puedes hacerlo tú mismo en Mi perfil.',
                'Pedir prueba de la autorización que nos diste.',
                'Saber, si lo pides, qué uso le damos a tus datos.',
                'Presentar quejas ante la Superintendencia de Industria y Comercio, después de haber hecho tu consulta o reclamo ante nosotros.',
                'Revocar la autorización y pedir que borremos tus datos, cuando no haya un deber legal o contractual de conservarlos.',
                'Consultar tus datos gratis.',
            ],
        },
        {
            titulo: '7. Cómo ejercer tus derechos',
            parrafos: [
                `Escribe a ${CORREO} con tu nombre, el correo de tu cuenta, lo que pides y cómo quieres que te respondamos.`,
            ],
            lista: [
                'Consultas: respondemos en máximo 10 días hábiles. Si no alcanzamos, te avisamos y respondemos en máximo 5 días hábiles más.',
                'Reclamos (corregir, actualizar, borrar o revocar): respondemos en máximo 15 días hábiles, prorrogables 8 días hábiles más con aviso. Si al reclamo le falta información, te pedimos completarla en los 5 días siguientes; si pasan 2 meses sin respuesta, entendemos que desististe.',
            ],
        },
        {
            titulo: '8. Cuánto tiempo los guardamos',
            lista: [
                'Mientras tu cuenta esté activa.',
                'Un registro sin verificar el correo se borra a los 7 días.',
                'Si se elimina una cuenta, se puede recuperar durante 90 días; después se borra para siempre con sus datos, sus mediciones y sus informes.',
                'Un resultado publicado no se modifica mientras exista la cuenta.',
                `${PENDIENTE} Cuánto tiempo se guardan los registros técnicos del sistema.`,
            ],
        },
        {
            titulo: '9. Cómo los protegemos',
            lista: [
                'Contraseñas cifradas y código de verificación del correo.',
                'Cada persona ve solo lo que su rol permite, y eso se revisa en el servidor.',
                'Límite de intentos de inicio de sesión y verificación contra robots.',
                'Los informes, las fotos y los logos se guardan en un espacio privado y solo los descarga quien tiene permiso.',
                'Cuando un administrador "ve como" tu cuenta, queda registrado.',
            ],
        },
        {
            titulo: '10. Cookies',
            parrafos: [
                'Usamos solo las cookies necesarias para que el sistema funcione: la de tu sesión, la que protege los formularios y, si marcas "Recordarme", la que mantiene tu sesión abierta. No usamos cookies de publicidad. El navegador también guarda si plegaste el menú lateral.',
            ],
        },
        {
            titulo: '11. Cambios en esta política',
            parrafos: [
                'Si hacemos cambios importantes, te avisaremos por correo antes de aplicarlos. Si cambia la finalidad, te pediremos de nuevo la autorización. La versión vigente siempre estará en esta página.',
            ],
        },
    ],
};

export const documentosLegales = {
    terminos: terminosDeUso,
    politica: politicaDeDatos,
} as const;

/** Parte un texto para resaltar las marcas [INFORMACIÓN PENDIENTE] y similares. */
export function partesConMarcas(
    texto: string,
): { texto: string; marca: boolean }[] {
    return texto
        .split(/(\[[A-ZÁÉÍÓÚÑ ]+\])/)
        .filter((parte) => parte !== '')
        .map((parte) => ({
            texto: parte,
            marca: /^\[[A-ZÁÉÍÓÚÑ ]+\]$/.test(parte),
        }));
}
