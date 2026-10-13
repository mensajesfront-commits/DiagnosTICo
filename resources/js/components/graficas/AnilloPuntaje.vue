<script setup lang="ts">
/**
 * Puntaje de 0 a 100 en un anillo del color de su nivel (E1, E5). El
 * puntaje y el nivel llegan calculados del backend.
 */
import { computed } from 'vue';
import { niveles } from '@/lib/niveles';
import type { Nivel } from '@/lib/niveles';

const props = withDefaults(
    defineProps<{ puntaje: number; nivel: Nivel; tamano?: number }>(),
    { tamano: 96 },
);

const radio = 42;
const largo = 2 * Math.PI * radio;
const trazo = computed(
    () =>
        `${(Math.max(0, Math.min(100, props.puntaje)) / 100) * largo} ${largo}`,
);
</script>

<template>
    <svg
        :width="tamano"
        :height="tamano"
        viewBox="0 0 100 100"
        role="img"
        :aria-label="`${puntaje} de 100 · ${niveles[nivel].nombre}`"
    >
        <circle
            cx="50"
            cy="50"
            :r="radio"
            fill="none"
            stroke="#ebe9e3"
            stroke-width="10"
        />
        <circle
            cx="50"
            cy="50"
            :r="radio"
            fill="none"
            :stroke="niveles[nivel].color"
            stroke-width="10"
            stroke-linecap="round"
            :stroke-dasharray="trazo"
            transform="rotate(-90 50 50)"
        />
        <text
            x="50"
            y="50"
            text-anchor="middle"
            dominant-baseline="central"
            class="fill-tinta font-mono text-[26px] font-semibold"
        >
            {{ puntaje }}
        </text>
    </svg>
</template>
