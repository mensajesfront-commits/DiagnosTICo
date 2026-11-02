<script setup lang="ts">
/**
 * E6 / A3.3 · Una recomendación de la IA con su plan: por qué hacerla, los
 * pasos en orden, el plazo y cómo saber si funcionó. Los resultados viejos
 * solo traen título y detalle, y se ven igual que antes (RN-023).
 */
import type { Recomendacion, RecomendacionCompleta } from '@/types/resultados';

defineProps<{
    recomendacion: Recomendacion | RecomendacionCompleta;
    /** Orden de prioridad (1 es la más importante). */
    numero?: number;
}>();

function pasos(r: Recomendacion | RecomendacionCompleta): string[] {
    return 'pasos' in r ? r.pasos : [];
}

function plazo(r: Recomendacion | RecomendacionCompleta): string {
    return 'plazo' in r ? r.plazo : '';
}

function indicador(r: Recomendacion | RecomendacionCompleta): string {
    return 'indicador' in r ? r.indicador : '';
}
</script>

<template>
    <article class="rounded-lg border border-linea bg-white px-4 py-3">
        <div class="flex items-start justify-between gap-3">
            <h4 class="text-base font-semibold">
                <span v-if="numero" class="mr-1 font-mono text-marca"
                    >{{ numero }}.</span
                >
                {{ recomendacion.titulo }}
            </h4>
            <span
                v-if="plazo(recomendacion)"
                class="shrink-0 rounded bg-lienzo px-2 py-0.5 text-xs whitespace-nowrap text-tinta-suave"
            >
                <span class="sr-only">Plazo:</span>{{ plazo(recomendacion) }}
            </span>
        </div>
        <p v-if="recomendacion.detalle" class="mt-1 text-sm text-tinta-suave">
            {{ recomendacion.detalle }}
        </p>
        <ol
            v-if="pasos(recomendacion).length"
            class="mt-3 flex flex-col gap-1.5 text-sm"
            aria-label="Pasos"
        >
            <li
                v-for="(paso, i) in pasos(recomendacion)"
                :key="i"
                class="flex gap-2"
            >
                <span
                    class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-marca-suave font-mono text-[11px] font-semibold text-marca"
                    aria-hidden="true"
                    >{{ i + 1 }}</span
                >
                <span>{{ paso }}</span>
            </li>
        </ol>
        <p
            v-if="indicador(recomendacion)"
            class="mt-3 border-t border-lienzo-oscuro pt-2 text-sm"
        >
            <strong class="font-medium">Cómo saber si funcionó:</strong>
            {{ indicador(recomendacion) }}
        </p>
    </article>
</template>
