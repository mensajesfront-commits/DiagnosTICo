<script setup lang="ts">
/**
 * E8 · Mi historial (HU-067) y A3.4 · Historial de una empresa (HU-039): la
 * misma pantalla para la empresa y para el equipo de NuevasTIC.
 *
 * - Arriba, cuatro cifras (`resumen`): la última calificación, el cambio
 *   desde la primera, la mejor y cuántas van.
 * - La medición pendiente, si hay. La empresa la continúa desde aquí si está
 *   en curso; no se inicia aquí (CA-005). El Administrador puede cambiar la
 *   fecha o cancelarla (A3.1c) y asignar otra (HU-039 CA-004).
 * - La evolución del puntaje total con las franjas de nivel, desde la 2.ª
 *   medición (CA-001, CA-002).
 * - Las mediciones terminadas con fecha, puntaje, nivel y variación (RN-020)
 *   y "Ver resultado", "Ver mis respuestas" y "Ver informe PDF".
 */
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChartLine,
    ClipboardCheck,
    ClipboardList,
    FileDown,
    MessageSquareText,
    TrendingDown,
    TrendingUp,
    Trophy,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import Paginacion from '@/components/base/Paginacion.vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import ModalCambiarFecha from '@/components/empresas/ModalCambiarFecha.vue';
import ModalCancelarMedicion from '@/components/empresas/ModalCancelarMedicion.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import EvolucionPuntaje from '@/components/graficas/EvolucionPuntaje.vue';
import SelectorEvolucion from '@/components/graficas/SelectorEvolucion.vue';
import { usePermisos } from '@/composables/usePermisos';
import { diaYMes, fechaLocal } from '@/lib/fechas';
import { niveles } from '@/lib/niveles';
import type { Nivel } from '@/lib/niveles';
import { useFilasQueCaben, usePaginacion } from '@/lib/paginacion';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import { empresa as inicioEmpresa } from '@/routes/inicio';
import type { Historial } from '@/types/historial';

const props = defineProps<
    Historial & {
        /** A3.4 · Vista interna: cambio de cada categoría (DatosHistorial). */
        cambios_categoria?: CambioCategoria[];
    }
>();

type CambioCategoria = {
    nombre: string;
    primera: { numero: number; puntaje: number };
    ultima: { numero: number; puntaje: number };
    cambio: number | null;
    nivel: Exclude<Nivel, 'sin'>;
};

// --- Tarjeta de la derecha: mediciones o por categoría (vista interna) ----------
const vistas = [
    { valor: 'mediciones' as const, etiqueta: 'Mediciones' },
    { valor: 'categorias' as const, etiqueta: 'Por categoría' },
];
const vistaDerecha = ref<'mediciones' | 'categorias'>('mediciones');
const hayCambios = computed(
    () =>
        props.interno &&
        (props.cambios_categoria ?? []).some((c) => c.cambio !== null),
);

const { puede } = usePermisos();

/** El consultor también ve el historial "por dentro", pero no es Administrador. */
const esConsultor = computed(() => usePage().props.auth.rol === 'Consultor');

const page = usePage();
const puedeAsignar = computed(() =>
    page.props.auth.permisos.includes('mediciones.asignar'),
);

// --- "Ver:" de la gráfica: total o una categoría ---------------------------------
const verCategoria = ref('');
const puntosEvolucion = computed(
    () =>
        props.evolucion_categorias.find((c) => c.nombre === verCategoria.value)
            ?.puntos ?? props.evolucion,
);

const fecha = (texto: string | null) =>
    texto ? diaYMes(fechaLocal(texto)) : '—';

// --- Tabla paginada con las filas que caben ------------------------------------
const contenedor = ref<HTMLElement | null>(null);
const { porPagina, ajustar } = useFilasQueCaben(contenedor, {
    movil: 8,
    minimo: 3,
    fila: '[data-fila]',
    encabezado: null,
});
const { pagina, paginas, total, desde, visibles } = usePaginacion(
    () => props.mediciones,
    porPagina,
);
watch(visibles, ajustar);

