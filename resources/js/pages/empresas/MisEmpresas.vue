<script setup lang="ts">
/**
 * C2 · Mis empresas del Consultor (HU-086, RN-030).
 *
 * Una tarjeta por empresa vinculada: el último puntaje y su nivel, la
 * variación (RN-020), la línea de evolución, la última medición y la
 * pendiente con su fecha límite. "Ver empresa" abre la ficha (A3.1),
 * "Historial", A3.4 y "Último resultado", A3.3.
 *
 * Arriba: filtros (todas, con medición pendiente, bajaron, sin resultado),
 * el orden y, con 7 empresas o más, un buscador. Es lo que ve el consultor
 * en /empresas (no tiene la lista A3).
 *
 * Los datos llegan calculados del servidor (DatosEmpresas::lista con los
 * extras del consultor); aquí solo se filtran y ordenan.
 */
import { Head, Link } from '@inertiajs/vue3';
import {
    Building2,
    CalendarClock,
    ChartLine,
    Eye,
    FileText,
    History,
    Minus,
    Search,
    TrendingDown,
    TrendingUp,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Entrada from '@/components/base/Entrada.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import { diaYMes, fechaLocal } from '@/lib/fechas';
import { niveles } from '@/lib/niveles';
import { rutas } from '@/lib/rutas';
import { normalizar, plural } from '@/lib/texto';
import { cn } from '@/lib/utils';
import type { FilaEmpresa } from '@/types/empresas';

type TarjetaEmpresa = FilaEmpresa & {
    /** Puntajes de las mediciones terminadas, de la primera a la última. */
    evolucion: number[];
    ultima: { id: number; numero: number; fecha: string } | null;
    /** Fecha límite de la medición pendiente ("AAAA-MM-DD"). */
    vence: string | null;
    vencida: boolean;
};

const props = defineProps<{ empresas: TarjetaEmpresa[] }>();

// --- Filtros, orden y búsqueda ------------------------------------------------

type Filtro = 'todas' | 'pendientes' | 'bajaron' | 'sin_resultado';

const filtros: Record<Filtro, (e: TarjetaEmpresa) => boolean> = {
    todas: () => true,
    pendientes: (e) => e.pendiente !== null,
    bajaron: (e) => (e.variacion ?? 0) < 0,
    sin_resultado: (e) => e.puntaje === null,
};

const filtro = ref<Filtro>('todas');

const opcionesFiltro = computed(() =>
    (
        [
            ['todas', 'Todas'],
            ['pendientes', 'Con medición pendiente'],
            ['bajaron', 'Bajaron'],
            ['sin_resultado', 'Sin resultado'],
        ] as [Filtro, string][]
    )
        .map(([valor, etiqueta]) => ({
            valor,
            etiqueta,
            cantidad: props.empresas.filter(filtros[valor]).length,
        }))
        // Solo los filtros que tienen algo (y "Todas" siempre).
        .filter((o) => o.valor === 'todas' || o.cantidad > 0),
);

type Orden = 'nombre' | 'puntaje_alto' | 'puntaje_bajo' | 'variacion';

const orden = ref<Orden>('nombre');

const ordenes: Record<Orden, (a: TarjetaEmpresa, b: TarjetaEmpresa) => number> =
    {
        nombre: (a, b) => a.nombre.localeCompare(b.nombre, 'es'),
        // Sin resultado al final en los dos sentidos.
        puntaje_alto: (a, b) => (b.puntaje ?? -1) - (a.puntaje ?? -1),
        puntaje_bajo: (a, b) => (a.puntaje ?? 101) - (b.puntaje ?? 101),
        variacion: (a, b) => (a.variacion ?? 0) - (b.variacion ?? 0),
    };

/** Con 7 empresas o más, un buscador por nombre o sector. */
const MINIMO_BUSCADOR = 7;
const busqueda = ref('');

const visibles = computed(() => {
    const texto = normalizar(busqueda.value.trim());

    return props.empresas
        .filter(filtros[filtro.value])
        .filter(
            (e) =>
                texto === '' ||
                normalizar(`${e.nombre} ${e.sector ?? ''}`).includes(texto),
        )
        .sort(ordenes[orden.value]);
});

function limpiar(): void {
    filtro.value = 'todas';
    busqueda.value = '';
}

// --- Tarjeta -----------------------------------------------------------------------

function fecha(iso: string): string {
    return diaYMes(fechaLocal(iso));
}

/**
 * Línea de evolución en un SVG de 120 × 36. La escala va del puntaje más bajo
 * al más alto (con 10 puntos de margen) para que el cambio se vea; el valor
 * exacto está en el anillo y en el historial.
 */
function linea(puntajes: number[]): { x: number; y: number }[] {
    const n = puntajes.length;
    const min = Math.max(0, Math.min(...puntajes) - 10);
    const max = Math.min(100, Math.max(...puntajes) + 10);
    const rango = Math.max(max - min, 1);

    return puntajes.map((p, i) => ({
        x: n === 1 ? 60 : 4 + (i * 112) / (n - 1),
        y: 32 - ((p - min) / rango) * 28,
    }));
}
</script>

<template>
    <Head title="Mis empresas" />

    <div class="flex flex-col gap-5 p-4 sm:p-6">
        <header class="flex items-start gap-3">
            <span
                class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                aria-hidden="true"
            >
                <Building2 class="size-5" />
            </span>
            <div>
                <h1 class="text-2xl font-semibold">Mis empresas</h1>
                <p class="mt-0.5 text-sm text-tinta-suave">
                    {{
                        plural(
                            empresas.length,
                            'empresa vinculada',
                            'empresas vinculadas',
                        )
                    }}. Abre una para ver su historial, sus resultados y el PDF.
                </p>
            </div>
        </header>

        <template v-if="empresas.length > 0">
            <!-- Filtros, orden y búsqueda -->
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                    <SelectorCompacto
                        v-model="filtro"
                        :opciones="opcionesFiltro"
                        etiqueta-accesible="Filtrar empresas"
                        class="whitespace-nowrap"
                    />
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div
                        v-if="empresas.length >= MINIMO_BUSCADOR"
                        class="relative sm:w-64"
                    >
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-tinta-suave"
                            aria-hidden="true"
                        />
                        <Entrada
                            v-model="busqueda"
                            type="search"
                            placeholder="Buscar por nombre o sector"
                            aria-label="Buscar empresa"
                            class="pl-9"
                        />
                    </div>
                    <label
                        class="flex items-center gap-2 text-sm text-tinta-suave"
                    >
                        <span class="shrink-0">Ordenar por</span>
                        <Seleccion v-model="orden" class="sm:w-48">
                            <option value="nombre">Nombre</option>
                            <option value="puntaje_alto">
                                Puntaje más alto
                            </option>
                            <option value="puntaje_bajo">
                                Puntaje más bajo
                            </option>
                            <option value="variacion">Bajaron primero</option>
                        </Seleccion>
                    </label>
                </div>
            </div>

            <div
                v-if="visibles.length === 0"
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-linea-fuerte bg-white px-5 py-10 text-center"
                role="status"
            >
                <Search class="size-8 text-marca/40" aria-hidden="true" />
                <p class="font-semibold">Ninguna empresa coincide</p>
                <button
                    type="button"
                    class="rounded-md text-sm font-medium text-marca hover:text-marca-hover"
                    @click="limpiar"
                >
                    Quitar filtros
                </button>
            </div>

            <ul
                v-else
                class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3"
                aria-label="Empresas vinculadas"
            >
                <li
                    v-for="e in visibles"
                    :key="e.id"
                    :class="
                        cn(
                            'flex flex-col overflow-hidden rounded-2xl border border-linea bg-white transition-shadow hover:shadow-sm',
                            !e.activa && 'opacity-80',
                        )
                    "
                >
                    <!-- Franja del color del nivel -->
                    <span
                        :class="cn('h-1 w-full', niveles[e.nivel].barra)"
                        aria-hidden="true"
                    />

                    <div class="flex flex-1 flex-col gap-4 p-4 sm:p-5">
                        <!-- Encabezado: anillo, nombre, nivel y variación -->
                        <div class="flex items-start gap-4">
                            <AnilloPuntaje
                                v-if="e.puntaje !== null"
                                :puntaje="e.puntaje"
                                :nivel="e.nivel"
                                :tamano="68"
                            />
                            <span
                                v-else
                                class="flex size-[68px] shrink-0 items-center justify-center rounded-full border-4 border-dashed border-linea text-xs text-tinta-suave"
                                aria-hidden="true"
                                >—</span
                            >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start gap-2">
                                    <h2
                                        class="min-w-0 flex-1 truncate text-base font-semibold"
                                        :title="e.nombre"
                                    >
                                        {{ e.nombre }}
                                    </h2>
                                    <span
                                        v-if="!e.activa"
                                        class="shrink-0 rounded-full bg-lienzo-oscuro px-2 py-0.5 text-xs text-tinta-suave"
                                        >Desactivada</span
                                    >
                                </div>
                                <p class="truncate text-sm text-tinta-suave">
                                    {{ e.sector ?? 'Sin sector' }}
                                </p>
                                <div
                                    class="mt-2 flex flex-wrap items-center gap-1.5 text-xs"
                                >
                                    <EtiquetaNivel :nivel="e.nivel" />
                                    <span
                                        v-if="e.variacion !== null"
                                        :class="
                                            cn(
                                                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-medium',
                                                e.variacion > 0
                                                    ? 'bg-exito-suave text-exito'
                                                    : e.variacion < 0
                                                      ? 'bg-aviso-suave text-aviso'
                                                      : 'bg-lienzo text-tinta-suave',
                                            )
                                        "
                                        :title="`Frente a la medición anterior`"
                                    >
                                        <TrendingUp
                                            v-if="e.variacion > 0"
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        <TrendingDown
                                            v-else-if="e.variacion < 0"
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        <Minus
                                            v-else
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        {{ e.variacion > 0 ? '+' : ''
                                        }}{{ e.variacion }} pts
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Evolución y última medición -->
                        <dl
                            class="grid grid-cols-2 gap-3 rounded-xl bg-lienzo/60 px-3 py-2.5 text-xs"
                        >
                            <div class="min-w-0">
                                <dt class="text-tinta-suave">Evolución</dt>
                                <dd class="mt-1">
                                    <svg
                                        v-if="e.evolucion.length >= 2"
                                        viewBox="0 0 120 36"
                                        class="h-9 w-full max-w-36"
                                        role="img"
                                        :aria-label="`Puntajes: ${e.evolucion.join(', ')}`"
                                    >
                                        <polyline
                                            :points="
                                                linea(e.evolucion)
                                                    .map((q) => `${q.x},${q.y}`)
                                                    .join(' ')
                                            "
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                            stroke-linejoin="round"
                                            class="text-tinta/70"
                                        />
                                        <circle
                                            v-for="(q, i) in linea(e.evolucion)"
                                            :key="i"
                                            :cx="q.x"
                                            :cy="q.y"
                                            :r="
                                                i === e.evolucion.length - 1
                                                    ? 3.5
                                                    : 2
                                            "
                                            :fill="
                                                i === e.evolucion.length - 1
                                                    ? niveles[e.nivel].color
                                                    : 'currentColor'
                                            "
                                            class="text-tinta/70"
                                        />
                                    </svg>
                                    <span
                                        v-else
                                        class="flex h-9 items-center gap-1.5 text-tinta-suave"
                                    >
                                        <ChartLine
                                            class="size-3.5"
                                            aria-hidden="true"
                                        />
                                        {{
                                            e.evolucion.length === 1
                                                ? 'Desde la 2.ª medición'
                                                : 'Sin mediciones aún'
                                        }}
                                    </span>
                                </dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-tinta-suave">
                                    Última medición
                                </dt>
                                <dd
                                    v-if="e.ultima"
                                    class="mt-1 flex h-9 flex-col justify-center"
                                >
                                    <span class="font-medium text-tinta">
                                        Medición {{ e.ultima.numero }}
                                    </span>
                                    <span class="text-tinta-suave">
                                        {{ fecha(e.ultima.fecha) }} ·
                                        {{
                                            plural(
                                                e.mediciones,
                                                'terminada',
                                                'terminadas',
                                            )
                                        }}
                                    </span>
                                </dd>
                                <dd
                                    v-else
                                    class="mt-1 flex h-9 items-center text-tinta-suave"
                                >
                                    Ninguna terminada
                                </dd>
                            </div>
                        </dl>

                        <!-- Medición pendiente -->
                        <p
                            v-if="e.pendiente"
                            :class="
                                cn(
                                    'flex flex-wrap items-center gap-x-2 gap-y-1 rounded-xl border px-3 py-2 text-xs',
                                    e.vencida
                                        ? 'border-aviso/25 bg-aviso-suave/60'
                                        : 'border-linea',
                                )
                            "
                        >
                            <span class="font-medium">
                                Medición {{ e.pendiente.numero }}
                            </span>
                            <EtiquetaEstado
                                :estado="e.pendiente.estado"
                                :avance="e.pendiente.avance ?? undefined"
                            />
                            <span
                                v-if="e.vence"
                                :class="
                                    cn(
                                        'ml-auto inline-flex items-center gap-1',
                                        e.vencida
                                            ? 'font-medium text-aviso'
                                            : 'text-tinta-suave',
                                    )
                                "
                            >
                                <CalendarClock
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                {{ e.vencida ? 'Venció' : 'Vence' }}
                                {{ fecha(e.vence) }}
                            </span>
                        </p>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-2 border-t border-linea bg-lienzo/40 px-4 py-3 sm:px-5"
                    >
                        <Boton tamano="sm" :href="rutas.empresas.ver(e.id)">
                            <Eye class="size-3.5" aria-hidden="true" />
                            Ver empresa
                        </Boton>
                        <Boton
                            v-if="e.mediciones > 0"
                            variante="secundario"
                            tamano="sm"
                            :href="rutas.historial.empresa(e.id)"
                        >
                            <History class="size-3.5" aria-hidden="true" />
                            Historial
                        </Boton>
                        <Link
                            v-if="e.ultima"
                            :href="rutas.resultados.ver(e.ultima.id)"
                            class="ml-auto inline-flex items-center gap-1 rounded-md text-xs font-medium text-marca hover:text-marca-hover"
                        >
                            <FileText class="size-3.5" aria-hidden="true" />
                            Último resultado
                        </Link>
                    </div>
                </li>
            </ul>
        </template>

        <div
            v-else
            class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-linea-fuerte bg-white px-5 py-12 text-center"
        >
            <span
                class="flex size-11 items-center justify-center rounded-xl bg-marca-suave text-marca"
                aria-hidden="true"
            >
                <Building2 class="size-5" />
            </span>
            <p class="font-semibold">Todavía no tienes empresas</p>
            <p class="max-w-sm text-sm text-tinta-suave">
                El equipo de NuevasTIC te las vincula. Cuando lo haga, las verás
                aquí.
            </p>
            <Link
                href="/mi-perfil"
                class="mt-1 text-sm font-medium text-marca hover:text-marca-hover"
            >
                Mientras tanto, completa tu perfil
            </Link>
        </div>
    </div>
</template>
