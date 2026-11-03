<script setup lang="ts">
/**
 * C1 · Inicio del Consultor (HU-086, RN-030).
 *
 * Solo sus empresas vinculadas: cuántas son, cuántas tienen resultado y
 * cuántas tienen una medición en curso; y una tarjeta por empresa con el
 * último puntaje, el nivel, la variación (RN-020) y la medición pendiente.
 * "Ver empresa" abre la ficha (A3.1) e "Historial", A3.4. Solo mira: no
 * responde ni asigna (los botones de la ficha dependen de los permisos).
 *
 * "Qué atender" (HU-086 CA-005) pone arriba lo que pide su atención: una
 * medición vencida, un puntaje que bajó, una medición sin empezar hace una
 * semana o un resultado nuevo. Con 7 empresas o más aparece un buscador.
 *
 * Los datos llegan calculados del servidor (DatosEmpresas::lista y
 * DatosInicioConsultor::avisos).
 */
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BellRing,
    Building2,
    ChartLine,
    ChevronRight,
    ClipboardList,
    Clock,
    Eye,
    History,
    Minus,
    Search,
    Sparkles,
    TrendingDown,
    TrendingUp,
    TriangleAlert,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import Entrada from '@/components/base/Entrada.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import { diaYMes, fechaLocal } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';
import { normalizar, plural } from '@/lib/texto';
import { cn } from '@/lib/utils';
import type { FilaEmpresa } from '@/types/empresas';

type TipoAviso = 'vencida' | 'bajo' | 'sin_empezar' | 'nuevo';

type Aviso = {
    tipo: TipoAviso;
    empresa: { id: number; nombre: string };
    medicion: number;
    numero: number;
    fecha: string | null;
    puntos: number | null;
    puntaje: number | null;
    nivel: string | null;
    dias: number | null;
};

const props = defineProps<{ empresas: FilaEmpresa[]; avisos: Aviso[] }>();

/** Cuántos avisos se ven antes de "Ver todos". */
const AVISOS_VISIBLES = 4;
const verTodos = ref(false);
const avisosVisibles = computed(() =>
    verTodos.value ? props.avisos : props.avisos.slice(0, AVISOS_VISIBLES),
);

const ESTILO_AVISO: Record<
    TipoAviso,
    { icono: typeof Clock; caja: string; etiqueta: string }
> = {
    vencida: {
        icono: TriangleAlert,
        caja: 'bg-aviso-suave text-aviso',
        etiqueta: 'Vencida',
    },
    bajo: {
        icono: TrendingDown,
        caja: 'bg-alerta-suave text-alerta',
        etiqueta: 'Bajó',
    },
    sin_empezar: {
        icono: Clock,
        caja: 'bg-lienzo text-tinta-suave',
        etiqueta: 'Sin empezar',
    },
    nuevo: {
        icono: Sparkles,
        caja: 'bg-exito-suave text-exito',
        etiqueta: 'Nuevo',
    },
};

function fecha(a: Aviso): string {
    return a.fecha ? diaYMes(fechaLocal(a.fecha)) : '';
}

function textoAviso(a: Aviso): string {
    switch (a.tipo) {
        case 'vencida':
            return `La medición ${a.numero} venció el ${fecha(a)} sin enviarse.`;
        case 'bajo':
            return `Bajó ${plural(a.puntos ?? 0, 'punto', 'puntos')} en la medición ${a.numero}: quedó en ${a.puntaje}.`;
        case 'sin_empezar':
            return `La medición ${a.numero} lleva ${plural(a.dias ?? 0, 'día', 'días')} sin empezar.`;
        case 'nuevo':
            return `Resultado nuevo de la medición ${a.numero} (${fecha(a)}): ${a.puntaje} puntos.`;
    }
}

/** El resultado para "bajó" y "nuevo"; la ficha para lo pendiente. */
function enlaceAviso(a: Aviso): string {
    return a.tipo === 'bajo' || a.tipo === 'nuevo'
        ? rutas.resultados.ver(a.medicion)
        : rutas.empresas.ver(a.empresa.id);
}

