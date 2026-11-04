<script setup lang="ts">
/**
 * A2.3 · Catálogo de categorías (HU-017, HU-018, HU-019).
 *
 * Catálogo común a todos los diagnósticos. Arriba, tres cifras (en uso,
 * archivadas y preguntas). La tabla usa todo el ancho, con búsqueda por
 * nombre, el filtro Todas / En uso / Archivadas en chips y 10 categorías por
 * página. Cada fila muestra la descripción, en cuántos diagnósticos se usa
 * (con su barra) y las acciones con íconos. Editar, crear (A2.3b) y archivar
 * (A2.3c) abren un modal en el centro.
 */
import { Head, router } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveRestore,
    CircleHelp,
    Info,
    Layers,
    Pencil,
    Search,
    SearchX,
    Shapes,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import Paginacion from '@/components/base/Paginacion.vue';
import Entrada from '@/components/base/Entrada.vue';
import ModalCategoria from '@/components/categorias/ModalCategoria.vue';
import ModalEditarCategoria from '@/components/categorias/ModalEditarCategoria.vue';
import ModalRetirarCategoria from '@/components/categorias/ModalRetirarCategoria.vue';
import { usePaginacion } from '@/lib/paginacion';
import { rutas } from '@/lib/rutas';
import { normalizar } from '@/lib/texto';
import { cn } from '@/lib/utils';
import type { Categoria, DiagnosticoBorrador } from '@/types/diagnosticos';
import { usePermisos } from '@/composables/usePermisos';

type Filtro = 'todas' | 'en-uso' | 'archivadas';

const props = withDefaults(
    defineProps<{
        categorias: Categoria[];
        /** Diagnósticos activos en total, para "13 de 13". */
        totalDiagnosticos: number;
        /** Diagnósticos en borrador, para agregar una categoría nueva. */
        borradores?: DiagnosticoBorrador[];
    }>(),
    { borradores: () => [] },
);

const { puede } = usePermisos();

const filtro = ref<Filtro>('todas');

const enUso = computed(() => props.categorias.filter((c) => !c.archivada));
const archivadas = computed(() => props.categorias.filter((c) => c.archivada));

const opcionesFiltro = computed(() => [
    {
        valor: 'todas' as Filtro,
        etiqueta: 'Todas',
        cantidad: props.categorias.length,
        tono: null,
    },
    {
        valor: 'en-uso' as Filtro,
        etiqueta: 'En uso',
        cantidad: enUso.value.length,
        tono: 'bg-exito',
    },
    {
        valor: 'archivadas' as Filtro,
        etiqueta: 'Archivadas',
        cantidad: archivadas.value.length,
        tono: 'bg-nivel-sin',
    },
]);

const busqueda = ref('');

const cifras = computed(() => [
    {
        valor: enUso.value.length,
        texto: 'Categorías en uso',
        icono: Layers,
        tono: 'bg-exito-suave text-exito',
    },
    {
        valor: archivadas.value.length,
        texto: 'Archivadas',
        icono: Archive,
        tono: 'bg-lienzo-oscuro text-tinta-suave',
    },
    {
        valor: enUso.value.reduce((suma, c) => suma + c.preguntas, 0),
        texto: 'Preguntas en total',
        icono: CircleHelp,
        tono: 'bg-marca-suave text-marca',
    },
]);

const filas = computed(() => {
    const lista =
        filtro.value === 'en-uso'
            ? enUso.value
            : filtro.value === 'archivadas'
              ? archivadas.value
              : [...enUso.value, ...archivadas.value];
    const buscado = normalizar(busqueda.value);

    return buscado === ''
        ? lista
        : lista.filter((c) => normalizar(c.nombre).includes(buscado));
});

/** Ancho de la barra "en N de M diagnósticos". */
const uso = (c: Categoria) =>
    props.totalDiagnosticos > 0
        ? Math.round((c.diagnosticos / props.totalDiagnosticos) * 100)
        : 0;

const claseIcono =
    'flex size-8 items-center justify-center rounded-lg text-tinta-suave transition';

const POR_PAGINA = 10;
const { pagina, paginas, total, desde, visibles } = usePaginacion(
    filas,
    POR_PAGINA,
);

const elegidaId = ref<number | null>(null);
const elegida = computed(
    () => props.categorias.find((c) => c.id === elegidaId.value) ?? null,
);
const modalEditar = ref(false);

function editar(categoria: Categoria): void {
    elegidaId.value = categoria.id;
    modalEditar.value = true;
}

const modalCrear = ref(false);
const modalRetirar = ref(false);
const aRetirar = ref<Categoria | null>(null);
const accionRetirar = ref<'archivar' | 'eliminar'>('archivar');

/** Archivar se puede siempre; eliminar, solo si nadie la ha respondido. */
function retirar(categoria: Categoria, accion: 'archivar' | 'eliminar'): void {
    aRetirar.value = categoria;
    accionRetirar.value = accion;
    modalRetirar.value = true;
}

