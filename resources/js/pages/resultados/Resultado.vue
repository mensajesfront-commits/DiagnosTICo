<script setup lang="ts">
/**
 * E6 · Resultado del diagnóstico (HU-064) y A3.3 · el mismo resultado visto
 * por el Administrador desde la ficha de la empresa (HU-038).
 *
 * Tres pestañas, para que la página quepa sin scroll:
 * - Resumen: puntaje total con su nivel, la frase que lo explica y la
 *   variación (RN-020; no hay en la primera medición), las 3 recomendaciones
 *   de las categorías más débiles y el radar actual vs. anterior.
 * - Categorías: la tabla con importancia, puntaje, nivel y "vs. anterior";
 *   al elegir una fila se ve su detalle: cada pregunta con la respuesta y la
 *   observación de la IA, la observación y la recomendación de la categoría.
 * - Recomendaciones: las de todas las categorías, de la más débil a la más
 *   fuerte.
 *
 * Todo llega calculado y no cambia (RN-023). Sin FODA ni comparación con
 * promedios (RN-028).
 *
 * Con `revision`, es el resultado que espera la aprobación de un
 * administrador (revisión antes de publicar): aún no se guarda ni lo ve la
 * empresa. Se aprueba y publica, o se rehace el análisis de una categoría o
 * de todas.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import Variacion from '@/components/empresas/Variacion.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import RadarCategorias from '@/components/graficas/RadarCategorias.vue';
import EtiquetaMetodo from '@/components/resultados/EtiquetaMetodo.vue';
import PreguntaResultado from '@/components/resultados/PreguntaResultado.vue';
import { diaYMes, fechaLocal, mesYAnio } from '@/lib/fechas';
import { niveles, ordenNiveles } from '@/lib/niveles';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import {
    administrador as inicioAdministrador,
    empresa as inicioEmpresa,
} from '@/routes/inicio';
import type {
    CabeceraResultado,
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
    /** Revisión antes de publicar: el resultado aún no se publica. */
    revision?: { desde: string | null } | null;
}>();

// --- Revisión antes de publicar ---------------------------------------------------
const modalAprobar = ref(false);
/** Categoría que se rehace; '' = todas. null = ventana cerrada. */
const rehacerCual = ref<string | null>(null);
const enviando = ref(false);

function aprobar(): void {
    router.post(
        rutas.revision.aprobar(props.medicion.id),
        {},
        {
            onStart: () => (enviando.value = true),
            onFinish: () => {
                enviando.value = false;
                modalAprobar.value = false;
            },
        },
    );
}

function rehacer(): void {
    router.post(
        rutas.revision.rehacer(props.medicion.id),
        rehacerCual.value ? { categoria: rehacerCual.value } : {},
        {
            onStart: () => (enviando.value = true),
            onFinish: () => {
                enviando.value = false;
                rehacerCual.value = null;
            },
        },
    );
}

const nombreRehacer = computed(
    () =>
        props.resultado.categorias.find((c) => c.ref === rehacerCual.value)
            ?.nombre ?? null,
);

