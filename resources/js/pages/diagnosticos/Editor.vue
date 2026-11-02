<script setup lang="ts">
/**
 * A2.1 · Editor del diagnóstico (HU-023 a HU-027, HU-029).
 *
 * - Arriba: nombre, sector, "Borrador v3 · Versiones" (A2.1d), el estado
 *   ("⚠ Incompleto · 2 categorías por corregir" o "✓ Listo para publicar"),
 *   "Vista previa y estado" (A2.4) y "Publicar versión 3".
 * - Izquierda: las categorías con ✓ o ⚠N, importancia y preguntas; el total
 *   de importancia, "Editar importancia" (A2.1c) y "+ Agregar categoría".
 * - Derecha: las preguntas de la categoría elegida, que se ordenan
 *   arrastrando ⋮⋮, con "Editar" (A2.1e) y "+ Agregar pregunta" (A2.1b).
 *
 * Cada cambio se guarda al momento en el borrador ("✓ Borrador guardado hace
 * 1 min"); las versiones publicadas no cambian (RN-010). En pantallas
 * grandes la página no se desplaza: las preguntas bajan y suben en su caja.
 * Con `?categoria=` y `?pregunta=` (desde "Corregir" de A2.4) abre esa
 * categoría y resalta la pregunta.
 *
 * Diseño: el estado, las versiones y el guardado van en píldoras con ícono.
 * Cada categoría muestra ✓ o ⚠N en un círculo, una barra con su importancia y
 * sus preguntas; la elegida se resalta en azul. Abajo, el total de
 * importancia con su barra. Arriba de las preguntas, una tarjeta con la
 * categoría, su importancia, sus preguntas y lo que falta corregir.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    CircleCheck,
    CloudCheck,
    Eye,
    GripVertical,
    History,
    Layers,
    Plus,
    Rocket,
    Scale,
    Store,
    TriangleAlert,
} from '@lucide/vue';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import ModalAgregarCategoria from '@/components/editor/ModalAgregarCategoria.vue';
import ModalEliminarPregunta from '@/components/editor/ModalEliminarPregunta.vue';
import ModalPregunta from '@/components/editor/ModalPregunta.vue';
import ModalVersiones from '@/components/editor/ModalVersiones.vue';
import TarjetaPregunta from '@/components/editor/TarjetaPregunta.vue';
import { usePermisos } from '@/composables/usePermisos';
import { guardadoHace } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { DatosEditor, PreguntaEditor } from '@/types/editor';

const props = defineProps<DatosEditor>();

const { puede } = usePermisos();

// --- Categoría elegida ----------------------------------------------------------
const parteId = ref<number | null>(
    props.partes.find((p) => p.id === props.parteInicial)?.id ??
        props.partes[0]?.id ??
        null,
);

const parte = computed(
    () => props.partes.find((p) => p.id === parteId.value) ?? null,
);

// Si se agregó la primera categoría o se borró la elegida.
watch(
    () => props.partes,
    (partes) => {
        if (!partes.some((p) => p.id === parteId.value)) {
            parteId.value = partes[0]?.id ?? null;
        }
    },
);

const resaltada = ref<number | null>(props.preguntaInicial);

onMounted(async () => {
    if (props.preguntaInicial) {
        await nextTick();
        document
            .getElementById(`pregunta-${props.preguntaInicial}`)
            ?.scrollIntoView({ block: 'center' });
        setTimeout(() => (resaltada.value = null), 4000);
    }
});

// --- Encabezado -------------------------------------------------------------------
const plural = (n: number, uno: string, varios: string) =>
    `${n} ${n === 1 ? uno : varios}`;

const textoEstado = computed(() =>
    props.estado.publicable
        ? 'Listo para publicar'
        : props.estado.por_corregir
          ? `Incompleto · ${plural(props.estado.por_corregir, 'categoría por corregir', 'categorías por corregir')}`
          : 'Incompleto · revisa la importancia',
);

const nadaQuePublicar = computed(
    () =>
        !props.diagnostico.hay_borrador &&
        props.diagnostico.ultima_publicada !== null,
);

/** Para que "hace 1 min" avance sin recargar. */
const ahora = ref(new Date());
onMounted(() => setInterval(() => (ahora.value = new Date()), 30000));

