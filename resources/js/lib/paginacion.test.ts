import { describe, expect, it } from 'vitest';
import { nextTick, ref } from 'vue';
import { contarPaginas, filasQueCaben, usePaginacion } from './paginacion';

describe('paginacion', () => {
    it('cuenta las páginas, con al menos una', () => {
        expect(contarPaginas(0, 10)).toBe(1);
        expect(contarPaginas(10, 10)).toBe(1);
        expect(contarPaginas(11, 10)).toBe(2);
    });

    it('calcula cuántas filas caben, con un mínimo', () => {
        expect(filasQueCaben(500, 70)).toBe(7);
        expect(filasQueCaben(100, 70)).toBe(3);
        expect(filasQueCaben(0, 70, 5)).toBe(5);
    });

    it('muestra la página elegida y vuelve a la 1 si cambia la lista', async () => {
        const lista = ref([1, 2, 3, 4, 5]);
        const p = usePaginacion(lista, 2);

        expect(p.visibles.value).toEqual([1, 2]);
        p.pagina.value = 3;
        expect(p.visibles.value).toEqual([5]);

        lista.value = [1, 2, 3];
        await nextTick();
        expect(p.pagina.value).toBe(1);
    });

    it('no se queda en una página que ya no existe', async () => {
        const porPagina = ref(2);
        const p = usePaginacion([1, 2, 3, 4, 5], porPagina);

        p.pagina.value = 3;
        porPagina.value = 5;
        await nextTick();
        expect(p.pagina.value).toBe(1);
    });
});
