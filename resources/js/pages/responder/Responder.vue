<script setup lang="ts">
/**
 * E2 · Responder el diagnóstico (HU-060).
 *
 * - Izquierda: las categorías como completas (✓), a medias (● 3/4) o por
 *   empezar, y "Revisar antes de enviar". Se puede saltar a cualquiera.
 * - Centro: las preguntas de la categoría, con "Cómo responder", si es
 *   obligatoria y el tipo: elegir una, marcar varias o escribir (máx. 1000).
 * - Cada respuesta se guarda al momento (las escritas, al dejar de escribir)
 *   y arriba se ve "✓ Guardado 10:42". "Guardar y salir" vuelve al inicio; al
 *   regresar sigue en la categoría donde iba.
 * - Abajo: categoría anterior y siguiente; en la última, "Revisar antes de
 *   enviar" (E3).
 *
 * La empresa no recibe los puntajes de las opciones (RN-013).
 *
 * Diseño para leer fácil (también con baja visión): el cuestionario usa casi
 * todo el ancho (hasta 64rem), letra de 18 px en las preguntas y 16 px en las
 * opciones, opciones grandes con borde (también las de "marca varias") y la
 * marca "Respondida" en cada pregunta.
 *
 * Diseño de la página: a la izquierda, un anillo con el avance total y las
 * categorías con su barra; arriba de las preguntas, una tarjeta con la
 * categoría y su avance; abajo, anterior y siguiente con ícono.
 *
 * La opción "Otro" que pide escribir cuál (`con_texto`): al elegirla aparece
 * el campo "¿Cuál?" (máx. 255) y se guarda al dejar de escribir. La IA lee
 * "Otro: lo que escribió".
 */
import { Head, Link } from '@inertiajs/vue3';
import {
    Check,
    ChevronLeft,
    ChevronRight,
    ClipboardCheck,
    Info,
} from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import Boton from '@/components/base/Boton.vue';
import BarraResponder from '@/components/responder/BarraResponder.vue';
import { rutas } from '@/lib/rutas';
import { cn, tokenXsrf } from '@/lib/utils';
import type {
    Avance,
    CabeceraMedicion,
    CategoriaResponder,
    PreguntaResponder,
    RespuestaGuardada,
} from '@/types/responder';

const props = defineProps<{
    medicion: CabeceraMedicion;
    categorias: CategoriaResponder[];
    respuestas: Record<string, RespuestaGuardada>;
    avance: Avance;
    categoriaInicial: number;
    guardadoEn: string | null;
}>();

const actual = ref(props.categoriaInicial);
const categoria = computed(() => props.categorias[actual.value]);
const avance = ref<Avance>(props.avance);

/** Las respuestas en pantalla; se guardan una por una. */
const respuestas = reactive<Record<string, RespuestaGuardada>>(
    Object.fromEntries(
        props.categorias.flatMap((c) =>
            c.preguntas.map((p) => [
                p.ref,
                props.respuestas[p.ref] ?? { opciones: [], texto: '' },
            ]),
        ),
    ),
);

// --- Guardado automático ------------------------------------------------------
const guardando = ref(0);
const hora = (iso: string) =>
    new Date(iso).toLocaleTimeString('es-CO', {
        hour: 'numeric',
        minute: '2-digit',
    });
const guardado = ref<string | null>(
    props.guardadoEn ? hora(props.guardadoEn) : null,
);

async function guardar(p: PreguntaResponder): Promise<void> {
    const r = respuestas[p.ref];
    guardando.value++;

    try {
        const res = await fetch(rutas.responder.guardar(props.medicion.id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': tokenXsrf(),
            },
            body: JSON.stringify({
                pregunta_ref: p.ref,
                opciones: r.opciones,
                texto: r.texto,
            }),
        });
        const datos = await res.json();

        if (!res.ok) {
            throw new Error(
                datos.message ?? 'No pudimos guardar tu respuesta.',
            );
        }

        avance.value = datos.avance;
        guardado.value = hora(datos.guardado_en);
    } catch (e) {
        toast.error(
            e instanceof Error
                ? `${e.message} Revisa tu conexión e inténtalo de nuevo.`
                : 'No pudimos guardar tu respuesta.',
        );
    } finally {
        guardando.value--;
    }
}

