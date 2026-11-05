<script setup lang="ts">
/**
 * A2 · Diagnósticos de un sector (HU-010), A2·T · Todos los diagnósticos
 * (HU-011) y A2b · Sector sin diagnósticos.
 *
 * Una sola pantalla: con `sector` muestra ese sector; sin `sector` muestra
 * "Todos", agrupados por sector, con orden y filtro por estado.
 * Props completas en docs/14_FRONTEND.md.
 *
 * En pantallas grandes la página no se desplaza: ocupa el alto de la ventana.
 * La tabla de diagnósticos va paginada con las filas que caben; la lista de
 * sectores baja y sube en su espacio. Las empresas del sector se ven en
 * Empresas (A3), con "Ver en Empresas" desde el resumen. La búsqueda filtra por nombre, sin mirar
 * tildes ni mayúsculas.
 */
import { Head } from '@inertiajs/vue3';
import {
    Archive,
    ArrowUpDown,
    FilePlus2,
    FolderOpen,
    Layers,
    Pencil,
    Search,
    SearchX,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Entrada from '@/components/base/Entrada.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import ListaSectores from '@/components/diagnosticos/ListaSectores.vue';
import ModalArchivados from '@/components/diagnosticos/modales/ModalArchivados.vue';
import ModalArchivarDiagnostico from '@/components/diagnosticos/modales/ModalArchivarDiagnostico.vue';
import ModalDuplicarDiagnostico from '@/components/diagnosticos/modales/ModalDuplicarDiagnostico.vue';
import ModalEliminarDiagnostico from '@/components/diagnosticos/modales/ModalEliminarDiagnostico.vue';
import ModalEliminarVarios from '@/components/diagnosticos/modales/ModalEliminarVarios.vue';
import ModalEliminarSector from '@/components/diagnosticos/modales/ModalEliminarSector.vue';
import ModalEstadoSector from '@/components/diagnosticos/modales/ModalEstadoSector.vue';
import ModalReasignarSector from '@/components/diagnosticos/modales/ModalReasignarSector.vue';
import ModalSector from '@/components/diagnosticos/modales/ModalSector.vue';
import ResumenDiagnosticos from '@/components/diagnosticos/ResumenDiagnosticos.vue';
import TablaDiagnosticos from '@/components/diagnosticos/TablaDiagnosticos.vue';
import { usePermisos } from '@/composables/usePermisos';
import { rutas } from '@/lib/rutas';
import { normalizar } from '@/lib/texto';
import { cn } from '@/lib/utils';
import type {
    DiagnosticoArchivado,
    DivisionCiiu,
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
        /** Catálogo CIIU para elegir los subsectores de un sector (DEC-018). */
        divisionesCiiu?: DivisionCiiu[];
        /** Archivados (HU-022): se restauran o se eliminan desde su ventana. */
        archivados?: DiagnosticoArchivado[];
    }>(),
    { sector: null, divisionesCiiu: () => [], archivados: () => [] },
);

const { puede } = usePermisos();

const modalArchivados = ref(false);

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

const esBorrador = (d: FilaDiagnostico) =>
    d.version_publicada === null || d.borrador_pendiente !== null;

const filtros = computed(() => [
    {
        valor: 'todos' as const,
        texto: 'Todos',
        n: props.diagnosticos.length,
        tono: null,
    },
    {
        valor: 'publicado' as const,
        texto: 'Publicados',
        n: props.diagnosticos.filter((d) => d.version_publicada !== null)
            .length,
        tono: 'bg-exito',
    },
    {
        valor: 'borrador' as const,
        texto: 'Borradores',
        n: props.diagnosticos.filter(esBorrador).length,
        tono: 'bg-alerta',
    },
]);

const plural = (n: number, uno: string, varios: string) =>
    `${n} ${n === 1 ? uno : varios}`;

const claseAccion =
    'inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs text-tinta-suave transition hover:bg-lienzo hover:text-tinta';

