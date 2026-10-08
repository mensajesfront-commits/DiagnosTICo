<script setup lang="ts">
/**
 * Pie de una tabla paginada: "Mostrando 1–10 de 34 · Página 1 de 4" con
 * "Anterior" y "Siguiente". Se usa con `usePaginacion` (`lib/paginacion.ts`).
 *
 * El slot por defecto va en el medio (por ejemplo, las acciones de una
 * selección), sin cambiar el alto del pie.
 */
import Boton from '@/components/base/Boton.vue';

defineProps<{
    /** Índice (desde 0) de la primera fila visible. */
    desde: number;
    /** Filas visibles en esta página. */
    cantidad: number;
    total: number;
    paginas: number;
}>();

const pagina = defineModel<number>('pagina', { required: true });
</script>

<template>
    <footer
        class="flex flex-wrap items-center justify-between gap-3 border-t border-linea px-5 py-3"
    >
        <p class="text-sm text-tinta-suave">
            <template v-if="total > 0">
                Mostrando {{ desde + 1 }}–{{ desde + cantidad }} de
                {{ total }}
                <span v-if="paginas > 1">
                    · Página {{ pagina }} de {{ paginas }}
                </span>
            </template>
            <template v-else>Sin resultados</template>
        </p>
        <div
            v-if="$slots.default"
            class="flex flex-1 flex-wrap items-center justify-end gap-2"
        >
            <slot />
        </div>
        <div class="flex gap-2">
            <Boton
                variante="secundario"
                tamano="sm"
                :disabled="pagina <= 1"
                @click="pagina--"
            >
                Anterior
            </Boton>
            <Boton
                variante="secundario"
                tamano="sm"
                :disabled="pagina >= paginas"
                @click="pagina++"
            >
                Siguiente
            </Boton>
        </div>
    </footer>
</template>
