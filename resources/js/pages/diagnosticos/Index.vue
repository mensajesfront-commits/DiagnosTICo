<script setup lang="ts">
/**
 * A2 · Diagnósticos de un sector (HU-010), A2·T · Todos los diagnósticos
 * (HU-011) y A2b · Sector sin diagnósticos.
 *
 * Una sola pantalla: con `sector` muestra ese sector; sin `sector` muestra
 * "Todos", agrupados por sector, con orden y filtro por estado.
 * Props completas en docs/14_FRONTEND.md.
 *
 * En pantallas grandes la página no se desplaza: ocupa el alto de la ventana
 * y solo bajan y suben la lista de sectores, la tabla de diagnósticos y la de
 * empresas, cada una en su espacio. La búsqueda filtra por nombre, sin mirar
 * tildes ni mayúsculas.
 */
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Entrada from '@/components/base/Entrada.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import EmpresasDelSector from '@/components/diagnosticos/EmpresasDelSector.vue';
import ListaSectores from '@/components/diagnosticos/ListaSectores.vue';
import ModalArchivarDiagnostico from '@/components/diagnosticos/modales/ModalArchivarDiagnostico.vue';
import ModalDuplicarDiagnostico from '@/components/diagnosticos/modales/ModalDuplicarDiagnostico.vue';
import ModalEliminarDiagnostico from '@/components/diagnosticos/modales/ModalEliminarDiagnostico.vue';
import ModalEliminarSector from '@/components/diagnosticos/modales/ModalEliminarSector.vue';
import ModalEstadoSector from '@/components/diagnosticos/modales/ModalEstadoSector.vue';
import ModalReasignarSector from '@/components/diagnosticos/modales/ModalReasignarSector.vue';
import ModalSector from '@/components/diagnosticos/modales/ModalSector.vue';
import ResumenDiagnosticos from '@/components/diagnosticos/ResumenDiagnosticos.vue';
import TablaDiagnosticos from '@/components/diagnosticos/TablaDiagnosticos.vue';
import { rutas } from '@/lib/rutas';
import { normalizar } from '@/lib/texto';
import type {
    EmpresaDelSector,
    FilaDiagnostico,
    MedicionesPorEstado,
    ResumenDiagnosticos as Resumen,
    Sector,
} from '@/types/diagnosticos';

const props = withDefaults(
    defineProps<{
        sectores: Sector[];
        /** Sector elegido; null en "Todos". */
        sector?: Sector | null;
        resumen: Resumen & { mediciones_por_estado?: MedicionesPorEstado };
        diagnosticos: FilaDiagnostico[];
        /** Solo con sector: primeras empresas del sector. */
        empresas?: EmpresaDelSector[];
    }>(),
    { sector: null, empresas: () => [] },
);

const totalDiagnosticos = computed(() =>
    props.sectores.reduce((suma, s) => suma + s.diagnosticos, 0),
);

// --- "Todos": orden y filtro (HU-011 CA-002) ---------------------------------
type Orden = 'sector' | 'edicion' | 'nombre';
type FiltroEstado = 'todos' | 'publicado' | 'borrador';

const orden = ref<Orden>('sector');
const filtro = ref<FiltroEstado>('todos');
const busqueda = ref('');
const buscando = computed(() => normalizar(busqueda.value) !== '');

const filas = computed(() => {
    let lista = [...props.diagnosticos];
    const buscado = normalizar(busqueda.value);

    if (buscado !== '') {
        lista = lista.filter((d) => normalizar(d.nombre).includes(buscado));
    }

    if (filtro.value === 'publicado') {
        lista = lista.filter((d) => d.version_publicada !== null);
    } else if (filtro.value === 'borrador') {
        lista = lista.filter(
            (d) =>
                d.version_publicada === null || d.borrador_pendiente !== null,
        );
    }

    const porNombre = (a: FilaDiagnostico, b: FilaDiagnostico) =>
        a.nombre.localeCompare(b.nombre, 'es');

    if (orden.value === 'nombre') {
        return lista.sort(porNombre);
    }

    if (orden.value === 'edicion') {
        return lista.sort((a, b) =>
            b.actualizado_en.localeCompare(a.actualizado_en),
        );
    }

    // Sector A–Z y, dentro de cada sector, publicados antes que borradores.
    return lista.sort(
        (a, b) =>
            a.sector_nombre.localeCompare(b.sector_nombre, 'es') ||
            Number(a.version_publicada === null) -
                Number(b.version_publicada === null),
    );
});