function restaurar(categoria: Categoria): void {
    router.post(
        rutas.categorias.restaurar(categoria.id),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Catálogo de categorías" />

    <div class="flex flex-col gap-5 p-6">
        <EncabezadoPagina
            titulo="Catálogo de categorías"
            :volver="{
                href: rutas.diagnosticos.todos(),
                texto: 'Diagnósticos',
            }"
        >
            <Boton
                v-if="puede('diagnosticos.editar')"
                @click="modalCrear = true"
            >
                + Crear categoría
            </Boton>
        </EncabezadoPagina>

        <div class="grid grid-cols-3 gap-2 sm:gap-3">
            <div
                v-for="cifra in cifras"
                :key="cifra.texto"
                class="flex flex-col gap-2 rounded-2xl border border-linea bg-white p-3 sm:flex-row sm:items-center sm:gap-3 sm:p-4"
            >
                <span
                    :class="[
                        'flex size-8 shrink-0 items-center justify-center rounded-xl sm:size-10',
                        cifra.tono,
                    ]"
                    aria-hidden="true"
                >
                    <component :is="cifra.icono" class="size-4 sm:size-5" />
                </span>
                <div>
                    <p
                        class="font-mono text-xl leading-none font-semibold sm:text-2xl"
                    >
                        {{ cifra.valor }}
                    </p>
                    <p class="mt-1 text-xs text-tinta-suave">
                        {{ cifra.texto }}
                    </p>
                </div>
            </div>
        </div>

        <section class="rounded-2xl border border-linea bg-white">
            <header class="flex flex-col gap-3 px-5 pt-5 pb-4">
                <p
                    class="flex items-start gap-2 rounded-xl bg-marca-suave/50 px-3 py-2.5 text-sm text-tinta"
                >
                    <Info
                        class="mt-0.5 size-4 shrink-0 text-marca"
                        aria-hidden="true"
                    />
                    El catálogo es común a todos los diagnósticos. Cada
                    diagnóstico elige cuáles categorías usa. Aquí se crean,
                    editan, archivan y restauran.
                </p>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative min-w-52 flex-1">
                        <label for="buscar-categoria" class="sr-only">
                            Buscar categoría
                        </label>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-tinta-suave"
                            aria-hidden="true"
                        />
                        <Entrada
                            id="buscar-categoria"
                            v-model="busqueda"
                            type="search"
                            placeholder="Buscar categoría por nombre"
                            autocomplete="off"
                            class="h-9 pl-9"
                        />
                    </div>
                    <div
                        class="flex flex-wrap items-center gap-1.5"
                        role="group"
                        aria-label="Filtrar categorías"
                    >
                        <button
                            v-for="opcion in opcionesFiltro"
                            :key="opcion.valor"
                            type="button"
                            :aria-pressed="filtro === opcion.valor"
                            :class="
                                cn(
                                    'inline-flex h-9 items-center gap-2 rounded-full border px-3 text-xs transition',
                                    filtro === opcion.valor
                                        ? 'border-marca bg-marca text-white'
                                        : 'border-linea bg-white hover:border-marca/40',
                                )
                            "
                            @click="filtro = opcion.valor"
                        >
                            <span
                                v-if="opcion.tono"
                                :class="cn('size-2 rounded-full', opcion.tono)"
                                aria-hidden="true"
                            />
                            {{ opcion.etiqueta }}
                            <span
                                :class="
                                    cn(
                                        'rounded-full px-1.5 font-mono text-[11px] font-semibold',
                                        filtro === opcion.valor
                                            ? 'bg-white/20'
                                            : 'bg-lienzo-oscuro',
                                    )
                                "
                            >
                                {{ opcion.cantidad }}
                            </span>
                        </button>
                    </div>
                </div>
            </header>

            <div
                v-if="filas.length === 0"
                class="flex flex-col items-center gap-2 border-t border-linea px-5 py-10 text-center text-sm text-tinta-suave"
            >
                <SearchX
                    v-if="busqueda.trim() !== ''"
                    class="size-6"
                    aria-hidden="true"
                />
                <Shapes v-else class="size-6" aria-hidden="true" />
                <template v-if="busqueda.trim() !== ''">
                    Ninguna categoría se llama «{{ busqueda.trim() }}».
                </template>
                <template v-else-if="filtro === 'archivadas'">
                    No hay categorías archivadas.
                </template>
                <template v-else>
                    Todavía no hay categorías. Crea la primera con «+ Crear
                    categoría».
                </template>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr
                            class="border-y border-linea bg-lienzo/60 text-left"
                        >
                            <th
                                v-for="columna in [
                                    'Categoría',
                                    'Se usa en',
                                    'Preguntas',
                                    'Estado',
                                    'Acciones',
                                ]"
                                :key="columna"
                                scope="col"
                                class="px-5 py-2.5 text-xs font-medium text-tinta-suave"
                            >
                                {{ columna }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="categoria in visibles"
                            :key="categoria.id"
                            class="border-b border-linea transition-colors last:border-b-0 hover:bg-lienzo/40"
                        >
                            <td class="px-5 py-3">
                                <div class="flex min-w-60 items-center gap-3">
                                    <span
                                        :class="
                                            cn(
                                                'flex size-9 shrink-0 items-center justify-center rounded-xl',
                                                categoria.archivada
                                                    ? 'bg-lienzo-oscuro text-tinta-suave'
                                                    : 'bg-marca-suave text-marca',
                                            )
                                        "
                                        aria-hidden="true"
                                    >
                                        <Archive
                                            v-if="categoria.archivada"
                                            class="size-4"
                                        />
                                        <Shapes v-else class="size-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <p
                                            :class="
                                                cn(
                                                    'font-medium',
                                                    categoria.archivada &&
                                                        'text-tinta-suave',
                                                )
                                            "
                                        >
                                            {{ categoria.nombre }}
                                        </p>
                                        <p
                                            v-if="categoria.descripcion"
                                            class="line-clamp-1 max-w-md text-xs text-tinta-suave"
                                            :title="categoria.descripcion"
                                        >
                                            {{ categoria.descripcion }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span
                                    v-if="categoria.archivada"
                                    class="text-tinta-suave"
                                >
                                    —
                                </span>
                                <div v-else class="w-32">
                                    <p class="text-xs">
                                        <span class="font-mono font-semibold">{{
                                            categoria.diagnosticos
                                        }}</span>
                                        de {{ totalDiagnosticos }}
                                        {{
                                            totalDiagnosticos === 1
                                                ? 'diagnóstico'
                                                : 'diagnósticos'
                                        }}
                                    </p>
                                    <div
                                        class="mt-1 h-1.5 rounded-full bg-lienzo-oscuro"
                                        aria-hidden="true"
                                    >
                                        <div
                                            class="h-1.5 rounded-full bg-marca"
                                            :style="{
                                                width: `${uso(categoria)}%`,
                                            }"
                                        />
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 font-mono">
                                {{ categoria.preguntas }}
                            </td>
                            <td class="px-5 py-3">
                                <Etiqueta
                                    v-if="categoria.archivada"
                                    class="rounded-full"
                                >
                                    ○ Archivada
                                </Etiqueta>
                                <Etiqueta
                                    v-else
                                    tono="exito"
                                    class="rounded-full"
                                >
                                    ● En uso
                                </Etiqueta>
                            </td>
                            <td
                                v-if="!puede('diagnosticos.editar')"
                                class="px-5 py-3 text-xs text-tinta-suave"
                            >
                                Solo lectura
                            </td>
                            <td v-else class="px-5 py-3 whitespace-nowrap">
                                <button
                                    v-if="categoria.archivada"
                                    type="button"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-linea px-3 text-xs font-medium text-marca transition hover:border-marca hover:bg-marca-suave/40"
                                    @click="restaurar(categoria)"
                                >
                                    <ArchiveRestore
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    Restaurar
                                </button>
                                <div v-else class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        :class="
                                            cn(
                                                claseIcono,
                                                'hover:bg-marca-suave hover:text-marca',
                                            )
                                        "
                                        :title="`Editar ${categoria.nombre}`"
                                        :aria-label="`Editar ${categoria.nombre}`"
                                        @click="editar(categoria)"
                                    >
                                        <Pencil class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        :class="
                                            cn(
                                                claseIcono,
                                                'hover:bg-lienzo hover:text-tinta',
                                            )
                                        "
                                        :title="`Archivar ${categoria.nombre}`"
                                        :aria-label="`Archivar ${categoria.nombre}`"
                                        @click="retirar(categoria, 'archivar')"
                                    >
                                        <Archive class="size-4" />
                                    </button>
                                    <button
                                        v-if="!categoria.tiene_respuestas"
                                        type="button"
                                        :class="
                                            cn(
                                                claseIcono,
                                                'hover:bg-aviso-suave hover:text-aviso',
                                            )
                                        "
                                        :title="`Eliminar ${categoria.nombre}`"
                                        :aria-label="`Eliminar ${categoria.nombre}`"
                                        @click="retirar(categoria, 'eliminar')"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Paginacion
                v-if="filas.length > 0"
                v-model:pagina="pagina"
                :desde="desde"
                :cantidad="visibles.length"
                :total="total"
                :paginas="paginas"
            />
        </section>
    </div>

    <ModalCategoria v-model:abierto="modalCrear" :borradores="borradores" />
    <ModalEditarCategoria
        v-if="elegida && !elegida.archivada"
        v-model:abierto="modalEditar"
        :categoria="elegida"
        :total-diagnosticos="totalDiagnosticos"
        @retirar="retirar(elegida, $event)"
    />
    <ModalRetirarCategoria
        v-if="aRetirar"
        v-model:abierto="modalRetirar"
        :categoria="aRetirar"
        :accion="accionRetirar"
    />
</template>
