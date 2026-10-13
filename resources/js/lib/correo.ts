/**
 * Correo de aviso de la medición (A3.1e, HU-037): variables y relleno para
 * la vista previa. Hace lo mismo que `App\Support\CorreoAviso::rellenar`,
 * que es el que se usa al enviar.
 */

export const VARIABLES = [
    'nombre_usuario',
    'empresa',
    'diagnostico',
    'numero_medicion',
    'mensaje_admin',
    'fecha_limite',
] as const;

export type VariableCorreo = (typeof VARIABLES)[number];

export type ValoresCorreo = Partial<Record<VariableCorreo, string>>;

/**
 * Cambia cada {variable} por su valor. Las desconocidas quedan como están; si
 * una queda vacía (sin mensaje), no deja líneas en blanco de más.
 */
export function rellenar(texto: string, valores: ValoresCorreo): string {
    return texto
        .replace(/\r\n/g, '\n')
        .replace(/\{([a-z_]+)\}/g, (todo, nombre: string) =>
            nombre in valores
                ? (valores[nombre as VariableCorreo] ?? '')
                : todo,
        )
        .replace(/\n[ \t]*\n(?:[ \t]*\n)+/g, '\n\n')
        .trim();
}

/** Los párrafos del cuerpo, como llegan en el correo. */
export function parrafos(texto: string): string[] {
    return texto
        .split(/\n\s*\n/)
        .map((p) => p.trim())
        .filter((p) => p !== '');
}

/** "20 de octubre de 2026" o "sin fecha límite" (como en el correo). */
export function fechaCorreo(fecha: string): string {
    if (!fecha) {
        return 'sin fecha límite';
    }

    const [anio, mes, dia] = fecha.split('-').map(Number);

    return new Date(anio, (mes ?? 1) - 1, dia ?? 1).toLocaleDateString(
        'es-CO',
        { day: 'numeric', month: 'long', year: 'numeric' },
    );
}
