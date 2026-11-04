<script setup lang="ts">
/**
 * A2.4 · Vista previa y estado (HU-028).
 *
 * - Izquierda: "Estado del diagnóstico" con cuántas categorías están
 *   completas, los 5 requisitos para publicar (RN-012) y las categorías que
 *   faltan corregir, con "Corregir" (lleva a la pregunta en el editor).
 * - Derecha: el cuestionario como lo ve la empresa, una categoría a la vez.
 *   Se puede responder para probar, pero nada se guarda (CA-004).
 * - Con `?version=` muestra una versión publicada en solo lectura.
 *
 * "Resultado de ejemplo" abre A2.6 (HU-030) con el mismo contenido (borrador o
 * versión).
 *
 * Diseño: a la izquierda, un anillo con las categorías completas, la lista
 * de requisitos con ✓ o ! y las categorías por corregir en tarjetas con
 * "Corregir". A la derecha, las categorías en chips (con ⚠ si tienen algo
 * por corregir) y las preguntas con el mismo estilo de E2: número en un
 * círculo, "Cómo responder" en un recuadro y opciones grandes que se marcan.
 * Abajo, "Categoría anterior" y "Siguiente categoría".
 */
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    BarChart3,
    ChevronLeft,
    ChevronRight,
    CircleAlert,
    CircleCheck,
    ClipboardList,
    Eye,
    Lock,
    Pencil,
    Rocket,
    TriangleAlert,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import ModalVersiones from '@/components/editor/ModalVersiones.vue';
import { usePermisos } from '@/composables/usePermisos';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type {
    CabeceraEditor,
    CategoriaVistaPrevia,
    EstadoEditor,
    VersionHistorial,
} from '@/types/editor';

const props = defineProps<{
    diagnostico: CabeceraEditor;
    /** null = el borrador. */
    version: { numero: number; publicada_en: string } | null;
    categorias: CategoriaVistaPrevia[];
    estado: EstadoEditor;
    versiones: VersionHistorial[];
    pendientes: number;
}>();

const { puede } = usePermisos();

const indice = ref(
    Math.max(
        0,
        props.categorias.findIndex((c) => c.problemas.length > 0),
    ),
);
const categoria = computed(() => props.categorias[indice.value] ?? null);

/** Respuestas de prueba: no se guardan. */
const respuestas = reactive<Record<string, string[] | string>>({});

/** Como en E2: "Ninguna" (exclusiva) desmarca las demás y al revés. */
function elegir(
    pregunta: string,
    opcion: { ref: string; exclusiva: boolean },
    opciones: { ref: string; exclusiva: boolean }[],
    multiple: boolean,
): void {
    const actual = respuestas[pregunta];

    if (!multiple) {
        respuestas[pregunta] = [opcion.ref];

        return;
    }

    const lista = Array.isArray(actual) ? actual : [];
    const exclusivas = opciones.filter((o) => o.exclusiva).map((o) => o.ref);

    if (lista.includes(opcion.ref)) {
        respuestas[pregunta] = lista.filter((o) => o !== opcion.ref);
    } else if (opcion.exclusiva) {
        respuestas[pregunta] = [opcion.ref];
    } else {
        respuestas[pregunta] = [
            ...lista.filter((o) => !exclusivas.includes(o)),
            opcion.ref,
        ];
    }
}

const marcada = (pregunta: string, opcion: string) => {
    const r = respuestas[pregunta];

    return Array.isArray(r) && r.includes(opcion);
};

const porCorregir = computed(() =>
    props.categorias.filter((c) => c.problemas.length > 0),
);
const problemas = computed(() =>
    porCorregir.value.reduce((s, c) => s + c.problemas.length, 0),
);

/** "Falta 1 indicación · 1 selección múltiple suma 80". */
function resumen(c: CategoriaVistaPrevia): string {
    const indicaciones = c.problemas.filter((p) =>
        p.startsWith('Falta la indicación'),
    ).length;
    const otros = c.problemas.filter(
        (p) => !p.startsWith('Falta la indicación'),
    );
    const partes = [
        ...(indicaciones
            ? [
                  indicaciones === 1
                      ? 'Falta 1 indicación'
                      : `Faltan ${indicaciones} indicaciones`,
              ]
            : []),
        ...otros.map((p) =>
            p.replace(
                /^La selección múltiple suma (solo )?/,
                '1 selección múltiple suma ',
            ),
        ),
    ];

    return partes.join(' · ');
}

