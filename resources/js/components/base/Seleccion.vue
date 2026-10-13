<script setup lang="ts">
/**
 * Lista desplegable nativa con el estilo de Entrada ("Selecciona tu sector").
 * Las opciones van en el slot como <option>.
 */
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

defineProps<{ invalida?: boolean; class?: string }>();

const modelo = defineModel<string | number>();
</script>

<template>
    <select
        v-model="modelo"
        v-bind="$attrs"
        :aria-invalid="invalida || undefined"
        :class="
            cn(
                'h-10 w-full rounded-md border border-linea-fuerte bg-white px-3 text-sm text-tinta focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none disabled:bg-lienzo',
                invalida && 'border-aviso',
                $props.class,
            )
        "
    >
        <slot />
    </select>
</template>
