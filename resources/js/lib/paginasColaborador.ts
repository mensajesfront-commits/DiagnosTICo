/**
 * Ícono de cada página que la cuenta principal le da a un colaborador
 * (E12, E12.1, E12.3). Las páginas nuevas que agregue el servidor usan el
 * ícono genérico.
 */
import { ChartColumn, ClipboardPen, FileDown, LayoutGrid } from '@lucide/vue';
import type { Component } from 'vue';

const iconos: Record<string, Component> = {
    'diagnostico.responder': ClipboardPen,
    'resultados.ver': ChartColumn,
    'resultados.pdf': FileDown,
};

export function iconoPagina(clave: string): Component {
    return iconos[clave] ?? LayoutGrid;
}
