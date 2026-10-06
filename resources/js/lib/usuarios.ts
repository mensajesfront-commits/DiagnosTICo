/**
 * Textos de "Usuarios y roles" (A5) que dependen de la cuenta.
 */
import type { Cuenta } from '@/types/usuarios';

/**
 * Aviso cuando la cuenta es la principal de una empresa (A5.3b, A5.2, A5.5):
 * si se desactiva o cambia de rol, la empresa y sus colaboradores se quedan
 * sin acceso para responder (RN-025).
 */
export function avisoCuentaPrincipal(
    cuenta: Pick<
        Cuenta,
        | 'nombre'
        | 'empresa'
        | 'es_principal'
        | 'colaboradores_activos'
        | 'medicion_pendiente'
    >,
    accion: 'desactivar' | 'cambiar',
): string | null {
    if (!cuenta.es_principal || !cuenta.empresa) {
        return null;
    }

    const n = cuenta.colaboradores_activos;
    const equipo =
        n === 0
            ? 'la empresa'
            : `la empresa y ${n === 1 ? 'su colaborador activo' : `sus ${n} colaboradores activos`}`;

    const inicio =
        accion === 'desactivar'
            ? `Es la cuenta principal de ${cuenta.empresa}: ${equipo} se ${n === 0 ? 'queda' : 'quedan'} sin acceso hasta que la reactives.`
            : `${cuenta.nombre} es la cuenta principal de ${cuenta.empresa}: si cambia de rol, ${equipo} se ${n === 0 ? 'queda' : 'quedan'} sin acceso para responder.`;

    if (!cuenta.medicion_pendiente) {
        return inicio;
    }

    return accion === 'desactivar'
        ? `${inicio} Tiene la ${cuenta.medicion_pendiente}: quedará en pausa.`
        : `${inicio} Tiene la ${cuenta.medicion_pendiente}.`;
}

/** Busca por nombre o correo, sin importar tildes ni mayúsculas. */
export function coincide(
    cuenta: Pick<Cuenta, 'nombre' | 'correo'>,
    texto: string,
): boolean {
    const normalizar = (valor: string) =>
        valor
            .normalize('NFD')
            .replace(/\p{Diacritic}/gu, '')
            .toLowerCase();
    const buscado = normalizar(texto.trim());

    return (
        buscado === '' ||
        normalizar(cuenta.nombre).includes(buscado) ||
        normalizar(cuenta.correo).includes(buscado)
    );
}
