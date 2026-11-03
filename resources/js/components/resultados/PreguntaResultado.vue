<script setup lang="ts">
/**
 * Una pregunta del resultado (E6 detalle, E9): el texto, la respuesta, su
 * puntaje ("· IA" si lo dio la IA, en las abiertas) y la observación de la
 * IA. Solo lectura (RN-017).
 *
 * `tarjeta` la muestra como tarjeta suelta (E9 · Mis respuestas); sin ella,
 * como fila de una lista (E6).
 */
import { CircleDot, ListChecks, PenLine, Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { PreguntaResultado } from '@/types/resultados';

const props = withDefaults(
    defineProps<{
        pregunta: PreguntaResultado;
        numero?: number;
        /** Lo ve el equipo de NuevasTIC: "Respuesta" en vez de "Tu respuesta". */
        interno?: boolean;
        tarjeta?: boolean;
    }>(),
    { numero: undefined, interno: false, tarjeta: false },
);

const tipos = {
    opcion_unica: { nombre: 'Opción única', icono: CircleDot },
    seleccion_multiple: { nombre: 'Selección múltiple', icono: ListChecks },
    abierta: { nombre: 'Pregunta abierta', icono: PenLine },
} as const;

const tipo = computed(() => tipos[props.pregunta.tipo]);

const extra = computed(
    () => props.pregunta.extra === true && props.pregunta.respuesta !== null,
);

/** Las opciones elegidas llegan separadas por " · " (una o varias). */
const opciones = computed(() =>
    props.pregunta.tipo === 'abierta' || props.pregunta.respuesta === null
        ? []
        : props.pregunta.respuesta.split(' · '),
);
</script>

<template>
    <article
        :class="
            cn(
                tarjeta
                    ? 'rounded-2xl border border-linea bg-white p-4 sm:p-5'
                    : 'border-b border-lienzo-oscuro pb-4 last:border-0',
            )
        "
    >
        <div class="flex items-start gap-3">
            <span
                v-if="numero"
                class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-marca-suave font-mono text-xs font-semibold text-marca"
                aria-hidden="true"
                >{{ numero }}</span
            >
            <div class="min-w-0 flex-1">
                <div
                    class="flex flex-col gap-1.5 sm:flex-row sm:items-start sm:justify-between sm:gap-4"
                >
                    <component
                        :is="tarjeta ? 'h3' : 'h4'"
                        class="text-sm leading-6 font-medium"
                    >
                        <span class="sr-only" v-if="numero"
                            >Pregunta {{ numero }}:
                        </span>
                        {{ pregunta.texto }}
                    </component>
                    <span
                        :class="
                            cn(
                                'inline-flex shrink-0 items-center gap-1 self-start rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
                                extra || pregunta.puntaje === null
                                    ? 'bg-lienzo-oscuro text-tinta-suave'
                                    : 'bg-marca-suave text-marca',
                            )
                        "
                    >
                        <template v-if="extra">Dato extra</template>
                        <template v-else-if="pregunta.puntaje !== null">
                            <span class="font-mono">{{
                                pregunta.puntaje
                            }}</span>
                            pts
                            <template v-if="pregunta.tipo === 'abierta'">
                                <Sparkles class="size-3" aria-hidden="true" />
                                <span class="sr-only">dados por la IA</span>
                                <span aria-hidden="true">IA</span>
                            </template>
                        </template>
                        <template v-else>Sin puntaje</template>
                    </span>
                </div>

                <p
                    class="mt-1 inline-flex items-center gap-1 text-xs text-tinta-suave"
                >
                    <component
                        :is="tipo.icono"
                        class="size-3.5"
                        aria-hidden="true"
                    />
                    {{ tipo.nombre }}
                </p>

                <div v-if="pregunta.respuesta !== null" class="mt-3">
                    <p class="mb-1.5 text-xs font-medium text-tinta-suave">
                        {{ interno ? 'Respuesta' : 'Tu respuesta' }}
                    </p>
                    <blockquote
                        v-if="pregunta.tipo === 'abierta'"
                        class="rounded-xl border-l-4 border-marca bg-lienzo px-4 py-2.5 text-sm text-tinta"
                    >
                        “{{ pregunta.respuesta }}”
                    </blockquote>
                    <ul v-else class="flex flex-wrap gap-1.5">
                        <li
                            v-for="o in opciones"
                            :key="o"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-marca/30 bg-marca-suave/60 px-2.5 py-1 text-sm text-tinta"
                        >
                            <span
                                class="size-1.5 rounded-full bg-marca"
                                aria-hidden="true"
                            />
                            {{ o }}
                        </li>
                    </ul>
                </div>
                <p
                    v-else
                    class="mt-3 rounded-xl border border-dashed border-linea px-3 py-2 text-sm text-tinta-suave italic"
                >
                    Sin responder (opcional). No cuenta en el puntaje.
                </p>

                <p v-if="extra" class="mt-2 text-xs text-tinta-suave">
                    Pregunta opcional: la IA la tiene en cuenta, pero no cuenta
                    en el puntaje.
                </p>

                <div
                    v-if="pregunta.observacion"
                    class="mt-3 flex items-start gap-2.5 rounded-xl bg-linear-to-br from-marca-suave/70 to-white px-3 py-2.5 text-sm"
                >
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-lg bg-white text-marca shadow-xs"
                        aria-hidden="true"
                    >
                        <Sparkles class="size-3.5" />
                    </span>
                    <p class="text-tinta">
                        <strong class="font-semibold">{{
                            pregunta.tipo === 'abierta'
                                ? 'Calificación de la IA: '
                                : 'Nota de la IA: '
                        }}</strong>
                        {{ pregunta.observacion }}
                    </p>
                </div>
            </div>
        </div>
    </article>
</template>
