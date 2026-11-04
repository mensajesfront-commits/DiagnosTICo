<script setup lang="ts">
/**
 * C1 · Inicio del Consultor (HU-086, RN-030).
 *
 * - Saludo con la fecha y una frase de cómo va su cartera.
 * - Cuatro cifras: empresas, puntaje promedio, mejoraron / bajaron y
 *   mediciones pendientes.
 * - "Qué atender" (HU-086 CA-005): vencidas, puntajes que bajaron, mediciones
 *   sin empezar hace una semana y resultados nuevos. "Todo al día" si no hay.
 * - "Resultados recientes": el último resultado de cada empresa, del más nuevo.
 * - A la derecha, "Cómo están por nivel" y "Próximas fechas límite".
 *
 * Las tarjetas de cada empresa están en "Mis empresas" (C2, /empresas). Solo
 * mira: no responde ni asigna. Todo llega calculado del servidor
 * (DatosInicioConsultor::resumen y ::avisos).
 */
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BellRing,
    Building2,
    CalendarClock,
    ChartLine,
    ChevronRight,
    CircleCheck,
    ClipboardList,
    Clock,
    Gauge,
    Layers,
    Minus,
    Sparkles,
    TrendingDown,
    TrendingUp,
    TriangleAlert,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import { diaYMes, fechaLocal } from '@/lib/fechas';
import { niveles, ordenNiveles } from '@/lib/niveles';
import { rutas } from '@/lib/rutas';
import { plural } from '@/lib/texto';
import { cn } from '@/lib/utils';
import type { EstadoMedicion } from '@/components/base/EtiquetaEstado.vue';
import type { Nivel } from '@/lib/niveles';
import type { Avance } from '@/types/empresas';

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

type Resumen = {
    empresas: number;
    con_resultado: number;
    promedio: number | null;
    nivel_promedio: Nivel;
    mejoraron: number;
    bajaron: number;
    pendientes: number;
    vencidas: number;
    por_nivel: Record<Nivel, number>;
    proximas: {
        empresa: { id: number; nombre: string };
        numero: number;
        estado: EstadoMedicion;
        avance: Avance | null;
        vence: string;
        dias: number;
    }[];
    recientes: {
        empresa: { id: number; nombre: string };
        medicion: number;
        numero: number;
        fecha: string;
        puntaje: number;
        nivel: Nivel;
        variacion: number | null;
    }[];
};

const props = defineProps<{ resumen: Resumen; avisos: Aviso[] }>();

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

const page = usePage();
const nombre = computed(
    () => page.props.auth.user?.name?.split(' ')[0] ?? 'consultor',
);

const hoy = new Intl.DateTimeFormat('es-CO', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
}).format(new Date());

/** Empresas distintas con algo que atender. */
const porAtender = computed(
    () => new Set(props.avisos.map((a) => a.empresa.id)).size,
);

const frase = computed(() => {
    if (props.resumen.empresas === 0) {
        return 'Todavía no tienes empresas vinculadas.';
    }

    if (porAtender.value === 0) {
        return `Tus ${plural(props.resumen.empresas, 'empresa está', 'empresas están')} al día.`;
    }

    return `${plural(porAtender.value, 'empresa necesita', 'empresas necesitan')} tu atención hoy.`;
});

function dias(n: number): string {
    if (n === 0) {
        return 'Vence hoy';
    }

    return n === 1 ? 'Vence mañana' : `En ${n} días`;
}

function fechaCorta(iso: string): string {
    return diaYMes(fechaLocal(iso));
}