// --- Pendiente -----------------------------------------------------------------------
const accionEmpresa = computed(() => {
    const p = props.pendiente;

    if (!p || props.interno) {
        return null;
    }

    switch (p.estado) {
        case 'en_curso':
            return {
                texto: 'Continuar diagnóstico',
                href: rutas.responder.mostrar(p.id),
            };
        case 'enviada':
            return {
                texto: 'Ver el avance del análisis',
                href: rutas.responder.enviado(p.id),
            };
        default:
            return {
                texto: 'Ir a mi inicio',
                href: inicioEmpresa().url,
                secundario: true,
            };
    }
});

const notaPendiente = computed(() => {
    switch (props.pendiente?.estado) {
        case 'enviada':
            return 'La estamos analizando: cuando termine, aparecerá en la tabla.';
        case 'vencida':
            return props.interno
                ? 'Venció sin enviarse. Cámbiale la fecha o asígnale otra.'
                : 'Venció. Desde tu inicio puedes pedir una nueva fecha.';
        case 'no_iniciada':
            return props.interno
                ? 'La empresa aún no la empieza.'
                : 'Empiézala desde tu inicio. Cuando la envíes, aparecerá en la tabla.';
        default:
            return 'Cuando la envíes, aparecerá en la tabla de mediciones terminadas.';
    }
});

const modalFecha = ref(false);
const modalCancelar = ref(false);
</script>

