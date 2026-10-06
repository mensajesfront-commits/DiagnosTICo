<script setup lang="ts">
/**
 * Radar de las categorías: la medición actual en línea continua y la anterior
 * punteada (E6, E8, A3.1, A2.6). Prueba técnica T-024.
 */
import {
    Chart as ChartJS,
    Filler,
    LineElement,
    PointElement,
    RadialLinearScale,
    Tooltip,
} from 'chart.js';
import type { ChartData, ChartOptions } from 'chart.js';
import { computed, onMounted, ref } from 'vue';
import { Radar } from 'vue-chartjs';
import { colores } from '@/lib/niveles';

ChartJS.register(RadialLinearScale, PointElement, LineElement, Filler, Tooltip);

export type SerieRadar = {
    nombre: string;
    puntajes: number[];
};

const props = defineProps<{
    etiquetas: string[];
    actual: SerieRadar;
    anterior?: SerieRadar | null;
}>();

const datos = computed<ChartData<'radar'>>(() => ({
    labels: props.etiquetas.map(
        (etiqueta, i) => `${etiqueta} · ${props.actual.puntajes[i] ?? '–'}`,
    ),
    datasets: [
        {
            label: props.actual.nombre,
            data: props.actual.puntajes,
            borderColor: colores.marca,
            backgroundColor: 'rgba(45, 74, 122, 0.12)',
            borderWidth: 2,
            pointRadius: 2,
            pointBackgroundColor: colores.marca,
            fill: true,
        },
        ...(props.anterior
            ? [
                  {
                      label: props.anterior.nombre,
                      data: props.anterior.puntajes,
                      borderColor: '#8a909c',
                      backgroundColor: 'transparent',
                      borderDash: [4, 4],
                      borderWidth: 1.5,
                      pointRadius: 0,
                  },
              ]
            : []),
    ],
}));

// Chart.js dibuja en un canvas, que no espera a las fuentes web: si se dibuja
// antes de que cargue IBM Plex Mono, las etiquetas salen con otra letra.
const fuentesListas = ref(false);

onMounted(async () => {
    await document.fonts.load("11px 'IBM Plex Mono'");
    fuentesListas.value = true;
});

const opciones: ChartOptions<'radar'> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        r: {
            min: 0,
            max: 100,
            ticks: { stepSize: 25, display: false },
            grid: { color: colores.linea },
            angleLines: { color: colores.linea },
            pointLabels: {
                font: { family: 'IBM Plex Mono', size: 11 },
                color: colores.tinta,
            },
        },
    },
};
</script>

<template>
    <div class="flex flex-col gap-2">
        <div
            class="flex items-center justify-end gap-4 text-xs text-tinta-suave"
        >
            <span class="flex items-center gap-1.5">
                <span class="h-0.5 w-5 bg-marca" />
                {{ actual.nombre }}
            </span>
            <span v-if="anterior" class="flex items-center gap-1.5">
                <span class="w-5 border-t-2 border-dashed border-[#8a909c]" />
                {{ anterior.nombre }}
            </span>
        </div>
        <div class="relative h-80">
            <Radar v-if="fuentesListas" :data="datos" :options="opciones" />
        </div>
    </div>
</template>
