import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    toValue,
    watch,
} from 'vue';
import type { MaybeRefOrGetter, Ref } from 'vue';

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

/**
 * Cuántas filas de una tabla caben en su caja (`contenedor`) en pantallas
 * grandes, para que la página no se desplace; en pantallas pequeñas,
 * `movil` por página. Mide la fila más alta que se ve y vuelve a medir al
 * cambiar el tamaño de la caja o cuando carga la letra. Llamar `medir()`
 * cuando la tabla pasa de vacía a tener filas y `ajustar()` después de
 * mostrar otra página (por si sus filas son más altas).
 */
export function useFilasQueCaben(
    contenedor: Ref<HTMLElement | null>,
    { movil = 10, minimo = 3 }: { movil?: number; minimo?: number } = {},
) {
    const porPagina = ref(movil);
    let observador: ResizeObserver | null = null;
    const pantallaGrande =
        typeof window !== 'undefined'
            ? window.matchMedia('(min-width: 1024px)')
            : null;

    function medir(): void {
        const caja = contenedor.value;

        if (!caja || !pantallaGrande?.matches) {
            porPagina.value = movil;

            return;
        }

        // Solo se mide al cambiar el tamaño (no al cambiar de página), así el
        // número de filas no salta.
        let altoFila = 0;

        for (const fila of caja.querySelectorAll('tbody tr')) {
            altoFila = Math.max(altoFila, fila.getBoundingClientRect().height);
        }

        const encabezado = caja.querySelector('thead')?.clientHeight ?? 40;
        porPagina.value = filasQueCaben(
            caja.clientHeight - encabezado,
            altoFila || 72,
            minimo,
        );
    }

    onMounted(() => {
        observador = new ResizeObserver(() => medir());

        if (contenedor.value) {
            observador.observe(contenedor.value);
        }

        pantallaGrande?.addEventListener('change', medir);
        medir();
        // Al cargar la letra y terminar de pintar, las filas cambian de alto.
        void document.fonts?.ready.then(() => nextTick(medir));
        setTimeout(medir, 300);
    });

    onBeforeUnmount(() => {
        observador?.disconnect();
        pantallaGrande?.removeEventListener('change', medir);
    });

    /**
     * Después de pintar una página: si sus filas no caben (algunas son más
     * altas), se muestra una menos por página. Solo baja, así no salta.
     */
    function ajustar(): void {
        void nextTick(() => {
            const caja = contenedor.value;

            if (
                caja &&
                pantallaGrande?.matches &&
                caja.scrollHeight > caja.clientHeight + 1 &&
                porPagina.value > minimo
            ) {
                porPagina.value--;
                ajustar();
            }
        });
    }

    return { porPagina, medir: () => nextTick(medir), ajustar };
}
