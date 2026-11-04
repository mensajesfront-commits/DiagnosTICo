<script setup lang="ts">
/**
 * E1 · Inicio de la empresa y del colaborador (HU-057, HU-058, HU-059,
 * HU-082).
 *
 * Arriba, la medición pendiente según su estado:
 * - Primera vez: "Descubre en qué punto está tu empresa" y "Cómo funciona".
 * - No iniciada: mensaje del equipo de NuevasTIC, fecha límite y "Iniciar
 *   diagnóstico".
 * - En curso: avance y "Continuar diagnóstico" (sigue donde iba).
 * - Vencida: no se responde (RN-016); "Pedir una nueva fecha" avisa al
 *   equipo, una solicitud a la vez.
 * - Enviada: la IA analiza las respuestas.
 * - Sin pendiente: "No tienes diagnósticos pendientes".
 *
 * La pendiente va en una tarjeta destacada: anillo con el porcentaje
 * respondido (o un ícono según el estado), datos con íconos y, a la derecha,
 * la cuenta regresiva (naranja con 3 días o menos, roja el último día) y el
 * botón principal.
 *
 * Abajo, el último resultado (anillo, nivel, variación, botones al resultado
 * y al PDF, evolución) y las 3 áreas a trabajar con barra de color, o la
 * lista de mediciones. La primera vez, "Cómo funciona" con íconos.
 *
 * "Ver resultado" abre E6 y "Ver informe PDF" descarga el PDF guardado.
 *
 * "Ver historial completo" abre E8 (HU-067).
 */
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    CalendarDays,
    ChartColumnIncreasing,
    ChevronRight,
    CircleAlert,
    CircleCheck,
    ClipboardPen,
    FileDown,
    Layers,
    LoaderCircle,
    Mail,
    Rocket,
    Save,
    Sparkles,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import MiniEvolucion from '@/components/graficas/MiniEvolucion.vue';
import { usePermisos } from '@/composables/usePermisos';
import { diaYMes, fechaLocal } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';
import { niveles } from '@/lib/niveles';
import { cn } from '@/lib/utils';
import type { InicioEmpresa } from '@/types/responder';

const props = defineProps<Partial<InicioEmpresa> & { sinEmpresa?: boolean }>();

const { puede } = usePermisos();

const plural = (n: number, uno: string, varios: string) =>
    `${n} ${n === 1 ? uno : varios}`;

const subtitulo = computed(() => {
    const e = props.empresa;

    if (!e) {
        return '';
    }

    const hechos = props.realizadas
        ? plural(
              props.realizadas,
              'diagnóstico realizado',
              'diagnósticos realizados',
          )
        : 'Aún no has realizado ningún diagnóstico';

    return `${e.nombre} · Sector ${e.sector} · ${hechos}`;
});

const p = computed(() => props.pendiente ?? null);

const fechaLarga = (texto: string) =>
    fechaLocal(texto).toLocaleDateString('es-CO', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });

const quedan = computed(() => {
    const d = p.value?.dias_restantes;

    if (d === null || d === undefined) {
        return null;
    }

    return d === 0
        ? 'Vence hoy a las 11:59 p. m.'
        : `Quedan ${plural(d, 'día', 'días')}`;
});

const evolucion = computed(() =>
    [...(props.mediciones ?? [])].reverse().map((m) => m.puntaje),
);

// --- HU-082: pedir una nueva fecha -------------------------------------------
const pidiendo = ref(false);

function pedirNueva(): void {
    if (!p.value) {
        return;
    }

    router.post(
        rutas.responder.solicitar(p.value.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (pidiendo.value = true),
            onFinish: () => (pidiendo.value = false),
        },
    );
}

// Cuenta regresiva: naranja con 3 días o menos, roja el último día o vencida.
const urgencia = computed(() => {
    const d = p.value?.dias_restantes;

    if (p.value?.estado === 'vencida' || d === 0) {
        return 'roja';
    }

    return d !== null && d !== undefined && d <= 3 ? 'naranja' : 'normal';
});

const hoyTexto = new Date().toLocaleDateString('es-CO', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});
const hoy = hoyTexto.charAt(0).toUpperCase() + hoyTexto.slice(1);