<template>
    <Head
        :title="interno ? `Historial de ${empresa.nombre}` : 'Mi historial'"
    />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
        <header class="flex shrink-0 flex-wrap items-end justify-between gap-4">
            <div>
                <Link
                    v-if="interno"
                    :href="rutas.empresas.ver(empresa.id)"
                    class="mb-2 inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-tinta-suave hover:text-marca"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    {{ empresa.nombre }}
                </Link>
                <h1
                    class="flex flex-wrap items-center gap-3 text-2xl font-semibold"
                >
                    {{
                        interno
                            ? `Historial de ${empresa.nombre}`
                            : 'Mi historial'
                    }}
                    <span
                        v-if="interno"
                        class="rounded bg-lienzo-oscuro px-2 py-0.5 text-xs font-normal text-tinta-suave"
                        >{{
                            esConsultor
                                ? 'Vista del consultor'
                                : 'Vista del Administrador'
                        }}</span
                    >
                </h1>
                <p class="mt-0.5 text-sm text-tinta-suave">
                    Todas las mediciones de {{ empresa.nombre }}.
                    {{
                        interno
                            ? 'La empresa ve este mismo historial en “Mi historial”.'
                            : 'El equipo de NuevasTIC ve este mismo historial.'
                    }}
                </p>
            </div>
            <Boton
                v-if="interno && puedeAsignar"
                variante="secundario"
                :href="rutas.mediciones.asignar(empresa.id)"
            >
                Asignar medición
            </Boton>
        </header>

        <!-- Cifras: la última, el cambio desde la primera y la mejor -->
        <section
            v-if="resumen"
            class="grid shrink-0 grid-cols-2 gap-3 xl:grid-cols-4"
        >
            <div
                class="col-span-2 flex items-center gap-4 rounded-2xl border border-linea bg-linear-to-br from-marca-suave/70 to-white p-4 xl:col-span-1"
            >
                <AnilloPuntaje
                    v-if="resumen.ultima.nivel"
                    :puntaje="resumen.ultima.puntaje ?? 0"
                    :nivel="resumen.ultima.nivel"
                    :tamano="72"
                />
                <div class="min-w-0">
                    <p class="text-xs font-medium text-tinta-suave">
                        Última calificación
                    </p>
                    <EtiquetaNivel
                        v-if="resumen.ultima.nivel"
                        :nivel="resumen.ultima.nivel"
                        class="mt-1"
                    />
                    <p class="mt-1 text-xs text-tinta-suave">
                        Medición {{ resumen.ultima.numero }} ·
                        {{ fecha(resumen.ultima.terminada) }}
                    </p>
                </div>
            </div>
            <div class="rounded-2xl border border-linea bg-white p-4">
                <p
                    class="flex items-center gap-1.5 text-xs font-medium text-tinta-suave"
                >
                    <TrendingUp class="size-4" aria-hidden="true" />
                    Desde la primera medición
                </p>
                <p
                    v-if="resumen.cambio !== null"
                    :class="[
                        'mt-1 font-mono text-3xl font-semibold',
                        resumen.cambio > 0
                            ? 'text-nivel-sigue-texto'
                            : resumen.cambio < 0
                              ? 'text-nivel-critico-texto'
                              : 'text-tinta',
                    ]"
                >
                    {{ resumen.cambio > 0 ? '+' : '' }}{{ resumen.cambio }}
                    <span class="font-sans text-sm font-normal text-tinta-suave"
                        >pts</span
                    >
                </p>
                <p v-else class="mt-1 text-sm text-tinta-suave">
                    Aparece con la segunda medición.
                </p>
                <p
                    v-if="resumen.cambio !== null"
                    class="text-xs text-tinta-suave"
                >
                    De {{ resumen.primera.puntaje }} en la medición
                    {{ resumen.primera.numero }} a
                    {{ resumen.ultima.puntaje }}
                </p>
            </div>
            <div class="rounded-2xl border border-linea bg-white p-4">
                <p
                    class="flex items-center gap-1.5 text-xs font-medium text-tinta-suave"
                >
                    <Trophy class="size-4" aria-hidden="true" />
                    Mejor calificación
                </p>
                <p class="mt-1 font-mono text-3xl font-semibold">
                    {{ resumen.mejor.puntaje }}
                    <span class="font-sans text-sm font-normal text-tinta-suave"
                        >de 100</span
                    >
                </p>
                <p class="text-xs text-tinta-suave">
                    Medición {{ resumen.mejor.numero }} ·
                    {{ fecha(resumen.mejor.terminada) }}
                </p>
            </div>
            <div
                class="col-span-2 rounded-2xl border border-linea bg-white p-4 xl:col-span-1"
            >
                <p
                    class="flex items-center gap-1.5 text-xs font-medium text-tinta-suave"
                >
                    <ClipboardCheck class="size-4" aria-hidden="true" />
                    Mediciones terminadas
                </p>
                <p class="mt-1 font-mono text-3xl font-semibold">
                    {{ resumen.cantidad }}
                </p>
                <p class="text-xs text-tinta-suave">
                    {{
                        pendiente
                            ? `Y la medición ${pendiente.numero} pendiente`
                            : 'Ninguna pendiente'
                    }}
                </p>
            </div>
        </section>

        <!-- Pendiente -->
        <section
            v-if="pendiente"
            class="flex shrink-0 flex-wrap items-center justify-between gap-3 rounded-2xl border border-marca/40 bg-marca-suave/40 px-5 py-3"
        >
            <div class="flex min-w-0 items-center gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-marca text-white"
                    aria-hidden="true"
                >
                    <ClipboardList class="size-5" />
                </span>
                <div class="min-w-0 text-sm">
                    <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <strong class="text-base"
                            >Medición {{ pendiente.numero }}</strong
                        >
                        <EtiquetaEstado
                            :estado="pendiente.estado"
                            :avance="pendiente.avance ?? undefined"
                        />
                        <span class="text-tinta-suave">
                            {{
                                pendiente.vence
                                    ? `Vence el ${fecha(pendiente.vence)}`
                                    : 'Sin fecha límite'
                            }}
                            <template v-if="interno && pendiente.diagnostico">
                                · {{ pendiente.diagnostico }} v{{
                                    pendiente.version
                                }}
                            </template>
                        </span>
                    </p>
                    <p class="mt-0.5 text-xs text-tinta-suave">
                        {{ notaPendiente }}
                    </p>
                </div>
            </div>
            <Boton
                v-if="accionEmpresa"
                :variante="accionEmpresa.secundario ? 'secundario' : 'primario'"
                :href="accionEmpresa.href"
            >
                {{ accionEmpresa.texto }}
            </Boton>
            <div
                v-else-if="
                    interno && puedeAsignar && pendiente.estado !== 'enviada'
                "
                class="flex gap-2"
            >
                <Boton variante="secundario" @click="modalFecha = true">
                    Cambiar fecha
                </Boton>
                <Boton variante="secundario" @click="modalCancelar = true">
                    Cancelar medición
                </Boton>
            </div>
        </section>

        <div
            class="grid grid-cols-[minmax(0,1fr)] gap-4 lg:min-h-0 lg:flex-1 lg:grid-rows-[minmax(0,1fr)] xl:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]"
        >
            <!-- Evolución -->
            <section
                class="flex flex-col rounded-2xl border border-linea bg-white p-5 lg:min-h-0"
            >
                <!-- "Ver:" va en su propia fila, a la derecha: así el título
                     largo de una categoría no mueve nada. -->
                <div class="flex flex-col gap-2">
                    <h2 class="truncate text-lg font-semibold">
                        {{
                            verCategoria
                                ? `Evolución de ${verCategoria}`
                                : interno
                                  ? 'Evolución del puntaje total'
                                  : 'Evolución de tu calificación'
                        }}
                    </h2>
                    <SelectorEvolucion
                        v-if="evolucion.length >= 2"
                        v-model="verCategoria"
                        class="self-end"
                        :categorias="evolucion_categorias"
                    />
                </div>
                <!-- La gráfica toma la altura de su contenedor. -->
                <div
                    v-if="evolucion.length >= 2"
                    class="mt-3 h-64 lg:h-auto lg:min-h-48 lg:flex-1"
                >
                    <EvolucionPuntaje
                        :key="verCategoria"
                        :puntos="puntosEvolucion"
                    />
                </div>
                <div
                    v-else
                    class="mt-3 flex flex-1 flex-col items-center justify-center gap-2 rounded-xl bg-lienzo px-6 py-10 text-center"
                >
                    <ChartLine
                        class="size-10 text-marca/60"
                        aria-hidden="true"
                    />
                    <p class="max-w-sm text-sm text-tinta-suave">
                        {{
                            evolucion.length === 1
                                ? 'Con la segunda medición verás aquí cómo sube o baja tu calificación.'
                                : 'La gráfica aparece desde la segunda medición terminada.'
                        }}
                    </p>
                </div>
            </section>

            <!-- Mediciones terminadas -->
            <section
                class="flex flex-col rounded-2xl border border-linea bg-white lg:min-h-0"
            >
                <div class="flex flex-col gap-2 px-5 pt-4 pb-2">
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <h2 class="text-lg font-semibold">
                            {{
                                vistaDerecha === 'categorias'
                                    ? 'Por categoría'
                                    : 'Mediciones terminadas'
                            }}
                        </h2>
                        <SelectorCompacto
                            v-if="hayCambios"
                            v-model="vistaDerecha"
                            :opciones="vistas"
                            etiqueta-accesible="Qué ver"
                        />
                    </div>
                    <p class="text-xs text-tinta-suave">
                        {{
                            vistaDerecha === 'categorias'
                                ? 'De la primera a la última medición, de la que más bajó a la que más subió. Toca una para verla en la gráfica.'
                                : 'De la más reciente a la primera. La variación es frente a la medición anterior.'
                        }}
                    </p>
                </div>
                <!-- A3.4 · Por categoría: de la primera a la última medición -->
                <ul
                    v-if="vistaDerecha === 'categorias'"
                    class="flex flex-col gap-1 px-3 pb-3 lg:min-h-0 lg:flex-1 lg:overflow-y-auto"
                    aria-label="Cambio por categoría"
                >
                    <li v-for="c in cambios_categoria" :key="c.nombre">
                        <button
                            type="button"
                            :aria-pressed="verCategoria === c.nombre"
                            :class="
                                cn(
                                    'flex w-full flex-col gap-1.5 rounded-xl px-3 py-2.5 text-left hover:bg-lienzo/70',
                                    verCategoria === c.nombre &&
                                        'bg-marca-suave/60 ring-1 ring-marca/30 hover:bg-marca-suave/60',
                                )
                            "
                            @click="
                                verCategoria =
                                    verCategoria === c.nombre ? '' : c.nombre
                            "
                        >
                            <span
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="min-w-0 truncate text-sm font-medium"
                                    >{{ c.nombre }}</span
                                >
                                <span
                                    v-if="c.cambio !== null"
                                    :class="
                                        cn(
                                            'inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 font-mono text-xs font-semibold',
                                            c.cambio > 0
                                                ? 'bg-exito-suave text-exito'
                                                : c.cambio < 0
                                                  ? 'bg-aviso-suave text-aviso'
                                                  : 'bg-lienzo text-tinta-suave',
                                        )
                                    "
                                >
                                    <TrendingUp
                                        v-if="c.cambio > 0"
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    <TrendingDown
                                        v-else-if="c.cambio < 0"
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    {{ c.cambio > 0 ? '+' : '' }}{{ c.cambio }}
                                </span>
                                <span
                                    v-else
                                    class="shrink-0 text-xs text-tinta-suave"
                                    >Una medición</span
                                >
                            </span>
                            <span
                                class="relative h-1.5 w-full overflow-hidden rounded-full bg-lienzo-oscuro"
                                aria-hidden="true"
                            >
                                <span
                                    v-if="c.cambio !== null"
                                    class="absolute inset-y-0 left-0 rounded-full bg-tinta/15"
                                    :style="{ width: `${c.primera.puntaje}%` }"
                                />
                                <span
                                    :class="
                                        cn(
                                            'absolute inset-y-0 left-0 rounded-full',
                                            niveles[c.nivel].barra,
                                            c.cambio !== null &&
                                                c.cambio < 0 &&
                                                'opacity-90',
                                        )
                                    "
                                    :style="{ width: `${c.ultima.puntaje}%` }"
                                />
                            </span>
                            <span class="text-xs text-tinta-suave">
                                <template v-if="c.cambio !== null">
                                    M{{ c.primera.numero }}
                                    <span class="font-mono">{{
                                        c.primera.puntaje
                                    }}</span>
                                    → M{{ c.ultima.numero }}
                                </template>
                                <template v-else
                                    >M{{ c.ultima.numero }}</template
                                >
                                <span
                                    class="ml-1 font-mono font-semibold text-tinta"
                                    >{{ c.ultima.puntaje }}</span
                                >
                                · {{ niveles[c.nivel].nombre }}
                            </span>
                        </button>
                    </li>
                </ul>
                <div
                    v-show="vistaDerecha === 'mediciones'"
                    ref="contenedor"
                    class="px-3 lg:min-h-0 lg:flex-1 lg:overflow-hidden"
                >
                    <ol v-if="mediciones.length" class="flex flex-col">
                        <li
                            v-for="(m, i) in visibles"
                            :key="m.id"
                            data-fila
                            class="border-b border-linea py-1 last:border-b-0"
                        >
                            <div
                                class="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-4 gap-y-2 rounded-xl px-2 py-2.5 hover:bg-lienzo sm:grid-cols-[auto_minmax(0,1fr)_auto]"
                            >
                                <AnilloPuntaje
                                    v-if="m.nivel"
                                    :puntaje="m.puntaje ?? 0"
                                    :nivel="m.nivel"
                                    :tamano="52"
                                />
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <strong>Medición {{ m.numero }}</strong>
                                        <span
                                            v-if="pagina === 1 && i === 0"
                                            class="rounded-full bg-marca px-2 py-0.5 text-[11px] font-medium text-white"
                                            >Más reciente</span
                                        >
                                        <span
                                            v-if="m.variacion !== null"
                                            :class="[
                                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                                m.variacion > 0
                                                    ? 'bg-nivel-sigue-suave text-nivel-sigue-texto'
                                                    : m.variacion < 0
                                                      ? 'bg-nivel-critico-suave text-nivel-critico-texto'
                                                      : 'bg-lienzo-oscuro text-tinta-suave',
                                            ]"
                                        >
                                            {{
                                                m.variacion > 0
                                                    ? `▲ +${m.variacion}`
                                                    : m.variacion < 0
                                                      ? `▼ ${m.variacion}`
                                                      : '= 0'
                                            }}
                                        </span>
                                        <span
                                            v-else
                                            class="text-xs text-tinta-suave"
                                            >Primera medición</span
                                        >
                                    </p>
                                    <p
                                        class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-tinta-suave"
                                    >
                                        <span>{{ fecha(m.terminada) }}</span>
                                        <EtiquetaNivel
                                            v-if="m.nivel"
                                            :nivel="m.nivel"
                                        />
                                    </p>
                                </div>
                                <div
                                    class="col-span-2 flex items-center gap-1 sm:col-span-1"
                                >
                                    <Boton
                                        variante="secundario"
                                        class="flex-1 sm:flex-none"
                                        :href="rutas.resultados.ver(m.id)"
                                    >
                                        Ver resultado
                                    </Boton>
                                    <Link
                                        :href="
                                            rutas.resultados.respuestas(m.id)
                                        "
                                        class="flex size-10 items-center justify-center rounded-md text-tinta-suave hover:bg-lienzo-oscuro hover:text-marca"
                                        :title="
                                            interno
                                                ? 'Respuestas'
                                                : 'Ver mis respuestas'
                                        "
                                    >
                                        <MessageSquareText class="size-5" />
                                        <span class="sr-only">{{
                                            interno
                                                ? 'Respuestas'
                                                : 'Ver mis respuestas'
                                        }}</span>
                                    </Link>
                                    <a
                                        v-if="puede('resultados.pdf')"
                                        :href="rutas.resultados.pdf(m.id)"
                                        class="flex size-10 items-center justify-center rounded-md text-tinta-suave hover:bg-lienzo-oscuro hover:text-marca"
                                        title="Ver informe PDF"
                                    >
                                        <FileDown class="size-5" />
                                        <span class="sr-only"
                                            >Ver informe PDF</span
                                        >
                                    </a>
                                </div>
                            </div>
                        </li>
                    </ol>
                    <div
                        v-else
                        class="flex flex-col items-center gap-2 px-5 py-10 text-center"
                    >
                        <ClipboardList
                            class="size-10 text-marca/60"
                            aria-hidden="true"
                        />
                        <p class="max-w-sm text-sm text-tinta-suave">
                            {{
                                interno
                                    ? 'Esta empresa aún no tiene mediciones terminadas.'
                                    : 'Aún no tienes mediciones terminadas. Cuando envíes tu diagnóstico y se analice, aparecerá aquí.'
                            }}
                        </p>
                    </div>
                </div>
                <div
                    v-if="paginas > 1 && vistaDerecha === 'mediciones'"
                    class="flex justify-end border-t border-linea px-5 py-2"
                >
                    <Paginacion
                        v-model:pagina="pagina"
                        :paginas="paginas"
                        :total="total"
                        :desde="desde"
                        :cantidad="visibles.length"
                    />
                </div>
            </section>
        </div>
    </div>

    <template v-if="interno && pendiente">
        <ModalCambiarFecha
            v-model:abierto="modalFecha"
            :medicion="pendiente"
            :empresa="empresa.nombre"
        />
        <ModalCancelarMedicion
            v-model:abierto="modalCancelar"
            :medicion="pendiente"
            :empresa="empresa.nombre"
        />
    </template>
</template>
