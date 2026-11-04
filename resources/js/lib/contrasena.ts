/**
 * Requisitos de la contraseña fuerte (RN-001), para la lista que se marca en
 * vivo en L2 (registro), L4 (contraseña nueva), A6, E11 y colaboradores.
 *
 * El servidor valida lo mismo; esta lista solo ayuda a la persona mientras
 * escribe. Además, el servidor pide que sea distinta de la anterior.
 *
 * El carácter especial de RN-001 se exige aunque los wireframes L2 y L4 no
 * lo muestren: NuevasTIC lo confirmó (23 de octubre de 2026).
 */

export type RequisitoContrasena = {
    id: string;
    texto: string;
    cumple: (
        contrasena: string,
        confirmacion: string,
        correo: string,
        anterior: string,
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

/** Trozos de 4 caracteres seguidos que no se pueden repetir de la actual. */
const LARGO_PARTE_ANTERIOR = 4;

/**
 * La nueva repite un trozo de 4 caracteres o más de la actual. Igual que
 * `SinPartesDeLaAnterior` en el servidor. Solo en Mi perfil, donde se escribe
 * la contraseña actual.
 */
export function repiteLaAnterior(nueva: string, anterior: string): boolean {
    const texto = normalizar(nueva);
    const vieja = normalizar(anterior);

    for (let i = 0; i + LARGO_PARTE_ANTERIOR <= vieja.length; i++) {
        if (texto.includes(vieja.slice(i, i + LARGO_PARTE_ANTERIOR))) {
            return true;
        }
    }

    return false;
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
        id: 'anterior',
        texto: 'Nada igual a la contraseña actual',
        cumple: (c, _confirmacion, _correo, anterior) =>
            c.length > 0 && !repiteLaAnterior(c, anterior),
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
 * Con la contraseña actual (Mi perfil) se agrega "Nada igual a la contraseña
 * actual". En L4 y con los colaboradores la actual no se conoce: el servidor
 * solo revisa que no sea idéntica.
 */
export function revisarContrasena(
    contrasena: string,
    confirmacion: string,
    {
        conConfirmacion = true,
        correo = '',
        anterior = '',
    }: { conConfirmacion?: boolean; correo?: string; anterior?: string } = {},
): { requisitos: EstadoRequisito[]; cumplidos: number; completa: boolean } {
    const requisitos = requisitosContrasena
        .filter((r) => conConfirmacion || r.id !== 'coinciden')
        .filter((r) => r.id !== 'correo' || partesDelCorreo(correo).length > 0)
        .filter((r) => r.id !== 'anterior' || anterior.length > 0)
        .map((r) => ({
            id: r.id,
            texto: r.texto,
            cumple: r.cumple(contrasena, confirmacion, correo, anterior),
        }));
    const cumplidos = requisitos.filter((r) => r.cumple).length;

    return {
        requisitos,
        cumplidos,
        completa: cumplidos === requisitos.length,
    };
}