/** Con 7 empresas o más, un buscador por nombre o sector. */
const MINIMO_BUSCADOR = 7;
const busqueda = ref('');
const empresasFiltradas = computed(() => {
    const texto = normalizar(busqueda.value.trim());

    if (texto === '') {
        return props.empresas;
    }

    return props.empresas.filter((e) =>
        normalizar(`${e.nombre} ${e.sector ?? ''}`).includes(texto),
    );
});

const page = usePage();
const nombre = computed(
    () => page.props.auth.user?.name?.split(' ')[0] ?? 'consultor',
);

const conResultado = computed(
    () => props.empresas.filter((e) => e.puntaje !== null).length,
);
const enCurso = computed(
    () => props.empresas.filter((e) => e.pendiente !== null).length,
);

const resumen = computed(() => [
    {
        icono: Building2,
        valor: props.empresas.length,
        texto: props.empresas.length === 1 ? 'empresa' : 'empresas',
    },
    {
        icono: ChartLine,
        valor: conResultado.value,
        texto: 'con resultado',
    },
    {
        icono: ClipboardList,
        valor: enCurso.value,
        texto:
            enCurso.value === 1
                ? 'medición pendiente'
                : 'mediciones pendientes',
    },
]);
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-col gap-5 p-4 sm:p-6">
        <header class="flex items-start gap-3">
            <span
                class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                aria-hidden="true"
            >
                <Eye class="size-5" />
            </span>
            <div>
                <h1 class="text-2xl font-semibold">Hola, {{ nombre }}</h1>
                <p class="mt-0.5 text-sm text-tinta-suave">
                    Estas son las empresas que acompañas. Puedes ver sus
                    resultados, su evolución y el informe en PDF.
                </p>
            </div>
        </header>

        <dl class="grid grid-cols-3 gap-3">
            <div
                v-for="r in resumen"
                :key="r.texto"
                class="flex flex-col-reverse gap-1 rounded-2xl border border-linea bg-white p-4 sm:flex-row-reverse sm:items-center sm:justify-end sm:gap-3"
            >
                <dt class="text-xs text-tinta-suave sm:text-sm">
                    {{ r.texto }}
                </dt>
                <dd class="flex items-center gap-2">
                    <span
                        class="hidden size-9 items-center justify-center rounded-xl bg-marca-suave text-marca sm:flex"
                        aria-hidden="true"
                    >
                        <component :is="r.icono" class="size-4.5" />
                    </span>
                    <span class="font-mono text-2xl font-semibold">{{
                        r.valor
                    }}</span>
                </dd>
            </div>
        </dl>

        <section
            v-if="avisos.length > 0"
            aria-labelledby="titulo-que-atender"
            class="rounded-2xl border border-linea bg-white"
        >
            <header
                class="flex items-center gap-2 border-b border-linea px-4 py-3 sm:px-5"
            >
                <BellRing class="size-4 text-marca" aria-hidden="true" />
                <h2 id="titulo-que-atender" class="font-semibold">
                    Qué atender
                </h2>
                <span
                    class="rounded-full bg-marca-suave px-2 py-0.5 font-mono text-xs font-semibold text-marca"
                    >{{ avisos.length }}</span
                >
            </header>
            <ul class="divide-y divide-linea">
                <li
                    v-for="a in avisosVisibles"
                    :key="`${a.tipo}-${a.medicion}`"
                >
                    <Link
                        :href="enlaceAviso(a)"
                        class="group flex items-center gap-3 px-4 py-3 hover:bg-lienzo/60 focus-visible:bg-lienzo/60 focus-visible:outline-none sm:px-5"
                    >
                        <span
                            :class="
                                cn(
                                    'flex size-9 shrink-0 items-center justify-center rounded-xl',
                                    ESTILO_AVISO[a.tipo].caja,
                                )
                            "
                            aria-hidden="true"
                        >
                            <component
                                :is="ESTILO_AVISO[a.tipo].icono"
                                class="size-4"
                            />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="flex flex-wrap items-center gap-x-2 gap-y-0.5"
                            >
                                <span class="truncate font-medium">{{
                                    a.empresa.nombre
                                }}</span>
                                <span
                                    :class="
                                        cn(
                                            'rounded-full px-2 py-0.5 text-xs font-medium',
                                            ESTILO_AVISO[a.tipo].caja,
                                        )
                                    "
                                    >{{ ESTILO_AVISO[a.tipo].etiqueta }}</span
                                >
                            </span>
                            <span class="mt-0.5 block text-sm text-tinta-suave">
                                {{ textoAviso(a) }}
                            </span>
                        </span>
                        <ChevronRight
                            class="size-4 shrink-0 text-tinta-suave group-hover:text-marca"
                            aria-hidden="true"
                        />
                    </Link>
                </li>
            </ul>
            <div
                v-if="avisos.length > AVISOS_VISIBLES"
                class="border-t border-linea px-4 py-2.5 sm:px-5"
            >
                <button
                    type="button"
                    class="rounded-md text-sm font-medium text-marca hover:text-marca-hover"
                    :aria-expanded="verTodos"
                    @click="verTodos = !verTodos"
                >
                    {{
                        verTodos
                            ? 'Ver menos'
                            : `Ver los ${avisos.length} avisos`
                    }}
                </button>
            </div>
        </section>

        <section aria-labelledby="titulo-mis-empresas">
            <div
                class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <h2 id="titulo-mis-empresas" class="font-semibold">
                    Mis empresas
                </h2>
                <div
                    v-if="empresas.length >= MINIMO_BUSCADOR"
                    class="relative sm:w-72"
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
            </div>

            <p
                v-if="empresas.length > 0 && empresasFiltradas.length === 0"
                class="rounded-2xl border border-dashed border-linea-fuerte bg-white px-5 py-8 text-center text-sm text-tinta-suave"
                role="status"
            >
                Ninguna empresa coincide con “{{ busqueda.trim() }}”.
            </p>

            <ul
                v-else-if="empresas.length > 0"
                class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3"
            >
                <li
                    v-for="e in empresasFiltradas"
                    :key="e.id"
                    class="flex flex-col rounded-2xl border border-linea bg-white"
                >
                    <div class="flex flex-1 items-start gap-4 p-4 sm:p-5">
                        <AnilloPuntaje
                            v-if="e.puntaje !== null"
                            :puntaje="e.puntaje"
                            :nivel="e.nivel"
                            :tamano="72"
                        />
                        <span
                            v-else
                            class="flex size-[72px] shrink-0 items-center justify-center rounded-full border-4 border-dashed border-linea text-xs text-tinta-suave"
                            aria-hidden="true"
                            >—</span
                        >
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate font-semibold">
                                {{ e.nombre }}
                            </h3>
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
                                    }}{{ e.variacion }}
                                </span>
                                <span
                                    class="rounded-full bg-lienzo px-2 py-0.5 text-tinta-suave"
                                >
                                    {{
                                        plural(
                                            e.mediciones,
                                            'medición terminada',
                                            'mediciones terminadas',
                                        )
                                    }}
                                </span>
                            </div>
                            <p
                                v-if="e.pendiente"
                                class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-tinta-suave"
                            >
                                Medición {{ e.pendiente.numero }}:
                                <EtiquetaEstado
                                    :estado="e.pendiente.estado"
                                    :avance="e.pendiente.avance ?? undefined"
                                />
                            </p>
                            <p
                                v-if="!e.activa"
                                class="mt-2 text-xs text-tinta-suave"
                            >
                                Cuenta desactivada: sus resultados se conservan.
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex flex-wrap gap-2 rounded-b-2xl border-t border-linea bg-lienzo/40 px-4 py-3 sm:px-5"
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
                    </div>
                </li>
            </ul>

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
                    El equipo de NuevasTIC te las vincula. Cuando lo haga, las
                    verás aquí.
                </p>
                <Link
                    href="/mi-perfil"
                    class="mt-1 text-sm font-medium text-marca hover:text-marca-hover"
                >
                    Mientras tanto, completa tu perfil
                </Link>
            </div>
        </section>
    </div>
</template>
