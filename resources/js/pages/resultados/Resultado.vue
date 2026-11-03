<script setup lang="ts">
/**
 * E6 · Resultado del diagnóstico (HU-064) y A3.3 · el mismo resultado visto
 * por el Administrador desde la ficha de la empresa (HU-038).
 *
 * Tres pestañas:
 * - Resumen: encabezado con el anillo, el nivel, la frase, la variación
 *   (RN-020; no hay en la primera medición), la barra de categorías por
 *   nivel y las tarjetas "Lo que más urge" y "Lo más fuerte". Debajo, "Qué
 *   hacer ahora" (las 3 categorías más débiles) y "Tus categorías" en barras
 *   o en el mapa (radar actual vs. anterior). Todo lleva al detalle.
 * - Categorías: lista con puntaje, barra, nivel e importancia; al elegir una
 *   se ve su análisis completo y cada pregunta con lo que dijo la IA. En el
 *   celular, la pantalla baja sola al detalle.
 * - Recomendaciones: la principal de cada categoría, de la más débil a la
 *   más fuerte, con un borde del color de su nivel.
 *
 * Todo llega calculado y no cambia (RN-023). Sin FODA ni comparación con
 * promedios (RN-028).
 *
 * A2.6 · Resultado de ejemplo: arriba una franja de «Datos de muestra» (borde
 * punteado ámbar) que explica el caso elegido y deja cambiar entre «Mejor
 * caso», «Caso medio» y «Peor caso» con tarjetas con ícono.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarClock,
    ChevronLeft,
    ChevronRight,
    FileText,
    FlaskConical,
    Info,
    Minus,
    TrendingDown,
    TrendingUp,
} from '@lucide/vue';
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import Variacion from '@/components/empresas/Variacion.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import RadarCategorias from '@/components/graficas/RadarCategorias.vue';
import AnalisisCategoria from '@/components/resultados/AnalisisCategoria.vue';
import EtiquetaMetodo from '@/components/resultados/EtiquetaMetodo.vue';
import PreguntaResultado from '@/components/resultados/PreguntaResultado.vue';
import TarjetaRecomendacion from '@/components/resultados/TarjetaRecomendacion.vue';
import { usePermisos } from '@/composables/usePermisos';
import { diaYMes, fechaLocal, mesYAnio } from '@/lib/fechas';
import { niveles } from '@/lib/niveles';
import type { Nivel } from '@/lib/niveles';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import {
    administrador as inicioAdministrador,
    empresa as inicioEmpresa,
} from '@/routes/inicio';
import type {
    CabeceraResultado,
    CategoriaResultado,
    EjemploResultado,
    Resultado,
} from '@/types/resultados';

const props = defineProps<{
    medicion: CabeceraResultado;
    resultado: Resultado;
    /** Lo abre el equipo de NuevasTIC (A3.3): vuelve a la ficha de la empresa. */
    interno: boolean;
    /** A2.6 · Resultado de ejemplo de un diagnóstico (HU-030): datos de muestra. */
    ejemplo?: EjemploResultado | null;
    /**
     * A3.3 · Las mediciones terminadas de la empresa, para pasar de una a
     * otra (solo vista interna: Administrador y Consultor).
     */
    mediciones?: {
        id: number;
        numero: number;
        fecha: string;
        puntaje: number;
        nivel: Nivel;
    }[];
}>();

const posicion = computed(() =>
    (props.mediciones ?? []).findIndex((m) => m.id === props.medicion.id),
);
const anteriorMedicion = computed(() =>
    posicion.value > 0 ? props.mediciones![posicion.value - 1] : null,
);
const siguienteMedicion = computed(() =>
    posicion.value >= 0 && posicion.value < (props.mediciones?.length ?? 0) - 1
        ? props.mediciones![posicion.value + 1]
        : null,
);

const { puede } = usePermisos();

