import { describe, expect, it } from 'vite-plus/test';
import { filtrarMenu, menuEmpresa } from '@/lib/menu';
import type { ItemMenu } from '@/lib/menu';

const titulos = (items: ItemMenu[]) => items.map((item) => item.titulo);

describe('filtrarMenu', () => {
    it('muestra Colaboradores a la empresa con el permiso', () => {
        const items = filtrarMenu(menuEmpresa, [
            'resultados.ver',
            'colaboradores.gestionar',
        ]);

        expect(titulos(items)).toEqual([
            'Inicio',
            'Mi historial',
            'Colaboradores',
        ]);
    });

    it('oculta Colaboradores al colaborador (HU-076)', () => {
        const items = filtrarMenu(menuEmpresa, ['resultados.ver']);

        expect(titulos(items)).toEqual(['Inicio', 'Mi historial']);
    });

    it('quita un submenú que se queda sin subsecciones visibles', () => {
        const menu: ItemMenu[] = [
            {
                titulo: 'Grupo',
                hijos: [{ titulo: 'Hijo', href: '/hijo', permiso: 'x' }],
            },
        ];

        expect(filtrarMenu(menu, [])).toEqual([]);
        expect(titulos(filtrarMenu(menu, ['x']))).toEqual(['Grupo']);
    });
});