const guardado = computed(() =>
    props.diagnostico.guardado_en
        ? guardadoHace(props.diagnostico.guardado_en, ahora.value)
        : null,
);

// --- Preguntas: arrastrar para ordenar (HU-023 CA-004) ------------------------
const orden = ref<PreguntaEditor[]>([]);
watch(parte, (p) => (orden.value = p ? [...p.preguntas] : []), {
    immediate: true,
});

const arrastrada = ref<number | null>(null);

function soltarSobre(destino: number): void {
    const origen = arrastrada.value;

    if (origen === null || origen === destino) {
        return;
    }

    const lista = [...orden.value];
    const [movida] = lista.splice(origen, 1);
    lista.splice(destino, 0, movida);
    orden.value = lista;
    arrastrada.value = destino;
}

function guardarOrden(): void {
    arrastrada.value = null;

    if (
        !parte.value ||
        orden.value.map((p) => p.id).join() ===
            parte.value.preguntas.map((p) => p.id).join()
    ) {
        return;
    }

    router.put(
        rutas.diagnosticos.ordenar(props.diagnostico.id, parte.value.id),
        { preguntas: orden.value.map((p) => p.id) },
        { preserveScroll: true, preserveState: true },
    );
}

function moverConTeclado(i: number, paso: -1 | 1): void {
    const destino = i + paso;

    if (destino < 0 || destino >= orden.value.length) {
        return;
    }

    arrastrada.value = i;
    soltarSobre(destino);
    guardarOrden();
    void nextTick(() =>
        document
            .querySelector<HTMLElement>(
                `#pregunta-${orden.value[destino].id} [draggable]`,
            )
            ?.focus(),
    );
}

// --- Modales ----------------------------------------------------------------------
const modalPregunta = ref(false);
const modalEliminar = ref(false);
const modalCategoria = ref(false);
const modalVersiones = ref(false);
const editando = ref<PreguntaEditor | null>(null);

function agregarPregunta(): void {
    editando.value = null;
    modalPregunta.value = true;
}

function editarPregunta(p: PreguntaEditor): void {
    editando.value = p;
    modalPregunta.value = true;
}

function pedirEliminar(): void {
    modalPregunta.value = false;
    modalEliminar.value = true;
}

const numeroEditando = computed(() =>
    editando.value
        ? orden.value.findIndex((p) => p.id === editando.value?.id) + 1
        : 0,
);

const totalOk = computed(
    () => Math.abs(props.estado.importancia - 100) < 0.005,
);
const porcentaje = (n: number) =>
    `${Number.isInteger(n) ? n : n.toFixed(2).replace(/\.?0+$/, '')}%`;
</script>

