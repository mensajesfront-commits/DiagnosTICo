<script setup lang="ts">
/**
 * A3 · Empresas (HU-031).
 *
 * Lista de empresas con búsqueda por nombre (sin mirar tildes ni
 * mayúsculas) y filtros de sector, nivel, cuenta y estado de la medición
 * pendiente. Cada fila muestra el sector, el último puntaje con su nivel y
 * variación, cuántas mediciones terminó, la medición pendiente y el estado
 * de la cuenta, con "Ver empresa" (A3.1). "Asignar medición" (A3.1b) va
 * arriba, como en el wireframe.
 *
 * En pantallas grandes la página no se desplaza: la tabla va paginada con
 * las filas que caben. Desde A2 se llega con `?sector=` ya filtrado.
 */
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Entrada from '@/components/base/Entrada.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import Paginacion from '@/components/base/Paginacion.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import Variacion from '@/components/empresas/Variacion.vue';
import { usePermisos } from '@/composables/usePermisos';
import { niveles, ordenNiveles } from '@/lib/niveles';
import type { Nivel } from '@/lib/niveles';
import { useFilasQueCaben, usePaginacion } from '@/lib/paginacion';
import { rutas } from '@/lib/rutas';
import { normalizar } from '@/lib/texto';
import type { FilaEmpresa, SectorFiltro } from '@/types/empresas';

const props = defineProps<{
    empresas: FilaEmpresa[];
    sectores: SectorFiltro[];
    sectorInicial: number | null;
}>();

const { puede } = usePermisos();

type FiltroMedicion = '' | 'no_iniciada' | 'en_curso' | 'vencida' | 'ninguna';
type FiltroCuenta = '' | 'activa' | 'desactivada';

const busqueda = ref('');
const filtroSector = ref<number | ''>(props.sectorInicial ?? '');
const filtroNivel = ref<Nivel | ''>('');
const filtroCuenta = ref<FiltroCuenta>('');
const filtroMedicion = ref<FiltroMedicion>('');

const opcionesMedicion: Record<Exclude<FiltroMedicion, ''>, string> = {
    no_iniciada: 'No iniciada',
    en_curso: 'En curso',
    vencida: 'Vencida',
    ninguna: 'Sin medición pendiente',
};

/** "En curso" incluye la enviada que la IA analiza (como en A1). */
function cumpleMedicion(e: FilaEmpresa, filtro: FiltroMedicion): boolean {
    const estado = e.pendiente?.estado;

    switch (filtro) {
        case '':
            return true;
        case 'ninguna':
            return e.pendiente === null;
        case 'en_curso':
            return estado === 'en_curso' || estado === 'enviada';
        default:
            return estado === filtro;
    }
}

const filtradas = computed(() => {
    const buscado = normalizar(busqueda.value);

    return props.empresas.filter(
        (e) =>
            (buscado === '' || normalizar(e.nombre).includes(buscado)) &&
            (filtroSector.value === '' || e.sector_id === filtroSector.value) &&
            (filtroNivel.value === '' || e.nivel === filtroNivel.value) &&
            cumpleMedicion(e, filtroMedicion.value) &&
            (filtroCuenta.value === '' ||
                e.activa === (filtroCuenta.value === 'activa')),
    );
});

const hayFiltros = computed(
    () =>
        busqueda.value !== '' ||
        filtroSector.value !== '' ||
        filtroNivel.value !== '' ||
        filtroMedicion.value !== '' ||
        filtroCuenta.value !== '',
);

function limpiar(): void {
    busqueda.value = '';
    filtroSector.value = '';
    filtroNivel.value = '';
    filtroMedicion.value = '';
    filtroCuenta.value = '';
}

// --- Paginado con las filas que caben -------------------------------------------
const contenedor = ref<HTMLElement | null>(null);
const { porPagina, medir, ajustar } = useFilasQueCaben(contenedor);
const { pagina, paginas, total, desde, visibles } = usePaginacion(
    filtradas,
    porPagina,
);

watch(
    () => filtradas.value.length > 0,
    () => medir(),
);
watch(visibles, ajustar);

const columnas = [
    'Empresa',
    'Sector',
    'Último puntaje',
    'Variación',
    'Mediciones',
    'Medición pendiente',
    'Cuenta',
    '',
];

const registradas = computed(() =>
    props.empresas.length === 1
        ? '1 empresa registrada'
        : `${props.empresas.length} empresas registradas`,
);
const enlace = 'text-marca underline underline-offset-2 hover:text-marca-hover';
</script>