/** ¿Eligió la opción "Otro" que pide escribir cuál? */
const pideCual = (p: PreguntaResponder) =>
    p.opciones.some(
        (o) => o.con_texto && respuestas[p.ref].opciones.includes(o.ref),
    );

/** Sin "Otro" elegida, lo que escribió en "¿Cuál?" ya no va. */
function limpiarCual(p: PreguntaResponder): void {
    if (!pideCual(p)) {
        respuestas[p.ref].texto = '';
    }
}

function elegirUna(p: PreguntaResponder, opcion: string): void {
    respuestas[p.ref].opciones = [opcion];
    limpiarCual(p);
    void guardar(p);
}

/**
 * Selección múltiple. Marcar "Ninguna" (exclusiva) desmarca las demás, y
 * marcar otra desmarca "Ninguna".
 */
function marcar(p: PreguntaResponder, opcion: string): void {
    const lista = respuestas[p.ref].opciones;
    const exclusivas = p.opciones.filter((o) => o.exclusiva).map((o) => o.ref);

    if (lista.includes(opcion)) {
        respuestas[p.ref].opciones = lista.filter((o) => o !== opcion);
    } else if (exclusivas.includes(opcion)) {
        respuestas[p.ref].opciones = [opcion];
    } else {
        respuestas[p.ref].opciones = [
            ...lista.filter((o) => !exclusivas.includes(o)),
            opcion,
        ];
    }

    limpiarCual(p);
    void guardar(p);
}

// Las escritas se guardan al dejar de escribir (o al salir del campo).
const esperas = new Map<string, ReturnType<typeof setTimeout>>();

function escribir(p: PreguntaResponder): void {
    clearTimeout(esperas.get(p.ref));
    esperas.set(
        p.ref,
        setTimeout(() => {
            esperas.delete(p.ref);
            void guardar(p);
        }, 900),
    );
}

function salirDelCampo(p: PreguntaResponder): void {
    if (esperas.has(p.ref)) {
        clearTimeout(esperas.get(p.ref));
        esperas.delete(p.ref);
        void guardar(p);
    }
}

onBeforeUnmount(() => {
    for (const [ref, espera] of esperas) {
        clearTimeout(espera);
        const p = props.categorias
            .flatMap((c) => c.preguntas)
            .find((q) => q.ref === ref);

        if (p) {
            void guardar(p);
        }
    }
});

// --- Navegación ---------------------------------------------------------------
watch(actual, async (i) => {
    // Al volver, sigue en esta categoría.
    window.history.replaceState(
        window.history.state,
        '',
        rutas.responder.mostrar(props.medicion.id, i),
    );
    await nextTick();
    document.getElementById('cuestionario')?.scrollTo({ top: 0 });
    window.scrollTo({ top: 0 });
});

const anterior = computed(() => props.categorias[actual.value - 1] ?? null);
const siguiente = computed(() => props.categorias[actual.value + 1] ?? null);

const estadoCategoria = (i: number) => {
    const c = avance.value.categorias[i];

    if (!c) {
        return 'vacia';
    }

    if (c.faltan_obligatorias === 0 && c.respondidas > 0) {
        return 'completa';
    }

    return c.respondidas > 0 ? 'media' : 'vacia';
};

const respondida = (p: PreguntaResponder) => {
    const r = respuestas[p.ref];

    return r.opciones.length > 0 || r.texto.trim() !== '';
};

const enEsta = computed(
    () => categoria.value.preguntas.filter(respondida).length,
);

const obligatoriasEnEsta = computed(
    () => categoria.value.preguntas.filter((p) => p.obligatoria).length,
);

/** Anillo del avance total. */
const RADIO = 30;
const CIRCUNFERENCIA = 2 * Math.PI * RADIO;

const completas = computed(
    () =>
        props.categorias.filter((_, i) => estadoCategoria(i) === 'completa')
            .length,
);