<template>
    <Head :title="`Editar · ${diagnostico.nombre}`" />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
        <header
            class="flex shrink-0 flex-wrap items-start justify-between gap-4 xl:flex-nowrap"
        >
            <div class="min-w-0 flex-1">
                <Link
                    :href="rutas.diagnosticos.sector(diagnostico.sector.id)"
                    class="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-tinta-suave hover:text-marca"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Diagnósticos · {{ diagnostico.sector.nombre }}
                </Link>
                <h1 class="text-2xl leading-tight font-semibold">
                    {{ diagnostico.nombre }}
                </h1>
                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
                    <span
                        class="inline-flex items-center gap-1 rounded-full border border-linea bg-white px-2.5 py-1 text-tinta"
                    >
                        <Store
                            class="size-3.5 text-tinta-suave"
                            aria-hidden="true"
                        />
                        {{ diagnostico.sector.nombre }}
                    </span>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-linea bg-white px-2.5 py-1 font-medium transition hover:border-marca hover:text-marca"
                        @click="modalVersiones = true"
                    >
                        <History class="size-3.5" aria-hidden="true" />
                        {{
                            nadaQuePublicar
                                ? `Publicada v${diagnostico.ultima_publicada}`
                                : `Borrador v${diagnostico.version}`
                        }}
                        · Versiones ▾
                    </button>
                    <Link
                        :href="rutas.diagnosticos.vistaPrevia(diagnostico.id)"
                        :class="
                            cn(
                                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium transition hover:brightness-95',
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
                        {{ textoEstado }} ›
                    </Link>
                    <span
                        v-if="guardado"
                        class="inline-flex items-center gap-1 px-1 text-exito"
                    >
                        <CloudCheck class="size-3.5" aria-hidden="true" />
                        Borrador guardado {{ guardado }}
                    </span>
                </div>
            </div>
            <div
                class="flex w-full flex-col items-start gap-1 sm:w-auto sm:shrink-0 sm:items-end"
            >
                <div class="flex flex-wrap gap-2">
                    <Boton
                        variante="secundario"
                        class="gap-1.5"
                        :href="rutas.diagnosticos.vistaPrevia(diagnostico.id)"
                    >
                        <Eye class="size-4" aria-hidden="true" />
                        Vista previa y estado
                    </Boton>
                    <Boton
                        v-if="puede('diagnosticos.publicar')"
                        class="gap-1.5"
                        :disabled="!estado.publicable || nadaQuePublicar"
                        @click="modalVersiones = true"
                    >
                        <Rocket class="size-4" aria-hidden="true" />
                        Publicar versión {{ diagnostico.version }}
                    </Boton>
                </div>
                <p
                    v-if="!estado.publicable && estado.por_corregir"
                    class="text-xs text-tinta-suave"
                >
                    Corrige
                    {{ plural(estado.por_corregir, 'categoría', 'categorías') }}
                    para publicar
                </p>
                <p v-else-if="nadaQuePublicar" class="text-xs text-tinta-suave">
                    Sin cambios desde la v{{ diagnostico.ultima_publicada }}
                </p>
            </div>
        </header>

        <div
            class="grid items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[300px_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)] lg:items-stretch"
        >
            <!-- Categorías -->
            <aside
                class="flex flex-col rounded-2xl border border-linea bg-white lg:min-h-0"
            >
                <div
                    class="flex shrink-0 items-center justify-between px-4 pt-4 pb-2"
                >
                    <h2
                        class="text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                    >
                        Categorías
                        <span class="font-mono font-normal normal-case">
                            · {{ partes.length }}
                        </span>
                    </h2>
                    <span
                        :class="
                            cn(
                                'rounded-full px-2 py-0.5 text-[11px] font-medium',
                                estado.completas === estado.total
                                    ? 'bg-exito-suave text-exito'
                                    : 'bg-alerta-suave text-alerta',
                            )
                        "
                    >
                        {{ estado.completas }} de {{ estado.total }} completas
                    </span>
                </div>
                <ul
                    class="flex min-h-0 flex-1 flex-col gap-0.5 overflow-y-auto px-2"
                >
                    <li v-for="p in partes" :key="p.id">
                        <button
                            type="button"
                            :aria-current="
                                p.id === parteId ? 'true' : undefined
                            "
                            :class="
                                cn(
                                    'flex w-full items-start gap-2.5 rounded-xl px-2.5 py-2.5 text-left text-sm transition-colors',
                                    p.id === parteId
                                        ? 'bg-marca text-white'
                                        : 'hover:bg-lienzo',
                                )
                            "
                            @click="parteId = p.id"
                        >
                            <span
                                v-if="p.problemas.length"
                                class="mt-px flex size-5 shrink-0 items-center justify-center rounded-full bg-alerta-suave font-mono text-[10px] font-semibold text-alerta"
                                :title="p.problemas.join(' · ')"
                            >
                                {{ p.problemas.length }}
                                <span class="sr-only">por corregir</span>
                            </span>
                            <span
                                v-else
                                :class="
                                    cn(
                                        'mt-px flex size-5 shrink-0 items-center justify-center rounded-full',
                                        p.id === parteId
                                            ? 'bg-white/20 text-white'
                                            : 'bg-exito-suave text-exito',
                                    )
                                "
                                title="Completa"
                            >
                                <Check class="size-3" aria-hidden="true" />
                                <span class="sr-only">completa</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="flex items-start justify-between gap-2"
                                >
                                    <span
                                        :class="
                                            p.id === parteId
                                                ? 'font-medium'
                                                : undefined
                                        "
                                    >
                                        {{ p.nombre }}
                                    </span>
                                    <span
                                        class="shrink-0 font-mono text-xs font-semibold"
                                    >
                                        {{ porcentaje(p.importancia) }}
                                    </span>
                                </span>
                                <span
                                    :class="
                                        cn(
                                            'mt-1.5 block h-1 rounded-full',
                                            p.id === parteId
                                                ? 'bg-white/25'
                                                : 'bg-lienzo-oscuro',
                                        )
                                    "
                                    aria-hidden="true"
                                >
                                    <span
                                        :class="
                                            cn(
                                                'block h-1 rounded-full',
                                                p.id === parteId
                                                    ? 'bg-white'
                                                    : 'bg-marca/70',
                                            )
                                        "
                                        :style="{
                                            width: `${Math.min(100, p.importancia)}%`,
                                        }"
                                    />
                                </span>
                                <span
                                    :class="
                                        cn(
                                            'mt-1 block text-[11px]',
                                            p.id === parteId
                                                ? 'text-white/75'
                                                : 'text-tinta-suave',
                                        )
                                    "
                                >
                                    {{
                                        plural(
                                            p.preguntas.length,
                                            'pregunta',
                                            'preguntas',
                                        )
                                    }}
                                </span>
                            </span>
                        </button>
                    </li>
                    <li
                        v-if="partes.length === 0"
                        class="px-2 py-6 text-center text-sm text-tinta-suave"
                    >
                        Todavía no tiene categorías.
                    </li>
                </ul>
                <div
                    class="flex shrink-0 flex-col gap-2 border-t border-linea p-4"
                >
                    <div>
                        <p
                            class="flex items-center justify-between text-sm font-semibold"
                        >
                            Total de importancia
                            <span
                                :class="
                                    cn(
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-mono text-xs',
                                        totalOk
                                            ? 'bg-exito-suave text-exito'
                                            : 'bg-aviso-suave text-aviso',
                                    )
                                "
                            >
                                {{ porcentaje(estado.importancia) }}
                                {{ totalOk ? '✓' : '✗' }}
                            </span>
                        </p>
                        <div
                            class="mt-2 h-1.5 rounded-full bg-lienzo-oscuro"
                            aria-hidden="true"
                        >
                            <div
                                :class="
                                    cn(
                                        'h-1.5 rounded-full',
                                        totalOk ? 'bg-exito' : 'bg-aviso',
                                    )
                                "
                                :style="{
                                    width: `${Math.min(100, estado.importancia)}%`,
                                }"
                            />
                        </div>
                        <p class="mt-1.5 text-xs text-tinta-suave">
                            Debe sumar 100%. Se ajusta solo para este
                            diagnóstico.
                        </p>
                    </div>
                    <Boton
                        variante="secundario"
                        class="gap-1.5"
                        :href="rutas.diagnosticos.importancia(diagnostico.id)"
                        :disabled="partes.length === 0"
                    >
                        <Scale class="size-4" aria-hidden="true" />
                        Editar importancia
                    </Boton>
                    <button
                        type="button"
                        class="flex h-10 items-center justify-center gap-1.5 rounded-lg border border-dashed border-linea-fuerte text-sm transition hover:border-marca hover:bg-marca-suave/50 hover:text-marca"
                        @click="modalCategoria = true"
                    >
                        <Plus class="size-4" aria-hidden="true" />
                        Agregar categoría
                    </button>
                </div>
            </aside>

            <!-- Preguntas de la categoría -->
            <section class="flex flex-col gap-3 lg:min-h-0">
                <template v-if="parte">
                    <div
                        class="flex shrink-0 flex-wrap items-center justify-between gap-3 rounded-2xl border border-linea bg-linear-to-br from-marca-suave/70 to-white px-4 py-3"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-marca shadow-sm"
                                aria-hidden="true"
                            >
                                <Layers class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <h2
                                    class="flex flex-wrap items-center gap-2 text-lg leading-tight font-semibold"
                                >
                                    {{ parte.nombre }}
                                    <span
                                        v-if="parte.problemas.length"
                                        class="inline-flex items-center gap-1 rounded-full bg-alerta-suave px-2 py-0.5 text-xs font-medium text-alerta"
                                        :title="parte.problemas.join(' · ')"
                                    >
                                        <TriangleAlert
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        {{ parte.problemas.length }} por
                                        corregir
                                    </span>
                                </h2>
                                <p
                                    class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-tinta-suave"
                                >
                                    <span>
                                        Importancia
                                        <strong class="font-mono text-tinta">
                                            {{ porcentaje(parte.importancia) }}
                                        </strong>
                                    </span>
                                    <span>
                                        {{
                                            plural(
                                                parte.preguntas.length,
                                                'pregunta',
                                                'preguntas',
                                            )
                                        }}
                                    </span>
                                    <span
                                        class="hidden items-center gap-1 sm:inline-flex"
                                    >
                                        <GripVertical
                                            class="size-3.5"
                                            aria-hidden="true"
                                        />
                                        Arrastra el número para ordenar
                                    </span>
                                </p>
                            </div>
                        </div>
                        <Boton class="gap-1.5" @click="agregarPregunta">
                            <Plus class="size-4" aria-hidden="true" />
                            Agregar pregunta
                        </Boton>
                    </div>

                    <ol
                        class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto pb-2"
                    >
                        <li
                            v-for="(p, i) in orden"
                            :key="p.id"
                            @dragstart="arrastrada = i"
                            @dragover.prevent="soltarSobre(i)"
                            @drop.prevent="guardarOrden"
                            @dragend="guardarOrden"
                        >
                            <TarjetaPregunta
                                :pregunta="p"
                                :numero="i + 1"
                                :resaltada="resaltada === p.id"
                                :arrastrando="arrastrada === i"
                                @editar="editarPregunta(p)"
                                @mover="(paso) => moverConTeclado(i, paso)"
                            />
                        </li>
                        <li
                            v-if="orden.length === 0"
                            class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-linea-fuerte bg-white px-4 py-10 text-center text-sm text-tinta-suave"
                        >
                            Esta categoría todavía no tiene preguntas. Necesita
                            al menos 1 para publicar.
                            <Boton
                                variante="secundario"
                                class="gap-1.5"
                                @click="agregarPregunta"
                            >
                                <Plus class="size-4" aria-hidden="true" />
                                Agregar pregunta
                            </Boton>
                        </li>
                        <li v-else class="flex justify-center pt-1">
                            <button
                                type="button"
                                class="flex h-11 w-full items-center justify-center gap-1.5 rounded-2xl border border-dashed border-linea-fuerte text-sm text-tinta-suave transition hover:border-marca hover:bg-white hover:text-marca"
                                @click="agregarPregunta"
                            >
                                <Plus class="size-4" aria-hidden="true" />
                                Agregar otra pregunta a {{ parte.nombre }}
                            </button>
                        </li>
                    </ol>
                </template>
                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-linea-fuerte bg-white p-10 text-center text-sm text-tinta-suave"
                >
                    <span
                        class="flex size-12 items-center justify-center rounded-2xl bg-marca-suave text-marca"
                        aria-hidden="true"
                    >
                        <Layers class="size-6" />
                    </span>
                    Agrega una categoría del catálogo para empezar a escribir
                    sus preguntas.
                    <Boton class="gap-1.5" @click="modalCategoria = true">
                        <Plus class="size-4" aria-hidden="true" />
                        Agregar categoría
                    </Boton>
                </div>
            </section>
        </div>
    </div>

    <ModalPregunta
        v-if="parte"
        v-model:abierto="modalPregunta"
        :diagnostico-id="diagnostico.id"
        :parte="parte"
        :pregunta="editando"
        @eliminar="pedirEliminar"
    />
    <ModalEliminarPregunta
        v-if="parte && editando"
        v-model:abierto="modalEliminar"
        :diagnostico-id="diagnostico.id"
        :parte="parte"
        :pregunta="editando"
        :numero="numeroEditando"
        @eliminada="editando = null"
    />
    <ModalAgregarCategoria
        v-model:abierto="modalCategoria"
        :diagnostico-id="diagnostico.id"
        :catalogo="catalogo"
        :partes="partes"
    />
    <ModalVersiones
        v-model:abierto="modalVersiones"
        :diagnostico="diagnostico"
        :estado="estado"
        :versiones="versiones"
        :pendientes="pendientes"
    />
</template>
