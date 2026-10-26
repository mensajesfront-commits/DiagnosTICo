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
 */
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
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
        ? '✓ Listo para publicar'
        : props.estado.por_corregir
          ? `⚠ Incompleto · ${plural(props.estado.por_corregir, 'categoría por corregir', 'categorías por corregir')}`
          : '⚠ Incompleto · revisa la importancia',
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
                <div class="mb-2 flex flex-wrap items-center gap-3 text-sm">
                    <Link
                        :href="rutas.diagnosticos.sector(diagnostico.sector.id)"
                        class="inline-flex items-center gap-1.5 font-medium text-tinta-suave hover:text-marca"
                    >
                        <ArrowLeft class="size-4" aria-hidden="true" />
                        Diagnósticos · {{ diagnostico.sector.nombre }}
                    </Link>
                    <span v-if="guardado" class="text-xs text-exito">
                        ✓ Borrador guardado {{ guardado }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold">
                        {{ diagnostico.nombre }}
                    </h1>
                    <Etiqueta>Sector: {{ diagnostico.sector.nombre }}</Etiqueta>
                    <button
                        type="button"
                        class="rounded bg-lienzo-oscuro px-2 py-0.5 text-xs font-medium hover:bg-linea"
                        @click="modalVersiones = true"
                    >
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
                                'rounded px-2 py-0.5 text-xs font-medium underline-offset-2 hover:underline',
                                estado.publicable
                                    ? 'bg-exito-suave text-exito'
                                    : 'bg-alerta-suave text-alerta',
                            )
                        "
                    >
                        {{ textoEstado }} ›
                    </Link>
                </div>
            </div>
            <div class="flex shrink-0 flex-col items-end gap-1">
                <div class="flex gap-3">
                    <Boton
                        variante="secundario"
                        :href="rutas.diagnosticos.vistaPrevia(diagnostico.id)"
                    >
                        Vista previa y estado
                    </Boton>
                    <Boton
                        v-if="puede('diagnosticos.publicar')"
                        :disabled="!estado.publicable || nadaQuePublicar"
                        @click="modalVersiones = true"
                    >
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
            class="grid items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[280px_1fr] lg:grid-rows-[minmax(0,1fr)] lg:items-stretch"
        >
            <!-- Categorías -->
            <aside
                class="flex flex-col rounded-xl border border-linea bg-white lg:min-h-0"
            >
                <div
                    class="flex shrink-0 items-baseline justify-between px-4 pt-4 pb-2"
                >
                    <h2 class="text-sm font-semibold">Categorías</h2>
                    <span class="text-xs text-tinta-suave">
                        Importancia · Preguntas
                    </span>
                </div>
                <ul class="flex min-h-0 flex-1 flex-col overflow-y-auto px-2">
                    <li v-for="p in partes" :key="p.id">
                        <button
                            type="button"
                            :aria-current="
                                p.id === parteId ? 'true' : undefined
                            "
                            :class="
                                cn(
                                    'grid w-full grid-cols-[2rem_1fr_3rem_1.5rem] items-center gap-1 rounded-md px-2 py-2 text-left text-sm',
                                    p.id === parteId
                                        ? 'bg-marca-suave font-medium'
                                        : 'hover:bg-lienzo',
                                )
                            "
                            @click="parteId = p.id"
                        >
                            <span
                                v-if="p.problemas.length"
                                class="rounded bg-alerta-suave px-1 text-center text-xs font-semibold text-alerta"
                                :title="p.problemas.join(' · ')"
                            >
                                ⚠{{ p.problemas.length }}
                            </span>
                            <span v-else class="text-center text-exito">✓</span>
                            <span>{{ p.nombre }}</span>
                            <span class="text-right font-mono text-xs">
                                {{ porcentaje(p.importancia) }}
                            </span>
                            <span
                                class="text-right font-mono text-xs text-tinta-suave"
                            >
                                {{ p.preguntas.length }}
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
                    <p class="flex justify-between text-sm font-semibold">
                        Total de importancia
                        <span
                            :class="
                                cn(
                                    'font-mono',
                                    totalOk ? 'text-exito' : 'text-aviso',
                                )
                            "
                        >
                            {{ porcentaje(estado.importancia) }}
                            {{ totalOk ? '✓' : '✗' }}
                        </span>
                    </p>
                    <p class="text-xs text-tinta-suave">
                        Cada diagnóstico tiene su propia importancia. Aquí se
                        ajusta solo para este diagnóstico.
                    </p>
                    <Boton
                        variante="secundario"
                        :href="rutas.diagnosticos.importancia(diagnostico.id)"
                        :disabled="partes.length === 0"
                    >
                        Editar importancia
                    </Boton>
                    <button
                        type="button"
                        class="h-10 rounded-md border border-dashed border-linea-fuerte text-sm hover:bg-lienzo"
                        @click="modalCategoria = true"
                    >
                        + Agregar categoría
                    </button>
                    <p class="text-xs text-tinta-suave">
                        ✓ = completa · ⚠ = cosas por corregir en la categoría
                    </p>
                </div>
            </aside>

            <!-- Preguntas de la categoría -->
            <section class="flex flex-col gap-3 lg:min-h-0">
                <template v-if="parte">
                    <div
                        class="flex shrink-0 flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <h2
                                class="flex items-center gap-2 text-lg font-semibold"
                            >
                                {{ parte.nombre }}
                                <span
                                    v-if="parte.problemas.length"
                                    class="rounded bg-alerta-suave px-1.5 text-xs text-alerta"
                                >
                                    ⚠ {{ parte.problemas.length }} por corregir
                                </span>
                            </h2>
                            <p class="text-xs text-tinta-suave">
                                Importancia
                                {{ porcentaje(parte.importancia) }} ·
                                {{
                                    plural(
                                        parte.preguntas.length,
                                        'pregunta',
                                        'preguntas',
                                    )
                                }}
                                · arrastra ⋮⋮ para ordenar · pulsa Editar para
                                cambiar una pregunta
                            </p>
                        </div>
                        <Boton @click="agregarPregunta"
                            >+ Agregar pregunta</Boton
                        >
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
                            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-linea-fuerte bg-white px-4 py-10 text-center text-sm text-tinta-suave"
                        >
                            Esta categoría todavía no tiene preguntas. Necesita
                            al menos 1 para publicar.
                            <Boton
                                variante="secundario"
                                @click="agregarPregunta"
                            >
                                + Agregar pregunta
                            </Boton>
                        </li>
                    </ol>
                </template>
                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-linea-fuerte bg-white p-10 text-center text-sm text-tinta-suave"
                >
                    Agrega una categoría del catálogo para empezar a escribir
                    sus preguntas.
                    <Boton @click="modalCategoria = true"
                        >+ Agregar categoría</Boton
                    >
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
