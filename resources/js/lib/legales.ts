/**
 * Términos y Condiciones y Política de Privacidad de Captter (L2).
 *
 * Son los textos oficiales que entregó la empresa el 4 de noviembre de 2026,
 * copiados tal cual (solo se quitó un espacio doble). No se editan aquí:
 * cualquier cambio lo hace la empresa y se copia de nuevo, con su fecha.
 *
 * Se muestran en una ventana desde Crear cuenta. Al aceptarlos se guarda la
 * fecha en `users.terminos_aceptados_en`.
 *
 * [INCONSISTENCIA DETECTADA] Los textos oficiales describen funciones que
 * este sistema de diagnóstico no tiene (pagos con Openpay y PSE, cookies de
 * analítica, ubicación del dispositivo, NIT y cédula) y no nombran a los
 * proveedores que sí usa (OpenAI para el análisis y Cloudflare Turnstile
 * contra robots). Detalle en docs/17_SEGURIDAD.md. Lo decide la empresa;
 * aquí no se cambia el texto oficial.
 */

export type SeccionLegal = {
    titulo: string;
    /** 1: "1. Métodos de Pago"; 2: "1.1. …"; 3: "1.1.1. …". */
    nivel?: 1 | 2 | 3;
    parrafos?: string[];
    lista?: string[];
    /** Párrafos que van después de la lista. */
    cierre?: string[];
};

export type DocumentoLegal = {
    clave: 'terminos' | 'politica';
    titulo: string;
    resumen: string;
    /** "Última actualización" del texto oficial. */
    actualizacion: string;
    /** Párrafos antes de la primera sección. */
    introduccion: string[];
    secciones: SeccionLegal[];
};

const CORREO = 'contacto@captter.com';

