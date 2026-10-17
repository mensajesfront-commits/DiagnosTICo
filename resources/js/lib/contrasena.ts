/**
 * Requisitos de la contraseña fuerte (RN-001), para la lista que se marca en
 * vivo en L2 (registro), L4 (contraseña nueva), A6, E11 y colaboradores.
 *
 * El servidor valida lo mismo; esta lista solo ayuda a la persona mientras
 * escribe. Además, el servidor pide que sea distinta de la anterior.
 *
 * [INCONSISTENCIA DETECTADA] RN-001 exige un carácter especial, pero los
 * wireframes L2 y L4 muestran solo 4 requisitos (sin el carácter especial).
 * Se sigue RN-001 porque 05_REQUISITOS_FUNCIONALES.md es la fuente de verdad.
 * Si se decide quitarlo, basta con borrar la entrada "especial".
 */

export type RequisitoContrasena = {
    id: string;
    texto: string;
    cumple: (
        contrasena: string,
        confirmacion: string,
        correo: string,
    ) => boolean;
};

/** Partes de menos de 3 caracteres no cuentan ("04", "co"). */
const MINIMO_PARTE = 3;

function normalizar(texto: string): string {
    return texto
        .trim()
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase();
}

/**
 * Partes del correo que no pueden ir en la contraseña. Igual que
 * `SinDatosDelCorreo::partes` en el servidor: de "juan.perez1102@empresa.co"
 * salen "juanperez1102", "juan", "perez", "1102" y "empresa".
 */
export function partesDelCorreo(correo: string): string[] {
    const limpio = normalizar(correo);

    if (!limpio.includes('@')) {
        return [];
    }

    const arroba = limpio.indexOf('@');
    const usuario = limpio.slice(0, arroba);
    const dominio = limpio.slice(arroba + 1);
    const partes = [usuario.replace(/[^a-z0-9]/g, '')];

    for (const trozo of usuario.split(/[^a-z0-9]+/)) {
        partes.push(trozo, ...(trozo.match(/[a-z]+|[0-9]+/g) ?? []));
    }

    partes.push(dominio.split('.')[0]);

    return [...new Set(partes.filter((p) => p.length >= MINIMO_PARTE))];
}

/** La primera parte del correo que aparece en la contraseña, o null. */
export function parteDelCorreoEn(
    contrasena: string,
    correo: string,
): string | null {
    const texto = normalizar(contrasena);

    return partesDelCorreo(correo).find((p) => texto.includes(p)) ?? null;
}

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
        id: 'correo',
        texto: 'Sin partes de tu correo',
        cumple: (c, _confirmacion, correo) =>
            c.length > 0 && parteDelCorreoEn(c, correo) === null,
    },
    {
        id: 'coinciden',
        texto: 'Las dos contraseñas coinciden',
        cumple: (c, confirmacion) => c.length > 0 && c === confirmacion,
    },
];

export type EstadoRequisito = { id: string; texto: string; cumple: boolean };

/**
 * Sin confirmación (E12.1, E12.3: la empresa escribe la contraseña de un
 * colaborador una sola vez), se omite el requisito "coinciden". Sin correo
 * (aún no escrito), se omite "Sin partes de tu correo".
 *
 * "Distinta de la anterior" solo lo puede revisar el servidor.
 */
export function revisarContrasena(
    contrasena: string,
    confirmacion: string,
    {
        conConfirmacion = true,
        correo = '',
    }: { conConfirmacion?: boolean; correo?: string } = {},
): { requisitos: EstadoRequisito[]; cumplidos: number; completa: boolean } {
    const requisitos = requisitosContrasena
        .filter((r) => conConfirmacion || r.id !== 'coinciden')
        .filter((r) => r.id !== 'correo' || partesDelCorreo(correo).length > 0)
        .map((r) => ({
            id: r.id,
            texto: r.texto,
            cumple: r.cumple(contrasena, confirmacion, correo),
        }));
    const cumplidos = requisitos.filter((r) => r.cumple).length;

    return {
        requisitos,
        cumplidos,
        completa: cumplidos === requisitos.length,
    };
}