function corregir(c: CategoriaVistaPrevia): string {
    const pregunta = c.preguntas.find((p) => p.problemas.length > 0);

    return rutas.diagnosticos.editar(
        props.diagnostico.id,
        c.parte_id ?? undefined,
        pregunta?.id,
    );
}

const progreso = computed(() =>
    props.estado.total
        ? (props.estado.completas / props.estado.total) * 100
        : 0,
);

/** Para el anillo de "N de M completas". */
const RADIO = 34;
const CIRCUNFERENCIA = 2 * Math.PI * RADIO;

const pendientesRequisitos = computed(
    () => props.estado.requisitos.filter((r) => !r.ok).length,
);

function respondida(ref: string): boolean {
    const r = respuestas[ref];

    return Array.isArray(r) ? r.length > 0 : !!r?.trim();
}

const respondidas = computed(
    () =>
        categoria.value?.preguntas.filter((p) => respondida(p.ref)).length ?? 0,
);

function irA(i: number): void {
    indice.value = i;
    document.getElementById('cuestionario')?.scrollTo({ top: 0 });
}

const opcionClase =
    'flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border px-4 py-2.5 text-sm leading-snug transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-marca/40';

const pistas = {
    abierta: 'Respuesta escrita',
    opcion_unica: 'Elige una',
    seleccion_multiple: 'Marca todas las que apliquen',
};

const modalVersiones = ref(false);
const esBorrador = computed(() => props.version === null);
/** Borrador que esta cuenta puede editar (permiso diagnosticos.editar). */
const editable = computed(
    () => esBorrador.value && puede('diagnosticos.editar'),
);
const nadaQuePublicar = computed(
    () =>
        !props.diagnostico.hay_borrador &&
        props.diagnostico.ultima_publicada !== null,
);
</script>