export const terminosDeUso: DocumentoLegal = {
    clave: 'terminos',
    titulo: 'Términos y Condiciones',
    resumen:
        'Las condiciones de acceso, uso y navegación en la plataforma CAPTTER.',
    actualizacion: '16 de septiembre de 2025',
    introduccion: [
        'Los presentes Términos y Condiciones regulan el acceso, uso y navegación en la plataforma CAPTTER, así como la contratación de los productos y servicios ofrecidos.',
        'Al utilizar nuestro sitio web, nuestras aplicaciones o cualquier canal digital habilitado por CAPTTER, usted acepta expresamente las disposiciones aquí establecidas, que incluyen aspectos sobre uso, pagos, seguridad y demás políticas aplicables.',
        'CAPTTER se reserva los derechos de modificar estos términos sin previo aviso. Se recomienda a los usuarios que consulten periódicamente estos términos y condiciones para estar informados y actualizados.',
    ],
    secciones: [
        {
            titulo: '1. Métodos de Pago',
            parrafos: [
                'En CAPTTER ponemos a disposición diferentes métodos de pago a través de la pasarela certificada Openpay, con el fin de facilitar, agilizar y garantizar la seguridad, transparencia y confiabilidad de cada transacción realizada en nuestro sitio web.',
            ],
        },
        { titulo: '1.1. Métodos de pago habilitados', nivel: 2 },
        {
            titulo: '1.1.1. Tarjeta de crédito o débito',
            nivel: 3,
            lista: [
                'Se aceptan tarjetas de crédito (Visa, MasterCard, American Express) y débito de bancos nacionales.',
                'La gestión de datos sensibles (número de tarjeta, CVV, fecha de vencimiento) se realiza únicamente en los servidores de Openpay, bajo certificación PCI DSS Nivel 1.',
                'Los pagos podrán requerir validaciones adicionales de autenticación (3D Secure, Verified by Visa, SecureCode).',
            ],
        },
        {
            titulo: '1.1.2. Pagos en línea mediante PSE',
            nivel: 3,
            lista: [
                'El Usuario podrá realizar pagos directamente desde su cuenta bancaria a través de ACH Colombia.',
                'Al seleccionar esta opción, el Usuario será redirigido a la plataforma del banco emisor para validar la transacción con sus credenciales personales (clave principal, clave dinámica, token u otros mecanismos de autenticación).',
                'Ni CAPTTER ni Openpay tienen acceso a dichas credenciales.',
            ],
        },
        {
            titulo: '1.2. Seguridad de la Información',
            nivel: 2,
            lista: [
                'CAPTTER no captura, almacena ni transmite información financiera o transaccionales de los Usuarios.',
                'Todas las transacciones se procesan directamente en Openpay bajo protocolos de cifrado y medidas de protección de datos con los más altos estándares de seguridad (certificación PCI DSS Nivel 1).',
                'La responsabilidad de CAPTTER se limita a ofrecer el acceso a la pasarela de pagos, sin intervenir en la autorización o rechazo de las transacciones.',
            ],
        },
        {
            titulo: '1.3. Confirmación de Pagos',
            nivel: 2,
            lista: [
                'El Usuario recibirá notificación electrónica de la aprobación o rechazo de la transacción.',
                'CAPTTER solo procederá con la activación de los servicios contratados una vez Openpay confirme la aprobación del pago.',
            ],
        },
        {
            titulo: '1.4. Rechazos y errores en las transacciones',
            nivel: 2,
            lista: [
                'CAPTTER no se hace responsable por rechazos ocasionados por fondos insuficientes, datos incorrectos, bloqueos de la entidad financiera o fallas de comunicación de terceros.',
                'En caso de inconsistencias en la transacción, el Usuario deberá contactar a su entidad bancaria y/o a Openpay para la respectiva verificación.',
            ],
        },
        {
            titulo: '1.5. Reversión, reembolsos y devolución de pagos',
            nivel: 2,
            lista: [
                'El Usuario podrá solicitar el reembolso de un pago únicamente dentro de los siete (7) días calendario siguientes a la fecha de la transacción. Transcurrido este plazo, no se aceptarán solicitudes de reembolso por ningún concepto.',
                'No procederán reembolsos cuando los fondos hayan sido utilizados, total o parcialmente, para la adquisición de servicios dentro de la plataforma, aun si el servicio adquirido no se ha consumido en su totalidad.',
                'Únicamente serán objeto de reembolso los pagos no utilizados y siempre que la solicitud se presente dentro del término señalado en el primer punto de este sub-apartado.',
                'CAPTTER pondrá a disposición los canales de contacto necesarios para gestionar estas solicitudes en coordinación con Openpay.',
            ],
        },
        {
            titulo: '1.6. Responsabilidad del Usuario',
            nivel: 2,
            lista: [
                'El Usuario es responsable de verificar que los datos ingresados durante el proceso de pago sean correctos y veraces.',
                'El Usuario debe contar con los fondos suficientes y con las autorizaciones correspondientes de su entidad financiera.',
                'El Usuario ha de mantener la confidencialidad de sus credenciales bancarias, las cuales no deberán ser compartidas con terceros.',
            ],
        },
        {
            titulo: '1.7. Limitación de responsabilidad de CAPTTER',
            nivel: 2,
            lista: [
                'CAPTTER no será responsable por daños derivados de la imposibilidad de realizar el pago por causas atribuibles al Usuario, a la entidad financiera o a Openpay.',
                'CAPTTER no será responsable de errores o retrasos generados por fallas en la red bancaria, problemas de conectividad o indisponibilidad de los servicios de Openpay.',
            ],
        },
        {
            titulo: '1.8. Aceptación',
            nivel: 2,
            parrafos: [
                'Al realizar un pago en la plataforma de CAPTTER, el Usuario declara haber leído, comprendido y aceptado en su totalidad los presentes términos y condiciones.',
            ],
        },
        {
            titulo: '2. Contacto',
            parrafos: [
                'Para consultas, reclamos o ejercicio de derechos relacionados con esta política, puede comunicarse con nosotros a:',
                `Correo electrónico: ${CORREO}`,
            ],
        },
    ],
};

