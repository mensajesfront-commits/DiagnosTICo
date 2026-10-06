<script setup lang="ts" generic="F extends Record<string, unknown>">
/**
 * Tabla del wireframe: encabezado gris claro, filas separadas por una línea
 * y mensaje cuando no hay filas.
 *
 * Cada columna se puede personalizar con un slot `celda-<clave>` que recibe
 * la fila: <template #celda-estado="{ fila }">…</template>
 */
import { cn } from '@/lib/utils';

export type ColumnaTabla = {
    clave: string;
    titulo: string;
    alineacion?: 'izquierda' | 'derecha' | 'centro';
    class?: string;
};

withDefaults(
    defineProps<{
        columnas: ColumnaTabla[];
        filas: F[];
        /** Campo único de cada fila, para que Vue siga los cambios. */
        claveFila?: keyof F;
        vacio?: string;
    }>(),
    { claveFila: 'id', vacio: 'No hay datos para mostrar.' },
);

const alineaciones = {
    izquierda: 'text-left',
    derecha: 'text-right',
    centro: 'text-center',
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="border-y border-linea bg-[#f9f8f4]">
                    <th
                        v-for="columna in columnas"
                        :key="columna.clave"
                        scope="col"
                        :class="
                            cn(
                                'px-5 py-2.5 text-xs font-normal text-tinta-suave',
                                alineaciones[columna.alineacion ?? 'izquierda'],
                                columna.class,
                            )
                        "
                    >
                        {{ columna.titulo }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(fila, indice) in filas"
                    :key="String(fila[claveFila] ?? indice)"
                    class="border-b border-linea last:border-b-0"
                >
                    <td
                        v-for="columna in columnas"
                        :key="columna.clave"
                        :class="
                            cn(
                                'px-5 py-3 align-middle text-tinta',
                                alineaciones[columna.alineacion ?? 'izquierda'],
                                columna.class,
                            )
                        "
                    >
                        <slot :name="`celda-${columna.clave}`" :fila="fila">
                            {{ fila[columna.clave] }}
                        </slot>
                    </td>
                </tr>
                <tr v-if="filas.length === 0">
                    <td
                        :colspan="columnas.length"
                        class="px-5 py-8 text-center text-sm text-tinta-suave"
                    >
                        <slot name="vacio">{{ vacio }}</slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
