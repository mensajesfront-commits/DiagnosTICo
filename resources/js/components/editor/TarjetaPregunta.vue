<script setup lang="ts">
/**
 * Una pregunta en el editor (A2.1): número, texto, tipo, indicación para
 * responder y opciones con su puntaje, con lo que le falta resaltado
 * ("⚠ Falta la indicación", "Suma máxima: 80 / 100 ⚠").
 *
 * El asa ⋮⋮ se arrastra para cambiar el orden (HU-023 CA-004); con el
 * teclado, flecha arriba o abajo sobre el asa.
 *
 * Diseño: número en un círculo, el tipo con ícono, "Cómo responder" en un
 * recuadro y cada opción con su letra, su puntaje en color (verde 100, azul
 * desde 50, ámbar por debajo, gris 0) y una barra. La nota "Ten en cuenta"
 * de la opción se ve debajo, en una línea.
 */
import {
    CircleDot,
    GripVertical,
    Info,
    Lightbulb,
    ListChecks,
    PenLine,
    Pencil,
    Sparkles,
    TriangleAlert,
} from '@lucide/vue';
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
    abierta: { texto: 'Abierta', icono: PenLine },
    opcion_unica: { texto: 'Opción única', icono: CircleDot },
    seleccion_multiple: { texto: 'Selección múltiple', icono: ListChecks },
};

/** Color del puntaje de una opción. */
function tonoPuntaje(puntaje: number): { texto: string; barra: string } {
    if (puntaje >= 100) {
        return { texto: 'bg-exito-suave text-exito', barra: 'bg-exito' };
    }

    if (puntaje >= 50) {
        return {
            texto: 'bg-nivel-camino-suave text-nivel-camino-texto',
            barra: 'bg-nivel-camino',
        };
    }

    if (puntaje > 0) {
        return { texto: 'bg-alerta-suave text-alerta', barra: 'bg-alerta' };
    }

    return {
        texto: 'bg-lienzo-oscuro text-tinta-suave',
        barra: 'bg-nivel-sin',
    };
}