// --- A2.6 · Resultado de ejemplo -------------------------------------------------
const casos = [
    {
        valor: 'mejor' as const,
        etiqueta: 'Mejor caso',
        texto: 'todas las respuestas cerca del máximo.',
        icono: TrendingUp,
    },
    {
        valor: 'medio' as const,
        etiqueta: 'Caso medio',
        texto: 'respuestas intermedias.',
        icono: Minus,
    },
    {
        valor: 'peor' as const,
        etiqueta: 'Peor caso',
        texto: 'todas las respuestas cerca del mínimo.',
        icono: TrendingDown,
    },
];

const casoActual = computed(() =>
    casos.find((c) => c.valor === props.ejemplo?.caso),
);

function cambiarCaso(caso: string): void {
    const url = new URL(props.ejemplo?.volver ?? '', 'http://x');
    const version = url.searchParams.get('version');
    router.get(
        window.location.pathname,
        { caso, ...(version ? { version } : {}) },
        { preserveState: false, replace: true },
    );
}

type Pestana = 'resumen' | 'categorias' | 'recomendaciones';

const pestanas = [
    { valor: 'resumen' as const, etiqueta: 'Resumen' },
    { valor: 'categorias' as const, etiqueta: 'Categorías' },
    { valor: 'recomendaciones' as const, etiqueta: 'Recomendaciones' },
];

const pestana = ref<Pestana>('resumen');

// Resumen: las categorías en barras (por defecto) o en el mapa (radar).
const vistas = [
    { valor: 'barras' as const, etiqueta: 'Barras' },
    { valor: 'mapa' as const, etiqueta: 'Mapa' },
];
const vistaCategorias = ref<'barras' | 'mapa'>('barras');

// Barra de distribución por nivel: solo los niveles con categorías.
const distribucionCompleta = computed(() => props.resultado.por_nivel ?? []);
const distribucion = computed(() => {
    const total = props.resultado.categorias.length || 1;

    return distribucionCompleta.value
        .filter((d) => d.cantidad > 0)
        .map((d) => ({ ...d, ancho: (d.cantidad * 100) / total }));
});
const distribucionTexto = computed(() =>
    distribucionCompleta.value
        .map((d) => `${d.cantidad} en ${niveles[d.clave].nombre}`)
        .join(', '),
);

const volver = computed(() =>
    props.ejemplo
        ? { href: props.ejemplo.volver, texto: 'Volver a la vista previa' }
        : props.interno
          ? puede('empresas.ver')
              ? {
                    href: rutas.empresas.ver(props.medicion.empresa.id),
                    texto: props.medicion.empresa.nombre,
                }
              : { href: inicioAdministrador(), texto: 'Inicio' }
          : { href: inicioEmpresa(), texto: 'Inicio' },
);

const fechaEnvio = computed(() =>
    props.medicion.enviada_en
        ? diaYMes(new Date(props.medicion.enviada_en))
        : null,
);

const subtitulo = computed(() =>
    [
        props.medicion.empresa.nombre,
        props.medicion.sector,
        fechaEnvio.value,
        `${props.medicion.diagnostico} v${props.medicion.version}`,
    ]
        .filter(Boolean)
        .join(' · '),
);

const radar = computed(() => ({
    etiquetas: props.resultado.categorias.map((c) => c.nombre),
    actual: {
        nombre: `Medición ${props.medicion.numero}`,
        puntajes: props.resultado.categorias.map((c) => c.puntaje),
    },
    anterior: props.resultado.anterior
        ? {
              nombre: `Medición ${props.resultado.anterior.numero}`,
              puntajes: props.resultado.categorias.map((c) => c.anterior ?? 0),
          }
        : null,
}));

// Recomendaciones de todas las categorías, de la más débil a la más fuerte.
const ordenadas = computed(() =>
    [...props.resultado.categorias].sort(
        (a, b) => a.puntaje - b.puntaje || b.importancia - a.importancia,
    ),
);

// Pestaña Categorías: abre la más débil.
const elegida = ref(ordenadas.value[0]?.ref ?? '');
const categoria = computed(
    () =>
        props.resultado.categorias.find((c) => c.ref === elegida.value) ??
        props.resultado.categorias[0],
);

