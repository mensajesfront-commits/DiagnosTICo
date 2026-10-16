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
 * La opción "Otro" que pide escribir cuál (`con_texto`): al elegirla aparece
 * el campo "¿Cuál?" (máx. 255) y se guarda al dejar de escribir. La IA lee
 * "Otro: lo que escribió".
 */
import { Head, Link } from '@inertiajs/vue3';
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

    <div class="grid min-h-[calc(100dvh-57px)] lg:grid-cols-[280px_1fr]">
        <aside
            class="border-b border-linea bg-lienzo px-4 py-5 text-sm lg:sticky lg:top-[57px] lg:h-[calc(100dvh-57px)] lg:overflow-y-auto lg:border-r lg:border-b-0"
        >
            <p class="px-2 text-xs text-tinta-suave">
                Puedes moverte entre categorías cuando quieras.
                <Link
                    :href="rutas.responder.revisar(medicion.id)"
                    class="text-marca underline"
                >
                    Revisar antes de enviar
                </Link>
            </p>
            <ol class="mt-3 flex flex-col gap-0.5">
                <li v-for="(c, i) in categorias" :key="c.ref">
                    <button
                        type="button"
                        :aria-current="i === actual ? 'step' : undefined"
                        :class="
                            cn(
                                'flex w-full items-center gap-2.5 rounded-md px-2 py-2 text-left',
                                i === actual
                                    ? 'bg-marca-suave font-medium'
                                    : 'hover:bg-white',
                            )
                        "
                        @click="actual = i"
                    >
                        <span
                            :class="
                                cn(
                                    'flex size-6 shrink-0 items-center justify-center rounded-full text-xs',
                                    i === actual
                                        ? 'bg-marca text-white'
                                        : estadoCategoria(i) === 'completa'
                                          ? 'text-exito'
                                          : estadoCategoria(i) === 'media'
                                            ? 'bg-alerta-suave text-alerta'
                                            : 'border border-linea-fuerte text-tinta-suave',
                                )
                            "
                            aria-hidden="true"
                        >
                            {{
                                i === actual
                                    ? i + 1
                                    : estadoCategoria(i) === 'completa'
                                      ? '✓'
                                      : estadoCategoria(i) === 'media'
                                        ? '●'
                                        : i + 1
                            }}
                        </span>
                        <span class="flex-1">{{ c.nombre }}</span>
                        <span
                            v-if="
                                estadoCategoria(i) !== 'completa' ||
                                i === actual
                            "
                            :class="
                                cn(
                                    'font-mono text-xs',
                                    estadoCategoria(i) === 'media' &&
                                        i !== actual
                                        ? 'text-alerta'
                                        : 'text-tinta-suave',
                                )
                            "
                        >
                            {{ avance.categorias[i]?.respondidas ?? 0 }}/{{
                                c.preguntas.length
                            }}
                        </span>
                    </button>
                </li>
            </ol>
            <p
                class="mt-4 border-t border-linea px-2 pt-3 text-xs text-tinta-suave"
            >
                <span class="text-exito">✓</span> Completa ·
                <span class="text-alerta">●</span> A medias · respondidas /
                total
            </p>
        </aside>

        <div class="flex min-w-0 flex-col">
            <main id="cuestionario" class="flex-1 px-6 py-6">
                <div class="mx-auto flex max-w-3xl flex-col gap-4">
                    <div>
                        <p class="font-mono text-xs text-tinta-suave">
                            CATEGORÍA {{ actual + 1 }} / {{ categorias.length }}
                        </p>
                        <h1 class="text-2xl font-semibold">
                            {{ categoria.nombre }}
                        </h1>
                        <p class="mt-1 text-sm text-tinta-suave">
                            No hay respuestas buenas o malas. Las preguntas
                            marcadas como <strong>Obligatoria</strong> se
                            necesitan para enviar.
                        </p>
                    </div>

                    <fieldset
                        v-for="(p, i) in categoria.preguntas"
                        :key="p.ref"
                        class="rounded-xl border border-linea bg-white px-5 py-4"
                    >
                        <legend class="sr-only">Pregunta {{ i + 1 }}</legend>
                        <div class="flex items-start justify-between gap-4">
                            <p :id="`texto-${p.ref}`" class="font-medium">
                                {{ i + 1 }}. {{ p.texto }}
                            </p>
                            <span
                                class="flex shrink-0 items-center gap-2 text-xs text-tinta-suave"
                            >
                                {{ pistas[p.tipo] }}
                                <span
                                    v-if="p.obligatoria"
                                    class="rounded bg-lienzo-oscuro px-1.5 py-0.5 font-medium text-tinta"
                                >
                                    Obligatoria
                                </span>
                                <span v-else>(opcional)</span>
                            </span>
                        </div>
                        <p v-if="p.indicacion" class="mt-1 text-xs">
                            <strong class="text-marca">Cómo responder</strong> ·
                            {{ p.indicacion }}
                        </p>

                        <div
                            v-if="p.tipo === 'opcion_unica'"
                            role="radiogroup"
                            :aria-labelledby="`texto-${p.ref}`"
                            class="mt-3 grid gap-2 sm:grid-cols-2"
                        >
                            <label
                                v-for="o in p.opciones"
                                :key="o.ref"
                                :class="
                                    cn(
                                        'flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-2.5 text-sm',
                                        respuestas[p.ref].opciones.includes(
                                            o.ref,
                                        )
                                            ? 'border-2 border-marca bg-marca-suave/40'
                                            : 'border-linea-fuerte hover:bg-lienzo',
                                    )
                                "
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
                                    class="size-4 accent-marca"
                                    @change="elegirUna(p, o.ref)"
                                />
                                {{ o.texto }}
                            </label>
                        </div>

                        <div
                            v-else-if="p.tipo === 'seleccion_multiple'"
                            class="mt-3 grid gap-x-4 gap-y-2 sm:grid-cols-3"
                        >
                            <label
                                v-for="o in p.opciones"
                                :key="o.ref"
                                class="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        respuestas[p.ref].opciones.includes(
                                            o.ref,
                                        )
                                    "
                                    class="size-4 accent-marca"
                                    @change="marcar(p, o.ref)"
                                />
                                {{ o.texto }}
                            </label>
                        </div>

                        <div
                            v-if="p.tipo !== 'abierta' && pideCual(p)"
                            class="mt-2"
                        >
                            <label
                                :for="`cual-${p.ref}`"
                                class="block text-xs font-medium"
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
                                class="mt-1 h-10 w-full rounded-md border border-linea-fuerte bg-white px-3 text-sm focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none sm:max-w-md"
                                @input="escribir(p)"
                                @blur="salirDelCampo(p)"
                            />
                        </div>

                        <div v-else-if="p.tipo === 'abierta'" class="mt-3">
                            <textarea
                                v-model="respuestas[p.ref].texto"
                                rows="4"
                                maxlength="1000"
                                :aria-labelledby="`texto-${p.ref}`"
                                class="w-full rounded-md border border-linea-fuerte bg-white px-3 py-2 text-sm focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none"
                                @input="escribir(p)"
                                @blur="salirDelCampo(p)"
                            />
                            <p
                                class="mt-1 flex justify-between text-xs text-tinta-suave"
                            >
                                Máximo 1000 caracteres
                                <span class="font-mono">
                                    {{ respuestas[p.ref].texto.length }} / 1000
                                </span>
                            </p>
                        </div>
                    </fieldset>
                </div>
            </main>

            <footer
                class="sticky bottom-0 flex items-center justify-between gap-4 border-t border-linea bg-white px-6 py-3"
            >
                <Boton v-if="anterior" variante="secundario" @click="actual--">
                    ← {{ anterior.nombre }}
                </Boton>
                <span v-else />
                <span class="hidden text-xs text-tinta-suave sm:inline">
                    {{ enEsta }} de {{ categoria.preguntas.length }} preguntas
                    respondidas en esta categoría
                </span>
                <Boton v-if="siguiente" @click="actual++">
                    Siguiente: {{ siguiente.nombre }} →
                </Boton>
                <Boton v-else :href="rutas.responder.revisar(medicion.id)">
                    Revisar antes de enviar →
                </Boton>
            </footer>
        </div>
    </div>
</template>