const detalle = computed(() => {
    const p = props.pregunta;
    if (p.tipo === 'abierta') {
        return 'Calificada por IA de 0 a 100';
    }

    const n = `${p.opciones.length} ${p.opciones.length === 1 ? 'opción' : 'opciones'}`;

    return p.tipo === 'seleccion_multiple' ? `${n} · se suman las marcadas` : n;
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
                'group rounded-2xl border bg-white p-4 transition-shadow hover:shadow-sm',
                resaltada
                    ? 'border-alerta shadow-[0_0_0_3px] shadow-alerta/20'
                    : pregunta.problemas.length
                      ? 'border-alerta/50'
                      : 'border-linea',
                arrastrando && 'opacity-50',
            )
        "
    >
        <div class="flex items-start gap-3">
            <button
                type="button"
                draggable="true"
                class="flex shrink-0 cursor-grab flex-col items-center gap-1 rounded-lg pt-0.5 text-tinta-suave hover:text-tinta focus-visible:ring-2 focus-visible:ring-marca/40 focus-visible:outline-none active:cursor-grabbing"
                :aria-label="`Mover la pregunta ${numero} (flechas arriba y abajo)`"
                @keydown="teclas"
            >
                <span
                    class="flex size-7 items-center justify-center rounded-full bg-marca font-mono text-xs font-semibold text-white"
                >
                    {{ numero }}
                </span>
                <GripVertical
                    class="size-4 opacity-60 group-hover:opacity-100"
                    aria-hidden="true"
                />
            </button>

            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                    <p class="pt-0.5 leading-snug font-medium">
                        {{ pregunta.texto }}
                    </p>
                    <Boton
                        variante="secundario"
                        tamano="sm"
                        class="shrink-0 gap-1.5"
                        :aria-label="`Editar la pregunta ${numero}`"
                        @click="emit('editar')"
                    >
                        <Pencil class="size-3.5" aria-hidden="true" />
                        Editar
                    </Boton>
                </div>

                <div
                    class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-tinta-suave"
                >
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-marca-suave px-2 py-0.5 font-medium text-marca"
                    >
                        <component
                            :is="tipos[pregunta.tipo].icono"
                            class="size-3.5"
                            aria-hidden="true"
                        />
                        {{ tipos[pregunta.tipo].texto }}
                    </span>
                    <span
                        :class="
                            cn(
                                'rounded-full px-2 py-0.5',
                                pregunta.obligatoria
                                    ? 'bg-lienzo-oscuro text-tinta'
                                    : 'bg-lienzo text-tinta-suave',
                            )
                        "
                    >
                        {{ pregunta.obligatoria ? 'Obligatoria' : 'Opcional' }}
                    </span>
                    <span>{{ detalle }}</span>
                </div>

                <p
                    v-if="sinIndicacion"
                    class="mt-3 flex items-center gap-2 rounded-lg bg-alerta-suave px-3 py-2 text-xs font-medium text-alerta"
                >
                    <TriangleAlert
                        class="size-3.5 shrink-0"
                        aria-hidden="true"
                    />
                    Falta la indicación de cómo responder
                </p>
                <p
                    v-else
                    class="mt-3 flex gap-2 rounded-lg bg-lienzo/70 px-3 py-2 text-xs"
                >
                    <Info
                        class="mt-px size-3.5 shrink-0 text-marca"
                        aria-hidden="true"
                    />
                    <span>
                        <strong class="font-semibold text-marca">
                            Cómo responder:
                        </strong>
                        {{ pregunta.indicacion }}
                    </span>
                </p>

                <ul
                    v-if="pregunta.opciones.length"
                    class="mt-3 grid gap-2 sm:grid-cols-2"
                >
                    <li
                        v-for="(o, i) in pregunta.opciones"
                        :key="o.id ?? i"
                        class="rounded-lg border border-linea px-3 py-2"
                    >
                        <div class="flex items-start gap-2">
                            <span
                                class="mt-px flex size-5 shrink-0 items-center justify-center rounded bg-lienzo-oscuro font-mono text-[11px] font-semibold text-tinta-suave"
                                aria-hidden="true"
                            >
                                {{ LETRAS[i] }}
                            </span>
                            <span class="min-w-0 flex-1 text-sm leading-snug">
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
                            </span>
                            <span
                                :class="
                                    cn(
                                        'shrink-0 rounded-full px-2 py-0.5 font-mono text-xs font-semibold',
                                        tonoPuntaje(o.puntaje).texto,
                                    )
                                "
                                :title="`${o.puntaje} puntos`"
                            >
                                {{ o.puntaje }}
                            </span>
                        </div>
                        <div
                            class="mt-1.5 ml-7 h-1 rounded-full bg-lienzo-oscuro"
                            aria-hidden="true"
                        >
                            <div
                                :class="[
                                    'h-1 rounded-full',
                                    tonoPuntaje(o.puntaje).barra,
                                ]"
                                :style="{
                                    width: `${Math.min(100, o.puntaje)}%`,
                                }"
                            />
                        </div>
                        <p
                            v-if="o.ten_en_cuenta"
                            class="mt-1.5 ml-7 flex gap-1 text-[11px] text-tinta-suave"
                            :title="o.ten_en_cuenta"
                        >
                            <Lightbulb
                                class="mt-px size-3 shrink-0 text-alerta"
                                aria-hidden="true"
                            />
                            <span class="line-clamp-1">
                                <span class="font-medium">Ten en cuenta:</span>
                                {{ o.ten_en_cuenta }}
                            </span>
                        </p>
                    </li>
                </ul>
                <p
                    v-if="pregunta.tipo === 'seleccion_multiple' && suma < 100"
                    class="mt-3 flex flex-wrap items-center gap-2 rounded-lg bg-alerta-suave px-3 py-2 text-xs text-alerta"
                >
                    <span class="font-mono font-semibold">
                        Suma máxima: {{ suma }} / 100 ⚠
                    </span>
                    Si marca todas llega a {{ suma }}: sube algún puntaje para
                    que pueda llegar a 100.
                </p>
                <p
                    v-if="pregunta.tipo === 'abierta'"
                    class="mt-3 flex gap-2 rounded-lg border border-dashed border-marca/30 bg-marca-suave/40 px-3 py-2 text-xs"
                >
                    <Sparkles
                        class="mt-px size-3.5 shrink-0 text-marca"
                        aria-hidden="true"
                    />
                    <span>
                        Respuesta escrita: la IA la califica de 0 a 100
                        {{
                            pregunta.criterio_ia
                                ? `con este criterio: ${pregunta.criterio_ia}`
                                : 'según el criterio de la categoría.'
                        }}
                    </span>
                </p>
                <p
                    v-for="p in otros"
                    :key="p"
                    class="mt-2 flex items-center gap-2 text-xs font-medium text-alerta"
                >
                    <TriangleAlert
                        class="size-3.5 shrink-0"
                        aria-hidden="true"
                    />
                    {{ p }}
                </p>
            </div>
        </div>
    </article>
</template>
