<script setup lang="ts">
/**
 * E9 · Mis respuestas (HU-065) y A3.5 · las mismas respuestas vistas por el
 * Administrador. Solo lectura (RN-017).
 *
 * A la izquierda (arriba en el celular), las categorías con su puntaje y su
 * nivel; a la derecha, la cabecera de la elegida y sus preguntas como
 * tarjetas, con la respuesta, el puntaje y la nota de la IA. Abajo, la
 * categoría anterior y la siguiente. "Volver al resultado" y "Ver informe PDF".
 *
 * El puntaje de cada pregunta lleva el color de su nivel. En la vista interna
 * (Administrador y Consultor) hay además un filtro "Todas · Para mejorar ·
 * Sin responder" y cada categoría dice cuántas preguntas tiene para mejorar
 * (nivel Crítico o Se puede mejorar, calculado en el servidor).
 */
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarCheck,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    Lock,
    Minus,
    TrendingDown,
    TrendingUp,
} from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import PreguntaResultado from '@/components/resultados/PreguntaResultado.vue';
import { usePermisos } from '@/composables/usePermisos';
import { diaYMes } from '@/lib/fechas';
import { niveles } from '@/lib/niveles';
import { rutas } from '@/lib/rutas';
import { plural } from '@/lib/texto';
import { cn } from '@/lib/utils';
import type {
    CabeceraResultado,
    CategoriaResultado,
    PreguntaResultado as Pregunta,
} from '@/types/resultados';

const props = defineProps<{
    medicion: CabeceraResultado;
    categorias: CategoriaResultado[];
    /** Categoría que se abre (?categoria=c3). */
    inicial: string | null;
    interno: boolean;
}>();

const { puede } = usePermisos();

const elegida = ref(
    props.categorias.some((c) => c.ref === props.inicial)
        ? (props.inicial as string)
        : (props.categorias[0]?.ref ?? ''),
);

const indice = computed(() =>
    Math.max(
        0,
        props.categorias.findIndex((c) => c.ref === elegida.value),
    ),
);
const categoria = computed(() => props.categorias[indice.value]);
const anterior = computed(() => props.categorias[indice.value - 1]);
const siguiente = computed(() => props.categorias[indice.value + 1]);

const lista = ref<HTMLElement | null>(null);

// --- Filtro de preguntas (vista interna) -------------------------------------

type Filtro = 'todas' | 'mejorar' | 'sin_responder';

const paraMejorar = (p: Pregunta) =>
    p.nivel === 'critico' || p.nivel === 'mejorar';

const filtros: Record<Filtro, (p: Pregunta) => boolean> = {
    todas: () => true,
    mejorar: paraMejorar,
    sin_responder: (p) => p.respuesta === null,
};

const filtro = ref<Filtro>('todas');

const opcionesFiltro = computed(() =>
    (
        [
            ['todas', 'Todas'],
            ['mejorar', 'Para mejorar'],
            ['sin_responder', 'Sin responder'],
        ] as [Filtro, string][]
    )
        .map(([valor, etiqueta]) => ({
            valor,
            etiqueta,
            cantidad:
                categoria.value?.preguntas.filter(filtros[valor]).length ?? 0,
        }))
        .filter((o) => o.valor === 'todas' || o.cantidad > 0),
);

/** Las preguntas con su número original, filtradas. */
const preguntas = computed(() =>
    (categoria.value?.preguntas ?? [])
        .map((p, i) => ({ p, numero: i + 1 }))
        .filter(({ p }) => filtros[filtro.value](p)),
);

async function elegir(ref: string) {
    elegida.value = ref;
    filtro.value = 'todas';
    await nextTick();
    lista.value?.scrollTo({ top: 0 });

    if (window.matchMedia('(max-width: 1023px)').matches) {
        document
            .getElementById('categoria-elegida')
            ?.scrollIntoView({ block: 'start' });
    }
}

const porcentaje = (n: number) =>
    `${Number.isInteger(n) ? n : n.toFixed(2).replace(/\.?0+$/, '')}%`;

const respondidas = (c: CategoriaResultado) =>
    c.preguntas.filter((p) => p.respuesta !== null).length;

const enviada = computed(() =>
    props.medicion.enviada_en
        ? diaYMes(new Date(props.medicion.enviada_en))
        : null,
);
</script>