// --- A2.6 · Resultado de ejemplo -------------------------------------------------
const casos = [
    {
        valor: 'mejor' as const,
        etiqueta: 'Mejor caso',
        texto: 'todas las respuestas cerca del máximo.',
    },
    {
        valor: 'medio' as const,
        etiqueta: 'Caso medio',
        texto: 'respuestas intermedias.',
    },
    {
        valor: 'peor' as const,
        etiqueta: 'Peor caso',
        texto: 'todas las respuestas cerca del mínimo.',
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

const volver = computed(() =>
    props.revision
        ? { href: inicioAdministrador(), texto: 'Inicio' }
        : props.ejemplo
          ? { href: props.ejemplo.volver, texto: 'Volver a la vista previa' }
          : props.interno
            ? {
                  href: rutas.empresas.ver(props.medicion.empresa.id),
                  texto: props.medicion.empresa.nombre,
              }
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

function abrirCategoria(ref: string): void {
    elegida.value = ref;
    pestana.value = 'categorias';
}

const porcentaje = (n: number) =>
    `${Number.isInteger(n) ? n : n.toFixed(2).replace(/\.?0+$/, '')}%`;

const numero = (i: number) => String(i + 1).padStart(2, '0');
</script>

<template>
    <Head
        :title="
            revision
                ? `Revisar resultado · Medición ${medicion.numero}`
                : ejemplo
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
                        class="rounded bg-nivel-mejorar-suave px-2 py-0.5 text-xs font-medium text-nivel-mejorar-texto"
                        >{{ ejemplo.etiqueta }}</span
                    >
                </h1>
                <h1
                    v-else-if="revision"
                    class="flex flex-wrap items-center gap-2 text-2xl font-semibold"
                >
                    Revisar resultado · Medición {{ medicion.numero }}
                    <span
                        class="rounded bg-nivel-mejorar-suave px-2 py-0.5 text-xs font-medium text-nivel-mejorar-texto"
                        >Sin publicar</span
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
                    class="inline-flex h-10 items-center rounded-md bg-marca px-4 text-sm font-medium text-white hover:bg-marca-hover"
                >
                    Ver formato del PDF
                </a>
            </div>
            <div v-else-if="revision" class="flex flex-wrap items-center gap-2">
                <Boton variante="secundario" @click="rehacerCual = ''">
                    Rehacer todo el análisis
                </Boton>
                <Boton @click="modalAprobar = true">Aprobar y publicar</Boton>
            </div>
            <div v-else class="flex flex-wrap items-center gap-2">
                <Boton
                    variante="secundario"
                    :href="rutas.resultados.respuestas(medicion.id)"
                >
                    {{ interno ? 'Ver respuestas' : 'Ver mis respuestas' }}
                </Boton>
                <Boton archivo :href="rutas.resultados.pdf(medicion.id)">
                    Ver informe PDF
                </Boton>
            </div>
        </header>

        <div class="flex shrink-0 flex-wrap items-center justify-between gap-3">
            <SelectorCompacto
                v-model="pestana"
                :opciones="pestanas"
                etiqueta-accesible="Secciones del resultado"
            />
            <div
                v-if="ejemplo"
                class="flex flex-wrap items-center gap-2 rounded-lg border border-nivel-mejorar/40 bg-nivel-mejorar-suave/60 px-3 py-1.5 text-xs"
            >
                <span :title="casoActual?.texto">
                    <strong class="text-nivel-mejorar-texto"
                        >Datos de muestra</strong
                    >
                    que no se guardan ni llaman a la IA ·
                </span>
                <span
                    class="flex items-center gap-1"
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
                                'rounded-md px-2.5 py-1',
                                ejemplo.caso === c.valor
                                    ? 'bg-marca font-medium text-white'
                                    : 'bg-white hover:bg-lienzo',
                            )
                        "
                        @click="
                            ejemplo.caso !== c.valor && cambiarCaso(c.valor)
                        "
                    >
                        {{ c.etiqueta }}
                    </button>
                </span>
            </div>
        </div>

        <!-- Resumen -->
        <div
            v-if="pestana === 'resumen'"
            class="grid gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[320px_1fr] lg:grid-rows-[minmax(0,1fr)]"
        >
            <section
                class="flex flex-col rounded-xl border border-linea bg-white p-5 lg:min-h-0 lg:overflow-y-auto"
            >
                <h2 class="text-xs text-tinta-suave">Puntaje total</h2>
                <div class="mt-3 flex flex-col items-center text-center">
                    <AnilloPuntaje
                        :puntaje="resultado.puntaje"
                        :nivel="resultado.nivel"
                        :tamano="148"
                    />
                    <p class="-mt-1 text-xs text-tinta-suave">de 100</p>
                    <EtiquetaNivel :nivel="resultado.nivel" class="mt-3" />
                    <p class="mt-3 text-sm leading-relaxed">
                        {{ resultado.frase }}
                    </p>
                    <template v-if="resultado.anterior">
                        <p class="mt-3 text-sm font-medium">
                            <Variacion :valor="resultado.variacion" />
                            vs. medición anterior
                        </p>
                        <p class="text-xs text-tinta-suave">
                            Medición {{ resultado.anterior.numero }} ·
                            {{ mesYAnio(resultado.anterior.fecha) }} ·
                            {{ resultado.anterior.puntaje }} pts
                        </p>
                    </template>
                    <p v-else class="mt-3 text-xs text-tinta-suave">
                        {{
                            ejemplo
                                ? 'Sin medición anterior: el ejemplo es una primera medición.'
                                : 'Es tu primera medición: será tu punto de partida.'
                        }}
                    </p>
                </div>
                <ul class="mt-auto grid grid-cols-4 gap-1 pt-5 text-[11px]">
                    <li
                        v-for="n in ordenNiveles.filter((n) => n !== 'sin')"
                        :key="n"
                        class="text-tinta-suave"
                    >
                        <span
                            :class="
                                cn(
                                    'mb-1 block h-1.5 rounded-full',
                                    niveles[n].barra,
                                )
                            "
                        />
                        {{ niveles[n].nombre }}<br />{{ niveles[n].rango }}
                    </li>
                </ul>
            </section>

            <div class="flex flex-col gap-4 lg:min-h-0">
                <section class="shrink-0">
                    <div class="flex flex-wrap items-baseline justify-between">
                        <h2 class="font-semibold">
                            Qué hacer ahora: recomendaciones priorizadas
                        </h2>
                        <span class="text-xs text-tinta-suave">
                            De la categoría más débil a la más fuerte
                        </span>
                    </div>
                    <ol class="mt-2 grid gap-2 xl:grid-cols-3">
                        <li
                            v-for="(p, i) in resultado.prioridades"
                            :key="p.ref"
                        >
                            <button
                                type="button"
                                class="h-full w-full rounded-xl border border-linea bg-white px-4 py-3 text-left hover:border-marca/40"
                                @click="abrirCategoria(p.ref)"
                            >
                                <span class="flex items-center gap-2">
                                    <span class="font-mono text-marca">{{
                                        numero(i)
                                    }}</span>
                                    <span
                                        :class="
                                            cn(
                                                'rounded px-1.5 py-0.5 text-[11px] font-medium',
                                                niveles[p.nivel].etiqueta,
                                            )
                                        "
                                    >
                                        {{ p.nombre }} · {{ p.puntaje }}
                                    </span>
                                    <EtiquetaMetodo
                                        v-if="p.metodo"
                                        :metodo="p.metodo"
                                    />
                                </span>
                                <span
                                    class="mt-1.5 block text-sm font-semibold"
                                >
                                    {{ p.recomendacion.titulo }}
                                </span>
                                <span
                                    class="mt-0.5 line-clamp-3 block text-xs text-tinta-suave"
                                >
                                    {{ p.recomendacion.detalle }}
                                </span>
                            </button>
                        </li>
                    </ol>
                </section>

                <section
                    class="flex min-h-72 flex-col rounded-xl border border-linea bg-white p-5 lg:min-h-0 lg:flex-1"
                >
                    <h2 class="font-semibold">
                        Mapa de las {{ resultado.categorias.length }}
                        categorías
                    </h2>
                    <RadarCategorias
                        class="min-h-0 flex-1"
                        alto="h-72 lg:h-full lg:min-h-48"
                        :etiquetas="radar.etiquetas"
                        :actual="radar.actual"
                        :anterior="radar.anterior"
                    />
                </section>
            </div>
        </div>

        <!-- Categorías: tabla y detalle -->
        <div
            v-else-if="pestana === 'categorias'"
            class="grid gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)]"
        >
            <section
                class="flex flex-col rounded-xl border border-linea bg-white lg:min-h-0"
            >
                <header
                    class="flex flex-wrap items-baseline justify-between gap-2 px-5 pt-4"
                >
                    <h2 class="font-semibold">Puntaje por categoría</h2>
                    <span class="text-xs text-tinta-suave">
                        La importancia indica cuánto aporta cada categoría al
                        total
                    </span>
                </header>
                <div class="mt-2 overflow-x-auto lg:min-h-0 lg:overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="sticky top-0 bg-white text-left text-xs text-tinta-suave"
                        >
                            <tr class="border-b border-linea">
                                <th class="py-2 pl-5 font-normal">Categoría</th>
                                <th class="px-2 py-2 font-normal">Import.</th>
                                <th class="px-2 py-2 font-normal">Puntaje</th>
                                <th class="px-2 py-2 font-normal">Nivel</th>
                                <th
                                    class="py-2 pr-5 pl-2 font-normal whitespace-nowrap"
                                >
                                    vs. anterior
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="c in resultado.categorias"
                                :key="c.ref"
                                :class="
                                    cn(
                                        'cursor-pointer border-b border-lienzo-oscuro hover:bg-lienzo',
                                        c.ref === elegida &&
                                            'bg-marca-suave/60',
                                    )
                                "
                                :aria-selected="c.ref === elegida"
                                @click="elegida = c.ref"
                            >
                                <td class="py-2.5 pl-5">
                                    <button
                                        type="button"
                                        class="text-left hover:text-marca"
                                        @click.stop="elegida = c.ref"
                                    >
                                        {{ c.nombre }}
                                    </button>
                                </td>
                                <td class="px-2 font-mono text-xs">
                                    {{ porcentaje(c.importancia) }}
                                </td>
                                <td class="px-2">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="hidden h-1.5 w-20 overflow-hidden rounded-full bg-lienzo-oscuro sm:block"
                                        >
                                            <span
                                                :class="
                                                    cn(
                                                        'block h-full rounded-full',
                                                        niveles[c.nivel].barra,
                                                    )
                                                "
                                                :style="{
                                                    width: `${c.puntaje}%`,
                                                }"
                                            />
                                        </span>
                                        <span class="font-mono font-semibold">
                                            {{ c.puntaje }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-2">
                                    <EtiquetaNivel :nivel="c.nivel" />
                                </td>
                                <td class="pr-5 pl-2 font-mono text-xs">
                                    <Variacion :valor="c.variacion" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section
                v-if="categoria"
                class="flex flex-col rounded-xl border border-linea bg-white lg:min-h-0"
            >
                <header
                    class="flex items-center justify-between gap-3 border-b border-linea px-5 py-3"
                >
                    <h2 class="font-semibold">{{ categoria.nombre }}</h2>
                    <span class="flex items-center gap-2">
                        <span class="font-mono font-semibold">{{
                            categoria.puntaje
                        }}</span>
                        <EtiquetaNivel :nivel="categoria.nivel" />
                    </span>
                </header>
                <div
                    class="flex flex-col gap-4 p-5 lg:min-h-0 lg:overflow-y-auto"
                >
                    <PreguntaResultado
                        v-for="p in categoria.preguntas"
                        :key="p.ref"
                        :pregunta="p"
                        :interno="interno"
                    />
                    <div
                        v-if="categoria.metodo"
                        class="flex flex-wrap items-center gap-2 text-sm"
                    >
                        <span class="text-tinta-suave">Analizado con</span>
                        <EtiquetaMetodo :metodo="categoria.metodo" />
                        <span
                            v-if="categoria.metodo.motivo"
                            class="text-xs text-tinta-suave"
                            >{{ categoria.metodo.motivo }}</span
                        >
                    </div>
                    <div class="rounded-lg bg-lienzo px-4 py-3 text-sm">
                        <span
                            class="mr-2 rounded border border-linea bg-white px-1 font-mono text-[10px]"
                            >IA</span
                        >
                        <strong>Observación de la categoría:</strong>
                        {{ categoria.observacion }}
                    </div>
                    <div
                        class="rounded-lg border-l-4 border-marca bg-lienzo px-4 py-3 text-sm"
                    >
                        <strong>Recomendación:</strong>
                        {{ categoria.recomendacion.titulo }}.
                        {{ categoria.recomendacion.detalle }}
                    </div>
                    <Boton
                        v-if="revision"
                        variante="secundario"
                        tamano="sm"
                        class="self-start"
                        @click="rehacerCual = categoria.ref"
                    >
                        Rehacer el análisis de esta categoría
                    </Boton>
                </div>
            </section>
        </div>

        <!-- Recomendaciones de todas las categorías -->
        <section v-else class="flex flex-col gap-2 lg:min-h-0 lg:flex-1">
            <p class="shrink-0 text-sm text-tinta-suave">
                Una recomendación por categoría, de la más débil a la más
                fuerte. Las redacta la IA a partir de
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
                    class="rounded-xl border border-linea bg-white px-4 py-3"
                >
                    <span class="flex items-center gap-2">
                        <span class="font-mono text-marca">{{
                            numero(i)
                        }}</span>
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
                    <p class="mt-1.5 text-sm font-semibold">
                        {{ c.recomendacion.titulo }}
                    </p>
                    <p class="mt-0.5 text-xs text-tinta-suave">
                        {{ c.recomendacion.detalle }}
                    </p>
                    <button
                        type="button"
                        class="mt-1 text-xs text-marca underline underline-offset-2"
                        @click="abrirCategoria(c.ref)"
                    >
                        Ver el detalle
                    </button>
                </li>
            </ol>
        </section>

        <p v-if="revision" class="shrink-0 text-xs text-tinta-suave">
            Este resultado aún no se publica: la empresa no lo ve hasta que lo
            apruebes. Revisa las observaciones y recomendaciones de cada
            categoría en la pestaña Categorías.
        </p>
        <p v-else-if="ejemplo" class="shrink-0 text-xs text-tinta-suave">
            Datos de ejemplo: las observaciones y recomendaciones reales las
            escribe la IA según las respuestas de cada empresa.
        </p>
        <p v-else class="shrink-0 text-xs text-tinta-suave">
            Publicado el
            {{ diaYMes(fechaLocal(resultado.publicado_en.slice(0, 10))) }}. El
            resultado ya no cambia.
        </p>
    </div>

    <template v-if="revision">
        <Modal
            v-model:abierto="modalAprobar"
            ancho="sm"
            titulo="¿Aprobar y publicar el resultado?"
            :descripcion="`${medicion.empresa.nombre} recibirá un correo y podrá ver su resultado y su informe PDF. Después de publicarlo ya no se puede cambiar.`"
        >
            <template #pie>
                <Boton variante="secundario" @click="modalAprobar = false">
                    Cancelar
                </Boton>
                <Boton :cargando="enviando" @click="aprobar">
                    Sí, publicar
                </Boton>
            </template>
        </Modal>
        <Modal
            :abierto="rehacerCual !== null"
            ancho="sm"
            :titulo="
                nombreRehacer
                    ? `¿Rehacer el análisis de «${nombreRehacer}»?`
                    : '¿Rehacer todo el análisis?'
            "
            descripcion="La IA lo vuelve a hacer con las mismas respuestas. Cuando termine, el resultado vuelve a quedar por revisar y te llega un correo."
            @update:abierto="(v) => !v && (rehacerCual = null)"
        >
            <template #pie>
                <Boton variante="secundario" @click="rehacerCual = null">
                    Cancelar
                </Boton>
                <Boton :cargando="enviando" @click="rehacer">
                    Sí, rehacer
                </Boton>
            </template>
        </Modal>
    </template>
</template>