/** Niveles con al menos una empresa, en orden de crítico a "sigue así". */
const distribucion = computed(() =>
    ordenNiveles
        .map((n) => ({ nivel: n, cantidad: props.resumen.por_nivel[n] ?? 0 }))
        .filter((d) => d.cantidad > 0),
);
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-col gap-5 p-4 sm:p-6">
        <!-- Saludo -->
        <header
            class="relative overflow-hidden rounded-2xl bg-marca px-5 py-5 text-white sm:px-6"
        >
            <span
                class="pointer-events-none absolute -top-10 -right-10 size-40 rounded-full bg-white/5"
                aria-hidden="true"
            />
            <span
                class="pointer-events-none absolute -right-4 -bottom-16 size-32 rounded-full bg-white/5"
                aria-hidden="true"
            />
            <p
                class="text-xs font-medium tracking-wide text-white/70 uppercase"
            >
                {{ hoy }}
            </p>
            <h1 class="mt-1 text-2xl font-semibold">Hola, {{ nombre }}</h1>
            <p class="mt-1 flex items-center gap-2 text-sm text-white/85">
                <BellRing
                    v-if="porAtender > 0"
                    class="size-4 shrink-0"
                    aria-hidden="true"
                />
                <CircleCheck
                    v-else-if="resumen.empresas > 0"
                    class="size-4 shrink-0"
                    aria-hidden="true"
                />
                {{ frase }}
            </p>
        </header>

        <template v-if="resumen.empresas > 0">
            <!-- Cifras -->
            <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-2xl border border-linea bg-white p-4">
                    <dt
                        class="flex items-center gap-1.5 text-xs text-tinta-suave"
                    >
                        <Building2 class="size-3.5" aria-hidden="true" />
                        Empresas
                    </dt>
                    <dd class="mt-1.5">
                        <span class="font-mono text-2xl font-semibold">{{
                            resumen.empresas
                        }}</span>
                        <span class="mt-0.5 block text-xs text-tinta-suave">
                            {{ resumen.con_resultado }} con resultado
                        </span>
                    </dd>
                </div>
                <div class="rounded-2xl border border-linea bg-white p-4">
                    <dt
                        class="flex items-center gap-1.5 text-xs text-tinta-suave"
                    >
                        <Gauge class="size-3.5" aria-hidden="true" />
                        Puntaje promedio
                    </dt>
                    <dd class="mt-1.5">
                        <span class="font-mono text-2xl font-semibold">{{
                            resumen.promedio ?? '—'
                        }}</span>
                        <span
                            v-if="resumen.promedio !== null"
                            class="ml-1 text-xs text-tinta-suave"
                            >/ 100</span
                        >
                        <span class="mt-1 block">
                            <EtiquetaNivel
                                :nivel="resumen.nivel_promedio"
                                class="text-xs"
                            />
                        </span>
                    </dd>
                </div>
                <div class="rounded-2xl border border-linea bg-white p-4">
                    <dt
                        class="flex items-center gap-1.5 text-xs text-tinta-suave"
                    >
                        <ChartLine class="size-3.5" aria-hidden="true" />
                        En su última medición
                    </dt>
                    <dd class="mt-1.5 flex items-baseline gap-3">
                        <span
                            class="inline-flex items-center gap-1 font-mono text-2xl font-semibold text-exito"
                            :title="`${plural(resumen.mejoraron, 'empresa mejoró', 'empresas mejoraron')}`"
                        >
                            <TrendingUp class="size-4" aria-hidden="true" />
                            {{ resumen.mejoraron }}
                        </span>
                        <span
                            class="inline-flex items-center gap-1 font-mono text-2xl font-semibold text-aviso"
                            :title="`${plural(resumen.bajaron, 'empresa bajó', 'empresas bajaron')}`"
                        >
                            <TrendingDown class="size-4" aria-hidden="true" />
                            {{ resumen.bajaron }}
                        </span>
                    </dd>
                    <dd class="mt-0.5 text-xs text-tinta-suave">
                        mejoraron · bajaron
                    </dd>
                </div>
                <div class="rounded-2xl border border-linea bg-white p-4">
                    <dt
                        class="flex items-center gap-1.5 text-xs text-tinta-suave"
                    >
                        <ClipboardList class="size-3.5" aria-hidden="true" />
                        Mediciones pendientes
                    </dt>
                    <dd class="mt-1.5">
                        <span class="font-mono text-2xl font-semibold">{{
                            resumen.pendientes
                        }}</span>
                        <span
                            :class="
                                cn(
                                    'mt-0.5 block text-xs',
                                    resumen.vencidas > 0
                                        ? 'font-medium text-aviso'
                                        : 'text-tinta-suave',
                                )
                            "
                        >
                            {{
                                resumen.vencidas > 0
                                    ? plural(
                                          resumen.vencidas,
                                          'vencida',
                                          'vencidas',
                                      )
                                    : 'Ninguna vencida'
                            }}
                        </span>
                    </dd>
                </div>
            </dl>

            <div
                class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_340px]"
            >
                <div class="flex min-w-0 flex-col gap-5">
                    <!-- Qué atender -->
                    <section
                        aria-labelledby="titulo-que-atender"
                        class="rounded-2xl border border-linea bg-white"
                    >
                        <header
                            class="flex items-center gap-2 border-b border-linea px-4 py-3 sm:px-5"
                        >
                            <BellRing
                                class="size-4 text-marca"
                                aria-hidden="true"
                            />
                            <h2 id="titulo-que-atender" class="font-semibold">
                                Qué atender
                            </h2>
                            <span
                                v-if="avisos.length > 0"
                                class="rounded-full bg-marca-suave px-2 py-0.5 font-mono text-xs font-semibold text-marca"
                                >{{ avisos.length }}</span
                            >
                        </header>
                        <ul
                            v-if="avisos.length > 0"
                            class="divide-y divide-linea"
                        >
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
                                            <span
                                                class="truncate font-medium"
                                                >{{ a.empresa.nombre }}</span
                                            >
                                            <span
                                                :class="
                                                    cn(
                                                        'rounded-full px-2 py-0.5 text-xs font-medium',
                                                        ESTILO_AVISO[a.tipo]
                                                            .caja,
                                                    )
                                                "
                                                >{{
                                                    ESTILO_AVISO[a.tipo]
                                                        .etiqueta
                                                }}</span
                                            >
                                        </span>
                                        <span
                                            class="mt-0.5 block text-sm text-tinta-suave"
                                        >
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
                        <p
                            v-else
                            class="flex items-center gap-3 px-4 py-4 text-sm text-tinta-suave sm:px-5"
                            role="status"
                        >
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-exito-suave text-exito"
                                aria-hidden="true"
                            >
                                <CircleCheck class="size-4" />
                            </span>
                            <span>
                                <span class="block font-medium text-tinta"
                                    >Todo al día</span
                                >
                                Ninguna medición vencida, ningún puntaje que
                                bajó ni resultados nuevos esta semana.
                            </span>
                        </p>
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

                    <!-- Resultados recientes -->
                    <section
                        v-if="resumen.recientes.length > 0"
                        aria-labelledby="titulo-recientes"
                        class="rounded-2xl border border-linea bg-white"
                    >
                        <header
                            class="flex items-center gap-2 border-b border-linea px-4 py-3 sm:px-5"
                        >
                            <Sparkles
                                class="size-4 text-marca"
                                aria-hidden="true"
                            />
                            <h2 id="titulo-recientes" class="font-semibold">
                                Resultados recientes
                            </h2>
                        </header>
                        <ul class="divide-y divide-linea">
                            <li
                                v-for="r in resumen.recientes"
                                :key="r.medicion"
                            >
                                <Link
                                    :href="rutas.resultados.ver(r.medicion)"
                                    class="group flex items-center gap-3 px-4 py-2.5 hover:bg-lienzo/60 focus-visible:bg-lienzo/60 focus-visible:outline-none sm:px-5"
                                >
                                    <AnilloPuntaje
                                        :puntaje="r.puntaje"
                                        :nivel="r.nivel"
                                        :tamano="40"
                                    />
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block truncate font-medium"
                                            >{{ r.empresa.nombre }}</span
                                        >
                                        <span
                                            class="block text-xs text-tinta-suave"
                                        >
                                            Medición {{ r.numero }} ·
                                            {{ fechaCorta(r.fecha) }}
                                        </span>
                                    </span>
                                    <EtiquetaNivel
                                        :nivel="r.nivel"
                                        class="hidden text-xs sm:inline-flex"
                                    />
                                    <span
                                        v-if="r.variacion !== null"
                                        :class="
                                            cn(
                                                'inline-flex w-14 shrink-0 items-center justify-end gap-1 font-mono text-xs font-semibold',
                                                r.variacion > 0
                                                    ? 'text-exito'
                                                    : r.variacion < 0
                                                      ? 'text-aviso'
                                                      : 'text-tinta-suave',
                                            )
                                        "
                                    >
                                        <TrendingUp
                                            v-if="r.variacion > 0"
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        <TrendingDown
                                            v-else-if="r.variacion < 0"
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        <Minus
                                            v-else
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        {{ r.variacion > 0 ? '+' : ''
                                        }}{{ r.variacion }}
                                    </span>
                                    <span
                                        v-else
                                        class="w-14 shrink-0 text-right text-xs text-tinta-suave"
                                        >1.ª</span
                                    >
                                    <ChevronRight
                                        class="size-4 shrink-0 text-tinta-suave group-hover:text-marca"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </li>
                        </ul>
                    </section>
                </div>

                <aside class="flex flex-col gap-5">
                    <!-- Cómo están por nivel -->
                    <section
                        aria-labelledby="titulo-por-nivel"
                        class="rounded-2xl border border-linea bg-white p-4 sm:p-5"
                    >
                        <h2
                            id="titulo-por-nivel"
                            class="flex items-center gap-2 font-semibold"
                        >
                            <Layers
                                class="size-4 text-marca"
                                aria-hidden="true"
                            />
                            Cómo están por nivel
                        </h2>
                        <div
                            class="mt-4 flex h-3 overflow-hidden rounded-full bg-lienzo"
                            role="img"
                            :aria-label="
                                distribucion
                                    .map(
                                        (d) =>
                                            `${niveles[d.nivel].nombre}: ${d.cantidad}`,
                                    )
                                    .join(', ')
                            "
                        >
                            <span
                                v-for="d in distribucion"
                                :key="d.nivel"
                                :class="niveles[d.nivel].barra"
                                :style="{
                                    width: `${(d.cantidad / resumen.empresas) * 100}%`,
                                }"
                            />
                        </div>
                        <ul class="mt-4 flex flex-col gap-2 text-sm">
                            <li
                                v-for="d in distribucion"
                                :key="d.nivel"
                                class="flex items-center gap-2"
                            >
                                <span
                                    :class="
                                        cn(
                                            'size-2.5 shrink-0 rounded-full',
                                            niveles[d.nivel].barra,
                                        )
                                    "
                                    aria-hidden="true"
                                />
                                <span class="flex-1">{{
                                    d.nivel === 'sin'
                                        ? 'Sin resultado'
                                        : niveles[d.nivel].nombre
                                }}</span>
                                <span class="font-mono font-semibold">{{
                                    d.cantidad
                                }}</span>
                            </li>
                        </ul>
                    </section>

                    <!-- Próximas fechas límite -->
                    <section
                        aria-labelledby="titulo-proximas"
                        class="rounded-2xl border border-linea bg-white p-4 sm:p-5"
                    >
                        <h2
                            id="titulo-proximas"
                            class="flex items-center gap-2 font-semibold"
                        >
                            <CalendarClock
                                class="size-4 text-marca"
                                aria-hidden="true"
                            />
                            Próximas fechas límite
                        </h2>
                        <ul
                            v-if="resumen.proximas.length > 0"
                            class="mt-3 flex flex-col"
                        >
                            <li
                                v-for="p in resumen.proximas"
                                :key="p.empresa.id"
                                class="border-t border-linea py-2.5 first:border-t-0 first:pt-0 last:pb-0"
                            >
                                <Link
                                    :href="rutas.empresas.ver(p.empresa.id)"
                                    class="group flex items-start gap-3 rounded-md focus-visible:outline-none"
                                >
                                    <span
                                        class="flex w-11 shrink-0 flex-col items-center rounded-lg border border-linea py-1 leading-tight"
                                        aria-hidden="true"
                                    >
                                        <span
                                            class="text-[10px] text-tinta-suave uppercase"
                                            >{{
                                                fechaCorta(p.vence).split(
                                                    ' ',
                                                )[1]
                                            }}</span
                                        >
                                        <span class="font-mono font-semibold">{{
                                            fechaCorta(p.vence).split(' ')[0]
                                        }}</span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block truncate text-sm font-medium group-hover:text-marca"
                                            >{{ p.empresa.nombre }}</span
                                        >
                                        <span
                                            class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-tinta-suave"
                                        >
                                            Medición {{ p.numero }}
                                            <EtiquetaEstado
                                                :estado="p.estado"
                                                :avance="p.avance ?? undefined"
                                            />
                                        </span>
                                    </span>
                                    <span
                                        :class="
                                            cn(
                                                'shrink-0 text-xs',
                                                p.dias <= 3
                                                    ? 'font-medium text-alerta'
                                                    : 'text-tinta-suave',
                                            )
                                        "
                                        >{{ dias(p.dias) }}</span
                                    >
                                </Link>
                            </li>
                        </ul>
                        <p v-else class="mt-3 text-sm text-tinta-suave">
                            <Clock
                                class="mr-1 inline size-3.5 align-[-2px]"
                                aria-hidden="true"
                            />
                            No hay fechas límite por venir.
                        </p>
                    </section>
                </aside>
            </div>
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
                en "Mis empresas".
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
