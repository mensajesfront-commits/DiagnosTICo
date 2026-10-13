import { tokenXsrf } from '@/lib/utils';
import type { ElementoPrompt } from '@/types/configuracionIa';

/**
 * Ayudas de A4 · Configuración de la IA.
 */

export const elementos: {
    clave: ElementoPrompt;
    titulo: string;
    ayuda: string;
}[] = [
    {
        clave: 'contexto',
        titulo: 'Contexto',
        ayuda: 'Quién es la IA, para qué sector trabaja y en qué situación está.',
    },
    {
        clave: 'tarea',
        titulo: 'Tarea',
        ayuda: 'Qué debe hacer, con un verbo claro.',
    },
    {
        clave: 'detalles',
        titulo: 'Detalles y restricciones',
        ayuda: 'Criterios, tono, extensión, método de análisis (por ejemplo FODA o PESTEL) y qué evitar.',
    },
    {
        clave: 'ejemplos',
        titulo: 'Ejemplos',
        ayuda: 'Opcional, pero muy útil: uno que sí y uno que no.',
    },
];

/** Códigos {así} que aparecen en un texto. */
export function variablesUsadas(texto: string): string[] {
    return [...texto.matchAll(/\{([a-z_]+)\}/g)].map((m) => m[1]);
}

/** Etiqueta del origen de una parte del prompt (A4.4). */
export const origenes = {
    general: { nombre: 'General', clase: '' },
    sector: {
        nombre: 'Sector',
        clase: 'bg-marca-suave/70 underline decoration-marca decoration-2 underline-offset-4',
    },
    empresa: {
        nombre: 'Empresa',
        clase: 'bg-violet-50 underline decoration-violet-500 decoration-2 underline-offset-4',
    },
} as const;

export type RespuestaJson<T> =
    | { ok: true; datos: T }
    | { ok: false; mensaje: string };

/** POST JSON con el token de la sesión; devuelve los datos o el mensaje de error. */
export async function enviarJson<T>(
    url: string,
    cuerpo: unknown,
): Promise<RespuestaJson<T>> {
    try {
        const r = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': tokenXsrf(),
            },
            body: JSON.stringify(cuerpo),
        });
        const datos = await r.json().catch(() => ({}));

        if (!r.ok) {
            return {
                ok: false,
                mensaje:
                    datos.message ??
                    'No pudimos completar la solicitud. Inténtalo de nuevo.',
            };
        }

        return { ok: true, datos: datos as T };
    } catch {
        return {
            ok: false,
            mensaje: 'No hay conexión con el servidor. Inténtalo de nuevo.',
        };
    }
}