const sePuedeEliminar = computed(
    () => props.resumen.empresas === 0 && props.resumen.mediciones === 0,
);

// --- Modales ------------------------------------------------------------------
const modalSector = ref(false);
const editandoSector = ref(false);
const modalReasignar = ref(false);
const modalEstado = ref(false);
const modalEliminarSector = ref(false);

const diagnosticoElegido = ref<FilaDiagnostico | null>(null);
const modalDuplicar = ref(false);
const modalArchivar = ref(false);
const modalEliminar = ref(false);
const eliminarQue = ref<'borrador' | 'diagnostico'>('diagnostico');

function crearSector(): void {
    editandoSector.value = false;
    modalSector.value = true;
}

function editarSector(): void {
    editandoSector.value = true;
    modalSector.value = true;
}

function elegir(
    fila: FilaDiagnostico,
    accion: 'duplicar' | 'archivar' | 'eliminar' | 'borrador',
): void {
    diagnosticoElegido.value = fila;

    if (accion === 'duplicar') {
        modalDuplicar.value = true;
    } else if (accion === 'archivar') {
        modalArchivar.value = true;
    } else {
        eliminarQue.value = accion === 'borrador' ? 'borrador' : 'diagnostico';
        modalEliminar.value = true;
    }
}
</script>

<template>
    <Head
        :title="sector ? `Diagnósticos de ${sector.nombre}` : 'Diagnósticos'"
    />

    <div class="flex flex-col gap-5 p-6 lg:h-dvh lg:overflow-hidden">
        <EncabezadoPagina
            titulo="Diagnósticos"
            descripcion="Cada sector puede tener varios diagnósticos. Aquí también se administran los sectores y el catálogo de categorías."
        >
            <Boton variante="secundario" :href="rutas.categorias.catalogo()">
                Catálogo de categorías
            </Boton>
            <Boton :href="rutas.diagnosticos.crear(sector?.id)">
                + Crear diagnóstico
            </Boton>
        </EncabezadoPagina>

        <div
            class="grid items-start gap-5 lg:min-h-0 lg:flex-1 lg:grid-cols-[240px_1fr] lg:grid-rows-[minmax(0,1fr)] lg:items-stretch"
        >
            <div class="flex flex-col gap-4 lg:min-h-0">
                <ListaSectores
                    class="lg:min-h-0"
                    :sectores="sectores"
                    :sector-actual-id="sector?.id ?? null"
                    :total-diagnosticos="totalDiagnosticos"
                    @crear="crearSector"
                />
                <ResumenDiagnosticos
                    class="shrink-0"
                    :resumen="resumen"
                    :sector="sector"
                />
            </div>

            <div class="flex min-w-0 flex-col gap-5 lg:min-h-0">
                <section
                    class="flex flex-col rounded-xl border border-linea bg-white lg:min-h-0"
                >
                    <header
                        class="flex flex-wrap items-center justify-between gap-3 px-5 pt-4 pb-3"
                    >
                        <h2 class="text-base font-semibold">
                            {{
                                sector
                                    ? `Diagnósticos de ${sector.nombre}`
                                    : 'Todos los diagnósticos'
                            }}
                            <span
                                class="ml-2 text-xs font-normal text-tinta-suave"
                            >
                                <template v-if="sector">
                                    {{ sector.diagnosticos }} diagnósticos ·
                                    {{ resumen.empresas }} empresas
                                </template>
                                <template v-else>
                                    {{ totalDiagnosticos }} diagnósticos ·
                                    {{ sectores.length }} sectores
                                </template>
                            </span>
                        </h2>

                        <div v-if="sector" class="flex gap-4 text-xs">
                            <button
                                type="button"
                                class="text-marca underline"
                                @click="editarSector"
                            >
                                Editar sector
                            </button>
                            <button
                                type="button"
                                class="text-marca underline"
                                @click="modalEliminarSector = true"
                            >
                                Eliminar sector
                            </button>
                        </div>
                        <div
                            v-else
                            class="flex flex-wrap items-center gap-3 text-xs"
                        >
                            <label class="flex items-center gap-2">
                                Ordenar por
                                <Seleccion
                                    v-model="orden"
                                    class="h-8 w-auto text-xs"
                                >
                                    <option value="sector">
                                        Sector (A–Z), luego estado
                                    </option>
                                    <option value="edicion">
                                        Última edición
                                    </option>
                                    <option value="nombre">Nombre (A–Z)</option>
                                </Seleccion>
                            </label>
                            <label class="flex items-center gap-2">
                                Estado
                                <Seleccion
                                    v-model="filtro"
                                    class="h-8 w-auto text-xs"
                                >
                                    <option value="todos">Todos</option>
                                    <option value="publicado">
                                        Publicados
                                    </option>
                                    <option value="borrador">Borradores</option>
                                </Seleccion>
                            </label>
                        </div>
                    </header>

                    <div
                        v-if="diagnosticos.length > 0"
                        class="shrink-0 px-5 pb-3"
                    >
                        <label for="buscar-diagnostico" class="sr-only">
                            Buscar diagnóstico
                        </label>
                        <Entrada
                            id="buscar-diagnostico"
                            v-model="busqueda"
                            type="search"
                            placeholder="Buscar diagnóstico por nombre"
                            autocomplete="off"
                            class="h-9"
                        />
                    </div>

                    <!-- A2b · sector sin diagnósticos -->
                    <div
                        v-if="sector && diagnosticos.length === 0"
                        class="border-t border-linea px-5 py-8 text-center"
                    >
                        <p class="font-semibold">
                            Este sector todavía no tiene diagnósticos
                        </p>
                        <p
                            class="mx-auto mt-1 max-w-md text-sm text-tinta-suave"
                        >
                            Crea el primero o duplica uno de otro sector. Las
                            empresas de {{ sector.nombre }} recibirán solo
                            diagnósticos publicados de este sector.
                        </p>
                        <div class="mt-4 flex flex-wrap justify-center gap-3">
                            <Boton :href="rutas.diagnosticos.crear(sector.id)">
                                + Crear diagnóstico para {{ sector.nombre }}
                            </Boton>
                            <Boton
                                variante="secundario"
                                :href="rutas.diagnosticos.crear(sector.id)"
                            >
                                Duplicar uno de otro sector
                            </Boton>
                        </div>
                    </div>

                    <p
                        v-else-if="filas.length === 0"
                        class="border-t border-linea px-5 py-8 text-center text-sm text-tinta-suave"
                    >
                        <template v-if="diagnosticos.length === 0">
                            Todavía no hay diagnósticos.
                        </template>
                        <template v-else-if="buscando">
                            Ningún diagnóstico se llama «{{ busqueda.trim() }}».
                        </template>
                        <template v-else>
                            No hay diagnósticos con ese estado.
                        </template>
                    </p>

                    <TablaDiagnosticos
                        v-else
                        class="min-h-0"
                        :filas="filas"
                        :mostrar-sector="!sector"
                        @duplicar="elegir($event, 'duplicar')"
                        @archivar="elegir($event, 'archivar')"
                        @eliminar="elegir($event, 'eliminar')"
                        @eliminar-borrador="elegir($event, 'borrador')"
                    />
                </section>

                <EmpresasDelSector
                    v-if="sector"
                    class="shrink-0"
                    :sector="sector"
                    :empresas="empresas"
                    :total="resumen.empresas"
                    :se-puede-eliminar="sePuedeEliminar"
                    @desactivar="modalEstado = true"
                />
            </div>
        </div>
    </div>

    <ModalSector
        v-model:abierto="modalSector"
        :sector="editandoSector ? sector : null"
        :resumen="resumen"
        @reasignar="modalReasignar = true"
        @desactivar="modalEstado = true"
        @eliminar="modalEliminarSector = true"
    />

    <template v-if="sector">
        <ModalReasignarSector
            v-model:abierto="modalReasignar"
            :sector="sector"
            :resumen="resumen"
            :sectores="sectores"
        />
        <ModalEstadoSector
            v-model:abierto="modalEstado"
            :sector="sector"
            :resumen="resumen"
        />
        <ModalEliminarSector
            v-model:abierto="modalEliminarSector"
            :sector="sector"
            :resumen="resumen"
            @reasignar="modalReasignar = true"
            @desactivar="modalEstado = true"
        />
    </template>

    <template v-if="diagnosticoElegido">
        <ModalDuplicarDiagnostico
            v-model:abierto="modalDuplicar"
            :diagnostico="diagnosticoElegido"
            :sectores="sectores"
        />
        <ModalArchivarDiagnostico
            v-model:abierto="modalArchivar"
            :diagnostico="diagnosticoElegido"
        />
        <ModalEliminarDiagnostico
            v-model:abierto="modalEliminar"
            :diagnostico="diagnosticoElegido"
            :que="eliminarQue"
        />
    </template>
</template>
