<script setup lang="ts">
/**
 * Evolución del puntaje de una empresa, medición por medición (A3.1,
 * HU-033 CA-001). Detrás de la línea, las franjas de los niveles (RN-019):
 * Crítico 0–29, Se puede mejorar 30–59, Vas en buen camino 60–79 y Sigue así
 * 80–100. Los puntajes llegan calculados del backend.
 */
import {
    CategoryScale,
    Chart as ChartJS,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import type { ChartData, ChartOptions, Plugin } from 'chart.js';
import { computed, onMounted, ref } from 'vue';
import { Line } from 'vue-chartjs';
import { diaYMes, fechaLocal } from '@/lib/fechas';
import { colores, niveles } from '@/lib/niveles';
import type { Nivel } from '@/lib/niveles';
import type { PuntoEvolucion } from '@/types/empresas';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
);

const props = defineProps<{ puntos: PuntoEvolucion[] }>();

/** Franjas de nivel: desde, hasta y nivel. */
const franjas: [number, number, Exclude<Nivel, 'sin'>][] = [
    [0, 30, 'critico'],
    [30, 60, 'mejorar'],
    [60, 80, 'camino'],
    [80, 100, 'sigue'],
];

const fondoNiveles: Plugin<'line'> = {
    id: 'fondoNiveles',
    beforeDatasetsDraw(chart) {
        const { ctx, chartArea, scales } = chart;
        const y = scales.y;

        ctx.save();
        ctx.font = "11px 'IBM Plex Sans', sans-serif";
        ctx.textAlign = 'right';
        ctx.textBaseline = 'top';

        for (const [desde, hasta, nivel] of franjas) {
            const arriba = y.getPixelForValue(hasta);
            const abajo = y.getPixelForValue(desde);
            ctx.globalAlpha = 0.12;
            ctx.fillStyle = niveles[nivel].color;
            ctx.fillRect(
                chartArea.left,
                arriba,
                chartArea.width,
                abajo - arriba,
            );
            // El nombre del nivel, arriba a la derecha de su franja.
            ctx.globalAlpha = 1;
            ctx.fillText(
                niveles[nivel].nombre,
                chartArea.right - 6,
                arriba + 4,
            );
        }

        ctx.restore();
    },
    // El puntaje encima de cada punto, como en el wireframe.
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        const puntos = chart.getDatasetMeta(0).data;

        ctx.save();
        ctx.font = "600 12px 'IBM Plex Mono', monospace";
        ctx.fillStyle = colores.tinta;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';
        puntos.forEach((punto, i) => {
            ctx.fillText(
                String(props.puntos[i]?.puntaje ?? ''),
                punto.x,
                punto.y - 8,
            );
        });
        ctx.restore();
    },
};

const datos = computed<ChartData<'line'>>(() => ({
    labels: props.puntos.map(
        (p) => `M${p.numero} · ${diaYMes(fechaLocal(p.fecha))}`,
    ),
    datasets: [
        {
            label: 'Puntaje',
            data: props.puntos.map((p) => p.puntaje),
            borderColor: colores.tinta,
            backgroundColor: colores.tinta,
            borderWidth: 2,
            pointRadius: 4,
            tension: 0.2,
        },
    ],
}));

const fuentesListas = ref(false);

onMounted(async () => {
    await document.fonts.load("11px 'IBM Plex Mono'");
    fuentesListas.value = true;
});

const fuente = { family: 'IBM Plex Mono', size: 11 };

const opciones: ChartOptions<'line'> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: {
            min: 0,
            max: 100,
            ticks: { font: fuente, color: colores.tintaSuave },
            // Las marcas en los cortes de los niveles (RN-019).
            afterBuildTicks: (eje) => {
                eje.ticks = [0, 30, 60, 80, 100].map((value) => ({ value }));
            },
            grid: { color: colores.linea },
        },
        x: {
            offset: true,
            ticks: { font: fuente, color: colores.tintaSuave },
            grid: { display: false },
        },
    },
};
</script>

<template>
    <div class="relative h-full min-h-40">
        <Line
            v-if="fuentesListas"
            :data="datos"
            :options="opciones"
            :plugins="[fondoNiveles]"
            aria-label="Evolución del puntaje por medición"
            role="img"
        />
    </div>
</template>
