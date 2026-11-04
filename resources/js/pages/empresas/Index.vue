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
 * Arriba, accesos rápidos con su conteo (Todas, En curso, Vencidas, Sin
 * diagnóstico, Desactivadas) que aplican el filtro con un toque. Cada fila
 * lleva las iniciales, el sector bajo el nombre, el puntaje en un anillo,
 * la variación en una píldora y la cuenta con un punto de color.
 *
 * En pantallas grandes la página no se desplaza: la tabla va paginada con
 * las filas que caben. Desde A2 se llega con `?sector=` ya filtrado.
 */
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Entrada from '@/components/base/Entrada.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import Paginacion from '@/components/base/Paginacion.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import { usePermisos } from '@/composables/usePermisos';
import { niveles, ordenNiveles } from '@/lib/niveles';
import type { Nivel } from '@/lib/niveles';
import { useFilasQueCaben, usePaginacion } from '@/lib/paginacion';
import { rutas } from '@/lib/rutas';
import { normalizar } from '@/lib/texto';
import { cn } from '@/lib/utils';
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

// --- Accesos rápidos: cada uno aplica un filtro --------------------------------
type Rapido = 'todas' | 'en_curso' | 'vencida' | 'sin' | 'desactivada';

const rapidos = computed(() => {
    const e = props.empresas;

    return [
        {
            clave: 'todas' as const,
            texto: 'Todas',
            n: e.length,
            tono: 'bg-marca',
        },
        {
            clave: 'en_curso' as const,
            texto: 'En curso',
            n: e.filter((x) => cumpleMedicion(x, 'en_curso')).length,
            tono: 'bg-nivel-camino',
        },
        {
            clave: 'vencida' as const,
            texto: 'Vencidas',
            n: e.filter((x) => cumpleMedicion(x, 'vencida')).length,
            tono: 'bg-nivel-critico',
        },
        {
            clave: 'sin' as const,
            texto: 'Sin diagnóstico',
            n: e.filter((x) => x.nivel === 'sin').length,
            tono: 'bg-nivel-sin',
        },
        {
            clave: 'desactivada' as const,
            texto: 'Desactivadas',
            n: e.filter((x) => !x.activa).length,
            tono: 'bg-tinta-suave',
        },
    ];
});

const rapidoActivo = computed<Rapido | null>(() => {
    const solo = (clave: 'medicion' | 'nivel' | 'cuenta') =>
        busqueda.value === '' &&
        filtroSector.value === '' &&
        (clave === 'medicion' || filtroMedicion.value === '') &&
        (clave === 'nivel' || filtroNivel.value === '') &&
        (clave === 'cuenta' || filtroCuenta.value === '');

    if (!hayFiltros.value) {
        return 'todas';
    }

    if (solo('medicion') && filtroMedicion.value === 'en_curso') {
        return 'en_curso';
    }

    if (solo('medicion') && filtroMedicion.value === 'vencida') {
        return 'vencida';
    }

    if (solo('nivel') && filtroNivel.value === 'sin') {
        return 'sin';
    }

    if (solo('cuenta') && filtroCuenta.value === 'desactivada') {
        return 'desactivada';
    }

    return null;
});

function aplicarRapido(clave: Rapido): void {
    limpiar();

    if (clave === 'en_curso' || clave === 'vencida') {
        filtroMedicion.value = clave;
    } else if (clave === 'sin') {
        filtroNivel.value = 'sin';
    } else if (clave === 'desactivada') {
        filtroCuenta.value = 'desactivada';
    }
}

/** "Panadería El Trigal" → "PE". */
const iniciales = (nombre: string) =>
    nombre
        .split(/\s+/)
        .filter((p) => p.length > 2 || /^[A-ZÁÉÍÓÚÑ]/.test(p))
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join('') || nombre.charAt(0).toUpperCase();
</script>