const pasos = [
    {
        icono: ClipboardPen,
        titulo: 'Respondes',
        texto: 'Preguntas sobre las áreas del marketing digital de tu negocio. Puedes pausar cuando quieras.',
    },
    {
        icono: Sparkles,
        titulo: 'Analizamos',
        texto: 'El sistema calcula tu puntaje y la IA analiza tus respuestas, categoría por categoría.',
    },
    {
        icono: ChartColumnIncreasing,
        titulo: 'Recibes tu resultado',
        texto: 'Tu puntaje de 0 a 100, tu nivel y un plan de acción con pasos, en pantalla y en PDF.',
    },
];

// A5.4 · En "Ver como" no se responde ni se piden fechas (solo lectura).
const enVerComo = computed(() => !!usePage().props.verComo);
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-col gap-5 p-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-semibold">Hola, {{ nombre }}</h1>
                <p class="mt-1 text-sm text-tinta-suave">{{ subtitulo }}</p>
            </div>
            <p
                class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-sm text-tinta-suave ring-1 ring-linea"
            >
                <CalendarDays class="size-4" aria-hidden="true" />
                {{ hoy }}
            </p>
        </header>

        <p
            v-if="sinEmpresa"
            class="rounded-2xl border border-dashed border-linea-fuerte bg-white p-6 text-sm text-tinta-suave"
        >
            Tu cuenta todavía no está vinculada a una empresa. Escríbele al
            equipo de NuevasTIC.
        </p>

        <!-- Medición pendiente -->
        <section
            v-if="p"
            :class="
                cn(
                    'grid overflow-hidden rounded-2xl border bg-white shadow-sm md:grid-cols-[minmax(0,1fr)_17rem]',
                    p.estado === 'vencida'
                        ? 'border-aviso/50'
                        : 'border-marca/30',
                )
            "
        >
            <div
                :class="
                    cn(
                        'flex flex-col gap-4 p-6 sm:flex-row sm:items-center',
                        p.estado === 'vencida'
                            ? 'bg-linear-to-br from-nivel-critico-suave/70 to-white'
                            : 'bg-linear-to-br from-marca-suave/80 to-white',
                    )
                "
            >
                <!-- Anillo del avance o ícono según el estado -->
                <div class="relative size-28 shrink-0" aria-hidden="true">
                    <template
                        v-if="p.estado === 'en_curso' || p.estado === 'vencida'"
                    >
                        <svg viewBox="0 0 120 120" class="size-28">
                            <circle
                                cx="60"
                                cy="60"
                                r="50"
                                fill="white"
                                stroke="var(--color-lienzo-oscuro)"
                                stroke-width="11"
                            />
                            <circle
                                cx="60"
                                cy="60"
                                r="50"
                                fill="none"
                                :stroke="
                                    p.estado === 'vencida'
                                        ? 'var(--color-tinta-suave)'
                                        : 'var(--color-marca)'
                                "
                                stroke-width="11"
                                stroke-linecap="round"
                                :stroke-dasharray="2 * Math.PI * 50"
                                :stroke-dashoffset="
                                    2 *
                                    Math.PI *
                                    50 *
                                    (1 - Math.max(3, p.avance.porcentaje) / 100)
                                "
                                transform="rotate(-90 60 60)"
                            />
                        </svg>
                        <span
                            class="absolute inset-0 flex flex-col items-center justify-center"
                        >
                            <span class="font-mono text-2xl font-semibold"
                                >{{ p.avance.porcentaje }}%</span
                            >
                            <span class="text-[11px] text-tinta-suave"
                                >respondido</span
                            >
                        </span>
                    </template>
                    <span
                        v-else
                        :class="
                            cn(
                                'flex size-28 items-center justify-center rounded-full',
                                p.estado === 'enviada'
                                    ? 'bg-white text-marca ring-8 ring-marca-suave'
                                    : 'bg-marca text-white ring-8 ring-marca-suave',
                            )
                        "
                    >
                        <LoaderCircle
                            v-if="p.estado === 'enviada'"
                            class="size-12 motion-safe:animate-spin"
                        />
                        <Rocket v-else class="size-12" />
                    </span>
                </div>

                <div class="flex min-w-0 flex-1 flex-col gap-2">
                    <p class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="font-semibold"
                            >Medición {{ p.numero }}</span
                        >
                        <EtiquetaEstado :estado="p.estado" />
                        <span
                            v-if="p.estado === 'no_iniciada'"
                            class="text-tinta-suave"
                        >
                            {{
                                p.primera
                                    ? 'Tu primer diagnóstico'
                                    : 'Nueva medición disponible'
                            }}
                        </span>
                        <span
                            v-if="p.estado === 'vencida' && p.solicitud_abierta"
                            class="rounded bg-alerta-suave px-2 py-0.5 font-medium text-alerta"
                        >
                            Nueva medición solicitada
                        </span>
                    </p>

                    <h2 class="text-2xl leading-tight font-semibold">
                        <template v-if="p.estado === 'no_iniciada'">{{
                            p.primera
                                ? 'Descubre en qué punto está tu empresa'
                                : 'Es momento de medir cómo vas'
                        }}</template>
                        <template v-else-if="p.estado === 'en_curso'"
                            >Continúa donde lo dejaste</template
                        >
                        <template v-else-if="p.estado === 'vencida'"
                            >La fecha límite de esta medición ya pasó</template
                        >
                        <template v-else
                            >Estamos analizando tus respuestas</template
                        >
                    </h2>

                    <p
                        v-if="p.estado === 'no_iniciada' && p.primera"
                        class="text-sm text-tinta-suave"
                    >
                        Este primer resultado será tu punto de partida. En los
                        próximos diagnósticos podrás ver cómo mejoras.
                    </p>
                    <p
                        v-else-if="
                            p.estado === 'en_curso' && p.avance.categoria
                        "
                        class="text-sm text-tinta-suave"
                    >
                        Vas en
                        <strong class="text-tinta">{{
                            p.avance.categoria
                        }}</strong>
                        (categoría {{ p.avance.categoria_numero }} de
                        {{ p.categorias }}) · {{ p.avance.respondidas }} de
                        {{ p.avance.total }} preguntas.
                    </p>
                    <p
                        v-else-if="p.estado === 'vencida'"
                        class="text-sm text-tinta-suave"
                    >
                        <template v-if="p.solicitud_abierta">
                            Ya le pediste al equipo de NuevasTIC una nueva
                            fecha: te escribirán cuando la asignen.
                        </template>
                        <template v-else>
                            Respondiste el {{ p.avance.porcentaje }}%. Pide una
                            nueva fecha al equipo de NuevasTIC.
                        </template>
                    </p>
                    <p
                        v-else-if="p.estado === 'enviada'"
                        class="text-sm text-tinta-suave"
                    >
                        Suele tardar 1 o 2 minutos; cuando esté listo te
                        avisamos por correo.
                    </p>

                    <p
                        v-if="p.mensaje && p.estado !== 'enviada'"
                        class="rounded-lg border-l-4 border-marca bg-white/80 px-4 py-2.5 text-sm"
                    >
                        <strong>Mensaje del equipo de NuevasTIC:</strong>
                        {{ p.mensaje }}
                    </p>

                    <ul
                        v-if="
                            p.estado === 'no_iniciada' ||
                            p.estado === 'en_curso'
                        "
                        class="mt-1 flex flex-wrap gap-2 text-xs text-tinta-suave"
                    >
                        <li
                            class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 ring-1 ring-linea"
                        >
                            <Layers class="size-3.5" aria-hidden="true" />
                            {{
                                plural(p.categorias, 'categoría', 'categorías')
                            }}
                            ·
                            {{ plural(p.preguntas, 'pregunta', 'preguntas') }}
                        </li>
                        <li
                            class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 ring-1 ring-linea"
                        >
                            <Save class="size-3.5" aria-hidden="true" />
                            {{
                                p.estado === 'en_curso'
                                    ? 'Tu avance se guarda solo'
                                    : 'Puedes pausar y seguir después'
                            }}
                        </li>
                        <li
                            v-if="p.estado === 'en_curso'"
                            class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 ring-1 ring-linea"
                        >
                            <ArrowLeftRight
                                class="size-3.5"
                                aria-hidden="true"
                            />
                            Muévete entre categorías
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Fecha límite y acción -->
            <div
                class="flex flex-col justify-center gap-4 border-t border-linea p-6 md:border-t-0 md:border-l"
            >
                <div v-if="p.fecha_limite">
                    <p class="text-xs font-medium text-tinta-suave">
                        Fecha límite
                    </p>
                    <p
                        v-if="
                            p.estado !== 'vencida' &&
                            p.dias_restantes !== null &&
                            p.dias_restantes !== undefined
                        "
                        :class="
                            cn(
                                'mt-1 font-mono text-4xl leading-none font-semibold',
                                urgencia === 'roja'
                                    ? 'text-aviso'
                                    : urgencia === 'naranja'
                                      ? 'text-alerta'
                                      : 'text-marca',
                            )
                        "
                    >
                        {{ p.dias_restantes === 0 ? 'Hoy' : p.dias_restantes }}
                        <span
                            v-if="p.dias_restantes > 0"
                            class="font-sans text-base font-medium"
                            >{{ p.dias_restantes === 1 ? 'día' : 'días' }}</span
                        >
                    </p>
                    <p
                        v-else-if="p.estado === 'vencida'"
                        class="mt-1 flex items-center gap-1.5 text-lg font-semibold text-aviso"
                    >
                        <CircleAlert class="size-5" aria-hidden="true" />
                        Vencida
                    </p>
                    <p class="mt-1 text-sm text-tinta-suave">
                        {{
                            p.dias_restantes === 0
                                ? 'Vence hoy a las 11:59 p. m.'
                                : fechaLarga(p.fecha_limite)
                        }}
                    </p>
                </div>

                <!-- Colaborador sin "Responder el diagnóstico" (E12.1). -->
                <p
                    v-if="!puede('diagnostico.responder')"
                    class="text-xs text-tinta-suave"
                >
                    Tu empresa no te dio acceso para responder el diagnóstico.
                </p>
                <Boton
                    v-else-if="
                        p.estado === 'no_iniciada' || p.estado === 'en_curso'
                    "
                    class="w-full"
                    :href="rutas.responder.mostrar(p.id)"
                    :disabled="enVerComo"
                >
                    {{
                        p.estado === 'en_curso'
                            ? 'Continuar diagnóstico'
                            : 'Iniciar diagnóstico'
                    }}
                </Boton>
                <template v-if="puede('diagnostico.responder')">
                    <p
                        v-if="enVerComo && p.estado !== 'enviada'"
                        class="text-center text-xs text-tinta-suave"
                    >
                        No disponible en Ver como
                    </p>
                    <Boton
                        v-else-if="p.estado === 'enviada'"
                        class="w-full"
                        :href="rutas.responder.enviado(p.id)"
                    >
                        Ver el avance del análisis
                    </Boton>
                    <!-- Solo la vencida pide una nueva fecha (HU-082). -->
                    <template v-else-if="p.estado === 'vencida'">
                        <Boton
                            v-if="!p.solicitud_abierta"
                            class="w-full"
                            :cargando="pidiendo"
                            :disabled="enVerComo"
                            @click="pedirNueva"
                        >
                            Pedir una nueva fecha
                        </Boton>
                        <a
                            :href="`mailto:${soporte}`"
                            class="flex items-center justify-center gap-1.5 text-xs text-tinta-suave hover:text-marca"
                        >
                            <Mail class="size-3.5" aria-hidden="true" />
                            {{ soporte }}
                        </a>
                    </template>
                </template>
            </div>
        </section>

        <!-- Sin pendiente -->
        <section
            v-else-if="!sinEmpresa"
            class="flex flex-wrap items-center gap-5 rounded-2xl border border-nivel-sigue/30 bg-linear-to-br from-exito-suave/70 to-white p-6"
        >
            <CircleCheck
                class="size-12 shrink-0 text-nivel-sigue"
                aria-hidden="true"
            />
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-semibold">
                    No tienes diagnósticos pendientes
                </h2>
                <p class="text-sm text-tinta-suave">
                    Cuando el equipo de NuevasTIC te asigne una nueva medición,
                    aparecerá aquí.<template v-if="ultima">
                        Mientras tanto, trabaja en el plan de acción de tu
                        medición {{ ultima.numero }}.</template
                    >
                </p>
            </div>
            <Boton
                v-if="ultima"
                variante="secundario"
                :href="rutas.resultados.ver(ultima.id)"
            >
                Ver resultado de la medición {{ ultima.numero }}
            </Boton>
        </section>

        <!-- Primera vez: cómo funciona -->
        <template v-if="!ultima && !sinEmpresa">
            <section class="rounded-2xl border border-linea bg-white p-6">
                <h2 class="text-lg font-semibold">Cómo funciona</h2>
                <ol class="relative mt-5 grid gap-6 md:grid-cols-3">
                    <!-- Línea que une los pasos -->
                    <span
                        class="absolute top-6 right-[16%] left-[16%] hidden border-t-2 border-dashed border-marca/25 md:block"
                        aria-hidden="true"
                    />
                    <li
                        v-for="(paso, i) in pasos"
                        :key="paso.titulo"
                        class="relative flex flex-col items-center text-center"
                    >
                        <span
                            class="flex size-12 items-center justify-center rounded-full bg-marca text-white ring-8 ring-white"
                        >
                            <component
                                :is="paso.icono"
                                class="size-6"
                                aria-hidden="true"
                            />
                        </span>
                        <p class="mt-3 font-semibold">
                            {{ i + 1 }}. {{ paso.titulo }}
                        </p>
                        <p class="mt-1 max-w-xs text-sm text-tinta-suave">
                            {{ paso.texto }}
                        </p>
                    </li>
                </ol>
            </section>
            <section
                v-if="puede('resultados.ver')"
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-linea-fuerte bg-white/60 p-8 text-center"
            >
                <ChartColumnIncreasing
                    class="size-10 text-marca/50"
                    aria-hidden="true"
                />
                <p class="font-semibold">Aquí verás tus resultados</p>
                <p class="max-w-md text-sm text-tinta-suave">
                    Tu puntaje, tu nivel y tu evolución aparecerán cuando
                    completes el primer diagnóstico.
                </p>
            </section>
        </template>

        <!-- Último resultado -->
        <div
            v-else-if="ultima"
            class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]"
        >
            <section class="rounded-2xl border border-linea bg-white p-5">
                <h2 class="text-lg font-semibold">
                    {{
                        realizadas === 1
                            ? 'Tu primera medición'
                            : 'Tu medición más reciente'
                    }}
                </h2>
                <p class="text-xs text-tinta-suave">
                    Medición {{ ultima.numero }}
                    <template v-if="ultima.fecha">
                        · respondida el {{ diaYMes(fechaLocal(ultima.fecha)) }}
                    </template>
                </p>
                <div class="mt-4 flex items-center gap-5">
                    <AnilloPuntaje
                        :puntaje="ultima.puntaje"
                        :nivel="ultima.nivel"
                        :tamano="112"
                    />
                    <div
                        class="flex min-w-0 flex-col items-start gap-2 text-sm"
                    >
                        <EtiquetaNivel :nivel="ultima.nivel" />
                        <span
                            v-if="ultima.variacion !== null"
                            :class="
                                cn(
                                    'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    ultima.variacion > 0
                                        ? 'bg-nivel-sigue-suave text-nivel-sigue-texto'
                                        : ultima.variacion < 0
                                          ? 'bg-nivel-critico-suave text-nivel-critico-texto'
                                          : 'bg-lienzo-oscuro text-tinta-suave',
                                )
                            "
                        >
                            {{
                                ultima.variacion > 0
                                    ? `▲ +${ultima.variacion}`
                                    : ultima.variacion < 0
                                      ? `▼ ${ultima.variacion}`
                                      : '= 0'
                            }}
                            vs. medición anterior
                        </span>
                        <span v-else class="text-xs text-tinta-suave">
                            Primera medición: tu punto de partida
                        </span>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <Boton :href="rutas.resultados.ver(ultima.id)">
                        Ver resultado completo
                    </Boton>
                    <Boton
                        v-if="puede('resultados.pdf')"
                        variante="secundario"
                        archivo
                        :href="rutas.resultados.pdf(ultima.id)"
                    >
                        <FileDown class="size-4" aria-hidden="true" />
                        Informe PDF
                    </Boton>
                </div>
                <div class="mt-5 border-t border-linea pt-3">
                    <p class="text-xs font-medium text-tinta-suave">
                        Evolución
                    </p>
                    <MiniEvolucion
                        v-if="evolucion.length > 1"
                        :puntajes="evolucion"
                        class="mt-2"
                    />
                    <p
                        v-else
                        class="mt-2 rounded-lg bg-lienzo px-3 py-2 text-xs text-tinta-suave"
                    >
                        La variación y la gráfica de evolución aparecen desde tu
                        segunda medición.
                    </p>
                </div>
            </section>

            <section
                v-if="p && areas && areas.length"
                class="rounded-2xl border border-linea bg-white p-5"
            >
                <div
                    class="flex flex-wrap items-baseline justify-between gap-2"
                >
                    <h2 class="text-lg font-semibold">
                        Tus {{ areas.length }} áreas a trabajar
                    </h2>
                    <span class="text-xs text-tinta-suave"
                        >Las de menor puntaje en tu último diagnóstico</span
                    >
                </div>
                <ul class="mt-3 flex flex-col gap-3">
                    <li
                        v-for="(a, i) in areas"
                        :key="a.nombre"
                        :class="
                            cn(
                                'rounded-xl border border-l-4 border-linea px-4 py-3',
                                niveles[a.nivel].borde,
                            )
                        "
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold">
                                    <span class="mr-1 text-tinta-suave"
                                        >{{ i + 1 }}.</span
                                    >{{ a.nombre }}
                                </p>
                                <p
                                    v-if="a.recomendacion"
                                    class="mt-0.5 text-sm text-tinta-suave"
                                >
                                    {{ a.recomendacion }}
                                </p>
                            </div>
                            <span class="flex shrink-0 items-center gap-2">
                                <strong class="font-mono text-xl">{{
                                    a.puntaje
                                }}</strong>
                                <EtiquetaNivel :nivel="a.nivel" />
                            </span>
                        </div>
                        <span
                            class="mt-2 block h-2 overflow-hidden rounded-full bg-lienzo-oscuro"
                        >
                            <span
                                :class="
                                    cn(
                                        'block h-full rounded-full',
                                        niveles[a.nivel].barra,
                                    )
                                "
                                :style="{ width: `${Math.max(2, a.puntaje)}%` }"
                            />
                        </span>
                    </li>
                </ul>
                <p class="mt-3 text-xs text-tinta-suave">
                    Tu próximo diagnóstico mostrará si estas áreas mejoraron.
                </p>
            </section>

            <section
                v-else
                class="flex flex-col rounded-2xl border border-linea bg-white"
            >
                <div
                    class="flex items-baseline justify-between gap-2 px-5 pt-4 pb-2"
                >
                    <h2 class="text-lg font-semibold">Mis mediciones</h2>
                    <Link
                        :href="rutas.historial.mio()"
                        class="inline-flex items-center gap-0.5 text-sm font-medium text-marca hover:text-marca-hover"
                    >
                        Ver historial completo
                        <ChevronRight class="size-4" aria-hidden="true" />
                    </Link>
                </div>
                <ol class="flex flex-col px-3 pb-3">
                    <li
                        v-for="m in mediciones"
                        :key="m.id"
                        class="border-b border-linea last:border-b-0"
                    >
                        <Link
                            :href="rutas.resultados.ver(m.id)"
                            class="flex items-center gap-4 rounded-xl px-2 py-2.5 hover:bg-lienzo"
                        >
                            <AnilloPuntaje
                                :puntaje="m.puntaje"
                                :nivel="m.nivel"
                                :tamano="48"
                            />
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold"
                                    >Medición {{ m.numero }}</span
                                >
                                <span
                                    class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-tinta-suave"
                                >
                                    <span v-if="m.fecha">{{
                                        diaYMes(fechaLocal(m.fecha))
                                    }}</span>
                                    <EtiquetaNivel :nivel="m.nivel" />
                                    <span
                                        v-if="m.variacion !== null"
                                        :class="
                                            cn(
                                                'rounded-full px-2 py-0.5 font-medium',
                                                m.variacion > 0
                                                    ? 'bg-nivel-sigue-suave text-nivel-sigue-texto'
                                                    : m.variacion < 0
                                                      ? 'bg-nivel-critico-suave text-nivel-critico-texto'
                                                      : 'bg-lienzo-oscuro',
                                            )
                                        "
                                        >{{
                                            m.variacion > 0
                                                ? `▲ +${m.variacion}`
                                                : m.variacion < 0
                                                  ? `▼ ${m.variacion}`
                                                  : '= 0'
                                        }}</span
                                    >
                                </span>
                            </span>
                            <ChevronRight
                                class="size-5 shrink-0 text-tinta-suave"
                                aria-hidden="true"
                            />
                        </Link>
                    </li>
                </ol>
            </section>
        </div>
    </div>
</template>
