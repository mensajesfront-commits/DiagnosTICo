<script setup lang="ts">
/**
 * Una pregunta en el editor (A2.1): número, texto, tipo, indicación para
 * responder y opciones con su puntaje, con lo que le falta resaltado
 * ("⚠ Falta la indicación", "Suma máxima: 80 / 100 ⚠").
 *
 * El asa ⋮⋮ se arrastra para cambiar el orden (HU-023 CA-004); con el
 * teclado, flecha arriba o abajo sobre el asa.
 */
import { GripVertical } from '@lucide/vue';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import { cn } from '@/lib/utils';
import type { PreguntaEditor } from '@/types/editor';

const props = defineProps<{
    pregunta: PreguntaEditor;
    numero: number;
    resaltada?: boolean;
    arrastrando?: boolean;
}>();

const emit = defineEmits<{ editar: []; mover: [paso: -1 | 1] }>();

const LETRAS = 'ABCDEFGHIJ';

const tipos = {
    abierta: '✎ Abierta',
    opcion_unica: '○ Opción única',
    seleccion_multiple: '☐ Selección múltiple',
};

const detalle = computed(() => {
    const p = props.pregunta;
    const obligatoria = p.obligatoria ? 'obligatoria' : 'opcional';

    if (p.tipo === 'abierta') {
        return `Calificada por IA de 0 a 100 · ${obligatoria}`;
    }

    const n = `${p.opciones.length} ${p.opciones.length === 1 ? 'opción' : 'opciones'}`;

    return p.tipo === 'seleccion_multiple'
        ? `${n} · se suman las marcadas · ${obligatoria}`
        : `${n} · ${obligatoria}`;
});

const sinIndicacion = computed(() => !props.pregunta.indicacion.trim());

const suma = computed(() =>
    props.pregunta.opciones.reduce((s, o) => s + o.puntaje, 0),
);

/** Problemas que no se muestran en otro lugar de la tarjeta. */
const otros = computed(() =>
    props.pregunta.problemas.filter(
        (p) =>
            !p.startsWith('Falta la indicación') &&
            !p.startsWith('La selección'),
    ),
);

function teclas(e: KeyboardEvent): void {
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
        e.preventDefault();
        emit('mover', e.key === 'ArrowUp' ? -1 : 1);
    }
}
</script>

<template>
    <article
        :id="`pregunta-${pregunta.id}`"
        :class="
            cn(
                'rounded-xl border bg-white px-4 py-3 transition-shadow',
                resaltada
                    ? 'border-alerta shadow-[0_0_0_3px] shadow-alerta/20'
                    : 'border-linea',
                arrastrando && 'opacity-50',
            )
        "
    >
        <div class="flex items-start gap-3">
            <button
                type="button"
                draggable="true"
                class="mt-0.5 flex cursor-grab items-center gap-0.5 rounded text-tinta-suave hover:text-tinta focus-visible:ring-2 focus-visible:ring-marca/40 focus-visible:outline-none"
                :aria-label="`Mover la pregunta ${numero} (flechas arriba y abajo)`"
                @keydown="teclas"
            >
                <GripVertical class="size-4" aria-hidden="true" />
                <span class="font-mono text-xs">{{ numero }}</span>
            </button>
            <div class="min-w-0 flex-1">
                <p class="font-medium">{{ pregunta.texto }}</p>
                <p
                    class="mt-1 flex flex-wrap items-center gap-2 text-xs text-tinta-suave"
                >
                    <span
                        class="rounded bg-lienzo-oscuro px-1.5 py-0.5 font-medium text-tinta"
                    >
                        {{ tipos[pregunta.tipo] }}
                    </span>
                    {{ detalle }}
                </p>
                <p
                    v-if="sinIndicacion"
                    class="mt-1.5 text-xs font-medium text-alerta"
                >
                    ⚠ Falta la indicación de cómo responder
                </p>
                <p v-else class="mt-1.5 text-xs">
                    <strong class="text-marca">Cómo responder</strong> ·
                    {{ pregunta.indicacion }}
                </p>

                <dl
                    v-if="pregunta.opciones.length"
                    class="mt-2 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2"
                >
                    <div
                        v-for="(o, i) in pregunta.opciones"
                        :key="o.id ?? i"
                        class="flex items-baseline gap-2 border-b border-linea pb-1"
                    >
                        <dt class="text-xs text-tinta-suave">
                            {{ LETRAS[i] }}
                        </dt>
                        <dd class="flex-1">
                            {{ o.texto }}
                            <span
                                v-if="o.exclusiva"
                                class="text-xs text-tinta-suave"
                                title="Al marcarla se desmarcan las demás"
                            >
                                · excluye las demás
                            </span>
                            <span
                                v-if="o.con_texto"
                                class="text-xs text-tinta-suave"
                                title="Al elegirla, la empresa escribe cuál"
                            >
                                · pide escribir cuál
                            </span>
                        </dd>
                        <dd class="font-mono text-xs">{{ o.puntaje }}</dd>
                    </div>
                </dl>
                <p
                    v-if="pregunta.tipo === 'seleccion_multiple' && suma < 100"
                    class="mt-2 flex flex-wrap items-center gap-2 text-xs text-alerta"
                >
                    <span
                        class="rounded bg-alerta-suave px-2 py-0.5 font-mono font-semibold"
                    >
                        Suma máxima: {{ suma }} / 100 ⚠
                    </span>
                    Si marca todas llega a {{ suma }}: sube algún puntaje para
                    que pueda llegar a 100.
                </p>
                <p
                    v-if="pregunta.tipo === 'abierta'"
                    class="mt-1.5 text-xs text-tinta-suave"
                >
                    Respuesta escrita: la IA la califica de 0 a 100
                    {{
                        pregunta.criterio_ia
                            ? `con este criterio: ${pregunta.criterio_ia}`
                            : 'según el criterio de la categoría.'
                    }}
                </p>
                <p
                    v-for="p in otros"
                    :key="p"
                    class="mt-1.5 text-xs font-medium text-alerta"
                >
                    ⚠ {{ p }}
                </p>
            </div>
            <Boton variante="secundario" tamano="sm" @click="emit('editar')">
                Editar
            </Boton>
        </div>
    </article>
</template>