const avanceDe = (i: number) =>
    Math.round(
        ((avance.value.categorias[i]?.respondidas ?? 0) * 100) /
            Math.max(1, props.categorias[i].preguntas.length),
    );

/** Opción grande con borde, fácil de leer y de tocar. */
const opcionClase =
    'flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border px-5 py-3 text-base leading-snug has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-marca/40';

const elegida = (p: PreguntaResponder, opcion: string) =>
    respuestas[p.ref].opciones.includes(opcion)
        ? 'border-2 border-marca bg-marca-suave/40 font-medium'
        : 'border-linea-fuerte hover:bg-lienzo';

const pistas = {
    abierta: 'Respuesta escrita',
    opcion_unica: 'Elige una',
    seleccion_multiple: 'Marca todas las que apliquen',
};
</script>

<template>
    <Head :title="`Medición ${medicion.numero} · ${categoria.nombre}`" />

    <BarraResponder
        :titulo="`Diagnóstico · ${medicion.sector} · Medición ${medicion.numero}`"
        :detalle="`Categoría ${actual + 1} de ${categorias.length} · ${avance.porcentaje}% respondido`"
        :porcentaje="avance.porcentaje"
        :guardado="guardado"
        :guardando="guardando > 0"
    />

    <div class="grid min-h-[calc(100dvh-61px)] lg:grid-cols-[280px_1fr]">
        <aside
            class="flex flex-col gap-4 border-b border-linea bg-lienzo px-4 py-5 text-sm lg:sticky lg:top-[61px] lg:h-[calc(100dvh-61px)] lg:overflow-y-auto lg:border-r lg:border-b-0"
        >
            <div
                class="hidden items-center gap-3 rounded-2xl border border-linea bg-white p-3 lg:flex"
            >
                <div class="relative size-16 shrink-0">
                    <svg
                        viewBox="0 0 70 70"
                        class="size-16 -rotate-90"
                        aria-hidden="true"
                    >
                        <circle
                            cx="35"
                            cy="35"
                            :r="RADIO"
                            fill="none"
                            stroke-width="7"
                            class="stroke-lienzo-oscuro"
                        />
                        <circle
                            cx="35"
                            cy="35"
                            :r="RADIO"
                            fill="none"
                            stroke-width="7"
                            stroke-linecap="round"
                            class="stroke-marca transition-all duration-500"
                            :stroke-dasharray="CIRCUNFERENCIA"
                            :stroke-dashoffset="
                                CIRCUNFERENCIA * (1 - avance.porcentaje / 100)
                            "
                        />
                    </svg>
                    <span
                        class="absolute inset-0 flex items-center justify-center font-mono text-sm font-semibold"
                    >
                        {{ avance.porcentaje }}%
                    </span>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold">Tu avance</p>
                    <p class="text-xs text-tinta-suave">
                        {{ completas }} de {{ categorias.length }}
                        {{
                            categorias.length === 1
                                ? 'categoría completa'
                                : 'categorías completas'
                        }}
                    </p>
                </div>
            </div>

            <div>
                <p
                    class="px-2 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                >
                    Categorías
                </p>
                <ol class="mt-2 flex flex-col gap-1">
                    <li v-for="(c, i) in categorias" :key="c.ref">
                        <button
                            type="button"
                            :aria-current="i === actual ? 'step' : undefined"
                            :class="
                                cn(
                                    'flex w-full items-start gap-2.5 rounded-xl px-2.5 py-2 text-left transition-colors lg:py-2.5',
                                    i === actual
                                        ? 'bg-marca text-white'
                                        : 'hover:bg-white',
                                )
                            "
                            @click="actual = i"
                        >
                            <span
                                :class="
                                    cn(
                                        'mt-px flex size-6 shrink-0 items-center justify-center rounded-full font-mono text-xs font-semibold',
                                        i === actual
                                            ? 'bg-white/20 text-white'
                                            : estadoCategoria(i) === 'completa'
                                              ? 'bg-exito text-white'
                                              : estadoCategoria(i) === 'media'
                                                ? 'bg-alerta-suave text-alerta'
                                                : 'border border-linea-fuerte bg-white text-tinta-suave',
                                    )
                                "
                                aria-hidden="true"
                            >
                                <Check
                                    v-if="
                                        estadoCategoria(i) === 'completa' &&
                                        i !== actual
                                    "
                                    class="size-3.5"
                                />
                                <template v-else>{{ i + 1 }}</template>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="flex items-start justify-between gap-2"
                                >
                                    <span
                                        :class="i === actual && 'font-medium'"
                                    >
                                        {{ c.nombre }}
                                    </span>
                                    <span
                                        :class="
                                            cn(
                                                'shrink-0 font-mono text-xs',
                                                i === actual
                                                    ? 'text-white/80'
                                                    : estadoCategoria(i) ===
                                                        'media'
                                                      ? 'text-alerta'
                                                      : 'text-tinta-suave',
                                            )
                                        "
                                    >
                                        {{
                                            avance.categorias[i]?.respondidas ??
                                            0
                                        }}/{{ c.preguntas.length }}
                                    </span>
                                </span>
                                <span
                                    :class="
                                        cn(
                                            'mt-1.5 block h-1 rounded-full',
                                            i === actual
                                                ? 'bg-white/25'
                                                : 'bg-lienzo-oscuro',
                                        )
                                    "
                                    aria-hidden="true"
                                >
                                    <span
                                        :class="
                                            cn(
                                                'block h-1 rounded-full transition-all',
                                                i === actual
                                                    ? 'bg-white'
                                                    : estadoCategoria(i) ===
                                                        'completa'
                                                      ? 'bg-exito'
                                                      : 'bg-alerta',
                                            )
                                        "
                                        :style="{ width: `${avanceDe(i)}%` }"
                                    />
                                </span>
                                <span class="sr-only">
                                    {{
                                        estadoCategoria(i) === 'completa'
                                            ? 'completa'
                                            : estadoCategoria(i) === 'media'
                                              ? 'a medias'
                                              : 'por empezar'
                                    }}
                                </span>
                            </span>
                        </button>
                    </li>
                </ol>
            </div>

            <Link
                :href="rutas.responder.revisar(medicion.id)"
                class="mt-auto flex items-center justify-center gap-1.5 rounded-xl border border-linea bg-white px-3 py-2.5 text-sm font-medium text-marca transition hover:border-marca"
            >
                <ClipboardCheck class="size-4" aria-hidden="true" />
                Revisar antes de enviar
            </Link>
            <p class="hidden px-2 text-xs text-tinta-suave lg:block">
                Puedes moverte entre categorías cuando quieras. Todo se guarda
                solo.
            </p>
        </aside>

        <div class="flex min-w-0 flex-col">
            <main id="cuestionario" class="flex-1 px-4 py-8 sm:px-8">
                <div class="mx-auto flex max-w-5xl flex-col gap-5">
                    <section
                        class="rounded-2xl border border-linea bg-linear-to-br from-marca-suave/70 to-white px-5 py-5 sm:px-8"
                    >
                        <div
                            class="flex flex-wrap items-end justify-between gap-4"
                        >
                            <div class="min-w-0">
                                <p
                                    class="font-mono text-sm font-semibold tracking-wide text-marca"
                                >
                                    CATEGORÍA {{ actual + 1 }} DE
                                    {{ categorias.length }}
                                </p>
                                <h1
                                    class="text-3xl leading-tight font-semibold"
                                >
                                    {{ categoria.nombre }}
                                </h1>
                                <p class="mt-1 text-sm text-tinta-suave">
                                    {{ categoria.preguntas.length }}
                                    {{
                                        categoria.preguntas.length === 1
                                            ? 'pregunta'
                                            : 'preguntas'
                                    }}
                                    · {{ obligatoriasEnEsta }}
                                    {{
                                        obligatoriasEnEsta === 1
                                            ? 'obligatoria'
                                            : 'obligatorias'
                                    }}
                                </p>
                            </div>
                            <div class="w-full sm:w-56">
                                <p
                                    class="flex justify-between text-sm text-tinta-suave"
                                >
                                    Respondidas
                                    <span
                                        class="font-mono font-semibold text-tinta"
                                    >
                                        {{ enEsta }}/{{
                                            categoria.preguntas.length
                                        }}
                                    </span>
                                </p>
                                <div
                                    class="mt-1.5 h-2 rounded-full bg-white"
                                    aria-hidden="true"
                                >
                                    <div
                                        class="h-2 rounded-full bg-marca transition-all duration-500"
                                        :style="{
                                            width: `${(enEsta * 100) / Math.max(1, categoria.preguntas.length)}%`,
                                        }"
                                    />
                                </div>
                            </div>
                        </div>
                        <p
                            class="mt-4 flex items-start gap-2 rounded-xl bg-white/80 px-3 py-2.5 text-base"
                        >
                            <Info
                                class="mt-1 size-4 shrink-0 text-marca"
                                aria-hidden="true"
                            />
                            <span>
                                No hay respuestas buenas o malas. Las preguntas
                                marcadas como <strong>Obligatoria</strong> se
                                necesitan para enviar.
                            </span>
                        </p>
                    </section>

                    <fieldset
                        v-for="(p, i) in categoria.preguntas"
                        :key="p.ref"
                        :class="
                            cn(
                                'rounded-2xl border bg-white px-5 py-6 sm:px-8',
                                respondida(p)
                                    ? 'border-marca/40'
                                    : 'border-linea',
                            )
                        "
                    >
                        <legend class="sr-only">Pregunta {{ i + 1 }}</legend>
                        <div class="flex items-start gap-4">
                            <span
                                :class="
                                    cn(
                                        'flex size-10 shrink-0 items-center justify-center rounded-full text-base font-semibold',
                                        respondida(p)
                                            ? 'bg-marca text-white'
                                            : 'bg-marca-suave text-marca',
                                    )
                                "
                                aria-hidden="true"
                            >
                                {{ respondida(p) ? '✓' : i + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex flex-wrap items-center gap-2 text-sm text-tinta-suave"
                                >
                                    <span>{{ pistas[p.tipo] }}</span>
                                    <span
                                        v-if="p.obligatoria"
                                        class="rounded-md bg-lienzo-oscuro px-2 py-0.5 font-medium text-tinta"
                                    >
                                        Obligatoria
                                    </span>
                                    <span v-else>(opcional)</span>
                                    <span
                                        v-if="respondida(p)"
                                        class="rounded-md bg-exito-suave px-2 py-0.5 font-medium text-exito"
                                    >
                                        ✓ Respondida
                                    </span>
                                </div>
                                <p
                                    :id="`texto-${p.ref}`"
                                    class="mt-1.5 text-lg leading-snug font-semibold"
                                >
                                    <span class="sr-only">{{ i + 1 }}. </span
                                    >{{ p.texto }}
                                </p>
                                <p
                                    v-if="p.indicacion"
                                    class="mt-3 rounded-lg bg-lienzo px-4 py-2.5 text-base"
                                >
                                    <strong class="text-marca"
                                        >Cómo responder:</strong
                                    >
                                    {{ p.indicacion }}
                                </p>
                            </div>
                        </div>
                        <div class="mt-5 sm:pl-14">
                            <div
                                v-if="p.tipo === 'opcion_unica'"
                                role="radiogroup"
                                :aria-labelledby="`texto-${p.ref}`"
                                class="grid gap-3 sm:grid-cols-2"
                            >
                                <label
                                    v-for="o in p.opciones"
                                    :key="o.ref"
                                    :class="cn(opcionClase, elegida(p, o.ref))"
                                >
                                    <input
                                        type="radio"
                                        :name="p.ref"
                                        :value="o.ref"
                                        :checked="
                                            respuestas[p.ref].opciones.includes(
                                                o.ref,
                                            )
                                        "
                                        class="size-5 shrink-0 accent-marca"
                                        @change="elegirUna(p, o.ref)"
                                    />
                                    {{ o.texto }}
                                </label>
                            </div>

                            <div
                                v-else-if="p.tipo === 'seleccion_multiple'"
                                role="group"
                                :aria-labelledby="`texto-${p.ref}`"
                                class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
                            >
                                <label
                                    v-for="o in p.opciones"
                                    :key="o.ref"
                                    :class="cn(opcionClase, elegida(p, o.ref))"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="
                                            respuestas[p.ref].opciones.includes(
                                                o.ref,
                                            )
                                        "
                                        class="size-5 shrink-0 rounded accent-marca"
                                        @change="marcar(p, o.ref)"
                                    />
                                    {{ o.texto }}
                                </label>
                            </div>

                            <div
                                v-if="p.tipo !== 'abierta' && pideCual(p)"
                                class="mt-4"
                            >
                                <label
                                    :for="`cual-${p.ref}`"
                                    class="block text-base font-medium"
                                    >¿Cuál?
                                    <span class="font-normal text-tinta-suave"
                                        >(opcional)</span
                                    ></label
                                >
                                <input
                                    :id="`cual-${p.ref}`"
                                    v-model="respuestas[p.ref].texto"
                                    type="text"
                                    maxlength="255"
                                    placeholder="Escribe cuál"
                                    class="mt-1.5 h-12 w-full rounded-lg border border-linea-fuerte bg-white px-4 text-base focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none sm:max-w-lg"
                                    @input="escribir(p)"
                                    @blur="salirDelCampo(p)"
                                />
                            </div>

                            <div v-else-if="p.tipo === 'abierta'">
                                <textarea
                                    v-model="respuestas[p.ref].texto"
                                    rows="5"
                                    maxlength="1000"
                                    placeholder="Escribe tu respuesta aquí"
                                    :aria-labelledby="`texto-${p.ref}`"
                                    class="w-full rounded-lg border border-linea-fuerte bg-white px-4 py-3 text-base leading-relaxed focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none"
                                    @input="escribir(p)"
                                    @blur="salirDelCampo(p)"
                                />
                                <p
                                    class="mt-1.5 flex justify-between text-sm text-tinta-suave"
                                >
                                    Máximo 1000 caracteres
                                    <span class="font-mono">
                                        {{ respuestas[p.ref].texto.length }} /
                                        1000
                                    </span>
                                </p>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </main>

            <footer
                class="sticky bottom-0 flex items-center justify-between gap-4 border-t border-linea bg-white/95 px-4 py-3 backdrop-blur sm:px-6"
            >
                <Boton
                    v-if="anterior"
                    variante="secundario"
                    class="min-w-0 gap-1"
                    @click="actual--"
                >
                    <ChevronLeft class="size-4 shrink-0" aria-hidden="true" />
                    <span class="truncate">{{ anterior.nombre }}</span>
                </Boton>
                <span v-else />
                <span
                    class="hidden items-center gap-1.5 text-sm text-tinta-suave md:inline-flex"
                >
                    <span
                        v-for="(c, i) in categorias"
                        :key="c.ref"
                        :class="
                            cn(
                                'h-1.5 rounded-full transition-all',
                                i === actual
                                    ? 'w-6 bg-marca'
                                    : estadoCategoria(i) === 'completa'
                                      ? 'w-1.5 bg-exito'
                                      : 'w-1.5 bg-linea-fuerte',
                            )
                        "
                        aria-hidden="true"
                    />
                    <span class="ml-2">
                        {{ enEsta }} de {{ categoria.preguntas.length }}
                        respondidas aquí
                    </span>
                </span>
                <Boton v-if="siguiente" class="min-w-0 gap-1" @click="actual++">
                    <span class="truncate">
                        Siguiente: {{ siguiente.nombre }}
                    </span>
                    <ChevronRight class="size-4 shrink-0" aria-hidden="true" />
                </Boton>
                <Boton
                    v-else
                    class="gap-1.5"
                    :href="rutas.responder.revisar(medicion.id)"
                >
                    <ClipboardCheck class="size-4" aria-hidden="true" />
                    Revisar antes de enviar
                </Boton>
            </footer>
        </div>
    </div>
</template>
