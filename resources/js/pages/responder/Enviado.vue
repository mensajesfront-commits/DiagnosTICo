<script setup lang="ts">
/**
 * E4 · Diagnóstico enviado: espera del análisis (HU-063).
 *
 * Muestra los dos pasos (puntajes y análisis por categoría) y se actualiza
 * sola cada 15 segundos. Se puede salir: las respuestas ya están guardadas
 * y, cuando esté listo, llega un correo.
 *
 * [FUNCIONALIDAD POR DEFINIR] El cálculo de puntajes y el análisis de la IA
 * (HU-068, HU-069), el correo de "listo", el aviso de categorías que fallaron
 * y "Ver mi resultado" (E6) llegan en la semana 5. Mientras tanto la
 * medición queda "Enviada".
 */
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted } from 'vue';
import BarraResponder from '@/components/responder/BarraResponder.vue';
import { cn } from '@/lib/utils';
import { empresa as inicioEmpresa } from '@/routes/inicio';
import type { CabeceraMedicion } from '@/types/responder';

const props = defineProps<{
    medicion: CabeceraMedicion;
    estado: string;
    enviadaEn: string | null;
}>();

const listo = computed(() => props.estado === 'terminada');

let reloj: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    reloj = setInterval(() => {
        if (!listo.value) {
            router.reload({ only: ['estado'] });
        }
    }, 15000);
});

onBeforeUnmount(() => reloj && clearInterval(reloj));

const pasos = [
    'Calculando puntajes de opción única y múltiple',
    'Analizando cada categoría: observaciones y recomendaciones',
];
</script>

<template>
    <Head title="Diagnóstico enviado" />

    <BarraResponder :con-salir="false" />

    <main
        class="flex min-h-[calc(100dvh-57px)] items-center justify-center px-6"
    >
        <section
            class="w-full max-w-lg rounded-xl border border-linea bg-white p-8"
        >
            <p class="font-mono text-xs text-tinta-suave">
                DIAGNÓSTICO ENVIADO · MEDICIÓN {{ medicion.numero }}
            </p>
            <h1 class="mt-1 text-2xl font-semibold">
                {{
                    listo
                        ? 'Tu resultado está listo'
                        : 'Estamos analizando tus respuestas'
                }}
            </h1>
            <p class="mt-1 text-sm text-tinta-suave">
                {{
                    listo
                        ? 'Ya puedes ver tu puntaje, tu nivel y tus recomendaciones.'
                        : 'Suele tardar menos de un minuto; si tarda más, te avisamos por correo.'
                }}
            </p>
            <div
                v-if="!listo"
                class="mt-5 h-1.5 overflow-hidden rounded-full bg-lienzo-oscuro"
                aria-hidden="true"
            >
                <div class="h-1.5 w-2/5 animate-pulse rounded-full bg-marca" />
            </div>
            <ul class="mt-5 flex flex-col gap-4 text-sm">
                <li
                    v-for="paso in pasos"
                    :key="paso"
                    class="flex items-center gap-3"
                >
                    <span
                        :class="
                            cn(
                                'flex size-6 shrink-0 items-center justify-center rounded-full text-xs',
                                listo
                                    ? 'bg-exito-suave text-exito'
                                    : 'bg-marca text-white',
                            )
                        "
                        aria-hidden="true"
                    >
                        {{ listo ? '✓' : '•' }}
                    </span>
                    <span class="flex-1">{{ paso }}</span>
                    <span class="text-xs text-tinta-suave">
                        {{ listo ? 'Listo' : 'En curso…' }}
                    </span>
                </li>
            </ul>
            <p class="mt-6 border-t border-linea pt-4 text-xs text-tinta-suave">
                Puedes cerrar esta ventana: tus respuestas ya están guardadas.
                <Link :href="inicioEmpresa()" class="text-marca underline">
                    Ir a mi inicio
                </Link>
            </p>
        </section>
    </main>
</template>
