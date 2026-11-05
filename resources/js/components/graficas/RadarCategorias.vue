<script setup lang="ts">
/**
 * Radar de las categorías: la medición actual en línea continua y la anterior
 * punteada (E6, E8, A3.1, A2.6). Prueba técnica T-024.
 *
 * Con `colores` (uno por categoría, el de su nivel), cada vértice lleva un
 * número en un círculo de ese color en vez del nombre: los nombres largos no
 * se cortan y la lista de al lado dice qué es cada número.
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
import { colores, niveles } from '@/lib/niveles';

/**
 * Con vértices numerados, los anillos marcan los límites de los niveles
 * (30, 60, 80 y 100) con un tono de su color.
 */
const ANILLOS: { valor: number; color: string }[] = [
    { valor: 30, color: niveles.critico.color },
    { valor: 60, color: niveles.mejorar.color },
    { valor: 80, color: niveles.camino.color },
    { valor: 100, color: niveles.sigue.color },
];

ChartJS.register(RadialLinearScale, PointElement, LineElement, Filler, Tooltip);

export type SerieRadar = {
    nombre: string;
    puntajes: number[];
};

const props = defineProps<{
    etiquetas: string[];
    actual: SerieRadar;
    anterior?: SerieRadar | null;
    /** Alto del área de la gráfica (clases de Tailwind). */
    alto?: string;
    /** Color de cada categoría (su nivel): vértices numerados. */
    colores?: string[];
}>();

const numerado = computed(() => (props.colores?.length ?? 0) > 0);

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
            pointRadius: numerado.value ? 4 : 2,
            pointBackgroundColor: props.colores?.length
                ? props.colores
                : colores.marca,
            pointBorderColor: '#ffffff',
            pointBorderWidth: numerado.value ? 1.5 : 0,
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

const opciones = computed<ChartOptions<'radar'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    layout: { padding: numerado.value ? 6 : 0 },
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                title: (items) =>
                    props.etiquetas[items[0]?.dataIndex ?? 0] ?? '',
            },
        },
    },
    scales: {
        r: {
            min: 0,
            max: 100,
            ticks: { stepSize: 25, display: false },
            ...(numerado.value
                ? {
                      afterBuildTicks: (escala: {
                          ticks: { value: number }[];
                      }) => {
                          escala.ticks = [
                              { value: 0 },
                              ...ANILLOS.map((a) => ({ value: a.valor })),
                          ];
                      },
                  }
                : {}),
            grid: numerado.value
                ? {
                      color: (ctx: { tick?: { value: number } }) => {
                          const anillo = ANILLOS.find(
                              (a) => a.valor === ctx.tick?.value,
                          );

                          return anillo ? `${anillo.color}66` : colores.linea;
                      },
                      lineWidth: 1.25,
                  }
                : { color: colores.linea },
            angleLines: { color: colores.linea },
            pointLabels: numerado.value
                ? {
                      callback: (_etiqueta: string, i: number) => String(i + 1),
                      font: {
                          family: 'IBM Plex Mono',
                          size: 12,
                          weight: 'bold',
                      },
                      color: '#ffffff',
                      backdropColor: (ctx: { index: number }) =>
                          props.colores?.[ctx.index] ?? colores.marca,
                      backdropPadding: { top: 4, bottom: 3, left: 7, right: 7 },
                      borderRadius: 9,
                      padding: 3,
                  }
                : {
                      font: { family: 'IBM Plex Mono', size: 11 },
                      color: colores.tinta,
                  },
        },
    },
}));
</script>

<template>
    <div class="flex flex-col gap-2">
        <div
            class="flex flex-wrap items-center justify-end gap-x-4 gap-y-1 text-xs text-tinta-suave"
        >
            <span
                v-if="numerado"
                class="mr-auto flex items-center gap-1.5"
                title="Los anillos marcan dónde empieza cada nivel: 30, 60, 80 y 100."
            >
                <span class="flex gap-0.5" aria-hidden="true">
                    <span
                        v-for="a in ANILLOS"
                        :key="a.valor"
                        class="size-2 rounded-full border-2"
                        :style="{ borderColor: a.color }"
                    />
                </span>
                Anillos: 30 · 60 · 80 · 100
            </span>
            <span class="flex items-center gap-1.5">
                <span class="h-0.5 w-5 bg-marca" />
                {{ actual.nombre }}
            </span>
            <span v-if="anterior" class="flex items-center gap-1.5">
                <span class="w-5 border-t-2 border-dashed border-[#8a909c]" />
                {{ anterior.nombre }}
            </span>
        </div>
        <div :class="['relative', alto ?? 'h-80']">
            <Radar v-if="fuentesListas" :data="datos" :options="opciones" />
        </div>
    </div>
</template>