// Cuántas recomendaciones tiene la categoría además de la principal.
function masRecomendaciones(c: CategoriaResultado): number {
    return Math.max(0, (c.recomendaciones?.length ?? 1) - 1);
}

// En pantallas angostas el detalle queda debajo de la lista: se baja a él.
const detalle = useTemplateRef<HTMLElement>('detalle');

function elegir(ref: string): void {
    elegida.value = ref;

    if (!window.matchMedia('(min-width: 1024px)').matches) {
        nextTick(() =>
            detalle.value?.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)')
                    .matches
                    ? 'auto'
                    : 'smooth',
            }),
        );
    }
}

function abrirCategoria(ref: string): void {
    elegida.value = ref;
    pestana.value = 'categorias';
}

const porcentaje = (n: number) =>
    `${Number.isInteger(n) ? n : n.toFixed(2).replace(/\.?0+$/, '')}%`;
</script>

<template>
    <Head
        :title="
            ejemplo
                ? 'Resultado de ejemplo'
                : `Resultado · Medición ${medicion.numero}`
        "
    />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
        <header class="flex shrink-0 flex-wrap items-end justify-between gap-4">
            <div>
                <Link
                    :href="volver.href"
                    class="mb-2 inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-tinta-suave hover:text-marca"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    {{ volver.texto }}
                </Link>
                <h1
                    v-if="ejemplo"
                    class="flex flex-wrap items-center gap-2 text-2xl font-semibold"
                >
                    Así se vería el resultado
                    <span
                        class="rounded-full border border-linea bg-white px-2.5 py-0.5 text-xs font-medium text-tinta-suave"
                        >{{ ejemplo.etiqueta }}</span
                    >
                </h1>
                <h1 v-else class="text-2xl font-semibold">
                    Resultado del diagnóstico · Medición {{ medicion.numero }}
                </h1>
                <p class="mt-0.5 text-sm text-tinta-suave">{{ subtitulo }}</p>
            </div>
            <div v-if="ejemplo" class="flex flex-wrap items-center gap-2">
                <a
                    :href="ejemplo.pdf"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-marca px-4 text-sm font-medium text-white hover:bg-marca-hover"
                >
                    <FileText class="size-4" aria-hidden="true" />
                    Ver formato del PDF
                </a>
            </div>
            <div v-else class="flex flex-wrap items-center gap-2">
                <Boton
                    variante="secundario"
                    :href="rutas.resultados.respuestas(medicion.id)"
                >
                    {{ interno ? 'Ver respuestas' : 'Ver mis respuestas' }}
                </Boton>
                <Boton
                    v-if="puede('resultados.pdf')"
                    archivo
                    :href="rutas.resultados.pdf(medicion.id)"
                >
                    Ver informe PDF
                </Boton>
            </div>
        </header>

        <!-- A2.6 · modo ejemplo: datos de muestra y caso -->
        <section
            v-if="ejemplo"
            class="flex shrink-0 flex-wrap items-center gap-4 rounded-2xl border border-dashed border-nivel-mejorar/60 bg-nivel-mejorar-suave/50 px-4 py-3"
        >
            <div class="flex min-w-60 flex-1 items-start gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-nivel-mejorar-texto shadow-sm"
                    aria-hidden="true"
                >
                    <FlaskConical class="size-5" />
                </span>
                <div class="text-sm">
                    <p class="font-semibold text-nivel-mejorar-texto">
                        Datos de muestra
                    </p>
                    <p class="text-xs text-tinta">
                        No se guardan ni llaman a la IA. Ahora ves el
                        <strong>{{ casoActual?.etiqueta.toLowerCase() }}</strong
                        >: {{ casoActual?.texto }}
                    </p>
                </div>
            </div>
            <div
                class="grid w-full grid-cols-3 gap-2 sm:w-auto"
                role="radiogroup"
                aria-label="Respuestas de muestra"
            >
                <button
                    v-for="c in casos"
                    :key="c.valor"
                    type="button"
                    role="radio"
                    :aria-checked="ejemplo.caso === c.valor"
                    :title="`Respuestas de muestra: ${c.texto}`"
                    :class="
                        cn(
                            'flex items-center justify-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-medium transition',
                            ejemplo.caso === c.valor
                                ? 'border-marca bg-marca text-white'
                                : 'border-linea bg-white hover:border-marca/40',
                        )
                    "
                    @click="ejemplo.caso !== c.valor && cambiarCaso(c.valor)"
                >
                    <component
                        :is="c.icono"
                        class="size-4"
                        aria-hidden="true"
                    />
                    {{ c.etiqueta }}
                </button>
            </div>
        </section>

        <div class="flex shrink-0 flex-wrap items-center justify-between gap-3">
            <SelectorCompacto
                v-model="pestana"
                :opciones="pestanas"
                etiqueta-accesible="Secciones del resultado"
            />

            <!-- A3.3 · Pasar a otra medición de la empresa -->
            <nav
                v-if="!ejemplo && interno && (mediciones?.length ?? 0) > 1"
                class="flex max-w-full items-center gap-1.5"
                aria-label="Otras mediciones de la empresa"
            >
                <Link
                    v-if="anteriorMedicion"
                    :href="rutas.resultados.ver(anteriorMedicion.id)"
                    class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-linea bg-white text-tinta-suave hover:border-marca hover:text-marca"
                    :aria-label="`Medición ${anteriorMedicion.numero}`"
                    preserve-scroll
                >
                    <ChevronLeft class="size-4" aria-hidden="true" />
                </Link>
                <ol
                    class="flex items-center gap-1 overflow-x-auto rounded-lg bg-lienzo-oscuro p-1"
                >
                    <li v-for="m in mediciones" :key="m.id">
                        <Link
                            :href="rutas.resultados.ver(m.id)"
                            :aria-current="
                                m.id === medicion.id ? 'page' : undefined
                            "
                            :title="`Medición ${m.numero} · ${diaYMes(fechaLocal(m.fecha))}`"
                            :class="
                                cn(
                                    'flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs whitespace-nowrap',
                                    m.id === medicion.id
                                        ? 'bg-white font-semibold text-tinta shadow-sm'
                                        : 'text-tinta-suave hover:text-tinta',
                                )
                            "
                        >
                            <span
                                :class="
                                    cn(
                                        'size-2 rounded-full',
                                        niveles[m.nivel].barra,
                                    )
                                "
                                aria-hidden="true"
                            />
                            M{{ m.numero }}
                            <span class="font-mono">{{ m.puntaje }}</span>
                        </Link>
                    </li>
                </ol>
                <Link
                    v-if="siguienteMedicion"
                    :href="rutas.resultados.ver(siguienteMedicion.id)"
                    class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-linea bg-white text-tinta-suave hover:border-marca hover:text-marca"
                    :aria-label="`Medición ${siguienteMedicion.numero}`"
                    preserve-scroll
                >
                    <ChevronRight class="size-4" aria-hidden="true" />
                </Link>
            </nav>
        </div>

        <!-- Resumen -->
        <div
            v-if="pestana === 'resumen'"
            class="flex flex-col gap-4 lg:min-h-0 lg:flex-1 lg:overflow-y-auto"
        >
            <!-- Encabezado: calificación, nivel, distribución y extremos -->
            <section
                class="grid shrink-0 gap-6 overflow-hidden rounded-2xl border border-linea bg-linear-to-br from-marca-suave/70 via-white to-white p-6 md:grid-cols-[auto_minmax(0,1fr)] xl:grid-cols-[auto_minmax(0,1fr)_minmax(0,18rem)]"
            >
                <div class="flex flex-col items-center text-center">
                    <div class="relative">
                        <AnilloPuntaje
                            :puntaje="resultado.puntaje"
                            :nivel="resultado.nivel"
                            :tamano="160"
                        />
                    </div>
                    <EtiquetaNivel
                        :nivel="resultado.nivel"
                        class="mt-2 text-sm"
                    />
                </div>

                <div class="flex min-w-0 flex-col justify-center">
                    <h2
                        class="text-xs font-medium tracking-wide text-tinta-suave uppercase"
                    >
                        Calificación general
                        <span
                            v-if="interno && !ejemplo"
                            class="font-normal tracking-normal normal-case"
                            >· el mensaje que lee la empresa</span
                        >
                    </h2>
                    <p class="mt-1 text-lg leading-snug font-medium">
                        {{ resultado.frase }}
                    </p>
                    <p
                        v-if="resultado.anterior"
                        class="mt-2 text-sm text-tinta-suave"
                    >
                        <Variacion :valor="resultado.variacion" />
                        frente a la medición {{ resultado.anterior.numero }} ({{
                            mesYAnio(resultado.anterior.fecha)
                        }}
                        · {{ resultado.anterior.puntaje }} pts)
                    </p>
                    <p v-else class="mt-2 text-sm text-tinta-suave">
                        {{
                            ejemplo
                                ? 'Sin medición anterior: el ejemplo es una primera medición.'
                                : interno
                                  ? 'Es su primera medición: será su punto de partida.'
                                  : 'Es tu primera medición: será tu punto de partida.'
                        }}
                    </p>

                    <!-- Cuántas categorías hay en cada nivel -->
                    <div v-if="distribucion.length" class="mt-4">
                        <div
                            class="flex h-3 overflow-hidden rounded-full bg-lienzo-oscuro"
                            role="img"
                            :aria-label="distribucionTexto"
                        >
                            <span
                                v-for="d in distribucion"
                                :key="d.clave"
                                :class="niveles[d.clave].barra"
                                :style="{ width: `${d.ancho}%` }"
                            />
                        </div>
                        <ul
                            class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-tinta-suave"
                        >
                            <li
                                v-for="d in distribucionCompleta"
                                :key="d.clave"
                                class="inline-flex items-center gap-1.5"
                            >
                                <span
                                    :class="
                                        cn(
                                            'size-2.5 rounded-full',
                                            niveles[d.clave].barra,
                                        )
                                    "
                                    aria-hidden="true"
                                />
                                <span class="font-semibold text-tinta">{{
                                    d.cantidad
                                }}</span>
                                {{ niveles[d.clave].nombre }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div
                    class="grid gap-3 md:col-span-2 md:grid-cols-2 xl:col-span-1 xl:grid-cols-1"
                >
                    <button
                        v-if="resultado.prioridades[0]"
                        type="button"
                        class="rounded-xl border border-nivel-critico/25 bg-white/80 px-4 py-3 text-left transition hover:border-nivel-critico/50 hover:shadow-sm"
                        @click="abrirCategoria(resultado.prioridades[0].ref)"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-nivel-critico-texto"
                        >
                            <TrendingDown class="size-4" aria-hidden="true" />
                            Lo que más urge
                        </span>
                        <span
                            class="mt-1 flex items-baseline justify-between gap-2"
                        >
                            <span class="truncate font-semibold">{{
                                resultado.prioridades[0].nombre
                            }}</span>
                            <span class="font-mono text-lg font-semibold">{{
                                resultado.prioridades[0].puntaje
                            }}</span>
                        </span>
                    </button>
                    <button
                        v-if="resultado.mas_fuerte"
                        type="button"
                        class="rounded-xl border border-nivel-sigue/30 bg-white/80 px-4 py-3 text-left transition hover:border-nivel-sigue/60 hover:shadow-sm"
                        @click="abrirCategoria(resultado.mas_fuerte.ref)"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-nivel-sigue-texto"
                        >
                            <TrendingUp class="size-4" aria-hidden="true" />
                            Lo más fuerte
                        </span>
                        <span
                            class="mt-1 flex items-baseline justify-between gap-2"
                        >
                            <span class="truncate font-semibold">{{
                                resultado.mas_fuerte.nombre
                            }}</span>
                            <span class="font-mono text-lg font-semibold">{{
                                resultado.mas_fuerte.puntaje
                            }}</span>
                        </span>
                    </button>
                </div>
            </section>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <!-- Qué hacer ahora -->
                <section class="rounded-2xl border border-linea bg-white p-5">
                    <div
                        class="flex flex-wrap items-baseline justify-between gap-2"
                    >
                        <h2 class="text-lg font-semibold">
                            {{
                                interno && !ejemplo
                                    ? 'Qué le conviene hacer'
                                    : 'Qué hacer ahora'
                            }}
                        </h2>
                        <span class="text-xs text-tinta-suave">
                            De la categoría más débil a la más fuerte
                        </span>
                    </div>
                    <ol class="mt-3 flex flex-col gap-3">
                        <li
                            v-for="(p, i) in resultado.prioridades"
                            :key="p.ref"
                        >
                            <button
                                type="button"
                                class="group flex w-full gap-4 rounded-xl border border-linea bg-white p-4 text-left transition hover:border-marca/40 hover:shadow-sm"
                                @click="abrirCategoria(p.ref)"
                            >
                                <span
                                    :class="
                                        cn(
                                            'flex size-10 shrink-0 items-center justify-center rounded-full font-mono text-lg font-semibold text-white',
                                            niveles[p.nivel].barra,
                                        )
                                    "
                                    aria-hidden="true"
                                    >{{ i + 1 }}</span
                                >
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block text-base leading-snug font-semibold"
                                    >
                                        {{ p.recomendacion.titulo }}
                                    </span>
                                    <span
                                        class="mt-1 flex flex-wrap items-center gap-2 text-xs"
                                    >
                                        <span
                                            :class="
                                                cn(
                                                    'rounded px-1.5 py-0.5 font-medium',
                                                    niveles[p.nivel].etiqueta,
                                                )
                                            "
                                            >{{ p.nombre }} ·
                                            {{ p.puntaje }}</span
                                        >
                                        <span
                                            v-if="p.principal?.plazo"
                                            class="inline-flex items-center gap-1 text-tinta-suave"
                                        >
                                            <CalendarClock
                                                class="size-3.5"
                                                aria-hidden="true"
                                            />
                                            {{ p.principal.pasos.length }} pasos
                                            ·
                                            {{ p.principal.plazo }}
                                        </span>
                                    </span>
                                    <span
                                        class="mt-1.5 line-clamp-2 block text-sm text-tinta-suave"
                                    >
                                        {{ p.recomendacion.detalle }}
                                    </span>
                                </span>
                                <ChevronRight
                                    class="mt-2 size-5 shrink-0 text-tinta-suave transition group-hover:translate-x-0.5 group-hover:text-marca"
                                    aria-hidden="true"
                                />
                            </button>
                        </li>
                    </ol>
                </section>

                <!-- Tus categorías: barras o mapa -->
                <section
                    class="flex flex-col rounded-2xl border border-linea bg-white p-5"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <h2 class="text-lg font-semibold">
                            {{ interno && !ejemplo ? 'Sus' : 'Tus' }}
                            {{ resultado.categorias.length }} categorías
                        </h2>
                        <SelectorCompacto
                            v-model="vistaCategorias"
                            :opciones="vistas"
                            etiqueta-accesible="Cómo ver las categorías"
                        />
                    </div>
                    <ul
                        v-if="vistaCategorias === 'barras'"
                        class="mt-3 flex flex-col gap-1"
                    >
                        <li v-for="c in resultado.categorias" :key="c.ref">
                            <button
                                type="button"
                                class="w-full rounded-lg px-2 py-2 text-left hover:bg-lienzo"
                                @click="abrirCategoria(c.ref)"
                            >
                                <span
                                    class="flex items-baseline justify-between gap-3 text-sm"
                                >
                                    <span class="truncate font-medium">{{
                                        c.nombre
                                    }}</span>
                                    <span
                                        class="flex shrink-0 items-baseline gap-2"
                                    >
                                        <Variacion
                                            v-if="c.variacion !== null"
                                            :valor="c.variacion"
                                            class="text-xs"
                                        />
                                        <span class="font-mono font-semibold">{{
                                            c.puntaje
                                        }}</span>
                                    </span>
                                </span>
                                <span
                                    class="mt-1.5 block h-2.5 overflow-hidden rounded-full bg-lienzo-oscuro"
                                >
                                    <span
                                        :class="
                                            cn(
                                                'block h-full rounded-full transition-[width] duration-700',
                                                niveles[c.nivel].barra,
                                            )
                                        "
                                        :style="{
                                            width: `${Math.max(2, c.puntaje)}%`,
                                        }"
                                    />
                                </span>
                            </button>
                        </li>
                    </ul>
                    <RadarCategorias
                        v-else
                        class="mt-2"
                        alto="h-80"
                        :etiquetas="radar.etiquetas"
                        :actual="radar.actual"
                        :anterior="radar.anterior"
                    />
                    <p class="mt-auto pt-3 text-xs text-tinta-suave">
                        Toca una categoría para ver su análisis completo.
                    </p>
                </section>
            </div>
        </div>

        <!-- Categorías: tabla y detalle -->
        <div
            v-else-if="pestana === 'categorias'"
            class="grid gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)]"
        >
            <section
                class="flex flex-col rounded-2xl border border-linea bg-white lg:min-h-0"
            >
                <header class="px-5 pt-4 pb-2">
                    <h2 class="text-lg font-semibold">Puntaje por categoría</h2>
                    <p class="text-xs text-tinta-suave">
                        La importancia indica cuánto aporta cada categoría al
                        total. Elige una para ver su análisis.
                    </p>
                </header>
                <ul
                    class="flex flex-col gap-2 px-3 pb-3 lg:min-h-0 lg:overflow-y-auto"
                    role="listbox"
                    aria-label="Categorías"
                >
                    <li
                        v-for="c in resultado.categorias"
                        :key="c.ref"
                        role="option"
                        :aria-selected="c.ref === elegida"
                    >
                        <button
                            type="button"
                            :class="
                                cn(
                                    'w-full rounded-xl border-l-4 px-4 py-3 text-left transition',
                                    c.ref === elegida
                                        ? 'border-marca bg-marca-suave/60 shadow-sm'
                                        : 'border-transparent hover:bg-lienzo',
                                )
                            "
                            @click="elegir(c.ref)"
                        >
                            <span
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="min-w-0">
                                    <span
                                        class="block truncate font-semibold"
                                        >{{ c.nombre }}</span
                                    >
                                    <span
                                        class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-tinta-suave"
                                    >
                                        <EtiquetaNivel :nivel="c.nivel" />
                                        <span
                                            >Importancia
                                            {{
                                                porcentaje(c.importancia)
                                            }}</span
                                        >
                                        <Variacion
                                            v-if="c.variacion !== null"
                                            :valor="c.variacion"
                                        />
                                    </span>
                                </span>
                                <span
                                    :class="
                                        cn(
                                            'shrink-0 font-mono text-2xl font-semibold',
                                            c.ref === elegida && 'text-marca',
                                        )
                                    "
                                    >{{ c.puntaje }}</span
                                >
                            </span>
                            <span
                                class="mt-2 block h-2 overflow-hidden rounded-full bg-lienzo-oscuro"
                            >
                                <span
                                    :class="
                                        cn(
                                            'block h-full rounded-full',
                                            niveles[c.nivel].barra,
                                        )
                                    "
                                    :style="{
                                        width: `${Math.max(2, c.puntaje)}%`,
                                    }"
                                />
                            </span>
                        </button>
                    </li>
                </ul>
            </section>

            <section
                v-if="categoria"
                ref="detalle"
                class="flex scroll-mt-4 flex-col overflow-hidden rounded-2xl border border-linea bg-white lg:min-h-0"
            >
                <header
                    class="flex items-center gap-4 border-b border-linea bg-linear-to-r from-marca-suave/60 to-white px-5 py-4"
                >
                    <AnilloPuntaje
                        :puntaje="categoria.puntaje"
                        :nivel="categoria.nivel"
                        :tamano="64"
                    />
                    <div class="min-w-0 flex-1">
                        <h2 class="text-xl leading-tight font-semibold">
                            {{ categoria.nombre }}
                        </h2>
                        <p
                            class="mt-1 flex flex-wrap items-center gap-2 text-xs text-tinta-suave"
                        >
                            <EtiquetaNivel :nivel="categoria.nivel" />
                            <span
                                >Importancia
                                {{ porcentaje(categoria.importancia) }}</span
                            >
                            <span v-if="categoria.variacion !== null"
                                ><Variacion :valor="categoria.variacion" /> vs.
                                anterior</span
                            >
                        </p>
                    </div>
                </header>
                <div
                    class="flex flex-col gap-4 p-5 lg:min-h-0 lg:overflow-y-auto"
                >
                    <AnalisisCategoria :categoria="categoria" />
                    <section class="flex flex-col gap-4">
                        <h3
                            class="border-t border-linea pt-4 text-sm font-semibold"
                        >
                            {{
                                interno
                                    ? 'Respuestas de la empresa'
                                    : 'Tus respuestas'
                            }}
                            y lo que dice la IA de cada una
                        </h3>
                        <PreguntaResultado
                            v-for="p in categoria.preguntas"
                            :key="p.ref"
                            :pregunta="p"
                            :interno="interno"
                        />
                    </section>
                </div>
            </section>
        </div>

        <!-- Recomendaciones de todas las categorías -->
        <section v-else class="flex flex-col gap-2 lg:min-h-0 lg:flex-1">
            <p class="shrink-0 text-sm text-tinta-suave">
                La recomendación más importante de cada categoría, de la más
                débil a la más fuerte, con sus pasos. Las redacta la IA a partir
                de
                {{
                    interno ? 'las respuestas de la empresa' : 'tus respuestas'
                }}.
            </p>
            <ol
                class="grid content-start gap-2 lg:min-h-0 lg:grid-cols-2 lg:overflow-y-auto"
            >
                <li
                    v-for="(c, i) in ordenadas"
                    :key="c.ref"
                    :class="
                        cn(
                            'rounded-2xl border border-l-4 border-linea bg-white px-4 py-3',
                            niveles[c.nivel].borde,
                        )
                    "
                >
                    <span class="flex items-center gap-2">
                        <span
                            :class="
                                cn(
                                    'flex size-7 shrink-0 items-center justify-center rounded-full font-mono text-sm font-semibold text-white',
                                    niveles[c.nivel].barra,
                                )
                            "
                            aria-hidden="true"
                            >{{ i + 1 }}</span
                        >
                        <span
                            :class="
                                cn(
                                    'rounded px-1.5 py-0.5 text-[11px] font-medium',
                                    niveles[c.nivel].etiqueta,
                                )
                            "
                        >
                            {{ c.nombre }} · {{ c.puntaje }}
                        </span>
                        <EtiquetaMetodo v-if="c.metodo" :metodo="c.metodo" />
                    </span>
                    <TarjetaRecomendacion
                        class="mt-2"
                        :recomendacion="
                            c.recomendaciones?.[0] ?? c.recomendacion
                        "
                    />
                    <button
                        type="button"
                        class="mt-2 text-sm text-marca underline underline-offset-2"
                        @click="abrirCategoria(c.ref)"
                    >
                        {{
                            masRecomendaciones(c) > 0
                                ? `Ver el análisis y ${masRecomendaciones(c)} ${masRecomendaciones(c) === 1 ? 'recomendación más' : 'recomendaciones más'}`
                                : 'Ver el análisis completo'
                        }}
                    </button>
                </li>
            </ol>
        </section>

        <p
            v-if="ejemplo"
            class="flex shrink-0 items-center gap-1.5 text-xs text-tinta-suave"
        >
            <Info class="size-3.5 shrink-0" aria-hidden="true" />
            Datos de ejemplo: las observaciones y recomendaciones reales las
            escribe la IA según las respuestas de cada empresa.
        </p>
        <p v-else class="shrink-0 text-xs text-tinta-suave">
            Publicado el
            {{ diaYMes(fechaLocal(resultado.publicado_en.slice(0, 10))) }}. El
            resultado ya no cambia.
        </p>
    </div>
</template>