<template>
    <Head title="Empresas" />

    <div class="flex flex-col gap-5 p-6 lg:h-dvh lg:overflow-hidden">
        <EncabezadoPagina titulo="Empresas" :descripcion="registradas">
            <Boton
                v-if="puede('mediciones.asignar')"
                variante="secundario"
                :href="rutas.mediciones.asignar()"
            >
                Asignar medición
            </Boton>
            <Boton
                v-if="puede('empresas.registrar')"
                :href="rutas.empresas.crear()"
            >
                + Registrar empresa
            </Boton>
        </EncabezadoPagina>

        <div
            class="grid shrink-0 gap-3 md:grid-cols-2 xl:grid-cols-[1fr_11rem_11rem_10rem_12rem]"
        >
            <div class="flex flex-col gap-1.5">
                <label for="buscar-empresa" class="text-xs font-medium">
                    Buscar
                </label>
                <Entrada
                    id="buscar-empresa"
                    v-model="busqueda"
                    type="search"
                    placeholder="Nombre de la empresa"
                    autocomplete="off"
                />
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="filtro-sector" class="text-xs font-medium">
                    Sector
                </label>
                <Seleccion id="filtro-sector" v-model="filtroSector">
                    <option value="">Todos los sectores</option>
                    <option v-for="s in sectores" :key="s.id" :value="s.id">
                        {{ s.nombre }}
                    </option>
                </Seleccion>
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="filtro-nivel" class="text-xs font-medium">
                    Nivel
                </label>
                <Seleccion id="filtro-nivel" v-model="filtroNivel">
                    <option value="">Todos los niveles</option>
                    <option v-for="n in ordenNiveles" :key="n" :value="n">
                        {{ niveles[n].nombre }}
                    </option>
                </Seleccion>
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="filtro-cuenta" class="text-xs font-medium">
                    Cuenta
                </label>
                <Seleccion id="filtro-cuenta" v-model="filtroCuenta">
                    <option value="">Todas</option>
                    <option value="activa">Activas</option>
                    <option value="desactivada">Desactivadas</option>
                </Seleccion>
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="filtro-medicion" class="text-xs font-medium">
                    Medición
                </label>
                <Seleccion id="filtro-medicion" v-model="filtroMedicion">
                    <option value="">Cualquier estado</option>
                    <option
                        v-for="(texto, clave) in opcionesMedicion"
                        :key="clave"
                        :value="clave"
                    >
                        {{ texto }}
                    </option>
                </Seleccion>
            </div>
        </div>

        <section
            class="flex flex-col rounded-xl border border-linea bg-white lg:min-h-0 lg:flex-1"
        >
            <div
                v-if="filtradas.length === 0"
                class="flex flex-1 flex-col items-center justify-center gap-3 px-5 py-12 text-center text-sm text-tinta-suave"
            >
                <template v-if="empresas.length === 0">
                    <p>Todavía no hay empresas registradas.</p>
                    <Boton
                        v-if="puede('empresas.registrar')"
                        :href="rutas.empresas.crear()"
                    >
                        + Registrar empresa
                    </Boton>
                </template>
                <template v-else>
                    <p>
                        Ninguna empresa coincide con la búsqueda o los filtros.
                    </p>
                    <Boton
                        v-if="hayFiltros"
                        variante="secundario"
                        tamano="sm"
                        @click="limpiar"
                    >
                        Quitar filtros
                    </Boton>
                </template>
            </div>

            <template v-else>
                <div
                    ref="contenedor"
                    class="min-h-0 flex-1 overflow-x-auto overflow-y-hidden"
                >
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr
                                class="border-b border-linea bg-[#f9f8f4] text-left"
                            >
                                <th
                                    v-for="columna in columnas"
                                    :key="columna"
                                    scope="col"
                                    :class="[
                                        'px-4 py-2.5 text-xs font-normal whitespace-nowrap text-tinta-suave',
                                        columna === '' && 'text-right',
                                    ]"
                                >
                                    {{ columna }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="e in visibles"
                                :key="e.id"
                                class="border-b border-linea last:border-b-0"
                            >
                                <td class="px-4 py-2.5">
                                    <Link
                                        :href="rutas.empresas.ver(e.id)"
                                        class="font-medium hover:text-marca hover:underline"
                                    >
                                        {{ e.nombre }}
                                    </Link>
                                </td>
                                <td class="px-4 py-2.5 text-tinta-suave">
                                    {{ e.sector ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="flex items-center gap-2">
                                        <strong class="w-6 font-mono text-base">
                                            {{ e.puntaje ?? '–' }}
                                        </strong>
                                        <EtiquetaNivel :nivel="e.nivel" />
                                    </span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <Variacion :valor="e.variacion" />
                                </td>
                                <td class="px-4 py-2.5 font-mono">
                                    {{ e.mediciones }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        v-if="e.pendiente"
                                        class="flex items-center gap-2 whitespace-nowrap"
                                    >
                                        <span class="font-mono text-xs">
                                            M{{ e.pendiente.numero }}
                                        </span>
                                        <EtiquetaEstado
                                            :estado="e.pendiente.estado"
                                            :avance="
                                                e.pendiente.avance ?? undefined
                                            "
                                        />
                                        <span
                                            v-if="e.pendiente.solicitud"
                                            class="text-xs font-medium text-aviso"
                                            title="La empresa pidió una medición nueva (HU-083)"
                                            >Pidió una nueva</span
                                        >
                                    </span>
                                    <span v-else class="text-tinta-suave">
                                        Sin medición pendiente
                                    </span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span v-if="e.activa">Activa</span>
                                    <Etiqueta v-else>⊘ Desactivada</Etiqueta>
                                </td>
                                <td
                                    class="px-4 py-2.5 text-right whitespace-nowrap"
                                >
                                    <Link
                                        :href="rutas.empresas.ver(e.id)"
                                        :class="enlace"
                                    >
                                        Ver empresa
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Paginacion
                    v-model:pagina="pagina"
                    class="shrink-0"
                    :desde="desde"
                    :cantidad="visibles.length"
                    :total="total"
                    :paginas="paginas"
                />
            </template>
        </section>
    </div>
</template>
