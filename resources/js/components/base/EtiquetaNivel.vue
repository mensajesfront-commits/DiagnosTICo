<script setup lang="ts">
/**
 * Etiqueta del nivel del resultado ("Se puede mejorar", "Sigue así"…).
 * El nivel llega calculado desde el backend (RN-019).
 */
import { niveles } from '@/lib/niveles';
import type { Nivel } from '@/lib/niveles';
import { cn } from '@/lib/utils';

defineProps<{ nivel: Nivel; class?: string }>();
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center rounded px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                niveles[nivel].etiqueta,
                $props.class,
            )
        "
    >
        {{ niveles[nivel].nombre }}
    </span>
</template>