const filas = computed(() => {
    let lista = [...props.diagnosticos];
    const buscado = normalizar(busqueda.value);

    if (buscado !== '') {
        lista = lista.filter((d) => normalizar(d.nombre).includes(buscado));
    }

    if (filtro.value === 'publicado') {
        lista = lista.filter((d) => d.version_publicada !== null);
    } else if (filtro.value === 'borrador') {
        lista = lista.filter(esBorrador);
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

// --- Eliminar varios a la vez ------------------------------------------------
const seleccion = ref<number[]>([]);
const modalEliminarVarios = ref(false);
const elegidos = computed(() =>
    props.diagnosticos.filter((d) => seleccion.value.includes(d.id)),
);

// Después de eliminar o de cambiar de sector, se quitan los que ya no están.
watch(
    () => props.diagnosticos,
    (lista) => {
        const ids = new Set(
            lista.filter((d) => d.version_publicada === null).map((d) => d.id),
        );
        seleccion.value = seleccion.value.filter((id) => ids.has(id));
    },
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
            <Boton
                v-if="puede('diagnosticos.editar')"
                :href="rutas.diagnosticos.crear(sector?.id)"
            >
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
                    class="flex flex-col rounded-2xl border border-linea bg-white lg:min-h-0 lg:flex-1"
                >
                    <header
                        class="flex flex-wrap items-start justify-between gap-3 px-5 pt-5 pb-3"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                                aria-hidden="true"
                            >
                                <FolderOpen v-if="sector" class="size-5" />
                                <Layers v-else class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <h2
                                    class="text-base leading-tight font-semibold"
                                >
                                    {{
                                        sector
                                            ? `Diagnósticos de ${sector.nombre}`
                                            : 'Todos los diagnósticos'
                                    }}
                                </h2>
                                <p class="mt-0.5 text-xs text-tinta-suave">
                                    <template v-if="sector">
                                        {{
                                            plural(
                                                sector.diagnosticos,
                                                'diagnóstico',
                                                'diagnósticos',
                                            )
                                        }}
                                        ·
                                        {{
                                            plural(
                                                resumen.empresas,
                                                'empresa',
                                                'empresas',
                                            )
                                        }}
                                        <template v-if="!sector.activo">
                                            · ○ Inactivo
                                        </template>
                                    </template>
                                    <template v-else>
                                        {{
                                            plural(
                                                totalDiagnosticos,
                                                'diagnóstico',
                                                'diagnósticos',
                                            )
                                        }}
                                        ·
                                        {{
                                            plural(
                                                sectores.length,
                                                'sector',
                                                'sectores',
                                            )
                                        }}
                                    </template>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-1">
                            <button
                                type="button"
                                :class="claseAccion"
                                @click="modalArchivados = true"
                            >
                                <Archive class="size-3.5" aria-hidden="true" />
                                Archivados
                                <span
                                    class="rounded-full bg-lienzo-oscuro px-1.5 font-mono text-[11px]"
                                >
                                    {{ archivados.length }}
                                </span>
                            </button>
                            <template
                                v-if="sector && puede('diagnosticos.editar')"
                            >
                                <button
                                    type="button"
                                    :class="claseAccion"
                                    @click="editarSector"
                                >
                                    <Pencil
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    Editar sector
                                </button>
                                <button
                                    type="button"
                                    :class="
                                        cn(
                                            claseAccion,
                                            'hover:bg-aviso-suave hover:text-aviso',
                                        )
                                    "
                                    @click="modalEliminarSector = true"
                                >
                                    <Trash2
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    Eliminar sector
                                </button>
                            </template>
                        </div>
                    </header>

                    <div
                        v-if="diagnosticos.length > 0"
                        class="flex shrink-0 flex-wrap items-center gap-2 px-5 pb-4"
                    >
                        <div class="relative min-w-52 flex-1">
                            <label for="buscar-diagnostico" class="sr-only">
                                Buscar diagnóstico
                            </label>
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-tinta-suave"
                                aria-hidden="true"
                            />
                            <Entrada
                                id="buscar-diagnostico"
                                v-model="busqueda"
                                type="search"
                                placeholder="Buscar diagnóstico por nombre"
                                autocomplete="off"
                                class="h-9 pl-9"
                            />
                        </div>

                        <div
                            class="flex flex-wrap items-center gap-1.5"
                            role="group"
                            aria-label="Filtrar por estado"
                        >
                            <button
                                v-for="f in filtros"
                                :key="f.valor"
                                type="button"
                                :aria-pressed="filtro === f.valor"
                                :class="
                                    cn(
                                        'inline-flex h-9 items-center gap-2 rounded-full border px-3 text-xs transition',
                                        filtro === f.valor
                                            ? 'border-marca bg-marca text-white'
                                            : 'border-linea bg-white hover:border-marca/40',
                                    )
                                "
                                @click="filtro = f.valor"
                            >
                                <span
                                    v-if="f.tono"
                                    :class="cn('size-2 rounded-full', f.tono)"
                                    aria-hidden="true"
                                />
                                {{ f.texto }}
                                <span
                                    :class="
                                        cn(
                                            'rounded-full px-1.5 font-mono text-[11px] font-semibold',
                                            filtro === f.valor
                                                ? 'bg-white/20'
                                                : 'bg-lienzo-oscuro',
                                        )
                                    "
                                >
                                    {{ f.n }}
                                </span>
                            </button>
                        </div>

                        <label
                            v-if="!sector"
                            class="flex items-center gap-2 text-xs text-tinta-suave"
                        >
                            <ArrowUpDown class="size-3.5" aria-hidden="true" />
                            <span class="sr-only">Ordenar por</span>
                            <Seleccion
                                v-model="orden"
                                class="h-9 w-auto text-xs"
                            >
                                <option value="sector">
                                    Sector (A–Z), luego estado
                                </option>
                                <option value="edicion">Última edición</option>
                                <option value="nombre">Nombre (A–Z)</option>
                            </Seleccion>
                        </label>
                    </div>

                    <!-- A2b · sector sin diagnósticos -->
                    <div
                        v-if="sector && diagnosticos.length === 0"
                        class="flex flex-1 flex-col items-center justify-center border-t border-linea px-5 py-10 text-center"
                    >
                        <span
                            class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-marca-suave text-marca"
                            aria-hidden="true"
                        >
                            <FilePlus2 class="size-6" />
                        </span>
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
                        <div
                            v-if="puede('diagnosticos.editar')"
                            class="mt-4 flex flex-wrap justify-center gap-3"
                        >
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
                        class="flex flex-1 flex-col items-center justify-center gap-2 border-t border-linea px-5 py-10 text-center text-sm text-tinta-suave"
                    >
                        <SearchX class="size-6" aria-hidden="true" />
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
                        v-model:seleccion="seleccion"
                        class="min-h-0 flex-1"
                        :filas="filas"
                        :mostrar-sector="!sector"
                        @duplicar="elegir($event, 'duplicar')"
                        @archivar="elegir($event, 'archivar')"
                        @eliminar="elegir($event, 'eliminar')"
                        @eliminar-borrador="elegir($event, 'borrador')"
                        @eliminar-seleccion="modalEliminarVarios = true"
                    />
                </section>
            </div>
        </div>
    </div>

    <ModalSector
        v-model:abierto="modalSector"
        :sector="editandoSector ? sector : null"
        :resumen="resumen"
        :divisiones="divisionesCiiu"
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

    <ModalArchivados
        v-model:abierto="modalArchivados"
        :archivados="archivados"
        :mostrar-sector="!sector"
    />

    <ModalEliminarVarios
        v-model:abierto="modalEliminarVarios"
        :diagnosticos="elegidos"
        @eliminados="seleccion = []"
    />

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
