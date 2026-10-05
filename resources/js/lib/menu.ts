/**
 * Secciones del menú lateral por tipo de cuenta (A1 y E1–E11).
 *
 * `permiso` es el permiso de spatie/laravel-permission que debe tener la
 * cuenta para ver la sección (matriz en docs/17_SEGURIDAD.md). Sin permiso,
 * la sección siempre se muestra. El servidor bloquea las rutas igual; ocultar
 * en el menú es solo para no mostrar lo que no se puede abrir.
 *
 * Las rutas de las secciones se crean en las semanas 3 a 6; mientras tanto
 * los enlaces apuntan a la URL prevista.
 */
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

export type ItemMenu = {
    titulo: string;
    href?: NonNullable<InertiaLinkProps['href']>;
    permiso?: string;
    /** Submenú plegable. */
    hijos?: ItemMenu[];
};

export const menuAdministrador: ItemMenu[] = [
    { titulo: 'Inicio', href: dashboard() },
    {
        titulo: 'Diagnósticos',
        href: '/diagnosticos',
        permiso: 'diagnosticos.ver',
    },
    { titulo: 'Empresas', href: '/empresas', permiso: 'empresas.ver' },
    // HU-041 CA-001: "Configuración IA" va sin submenú de fases.
    {
        titulo: 'Configuración IA',
        href: '/configuracion-ia',
        permiso: 'ia.configurar',
    },
    {
        titulo: 'Usuarios y roles',
        href: '/usuarios',
        permiso: 'usuarios.gestionar',
    },
];

export const menuEmpresa: ItemMenu[] = [
    { titulo: 'Inicio', href: dashboard() },
    { titulo: 'Mi historial', href: '/historial', permiso: 'resultados.ver' },
    // HU-076 / RN-025: el colaborador no ve esta sección.
    {
        titulo: 'Colaboradores',
        href: '/colaboradores',
        permiso: 'colaboradores.gestionar',
    },
];

/**
 * Deja solo las secciones (y subsecciones) que la cuenta puede ver. Un grupo
 * con submenú desaparece si no le queda ninguna subsección visible.
 */
export function filtrarMenu(items: ItemMenu[], permisos: string[]): ItemMenu[] {
    return items.flatMap((item) => {
        if (item.permiso && !permisos.includes(item.permiso)) {
            return [];
        }

        if (!item.hijos) {
            return [item];
        }

        const hijos = filtrarMenu(item.hijos, permisos);

        return hijos.length > 0 ? [{ ...item, hijos }] : [];
    });
}
