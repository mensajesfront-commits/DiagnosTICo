<script setup lang="ts">
/**
 * Caja de texto del wireframe: fondo blanco, borde gris y altura de 40 px.
 * Pasa al <input> todos los atributos (name, type, autocomplete…), así sirve
 * igual con el componente <Form> de Inertia.
 */
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

defineProps<{ invalida?: boolean; class?: string }>();

const modelo = defineModel<string>();
</script>

<template>
    <input
        v-model="modelo"
        v-bind="$attrs"
        :aria-invalid="invalida || undefined"
        :class="
            cn(
                'h-10 w-full rounded-md border border-linea-fuerte bg-white px-3 text-sm text-tinta placeholder:text-tinta-suave/70 focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none disabled:bg-lienzo disabled:text-tinta-suave',
                invalida && 'border-aviso',
                $props.class,
            )
        "
    />
</template>
