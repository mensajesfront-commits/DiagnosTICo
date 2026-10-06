<script setup lang="ts">
/**
 * Prueba técnica T-024 y T-025: el radar de Chart.js en pantalla y el enlace
 * para descargar el mismo radar en PDF. Solo existe en local.
 */
import { Head } from '@inertiajs/vue3';
import RadarCategorias from '@/components/graficas/RadarCategorias.vue';
import type { SerieRadar } from '@/components/graficas/RadarCategorias.vue';
import Tarjeta from '@/components/base/Tarjeta.vue';
import { pdf } from '@/routes/prueba-tecnica';

defineProps<{
    radar: {
        empresa: string;
        etiquetas: string[];
        actual: SerieRadar;
        anterior: SerieRadar;
    };
}>();
</script>

<template>
    <Head title="Prueba técnica · gráficas" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold text-tinta">
                Prueba técnica · radar
            </h1>
            <p class="text-sm text-tinta-suave">
                Chart.js con vue-chartjs. El mismo radar se genera en PDF con
                spatie/laravel-pdf.
                <a :href="pdf().url" class="font-medium text-marca underline">
                    Descargar PDF
                </a>
            </p>
        </div>

        <Tarjeta titulo="Mapa de las 10 categorías" class="max-w-3xl">
            <RadarCategorias
                :etiquetas="radar.etiquetas"
                :actual="radar.actual"
                :anterior="radar.anterior"
            />
        </Tarjeta>
    </div>
</template>
