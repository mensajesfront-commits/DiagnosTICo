<script setup lang="ts">
/**
 * Línea pequeña de la evolución del puntaje (E1): un punto por medición
 * terminada, con su puntaje debajo. Desde la 2.ª medición (RN-020).
 */
import { computed } from 'vue';

const props = defineProps<{ puntajes: number[] }>();

const ancho = 280;
const alto = 56;
const margen = 12;

const puntos = computed(() => {
    const n = props.puntajes.length;

    return props.puntajes.map((p, i) => ({
        x: n === 1 ? ancho / 2 : margen + (i * (ancho - 2 * margen)) / (n - 1),
        y: alto - 16 - (p / 100) * (alto - 24),
        p,
    }));
});
</script>

<template>
    <svg
        :viewBox="`0 0 ${ancho} ${alto}`"
        class="w-full"
        role="img"
        :aria-label="`Evolución: ${puntajes.join(', ')}`"
    >
        <polyline
            :points="puntos.map((q) => `${q.x},${q.y}`).join(' ')"
            fill="none"
            stroke="#1e2533"
            stroke-width="1.5"
        />
        <g v-for="(q, i) in puntos" :key="i">
            <circle :cx="q.x" :cy="q.y" r="3.5" fill="#1e2533" />
            <text
                :x="q.x"
                :y="alto - 2"
                text-anchor="middle"
                class="fill-tinta-suave font-mono text-[10px]"
            >
                {{ q.p }}
            </text>
        </g>
    </svg>
</template>
