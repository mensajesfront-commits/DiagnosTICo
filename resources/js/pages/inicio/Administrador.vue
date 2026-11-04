<script setup lang="ts">
/**
 * A1 · Inicio del Administrador (HU-006, HU-007).
 *
 * Cuatro indicadores arriba, con ícono (las pendientes con una barra de sin
 * iniciar, en curso y vencidas; el promedio con su anillo); la franja
 * "Requiere tu atención" si hay vencidas o solicitudes; la tabla
 * "Mediciones" con las pestañas Todas, En curso, No iniciadas, Vencidas y
 * Terminadas; y a la derecha "Empresas por nivel" (barra de colores) y
 * "Diagnósticos por sector". Todo llega calculado del backend
 * (`DatosInicio`). En pantallas grandes la página no se desplaza: la tabla
 * va paginada con las filas que caben.
 *
 * Registrar empresas y asignar mediciones no se hace aquí, sino en Empresas
 * (A3).
 *
 * HU-083: si alguna empresa pidió una medición nueva porque la suya venció,
 * lo dice la franja "Requiere tu atención"; lleva a la pestaña Vencidas,
 * donde esas filas van primero con "Asignar nueva".
 */
import { Head } from '@inertiajs/vue3';
import {
    Building2,
    CalendarDays,
    ChevronRight,
    CircleCheckBig,
    ClipboardList,
    Gauge,
    TriangleAlert,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import DiagnosticosPorSector from '@/components/inicio/DiagnosticosPorSector.vue';
import EmpresasPorNivel from '@/components/inicio/EmpresasPorNivel.vue';
import TablaMediciones from '@/components/inicio/TablaMediciones.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import { niveles as infoNiveles } from '@/lib/niveles';
import type { Nivel } from '@/lib/niveles';
import type {
    FilaMedicion,
    IndicadoresInicio,
    SectorInicio,
} from '@/types/inicio';

const props = defineProps<{
    indicadores: IndicadoresInicio;
    mediciones: FilaMedicion[];
    niveles: Record<Nivel, number>;
    sectores: SectorInicio[];
    /** Empresas con una solicitud de medición nueva abierta (HU-083). */
    solicitudes: number;
}>();

// --- Pestañas (HU-007) ---------------------------------------------------------
type Pestana = 'todas' | 'en_curso' | 'no_iniciada' | 'vencida' | 'terminada';

const pestana = ref<Pestana>('todas');

const deLaPestana = (p: Pestana, f: FilaMedicion) =>
    p === 'todas' ||
    f.estado === p ||
    // "Enviada" cuenta como en curso hasta que se publica el resultado.
    (p === 'en_curso' && f.estado === 'enviada');

const opciones = computed(() =>
    (
        [
            ['todas', 'Todas'],
            ['en_curso', 'En curso'],
            ['no_iniciada', 'No iniciadas'],
            ['vencida', 'Vencidas'],
            ['terminada', 'Terminadas'],
        ] as const
    ).map(([valor, etiqueta]) => ({
        valor,
        etiqueta,
        cantidad: props.mediciones.filter((f) => deLaPestana(valor, f)).length,
    })),
);

const filas = computed(() =>
    props.mediciones.filter((f) => deLaPestana(pestana.value, f)),
);

const vacio: Record<Pestana, string> = {
    todas: 'Todavía no hay mediciones. Se asignan desde Empresas.',
    en_curso: 'No hay mediciones en curso.',
    no_iniciada: 'No hay mediciones sin iniciar.',
    vencida: 'No hay mediciones vencidas.',
    terminada: 'Todavía no hay mediciones terminadas.',
};

// --- Indicadores (HU-006 CA-001) -----------------------------------------------
const plural = (n: number, uno: string, varios: string) =>
    `${n} ${n === 1 ? uno : varios}`;

// Barra de las pendientes: cuántas sin iniciar, en curso y vencidas.
const pendientesBarra = computed(() => {
    const p = props.indicadores.pendientes_por_estado;
    const total = p.no_iniciada + p.en_curso + p.vencida || 1;

    return [
        {
            clave: 'no_iniciada',
            texto: 'sin iniciar',
            n: p.no_iniciada,
            clase: 'bg-linea-fuerte',
        },
        {
            clave: 'en_curso',
            texto: 'en curso',
            n: p.en_curso,
            clase: 'bg-marca',
        },
        {
            clave: 'vencida',
            texto: p.vencida === 1 ? 'vencida' : 'vencidas',
            n: p.vencida,
            clase: 'bg-nivel-critico',
        },
    ].map((x) => ({ ...x, ancho: (x.n * 100) / total }));
});

// Nivel del puntaje promedio, para el color del anillo (el backend da el número).
const nivelPromedio = computed<Exclude<Nivel, 'sin'> | null>(() => {
    const p = props.indicadores.puntaje_promedio;

    if (p === null) {
        return null;
    }

    return p < 30
        ? 'critico'
        : p < 60
          ? 'mejorar'
          : p < 80
            ? 'camino'
            : 'sigue';
});

// "Requiere tu atención": vencidas y solicitudes de medición nueva.
const vencidas = computed(
    () => props.indicadores.pendientes_por_estado.vencida,
);

const hoyTexto = new Date().toLocaleDateString('es-CO', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});
const hoy = hoyTexto.charAt(0).toUpperCase() + hoyTexto.slice(1);
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-col gap-5 p-6 lg:h-dvh lg:overflow-hidden">
        <header class="flex shrink-0 flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Inicio</h1>
                <p class="mt-0.5 text-sm text-tinta-suave">
                    Cómo van las empresas y sus mediciones.
                </p>
            </div>
            <p
                class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-sm text-tinta-suave ring-1 ring-linea"
            >
                <CalendarDays class="size-4" aria-hidden="true" />
                {{ hoy }}
            </p>
        </header>

        <!-- Indicadores -->
        <div class="grid shrink-0 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div
                class="flex items-center gap-4 rounded-2xl border border-linea bg-white p-4"
            >
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                >
                    <Building2 class="size-5" aria-hidden="true" />
                </span>
                <dl class="min-w-0">
                    <dt class="text-xs font-medium text-tinta-suave">
                        Empresas registradas
                    </dt>
                    <dd class="font-mono text-3xl leading-tight font-semibold">
                        {{ indicadores.empresas }}
                    </dd>
                    <dd class="text-xs text-tinta-suave">
                        en
                        {{
                            plural(
                                indicadores.sectores_con_empresas,
                                'sector',
                                'sectores',
                            )
                        }}
                    </dd>
                </dl>
            </div>

            <div class="rounded-2xl border border-linea bg-white p-4">
                <div class="flex items-center gap-4">
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-nivel-mejorar-suave text-nivel-mejorar-texto"
                    >
                        <ClipboardList class="size-5" aria-hidden="true" />
                    </span>
                    <dl class="min-w-0">
                        <dt class="text-xs font-medium text-tinta-suave">
                            Mediciones pendientes
                        </dt>
                        <dd
                            class="font-mono text-3xl leading-tight font-semibold"
                        >
                            {{ indicadores.pendientes }}
                        </dd>
                    </dl>
                </div>
                <div v-if="indicadores.pendientes > 0" class="mt-2">
                    <span
                        class="flex h-2 overflow-hidden rounded-full bg-lienzo-oscuro"
                        aria-hidden="true"
                    >
                        <span
                            v-for="x in pendientesBarra"
                            :key="x.clave"
                            :class="x.clase"
                            :style="{ width: `${x.ancho}%` }"
                        />
                    </span>
                    <span
                        class="mt-1.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-tinta-suave"
                    >
                        <span
                            v-for="x in pendientesBarra"
                            :key="x.clave"
                            class="inline-flex items-center gap-1"
                        >
                            <span
                                :class="['size-2 rounded-full', x.clase]"
                                aria-hidden="true"
                            />
                            <strong class="text-tinta">{{ x.n }}</strong>
                            {{ x.texto }}
                        </span>
                    </span>
                </div>
                <p v-else class="mt-1 text-xs text-tinta-suave">
                    Ninguna por ahora
                </p>
            </div>

            <div
                class="flex items-center gap-4 rounded-2xl border border-linea bg-white p-4"
            >
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-nivel-sigue-suave text-nivel-sigue-texto"
                >
                    <CircleCheckBig class="size-5" aria-hidden="true" />
                </span>
                <dl class="min-w-0">
                    <dt class="text-xs font-medium text-tinta-suave">
                        Diagnósticos completados
                    </dt>
                    <dd class="font-mono text-3xl leading-tight font-semibold">
                        {{ indicadores.completados_mes }}
                    </dd>
                    <dd class="text-xs text-tinta-suave">este mes</dd>
                </dl>
            </div>

            <div
                class="flex items-center gap-4 rounded-2xl border border-linea bg-white p-4"
            >
                <AnilloPuntaje
                    v-if="
                        nivelPromedio && indicadores.puntaje_promedio !== null
                    "
                    :puntaje="indicadores.puntaje_promedio"
                    :nivel="nivelPromedio"
                    :tamano="52"
                />
                <span
                    v-else
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-lienzo-oscuro text-tinta-suave"
                >
                    <Gauge class="size-5" aria-hidden="true" />
                </span>
                <dl class="min-w-0">
                    <dt class="text-xs font-medium text-tinta-suave">
                        Puntaje promedio
                    </dt>
                    <dd class="font-mono text-3xl leading-tight font-semibold">
                        {{
                            indicadores.puntaje_promedio === null
                                ? '—'
                                : indicadores.puntaje_promedio
                        }}
                    </dd>
                    <dd class="text-xs text-tinta-suave">
                        {{
                            nivelPromedio
                                ? infoNiveles[nivelPromedio].nombre + ' · '
                                : ''
                        }}último diagnóstico de cada empresa
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Requiere tu atención -->
        <button
            v-if="vencidas > 0 || solicitudes > 0"
            type="button"
            class="flex shrink-0 flex-wrap items-center gap-3 rounded-2xl border border-aviso/40 bg-aviso-suave/60 px-4 py-3 text-left text-sm transition hover:border-aviso/70"
            @click="pestana = 'vencida'"
        >
            <TriangleAlert
                class="size-5 shrink-0 text-aviso"
                aria-hidden="true"
            />
            <span class="font-semibold">Requiere tu atención:</span>
            <span v-if="vencidas > 0">{{
                plural(vencidas, 'medición vencida', 'mediciones vencidas')
            }}</span>
            <span
                v-if="vencidas > 0 && solicitudes > 0"
                class="text-tinta-suave"
                >·</span
            >
            <span v-if="solicitudes > 0">
                {{
                    solicitudes === 1
                        ? '1 empresa pidió una medición nueva'
                        : `${solicitudes} empresas pidieron una medición nueva`
                }}
            </span>
            <span
                class="ml-auto inline-flex items-center gap-0.5 font-medium text-aviso"
            >
                Revisar <ChevronRight class="size-4" aria-hidden="true" />
            </span>
        </button>

        <div
            class="grid grid-cols-[minmax(0,1fr)] items-start gap-5 lg:min-h-0 lg:flex-1 lg:grid-cols-[minmax(0,1fr)_320px] lg:grid-rows-[minmax(0,1fr)] lg:items-stretch"
        >
            <section
                class="flex flex-col overflow-hidden rounded-2xl border border-linea bg-white lg:min-h-0"
            >
                <header
                    class="flex shrink-0 flex-wrap items-center justify-between gap-3 px-5 py-3"
                >
                    <h2 class="text-lg font-semibold">Mediciones</h2>
                    <SelectorCompacto
                        v-model="pestana"
                        :opciones="opciones"
                        etiqueta-accesible="Filtrar mediciones por estado"
                    />
                </header>

                <div
                    v-if="filas.length === 0"
                    class="flex flex-1 flex-col items-center justify-center gap-2 border-t border-linea px-5 py-10 text-center"
                >
                    <ClipboardList
                        class="size-10 text-marca/50"
                        aria-hidden="true"
                    />
                    <p class="text-sm text-tinta-suave">{{ vacio[pestana] }}</p>
                </div>
                <TablaMediciones
                    v-else
                    :key="pestana"
                    class="min-h-0 flex-1"
                    :filas="filas"
                />
            </section>

            <div class="flex flex-col gap-5 lg:min-h-0">
                <EmpresasPorNivel class="shrink-0" :cuenta="niveles" />
                <DiagnosticosPorSector
                    class="lg:min-h-0"
                    :sectores="sectores"
                />
            </div>
        </div>
    </div>
</template>