<template>
    <Head title="Empresas" />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
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

        <!-- Accesos rápidos -->
        <div
            class="flex shrink-0 flex-wrap gap-2"
            role="group"
            aria-label="Filtros rápidos"
        >
            <button
                v-for="r in rapidos"
                :key="r.clave"
                type="button"
                :aria-pressed="rapidoActivo === r.clave"
                :class="
                    cn(
                        'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm transition',
                        rapidoActivo === r.clave
                            ? 'border-marca bg-marca text-white'
                            : 'border-linea bg-white hover:border-marca/40',
                    )
                "
                @click="aplicarRapido(r.clave)"
            >
                <span
                    v-if="r.clave !== 'todas'"
                    :class="cn('size-2 rounded-full', r.tono)"
                    aria-hidden="true"
                />
                {{ r.texto }}
                <span
                    :class="
                        cn(
                            'rounded-full px-1.5 font-mono text-xs font-semibold',
                            rapidoActivo === r.clave
                                ? 'bg-white/20'
                                : 'bg-lienzo-oscuro text-tinta-suave',
                        )
                    "
                    >{{ r.n }}</span
                >
            </button>
        </div>

        <!-- Búsqueda y filtros -->
        <div class="shrink-0 rounded-2xl border border-linea bg-white p-4">
            <div
                class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1fr_11rem_11rem_10rem_12rem]"
            >
                <div class="flex flex-col gap-1.5">
                    <label for="buscar-empresa" class="text-xs font-medium">
                        Buscar
                    </label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-tinta-suave"
                            aria-hidden="true"
                        />
                        <Entrada
                            id="buscar-empresa"
                            v-model="busqueda"
                            type="search"
                            class="pl-9"
                            placeholder="Nombre de la empresa"
                            autocomplete="off"
                        />
                    </div>
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
            <p
                v-if="hayFiltros"
                class="mt-3 flex flex-wrap items-center gap-3 text-sm text-tinta-suave"
                aria-live="polite"
            >
                {{ filtradas.length }} de {{ empresas.length }}
                {{ empresas.length === 1 ? 'empresa' : 'empresas' }}
                <button
                    type="button"
                    class="font-medium text-marca underline underline-offset-2 hover:text-marca-hover"
                    @click="limpiar"
                >
                    Quitar filtros
                </button>
            </p>
        </div>

        <section
            class="flex flex-col overflow-hidden rounded-2xl border border-linea bg-white lg:min-h-0 lg:flex-1"
        >
            <div
                v-if="filtradas.length === 0"
                class="flex flex-1 flex-col items-center justify-center gap-3 px-5 py-12 text-center text-sm text-tinta-suave"
            >
                <Search class="size-10 text-marca/40" aria-hidden="true" />
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
                                class="border-b border-linea bg-lienzo/60 text-left"
                            >
                                <th
                                    v-for="columna in columnas"
                                    :key="columna"
                                    scope="col"
                                    :class="[
                                        'px-4 py-2.5 text-xs font-medium whitespace-nowrap text-tinta-suave',
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
                                :class="
                                    cn(
                                        'border-b border-linea transition-colors last:border-b-0 hover:bg-lienzo/60',
                                        !e.activa && 'text-tinta-suave',
                                    )
                                "
                            >
                                <td class="px-4 py-2.5">
                                    <span class="flex items-center gap-3">
                                        <span
                                            :class="
                                                cn(
                                                    'flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                                    e.activa
                                                        ? 'bg-marca-suave text-marca'
                                                        : 'bg-lienzo-oscuro text-tinta-suave',
                                                )
                                            "
                                            aria-hidden="true"
                                            >{{ iniciales(e.nombre) }}</span
                                        >
                                        <span class="min-w-0">
                                            <Link
                                                :href="rutas.empresas.ver(e.id)"
                                                class="block font-semibold whitespace-nowrap text-tinta hover:text-marca hover:underline"
                                            >
                                                {{ e.nombre }}
                                            </Link>
                                            <span
                                                class="block text-xs whitespace-nowrap text-tinta-suave"
                                                >{{
                                                    e.sector ?? 'Sin sector'
                                                }}</span
                                            >
                                        </span>
                                    </span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="flex items-center gap-2.5">
                                        <AnilloPuntaje
                                            v-if="
                                                e.puntaje !== null &&
                                                e.nivel !== 'sin'
                                            "
                                            :puntaje="e.puntaje"
                                            :nivel="e.nivel"
                                            :tamano="40"
                                        />
                                        <span
                                            v-else
                                            class="flex size-10 items-center justify-center rounded-full border-2 border-dashed border-linea-fuerte text-tinta-suave"
                                            aria-hidden="true"
                                            >–</span
                                        >
                                        <EtiquetaNivel :nivel="e.nivel" />
                                    </span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        v-if="e.variacion !== null"
                                        :class="
                                            cn(
                                                'rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                                                e.variacion > 0
                                                    ? 'bg-nivel-sigue-suave text-nivel-sigue-texto'
                                                    : e.variacion < 0
                                                      ? 'bg-nivel-critico-suave text-nivel-critico-texto'
                                                      : 'bg-lienzo-oscuro text-tinta-suave',
                                            )
                                        "
                                        >{{
                                            e.variacion > 0
                                                ? `▲ +${e.variacion}`
                                                : e.variacion < 0
                                                  ? `▼ ${e.variacion}`
                                                  : '= 0'
                                        }}</span
                                    >
                                    <span v-else class="text-tinta-suave"
                                        >—</span
                                    >
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
                                    <span
                                        v-else
                                        class="text-xs whitespace-nowrap text-tinta-suave"
                                    >
                                        Sin medición pendiente
                                    </span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="inline-flex items-center gap-1.5 whitespace-nowrap"
                                    >
                                        <span
                                            :class="
                                                cn(
                                                    'size-2 rounded-full',
                                                    e.activa
                                                        ? 'bg-nivel-sigue'
                                                        : 'bg-linea-fuerte',
                                                )
                                            "
                                            aria-hidden="true"
                                        />
                                        {{
                                            e.activa ? 'Activa' : 'Desactivada'
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <Link
                                        :href="rutas.empresas.ver(e.id)"
                                        class="inline-flex items-center gap-0.5 rounded-md px-2 py-1 font-medium whitespace-nowrap text-marca hover:bg-marca-suave"
                                    >
                                        Ver
                                        <ChevronRight
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                        <span class="sr-only">{{
                                            e.nombre
                                        }}</span>
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
