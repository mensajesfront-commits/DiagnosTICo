<script setup lang="ts">
/**
 * "Ver:" de la gráfica de evolución (A3.1, A3.4, E8): el puntaje total o el
 * de una categoría. El valor vacío es el puntaje total.
 */
import type { EvolucionCategoria } from '@/types/empresas';

defineProps<{ categorias: EvolucionCategoria[] }>();

const elegida = defineModel<string>({ default: '' });
</script>

<template>
    <label
        v-if="categorias.length > 0"
        class="flex items-center gap-1.5 text-xs text-tinta-suave"
    >
        Ver:
        <select
            v-model="elegida"
            class="h-7 max-w-52 rounded-md border border-linea-fuerte bg-white px-2 text-xs text-tinta"
        >
            <option value="">Puntaje total</option>
            <option v-for="c in categorias" :key="c.nombre" :value="c.nombre">
                {{ c.nombre }}
            </option>
        </select>
    </label>
</template>
