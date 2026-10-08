import { computed, ref, watch } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import { toValue } from 'vue';

/** Número de páginas para `total` elementos; siempre al menos 1. */
export function contarPaginas(total: number, porPagina: number): number {
    return Math.max(1, Math.ceil(total / Math.max(1, porPagina)));
}

/**
 * Cuántas filas caben en un alto disponible. Al menos `minimo`, para que la
 * tabla nunca quede vacía aunque la ventana sea muy baja.
 */
export function filasQueCaben(
    alto: number,
    altoFila: number,
    minimo = 3,
): number {
    if (alto <= 0 || altoFila <= 0) {
        return minimo;
    }

    return Math.max(minimo, Math.floor(alto / altoFila));
}

/**
 * Paginado en el navegador de una lista ya filtrada. Vuelve a la página 1
 * cuando cambia la lista (búsqueda, filtro u orden) y no deja quedar en una
 * página que ya no existe (por ejemplo, al eliminar la última fila).
 */
export function usePaginacion<T>(
    lista: MaybeRefOrGetter<T[]>,
    porPagina: MaybeRefOrGetter<number>,
) {
    const pagina = ref(1);
    const total = computed(() => toValue(lista).length);
    const paginas = computed(() =>
        contarPaginas(total.value, toValue(porPagina)),
    );
    const desde = computed(() => (pagina.value - 1) * toValue(porPagina));
    const visibles = computed(() =>
        toValue(lista).slice(desde.value, desde.value + toValue(porPagina)),
    );

    watch(
        () => toValue(lista),
        () => (pagina.value = 1),
    );
    watch(paginas, (n) => {
        if (pagina.value > n) {
            pagina.value = n;
        }
    });

    return { pagina, paginas, total, desde, visibles };
}