export const politicaDeDatos: DocumentoLegal = {
    clave: 'politica',
    titulo: 'Política de Privacidad',
    resumen:
        'Cómo Captter recopila, usa y protege su información (Ley 1581 de 2012).',
    actualizacion: '27 de mayo de 2025',
    introduccion: [
        'Captter («nosotros», «nuestra» o «el Software») valora su privacidad. Esta política explica cómo recopilamos, usamos y protegemos la información de usuarios, comercios y profesionales que utilizan nuestra plataforma para ejecutar estrategias digitales, conectarse y transaccionar productos y servicios.',
        'Al usar Captter, usted acepta esta política. Si no está de acuerdo, por favor absténgase de utilizar nuestros servicios.',
    ],
    secciones: [
        { titulo: '1. Información que Recopilamos' },
        {
            titulo: '1.1 Información Proporcionada Directamente',
            nivel: 2,
            lista: [
                'Nombre completo',
                'Correo Electrónico',
                'Número telefónico',
                'Dirección física',
                'Datos de pago (procesados por pasarelas seguras)',
                'Para comercios: NIT, Certificado de Cámara de Comercio',
                'Para personas naturales: Cédula de ciudadanía',
            ],
        },
        {
            titulo: '1.2 Información Recopilada Automáticamente',
            nivel: 2,
            lista: [
                'Datos del dispositivo (modelo, sistema operativo, dirección IP)',
                'Ubicación (si se otorga permiso)',
                'Historial de uso, búsquedas y transacciones dentro de la plataforma',
            ],
        },
        {
            titulo: '2. Uso de la Información',
            parrafos: ['Usamos sus datos para:'],
            lista: [
                'Proporcionar y mejorar nuestros servicios',
                'Verificar identidades y autenticar usuarios',
                'Personalizar su experiencia en la plataforma',
                'Procesar pagos de forma segura',
                'Cumplir con requerimientos legales',
                'Generar análisis estadísticos de uso',
                'Finalidades comerciales relacionadas con la mejora del servicio',
            ],
        },
        {
            titulo: '3. Compartir Información',
            parrafos: [
                'Captter NO vende ni comercializa sus datos personales.',
                'Podemos compartir información únicamente con:',
            ],
            lista: [
                'Proveedores de servicios de pago',
                'Socios logísticos (cuando sea estrictamente necesario)',
                'Autoridades competentes (cuando la ley lo requiera)',
                'Aliados estratégicos autorizados, en los términos permitidos por la Ley 1581 de 2012',
            ],
        },
        {
            titulo: '4. Seguridad de la Información',
            parrafos: [
                'Captter adopta medidas de seguridad razonables y adecuadas, incluyendo:',
            ],
            lista: [
                'Encriptación de datos sensibles',
                'Control de acceso restringido al personal autorizado',
                'Monitoreo constante para prevenir accesos no autorizados y ciber-amenazas',
            ],
        },
        {
            titulo: '5. Derechos del Titular',
            parrafos: [
                'Usted puede ejercer los siguientes derechos sobre sus datos personales:',
            ],
            lista: [
                'Acceso',
                'Rectificación',
                'Cancelación',
                'Oposición (Derechos ARCO)',
            ],
            cierre: [
                `Estas solicitudes podrán realizarse en cualquier momento escribiendo al correo: ${CORREO}, conforme a lo establecido por la Ley 1581 de 2012.`,
            ],
        },
        {
            titulo: '6. Cookies y Tecnologías Similares',
            parrafos: ['Captter utiliza cookies para:'],
            lista: [
                'Funcionalidad básica del sistema',
                'Analítica de uso de la plataforma',
                'Personalización opcional de la experiencia del usuario',
            ],
            cierre: [
                'Los usuarios pueden gestionar el uso de cookies desde la configuración de su navegador.',
            ],
        },
        {
            titulo: '7. Cambios a esta Política',
            parrafos: [
                'Captter se reserva el derecho de modificar esta Política de Privacidad en cualquier momento.',
                'En caso de cambios sustanciales, se notificará a los usuarios mediante:',
            ],
            lista: [
                'Notificaciones en la aplicación y/o',
                'Correo electrónico registrado (si aplica)',
            ],
        },
        {
            titulo: '8. Contacto',
            parrafos: [
                'Para consultas, reclamos o ejercicio de derechos relacionados con esta política, puede comunicarse con nosotros a:',
                `Correo electrónico: ${CORREO}`,
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
