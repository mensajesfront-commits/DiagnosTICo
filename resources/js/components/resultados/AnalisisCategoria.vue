<script setup lang="ts">
/**
 * E6 / A3.3 · Análisis de la IA de una categoría: el diagnóstico, lo que
 * encontró el método por grupos, lo que la empresa ya hace bien y las
 * recomendaciones en orden de prioridad con sus pasos.
 *
 * Los resultados publicados antes del análisis completo solo traen la
 * observación y una recomendación; se muestran tal cual (RN-023).
 */
import { computed, useId } from 'vue';
import EtiquetaMetodo from '@/components/resultados/EtiquetaMetodo.vue';
import TarjetaRecomendacion from '@/components/resultados/TarjetaRecomendacion.vue';
import type { CategoriaResultado } from '@/types/resultados';

const props = defineProps<{ categoria: CategoriaResultado }>();

const id = useId();

const hallazgos = computed(() => props.categoria.hallazgos ?? []);
const fortalezas = computed(() => props.categoria.fortalezas ?? []);
const recomendaciones = computed(() =>
    props.categoria.recomendaciones?.length
        ? props.categoria.recomendaciones
        : [props.categoria.recomendacion],
);
</script>

<template>
    <div class="flex flex-col gap-5">
        <section :aria-labelledby="`${id}-diagnostico`">
            <h3
                :id="`${id}-diagnostico`"
                class="flex items-center gap-2 text-sm font-semibold"
            >
                <span
                    class="rounded border border-linea bg-white px-1 font-mono text-[10px]"
                    >IA</span
                >
                Diagnóstico
            </h3>
            <p
                class="mt-1.5 rounded-lg bg-lienzo px-4 py-3 text-sm leading-relaxed"
            >
                {{ categoria.observacion }}
            </p>
        </section>

        <section v-if="categoria.metodo" :aria-labelledby="`${id}-hallazgos`">
            <h3
                :id="`${id}-hallazgos`"
                class="flex flex-wrap items-center gap-2 text-sm font-semibold"
            >
                Lo que encontró
                <EtiquetaMetodo :metodo="categoria.metodo" />
            </h3>
            <p
                v-if="categoria.metodo.motivo"
                class="mt-1 text-xs text-tinta-suave"
            >
                {{ categoria.metodo.motivo }}
            </p>
            <dl v-if="hallazgos.length" class="mt-2 grid gap-2 sm:grid-cols-2">
                <div
                    v-for="h in hallazgos"
                    :key="h.grupo"
                    class="rounded-lg border border-linea px-3 py-2"
                >
                    <dt
                        class="text-xs font-semibold tracking-wide text-marca uppercase"
                    >
                        {{ h.grupo }}
                    </dt>
                    <dd>
                        <ul
                            class="mt-1 flex list-disc flex-col gap-1 pl-4 text-sm"
                        >
                            <li v-for="(punto, i) in h.puntos" :key="i">
                                {{ punto }}
                            </li>
                        </ul>
                    </dd>
                </div>
            </dl>
        </section>

        <section v-if="fortalezas.length" :aria-labelledby="`${id}-fortalezas`">
            <h3 :id="`${id}-fortalezas`" class="text-sm font-semibold">
                Lo que ya haces bien
            </h3>
            <ul class="mt-1.5 flex flex-col gap-1 text-sm">
                <li
                    v-for="(f, i) in fortalezas"
                    :key="i"
                    class="flex gap-2 rounded-lg bg-lienzo px-3 py-2"
                >
                    <span class="text-marca" aria-hidden="true">✓</span>
                    <span>{{ f }}</span>
                </li>
            </ul>
        </section>

        <section :aria-labelledby="`${id}-recomendaciones`">
            <h3 :id="`${id}-recomendaciones`" class="text-sm font-semibold">
                {{
                    recomendaciones.length > 1
                        ? 'Recomendaciones, de la más a la menos importante'
                        : 'Recomendación'
                }}
            </h3>
            <div class="mt-1.5 flex flex-col gap-2">
                <TarjetaRecomendacion
                    v-for="(r, i) in recomendaciones"
                    :key="i"
                    :recomendacion="r"
                    :numero="recomendaciones.length > 1 ? i + 1 : undefined"
                />
            </div>
        </section>
    </div>
</template>
