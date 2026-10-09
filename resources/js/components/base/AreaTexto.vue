<script setup lang="ts">
/** Área de texto con el estilo de Entrada (descripciones, indicaciones). */
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

defineProps<{ invalida?: boolean; class?: string }>();

const modelo = defineModel<string>();
</script>

<template>
    <textarea
        v-model="modelo"
        v-bind="$attrs"
        :aria-invalid="invalida || undefined"
        :class="
            cn(
                'min-h-20 w-full rounded-md border border-linea-fuerte bg-white px-3 py-2 text-sm text-tinta placeholder:text-tinta-suave/70 focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none disabled:bg-lienzo disabled:text-tinta-suave',
                invalida && 'border-aviso',
                $props.class,
            )
        "
    />
</template>
