<script setup lang="ts">
/**
 * A2.3 · Catálogo de categorías (HU-017, HU-018, HU-019).
 *
 * Catálogo común a todos los diagnósticos: la tabla usa todo el ancho, con el
 * filtro Todas / En uso / Archivadas y 10 categorías por página. Editar, crear (A2.3b) y archivar
 * (A2.3c) abren un modal en el centro.
 */
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import Paginacion from '@/components/base/Paginacion.vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import ModalCategoria from '@/components/categorias/ModalCategoria.vue';
import ModalEditarCategoria from '@/components/categorias/ModalEditarCategoria.vue';
import ModalRetirarCategoria from '@/components/categorias/ModalRetirarCategoria.vue';
import { usePaginacion } from '@/lib/paginacion';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { Categoria, DiagnosticoBorrador } from '@/types/diagnosticos';

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

const filtro = ref<Filtro>('todas');

const enUso = computed(() => props.categorias.filter((c) => !c.archivada));
const archivadas = computed(() => props.categorias.filter((c) => c.archivada));

const opcionesFiltro = computed(() => [
    {
        valor: 'todas' as Filtro,
        etiqueta: 'Todas',
        cantidad: props.categorias.length,
    },
    {
        valor: 'en-uso' as Filtro,
        etiqueta: 'En uso',
        cantidad: enUso.value.length,
    },
    {
        valor: 'archivadas' as Filtro,
        etiqueta: 'Archivadas',
        cantidad: archivadas.value.length,
    },
]);

const filas = computed(() => {
    if (filtro.value === 'en-uso') {
        return enUso.value;
    }

    if (filtro.value === 'archivadas') {
        return archivadas.value;
    }

    return [...enUso.value, ...archivadas.value];
});

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
            <Boton @click="modalCrear = true">+ Crear categoría</Boton>
        </EncabezadoPagina>

        <section class="rounded-xl border border-linea bg-white">
            <header
                class="flex flex-wrap items-center justify-between gap-4 px-5 py-4"
            >
                <p class="min-w-60 flex-1 text-sm text-tinta-suave">
                    El catálogo es común a todos los diagnósticos. Cada
                    diagnóstico elige cuáles categorías usa. Aquí se crean,
                    editan, archivan y restauran.
                </p>
                <SelectorCompacto
                    v-model="filtro"
                    :opciones="opcionesFiltro"
                    etiqueta-accesible="Filtrar categorías"
                    class="shrink-0"
                />
            </header>

            <p
                v-if="filas.length === 0"
                class="border-t border-linea px-5 py-8 text-center text-sm text-tinta-suave"
            >
                {{
                    filtro === 'archivadas'
                        ? 'No hay categorías archivadas.'
                        : 'Todavía no hay categorías. Crea la primera con «+ Crear categoría».'
                }}
            </p>

            <div v-else class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr
                            class="border-y border-linea bg-[#f9f8f4] text-left"
                        >
                            <th
                                v-for="columna in [
                                    'Categoría',
                                    'Diagnósticos',
                                    'Preguntas',
                                    'Estado',
                                    'Acciones',
                                ]"
                                :key="columna"
                                scope="col"
                                class="px-5 py-2.5 text-xs font-normal text-tinta-suave"
                            >
                                {{ columna }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="categoria in visibles"
                            :key="categoria.id"
                            class="border-b border-linea last:border-b-0"
                        >
                            <td
                                :class="
                                    cn(
                                        'px-5 py-3 font-medium',
                                        categoria.archivada &&
                                            'text-tinta-suave',
                                    )
                                "
                            >
                                {{ categoria.nombre }}
                            </td>
                            <td class="px-5 py-3 font-mono whitespace-nowrap">
                                {{
                                    categoria.archivada
                                        ? '—'
                                        : `${categoria.diagnosticos} de ${totalDiagnosticos}`
                                }}
                            </td>
                            <td class="px-5 py-3 font-mono">
                                {{ categoria.preguntas }}
                            </td>
                            <td class="px-5 py-3">
                                <Etiqueta v-if="categoria.archivada">
                                    ▣ Archivada
                                </Etiqueta>
                                <Etiqueta v-else tono="exito">
                                    ✓ En uso
                                </Etiqueta>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <button
                                    v-if="categoria.archivada"
                                    type="button"
                                    class="text-marca underline hover:text-marca-hover"
                                    @click="restaurar(categoria)"
                                >
                                    Restaurar
                                </button>
                                <template v-else>
                                    <button
                                        type="button"
                                        class="text-marca underline hover:text-marca-hover"
                                        @click="editar(categoria)"
                                    >
                                        Editar
                                    </button>
                                    <span class="mx-1.5 text-tinta-suave"
                                        >·</span
                                    >
                                    <button
                                        type="button"
                                        class="text-tinta underline hover:text-marca"
                                        @click="retirar(categoria, 'archivar')"
                                    >
                                        Archivar
                                    </button>
                                    <template
                                        v-if="!categoria.tiene_respuestas"
                                    >
                                        <span class="mx-1.5 text-tinta-suave"
                                            >·</span
                                        >
                                        <button
                                            type="button"
                                            class="text-tinta underline hover:text-aviso"
                                            @click="
                                                retirar(categoria, 'eliminar')
                                            "
                                        >
                                            Eliminar
                                        </button>
                                    </template>
                                </template>
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
