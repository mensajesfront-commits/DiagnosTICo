/**
 * Requisitos de la contraseña fuerte (RN-001), para la lista que se marca en
 * vivo en L2 (registro), L4 (contraseña nueva), A6, E11 y colaboradores.
 *
 * El servidor valida lo mismo; esta lista solo ayuda a la persona mientras
 * escribe.
 *
 * [INCONSISTENCIA DETECTADA] RN-001 exige un carácter especial, pero los
 * wireframes L2 y L4 muestran solo 4 requisitos (sin el carácter especial).
 * Se sigue RN-001 porque 05_REQUISITOS_FUNCIONALES.md es la fuente de verdad.
 * Si se decide quitarlo, basta con borrar la entrada "especial".
 */

export type RequisitoContrasena = {
    id: string;
    texto: string;
    cumple: (contrasena: string, confirmacion: string) => boolean;
};

export const requisitosContrasena: RequisitoContrasena[] = [
    {
        id: 'longitud',
        texto: 'Mínimo 8 caracteres',
        cumple: (c) => c.length >= 8,
    },
    {
        id: 'mayuscula',
        texto: 'Al menos una mayúscula',
        cumple: (c) => /\p{Lu}/u.test(c),
    },
    {
        id: 'numero',
        texto: 'Al menos un número',
        cumple: (c) => /\d/.test(c),
    },
    {
        id: 'especial',
        texto: 'Al menos un carácter especial',
        cumple: (c) => /[^\p{L}\p{N}\s]/u.test(c),
    },
    {
        id: 'coinciden',
        texto: 'Las dos contraseñas coinciden',
        cumple: (c, confirmacion) => c.length > 0 && c === confirmacion,
    },
];

export type EstadoRequisito = { id: string; texto: string; cumple: boolean };

export function revisarContrasena(
    contrasena: string,
    confirmacion: string,
): { requisitos: EstadoRequisito[]; cumplidos: number; completa: boolean } {
    const requisitos = requisitosContrasena.map((r) => ({
        id: r.id,
        texto: r.texto,
        cumple: r.cumple(contrasena, confirmacion),
    }));
    const cumplidos = requisitos.filter((r) => r.cumple).length;

    return {
        requisitos,
        cumplidos,
        completa: cumplidos === requisitos.length,
    };
}
