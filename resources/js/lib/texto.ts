/**
 * Comparación de textos para buscar: sin tildes, sin mayúsculas y sin
 * espacios de más ("Bogotá" = "bogota").
 */
export function normalizar(texto: string): string {
    return texto
        .normalize('NFD')
        .replace(/\p{M}/gu, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

/**
 * Filtra opciones que contienen lo escrito; primero las que empiezan así.
 * Sin texto, devuelve todas.
 */
export function filtrarOpciones(opciones: string[], texto: string): string[] {
    const buscado = normalizar(texto);

    if (buscado === '') {
        return opciones;
    }

    const empiezan: string[] = [];
    const contienen: string[] = [];

    for (const opcion of opciones) {
        const n = normalizar(opcion);

        if (n.startsWith(buscado)) {
            empiezan.push(opcion);
        } else if (n.includes(buscado)) {
            contienen.push(opcion);
        }
    }

    return [...empiezan, ...contienen];
}

/** La opción que coincide exactamente con lo escrito, sin mirar tildes. */
export function opcionExacta(
    opciones: string[],
    texto: string,
): string | undefined {
    const buscado = normalizar(texto);

    return buscado === ''
        ? undefined
        : opciones.find((o) => normalizar(o) === buscado);
}
