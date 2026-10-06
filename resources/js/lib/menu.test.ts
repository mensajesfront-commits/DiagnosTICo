import { describe, expect, it } from 'vite-plus/test';
import { filtrarMenu, menuAdministrador, menuEmpresa } from '@/lib/menu';
import type { ItemMenu } from '@/lib/menu';

const titulos = (items: ItemMenu[]) => items.map((item) => item.titulo);

describe('filtrarMenu', () => {
    it('muestra Colaboradores a la cuenta principal de la empresa', () => {
        const items = filtrarMenu(menuEmpresa, ['resultados.ver'], 'Empresa');

        expect(titulos(items)).toEqual([
            'Inicio',
            'Mi historial',
            'Colaboradores',
        ]);
    });

    it('oculta Colaboradores al colaborador (HU-076)', () => {
        const items = filtrarMenu(
            menuEmpresa,
            ['resultados.ver'],
            'Colaborador',
        );

        expect(titulos(items)).toEqual(['Inicio', 'Mi historial']);
    });

    it('muestra al Administrador solo las secciones con permiso', () => {
        const items = filtrarMenu(
            menuAdministrador,
            ['diagnosticos.ver', 'empresas.ver'],
            'Revisor',
        );

        expect(titulos(items)).toEqual(['Inicio', 'Diagnósticos', 'Empresas']);
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