<template>
    <Head :title="`Respuestas · Medición ${medicion.numero}`" />

    <div class="flex flex-col gap-5 p-4 sm:p-6 lg:h-dvh lg:overflow-hidden">
        <header
            class="flex shrink-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div class="min-w-0">
                <Link
                    :href="rutas.resultados.ver(medicion.id)"
                    class="mb-3 inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-tinta-suave hover:text-marca"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Volver al resultado
                </Link>
                <div class="flex items-start gap-3">
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                        aria-hidden="true"
                    >
                        <ClipboardList class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <h1 class="text-2xl font-semibold">
                            {{ interno ? 'Respuestas' : 'Mis respuestas' }} ·
                            Medición {{ medicion.numero }}
                        </h1>
                        <ul
                            class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs text-tinta-suave"
                        >
                            <li
                                class="rounded-full bg-white px-2.5 py-1 font-medium text-tinta ring-1 ring-linea"
                            >
                                {{ medicion.empresa.nombre }}
                            </li>
                            <li
                                class="rounded-full bg-white px-2.5 py-1 ring-1 ring-linea"
                            >
                                {{ medicion.sector }}
                            </li>
                            <li
                                class="rounded-full bg-white px-2.5 py-1 ring-1 ring-linea"
                            >
                                {{ medicion.diagnostico }} v{{
                                    medicion.version
                                }}
                            </li>
                            <li
                                v-if="enviada"
                                class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 ring-1 ring-linea"
                            >
                                <CalendarCheck
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                Enviada el {{ enviada }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-lienzo-oscuro px-3 py-1.5 text-xs font-medium text-tinta-suave"
                >
                    <Lock class="size-3.5" aria-hidden="true" />
                    Solo lectura
                </span>
                <Boton
                    v-if="puede('resultados.pdf')"
                    archivo
                    :href="rutas.resultados.pdf(medicion.id)"
                >
                    {{ interno ? 'Informe completo PDF' : 'Ver informe PDF' }}
                </Boton>
            </div>
        </header>

        <div
            class="grid gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[300px_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)] lg:gap-5"
        >
            <nav
                class="min-w-0 self-start rounded-2xl border border-linea bg-white p-2 lg:max-h-full lg:overflow-y-auto"
                aria-labelledby="titulo-categorias"
            >
                <p
                    id="titulo-categorias"
                    class="flex items-center justify-between px-2 pt-1.5 pb-2 text-xs font-medium text-tinta-suave"
                >
                    Categorías
                    <span>Puntaje</span>
                </p>
                <ul
                    class="-mx-2 flex gap-2 overflow-x-auto px-2 pb-1 lg:mx-0 lg:flex-col lg:gap-1 lg:overflow-visible lg:px-0 lg:pb-0"
                >
                    <li
                        v-for="c in categorias"
                        :key="c.ref"
                        class="w-56 shrink-0 lg:w-auto"
                    >
                        <button
                            type="button"
                            :class="
                                cn(
                                    'flex w-full flex-col gap-2 rounded-xl border px-3 py-2.5 text-left transition-colors',
                                    c.ref === elegida
                                        ? 'border-marca/40 bg-marca-suave'
                                        : 'border-transparent bg-lienzo/60 hover:bg-lienzo lg:bg-transparent',
                                )
                            "
                            :aria-current="
                                c.ref === elegida ? 'true' : undefined
                            "
                            @click="elegir(c.ref)"
                        >
                            <span
                                class="flex w-full items-start justify-between gap-2"
                            >
                                <span
                                    :class="
                                        cn(
                                            'text-sm leading-5',
                                            c.ref === elegida
                                                ? 'font-semibold text-marca'
                                                : 'font-medium',
                                        )
                                    "
                                    >{{ c.nombre }}</span
                                >
                                <span
                                    class="font-mono text-sm font-semibold text-tinta"
                                    >{{ c.puntaje }}</span
                                >
                            </span>
                            <span
                                class="h-1.5 w-full overflow-hidden rounded-full bg-lienzo-oscuro"
                                aria-hidden="true"
                            >
                                <span
                                    :class="
                                        cn(
                                            'block h-full rounded-full',
                                            niveles[c.nivel].barra,
                                        )
                                    "
                                    :style="{ width: `${c.puntaje}%` }"
                                />
                            </span>
                            <span
                                class="flex w-full items-center justify-between gap-2 text-xs text-tinta-suave"
                            >
                                <span>{{ niveles[c.nivel].nombre }}</span>
                                <span
                                    v-if="
                                        interno && c.preguntas.some(paraMejorar)
                                    "
                                    class="font-medium text-alerta"
                                    >{{
                                        c.preguntas.filter(paraMejorar).length
                                    }}
                                    para mejorar</span
                                >
                                <span v-else>{{
                                    plural(
                                        c.preguntas.length,
                                        'pregunta',
                                        'preguntas',
                                    )
                                }}</span>
                            </span>
                        </button>
                    </li>
                </ul>
            </nav>

            <section
                v-if="categoria"
                id="categoria-elegida"
                class="flex min-w-0 scroll-mt-4 flex-col self-start overflow-hidden rounded-2xl border border-linea bg-lienzo/40 lg:max-h-full lg:min-h-0"
                aria-labelledby="titulo-categoria"
            >
                <header
                    class="flex flex-col gap-4 border-b border-linea bg-linear-to-br from-marca-suave/70 to-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
                >
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-tinta-suave">
                            Categoría {{ indice + 1 }} de
                            {{ categorias.length }}
                        </p>
                        <h2
                            id="titulo-categoria"
                            class="mt-0.5 flex flex-wrap items-center gap-2 text-lg font-semibold"
                        >
                            {{ categoria.nombre }}
                            <EtiquetaNivel :nivel="categoria.nivel" />
                            <span
                                v-if="categoria.variacion !== null"
                                :class="
                                    cn(
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                                        categoria.variacion > 0
                                            ? 'bg-exito-suave text-exito'
                                            : categoria.variacion < 0
                                              ? 'bg-aviso-suave text-aviso'
                                              : 'bg-lienzo text-tinta-suave',
                                    )
                                "
                                :title="`Antes: ${categoria.anterior}`"
                            >
                                <TrendingUp
                                    v-if="categoria.variacion > 0"
                                    class="size-3"
                                    aria-hidden="true"
                                />
                                <TrendingDown
                                    v-else-if="categoria.variacion < 0"
                                    class="size-3"
                                    aria-hidden="true"
                                />
                                <Minus
                                    v-else
                                    class="size-3"
                                    aria-hidden="true"
                                />
                                {{ categoria.variacion > 0 ? '+' : ''
                                }}{{ categoria.variacion }} vs. la anterior
                            </span>
                        </h2>
                        <ul
                            class="mt-2 flex flex-wrap gap-1.5 text-xs text-tinta-suave"
                        >
                            <li
                                class="rounded-full bg-white px-2.5 py-1 ring-1 ring-linea"
                            >
                                Importancia
                                <strong class="font-semibold text-tinta">{{
                                    porcentaje(categoria.importancia)
                                }}</strong>
                            </li>
                            <li
                                class="rounded-full bg-white px-2.5 py-1 ring-1 ring-linea"
                            >
                                <strong class="font-semibold text-tinta">{{
                                    respondidas(categoria)
                                }}</strong>
                                de
                                {{
                                    plural(
                                        categoria.preguntas.length,
                                        'pregunta respondida',
                                        'preguntas respondidas',
                                    )
                                }}
                            </li>
                        </ul>
                    </div>
                    <div
                        class="flex shrink-0 items-baseline gap-1 self-start rounded-xl bg-white px-4 py-2 ring-1 ring-linea sm:self-center"
                    >
                        <span class="sr-only">Puntaje de la categoría:</span>
                        <span class="font-mono text-3xl font-semibold">{{
                            categoria.puntaje
                        }}</span>
                        <span class="text-sm text-tinta-suave">/ 100</span>
                    </div>
                </header>

                <div
                    ref="lista"
                    class="flex flex-col gap-3 p-3 sm:p-5 lg:min-h-0 lg:overflow-y-auto"
                >
                    <SelectorCompacto
                        v-if="interno && opcionesFiltro.length > 1"
                        v-model="filtro"
                        :opciones="opcionesFiltro"
                        etiqueta-accesible="Filtrar preguntas"
                        class="self-start"
                    />

                    <PreguntaResultado
                        v-for="{ p, numero } in preguntas"
                        :key="p.ref"
                        :pregunta="p"
                        :numero="numero"
                        :interno="interno"
                        tarjeta
                    />

                    <nav
                        v-if="anterior || siguiente"
                        class="mt-1 grid grid-cols-2 gap-3"
                        aria-label="Otras categorías"
                    >
                        <button
                            v-if="anterior"
                            type="button"
                            class="flex min-w-0 items-center gap-2 rounded-xl border border-linea bg-white px-3 py-2.5 text-left text-sm hover:border-marca/40 hover:bg-marca-suave/40"
                            @click="elegir(anterior.ref)"
                        >
                            <ChevronLeft
                                class="size-4 shrink-0 text-tinta-suave"
                                aria-hidden="true"
                            />
                            <span class="min-w-0">
                                <span class="block text-xs text-tinta-suave"
                                    >Anterior</span
                                >
                                <span class="block truncate font-medium">{{
                                    anterior.nombre
                                }}</span>
                            </span>
                        </button>
                        <span v-else />
                        <button
                            v-if="siguiente"
                            type="button"
                            class="flex min-w-0 items-center justify-end gap-2 rounded-xl border border-linea bg-white px-3 py-2.5 text-right text-sm hover:border-marca/40 hover:bg-marca-suave/40"
                            @click="elegir(siguiente.ref)"
                        >
                            <span class="min-w-0">
                                <span class="block text-xs text-tinta-suave"
                                    >Siguiente</span
                                >
                                <span class="block truncate font-medium">{{
                                    siguiente.nombre
                                }}</span>
                            </span>
                            <ChevronRight
                                class="size-4 shrink-0 text-tinta-suave"
                                aria-hidden="true"
                            />
                        </button>
                    </nav>
                </div>

                <p
                    class="flex items-start gap-2 border-t border-linea bg-white px-4 py-3 text-xs text-tinta-suave sm:px-5"
                >
                    <Lock class="mt-px size-3.5 shrink-0" aria-hidden="true" />
                    {{
                        interno
                            ? 'Las respuestas enviadas no se cambian.'
                            : 'Para cambiar tus respuestas, espera tu siguiente medición.'
                    }}
                </p>
            </section>
        </div>
    </div>
</template>