<template>
    <Head :title="`Vista previa · ${diagnostico.nombre}`" />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
        <header
            class="flex shrink-0 flex-wrap items-start justify-between gap-4"
        >
            <div class="min-w-0">
                <Link
                    :href="
                        editable
                            ? rutas.diagnosticos.editar(diagnostico.id)
                            : rutas.diagnosticos.sector(diagnostico.sector.id)
                    "
                    class="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-tinta-suave hover:text-marca"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    {{
                        editable
                            ? 'Volver al editor'
                            : `Diagnósticos · ${diagnostico.sector.nombre}`
                    }}
                </Link>
                <p
                    class="flex items-center gap-1.5 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                >
                    <Eye class="size-3.5" aria-hidden="true" />
                    Vista previa
                </p>
                <h1 class="text-2xl leading-tight font-semibold">
                    {{ diagnostico.nombre }}
                </h1>
                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
                    <span
                        v-if="version"
                        class="inline-flex items-center gap-1 rounded-full bg-exito-suave px-2.5 py-1 font-medium text-exito"
                    >
                        <Lock class="size-3.5" aria-hidden="true" />
                        v{{ version.numero }} publicada · solo lectura
                    </span>
                    <span
                        v-else
                        :class="
                            cn(
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium',
                                estado.publicable
                                    ? 'bg-exito-suave text-exito'
                                    : 'bg-alerta-suave text-alerta',
                            )
                        "
                    >
                        <CircleCheck
                            v-if="estado.publicable"
                            class="size-3.5"
                            aria-hidden="true"
                        />
                        <TriangleAlert
                            v-else
                            class="size-3.5"
                            aria-hidden="true"
                        />
                        Borrador v{{ diagnostico.version }} ·
                        {{ estado.publicable ? 'completo' : 'incompleto' }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-full border border-linea bg-white px-2.5 py-1 text-tinta"
                    >
                        {{ diagnostico.sector.nombre }}
                    </span>
                </div>
            </div>
            <div
                class="flex w-full flex-col items-start gap-1 sm:w-auto sm:items-end"
            >
                <div class="flex flex-wrap gap-2">
                    <Boton
                        v-if="editable"
                        variante="secundario"
                        class="gap-1.5"
                        :href="rutas.diagnosticos.editar(diagnostico.id)"
                    >
                        <Pencil class="size-4" aria-hidden="true" />
                        Editar
                    </Boton>
                    <Boton
                        v-if="esBorrador && puede('diagnosticos.publicar')"
                        class="gap-1.5"
                        :disabled="!estado.publicable || nadaQuePublicar"
                        @click="modalVersiones = true"
                    >
                        <Rocket class="size-4" aria-hidden="true" />
                        Publicar versión {{ diagnostico.version }}
                    </Boton>
                </div>
                <p
                    v-if="esBorrador && !estado.publicable"
                    class="text-xs text-tinta-suave"
                >
                    Cumple los 5 requisitos para publicar
                </p>
                <p
                    v-else-if="esBorrador && nadaQuePublicar"
                    class="text-xs text-tinta-suave"
                >
                    Sin cambios desde la v{{ diagnostico.ultima_publicada }}
                </p>
            </div>
        </header>

        <div
            class="grid items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[320px_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)] lg:items-stretch"
        >
            <!-- Estado -->
            <aside
                class="flex flex-col gap-4 rounded-2xl border border-linea bg-white p-5 text-sm lg:min-h-0 lg:overflow-y-auto"
            >
                <div class="flex items-center gap-4">
                    <div class="relative size-20 shrink-0">
                        <svg
                            viewBox="0 0 80 80"
                            class="size-20 -rotate-90"
                            aria-hidden="true"
                        >
                            <circle
                                cx="40"
                                cy="40"
                                :r="RADIO"
                                fill="none"
                                stroke-width="8"
                                class="stroke-lienzo-oscuro"
                            />
                            <circle
                                cx="40"
                                cy="40"
                                :r="RADIO"
                                fill="none"
                                stroke-width="8"
                                stroke-linecap="round"
                                :class="
                                    estado.publicable
                                        ? 'stroke-exito'
                                        : 'stroke-alerta'
                                "
                                :stroke-dasharray="CIRCUNFERENCIA"
                                :stroke-dashoffset="
                                    CIRCUNFERENCIA * (1 - progreso / 100)
                                "
                            />
                        </svg>
                        <span
                            class="absolute inset-0 flex flex-col items-center justify-center font-mono leading-none"
                        >
                            <span class="text-lg font-semibold">
                                {{ estado.completas }}/{{ estado.total }}
                            </span>
                        </span>
                    </div>
                    <div>
                        <h2 class="font-semibold">Estado del diagnóstico</h2>
                        <p class="mt-0.5 text-xs text-tinta-suave">
                            {{ estado.completas }} de {{ estado.total }}
                            {{
                                estado.total === 1
                                    ? 'categoría completa'
                                    : 'categorías completas'
                            }}
                        </p>
                        <p
                            :class="
                                cn(
                                    'mt-1.5 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                                    estado.publicable
                                        ? 'bg-exito-suave text-exito'
                                        : 'bg-alerta-suave text-alerta',
                                )
                            "
                        >
                            {{
                                estado.publicable
                                    ? 'Listo para publicar'
                                    : pendientesRequisitos === 1
                                      ? 'Falta 1 requisito'
                                      : `Faltan ${pendientesRequisitos} requisitos`
                            }}
                        </p>
                    </div>
                </div>

                <div>
                    <h2
                        class="text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                    >
                        Requisitos para publicar
                    </h2>
                    <ul class="mt-2 flex flex-col gap-1.5">
                        <li
                            v-for="r in estado.requisitos"
                            :key="r.clave"
                            :class="
                                cn(
                                    'flex items-start gap-2.5 rounded-xl px-3 py-2',
                                    r.ok
                                        ? 'bg-lienzo/60'
                                        : 'bg-alerta-suave/60',
                                )
                            "
                        >
                            <CircleCheck
                                v-if="r.ok"
                                class="mt-px size-4 shrink-0 text-exito"
                                aria-label="Cumple"
                            />
                            <CircleAlert
                                v-else
                                class="mt-px size-4 shrink-0 text-alerta"
                                aria-label="No cumple"
                            />
                            <span class="min-w-0 leading-snug">
                                {{ r.texto }}
                                <span
                                    v-if="r.detalle"
                                    class="mt-0.5 block text-xs font-medium text-alerta"
                                >
                                    {{ r.detalle }}
                                </span>
                            </span>
                        </li>
                    </ul>
                </div>

                <div v-if="porCorregir.length">
                    <h2
                        class="text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                    >
                        Por corregir
                        <span class="font-normal normal-case">
                            · {{ problemas }}
                            {{ problemas === 1 ? 'problema' : 'problemas' }} en
                            {{ porCorregir.length }}
                            {{
                                porCorregir.length === 1
                                    ? 'categoría'
                                    : 'categorías'
                            }}
                        </span>
                    </h2>
                    <ul class="mt-2 flex flex-col gap-2">
                        <li
                            v-for="c in porCorregir"
                            :key="c.ref"
                            class="rounded-xl border border-alerta/30 bg-white p-3"
                        >
                            <p class="flex items-start gap-2 font-medium">
                                <TriangleAlert
                                    class="mt-0.5 size-4 shrink-0 text-alerta"
                                    aria-hidden="true"
                                />
                                {{ c.nombre }}
                            </p>
                            <p class="mt-1 pl-6 text-xs text-alerta">
                                {{ resumen(c) }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-3 pl-6 text-xs">
                                <button
                                    type="button"
                                    class="font-medium text-tinta-suave hover:text-marca"
                                    @click="irA(categorias.indexOf(c))"
                                >
                                    Ver aquí
                                </button>
                                <Link
                                    v-if="editable"
                                    :href="corregir(c)"
                                    class="inline-flex items-center gap-1 font-medium text-marca hover:underline"
                                >
                                    Corregir en el editor
                                    <ArrowRight
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </div>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Cuestionario -->
            <section class="flex min-w-0 flex-col gap-3 lg:min-h-0">
                <div
                    class="flex shrink-0 flex-wrap items-center justify-between gap-3"
                >
                    <nav
                        class="inline-flex rounded-xl border border-linea bg-white p-1 text-sm"
                        aria-label="Vistas"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 rounded-lg bg-marca px-3 py-1.5 font-medium text-white"
                            aria-current="page"
                        >
                            <ClipboardList class="size-4" aria-hidden="true" />
                            Cuestionario
                        </span>
                        <Link
                            :href="
                                rutas.diagnosticos.resultadoEjemplo(
                                    diagnostico.id,
                                    version?.numero,
                                )
                            "
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-tinta-suave hover:bg-lienzo hover:text-marca"
                        >
                            <BarChart3 class="size-4" aria-hidden="true" />
                            Resultado de ejemplo
                        </Link>
                    </nav>
                    <p
                        class="flex items-center gap-1.5 text-xs text-tinta-suave"
                    >
                        <Eye class="size-3.5" aria-hidden="true" />
                        Así lo verá la empresa · las respuestas no se guardan
                    </p>
                </div>

                <div
                    v-if="categorias.length"
                    class="flex shrink-0 flex-wrap gap-1.5"
                    role="tablist"
                    aria-label="Categorías"
                >
                    <button
                        v-for="(c, i) in categorias"
                        :key="c.ref"
                        type="button"
                        role="tab"
                        :aria-selected="i === indice"
                        :class="
                            cn(
                                'inline-flex shrink-0 items-center gap-2 rounded-full border py-1.5 pr-3 pl-1.5 text-sm whitespace-nowrap transition',
                                i === indice
                                    ? 'border-marca bg-marca text-white'
                                    : 'border-linea bg-white hover:border-marca/40',
                            )
                        "
                        @click="irA(i)"
                    >
                        <span
                            :class="
                                cn(
                                    'flex size-6 items-center justify-center rounded-full font-mono text-xs font-semibold',
                                    i === indice
                                        ? 'bg-white/20'
                                        : c.problemas.length
                                          ? 'bg-alerta-suave text-alerta'
                                          : 'bg-lienzo-oscuro text-tinta-suave',
                                )
                            "
                        >
                            {{ i + 1 }}
                        </span>
                        {{ c.nombre }}
                        <TriangleAlert
                            v-if="c.problemas.length"
                            :class="
                                cn(
                                    'size-3.5',
                                    i === indice ? 'text-white' : 'text-alerta',
                                )
                            "
                            aria-label="Por corregir"
                        />
                    </button>
                </div>

                <div
                    id="cuestionario"
                    class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto rounded-2xl border border-linea bg-lienzo/50 p-4 sm:p-5"
                >
                    <template v-if="categoria">
                        <div
                            class="flex flex-wrap items-end justify-between gap-3 rounded-2xl bg-linear-to-br from-marca-suave/70 to-white px-5 py-4"
                        >
                            <div>
                                <p
                                    class="font-mono text-xs font-semibold tracking-wide text-marca"
                                >
                                    CATEGORÍA {{ indice + 1 }} DE
                                    {{ categorias.length }}
                                </p>
                                <h2 class="text-xl font-semibold">
                                    {{ categoria.nombre }}
                                </h2>
                            </div>
                            <div v-if="categoria.preguntas.length" class="w-44">
                                <p
                                    class="flex justify-between text-xs text-tinta-suave"
                                >
                                    Respondidas de prueba
                                    <span class="font-mono">
                                        {{ respondidas }}/{{
                                            categoria.preguntas.length
                                        }}
                                    </span>
                                </p>
                                <div
                                    class="mt-1 h-1.5 rounded-full bg-white"
                                    aria-hidden="true"
                                >
                                    <div
                                        class="h-1.5 rounded-full bg-marca transition-all"
                                        :style="{
                                            width: `${(respondidas / categoria.preguntas.length) * 100}%`,
                                        }"
                                    />
                                </div>
                            </div>
                        </div>

                        <p
                            v-if="categoria.preguntas.length === 0"
                            class="rounded-2xl border border-dashed border-alerta bg-alerta-suave/40 px-4 py-8 text-center text-sm text-alerta"
                        >
                            Esta categoría no tiene preguntas: la empresa no
                            vería nada aquí.
                        </p>

                        <article
                            v-for="(p, i) in categoria.preguntas"
                            :key="p.ref"
                            :class="
                                cn(
                                    'rounded-2xl border bg-white p-4 sm:p-5',
                                    p.problemas.length
                                        ? 'border-2 border-dashed border-alerta'
                                        : 'border-linea',
                                )
                            "
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    :class="
                                        cn(
                                            'flex size-8 shrink-0 items-center justify-center rounded-full font-mono text-sm font-semibold',
                                            respondida(p.ref)
                                                ? 'bg-marca text-white'
                                                : 'bg-marca-suave text-marca',
                                        )
                                    "
                                    aria-hidden="true"
                                >
                                    {{ respondida(p.ref) ? '✓' : i + 1 }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex flex-wrap items-center gap-2 text-xs text-tinta-suave"
                                    >
                                        <span>{{ pistas[p.tipo] }}</span>
                                        <span
                                            v-if="p.obligatoria"
                                            class="rounded-md bg-lienzo-oscuro px-2 py-0.5 font-medium text-tinta"
                                        >
                                            Obligatoria
                                        </span>
                                        <span v-else>(opcional)</span>
                                    </div>
                                    <p
                                        :id="`vp-texto-${p.ref}`"
                                        class="mt-1 text-base leading-snug font-semibold"
                                    >
                                        <span class="sr-only"
                                            >{{ i + 1 }}. </span
                                        >{{ p.texto }}
                                    </p>
                                    <p
                                        v-if="p.indicacion.trim()"
                                        class="mt-2.5 rounded-lg bg-lienzo px-3 py-2 text-sm"
                                    >
                                        <strong class="text-marca"
                                            >Cómo responder:</strong
                                        >
                                        {{ p.indicacion }}
                                    </p>
                                    <p
                                        v-for="problema in p.problemas"
                                        :key="problema"
                                        class="mt-2.5 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-alerta-suave px-3 py-2 text-xs text-alerta"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            <TriangleAlert
                                                class="size-3.5 shrink-0"
                                                aria-hidden="true"
                                            />
                                            {{ problema }}. La empresa la vería
                                            así.
                                        </span>
                                        <Link
                                            v-if="
                                                editable && categoria.parte_id
                                            "
                                            :href="
                                                rutas.diagnosticos.editar(
                                                    diagnostico.id,
                                                    categoria.parte_id,
                                                    p.id,
                                                )
                                            "
                                            class="inline-flex items-center gap-1 font-medium text-marca hover:underline"
                                        >
                                            Corregir
                                            <ArrowRight
                                                class="size-3.5"
                                                aria-hidden="true"
                                            />
                                        </Link>
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 sm:pl-11">
                                <div
                                    v-if="p.tipo !== 'abierta'"
                                    :role="
                                        p.tipo === 'opcion_unica'
                                            ? 'radiogroup'
                                            : 'group'
                                    "
                                    :aria-labelledby="`vp-texto-${p.ref}`"
                                    class="grid gap-2.5 sm:grid-cols-2"
                                >
                                    <label
                                        v-for="o in p.opciones"
                                        :key="o.ref"
                                        :class="
                                            cn(
                                                opcionClase,
                                                marcada(p.ref, o.ref)
                                                    ? 'border-2 border-marca bg-marca-suave/40 font-medium'
                                                    : 'border-linea-fuerte bg-white hover:bg-lienzo',
                                            )
                                        "
                                    >
                                        <input
                                            :type="
                                                p.tipo === 'opcion_unica'
                                                    ? 'radio'
                                                    : 'checkbox'
                                            "
                                            :name="`vp-${p.ref}`"
                                            :checked="marcada(p.ref, o.ref)"
                                            :class="
                                                cn(
                                                    'size-4 shrink-0 accent-marca',
                                                    p.tipo ===
                                                        'seleccion_multiple' &&
                                                        'rounded',
                                                )
                                            "
                                            @change="
                                                elegir(
                                                    p.ref,
                                                    o,
                                                    p.opciones,
                                                    p.tipo ===
                                                        'seleccion_multiple',
                                                )
                                            "
                                        />
                                        {{ o.texto }}
                                    </label>
                                </div>
                                <input
                                    v-if="
                                        p.tipo !== 'abierta' &&
                                        p.opciones.some(
                                            (o) =>
                                                o.con_texto &&
                                                marcada(p.ref, o.ref),
                                        )
                                    "
                                    type="text"
                                    maxlength="255"
                                    placeholder="¿Cuál?"
                                    :aria-label="`¿Cuál? Pregunta ${i + 1}`"
                                    class="mt-3 h-10 w-full max-w-md rounded-lg border border-linea-fuerte bg-white px-3 text-sm focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none"
                                />
                                <textarea
                                    v-if="p.tipo === 'abierta'"
                                    v-model="respuestas[p.ref] as string"
                                    rows="3"
                                    maxlength="1000"
                                    placeholder="Escribe tu respuesta aquí"
                                    :aria-label="`Respuesta de prueba a la pregunta ${i + 1}`"
                                    class="w-full rounded-lg border border-linea-fuerte bg-white px-3 py-2 text-sm focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none"
                                />
                            </div>
                        </article>

                        <div
                            class="flex flex-wrap items-center justify-between gap-3 pt-1"
                        >
                            <Boton
                                variante="secundario"
                                class="gap-1"
                                :disabled="indice === 0"
                                @click="irA(indice - 1)"
                            >
                                <ChevronLeft
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                Categoría anterior
                            </Boton>
                            <Boton
                                variante="secundario"
                                class="gap-1"
                                :disabled="indice >= categorias.length - 1"
                                @click="irA(indice + 1)"
                            >
                                Siguiente categoría
                                <ChevronRight
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </Boton>
                        </div>
                    </template>
                    <p
                        v-else
                        class="py-10 text-center text-sm text-tinta-suave"
                    >
                        El diagnóstico todavía no tiene categorías.
                    </p>
                </div>
            </section>
        </div>
    </div>

    <ModalVersiones
        v-if="esBorrador"
        v-model:abierto="modalVersiones"
        :diagnostico="diagnostico"
        :estado="estado"
        :versiones="versiones"
        :pendientes="pendientes"
    />
</template>
