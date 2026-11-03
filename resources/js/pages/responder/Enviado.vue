<script setup lang="ts">
/**
 * E4 · Diagnóstico enviado: espera del análisis (HU-063).
 *
 * Un anillo con el porcentaje analizado, la lista de categorías con su
 * estado en vivo (lista, analizando, en espera o con error) y datos que
 * rotan sobre lo que hace la IA mientras se espera. Se actualiza sola cada
 * 5 segundos y, al publicarse el resultado, el servidor la lleva a E6
 * (CA-002). Se puede salir: las respuestas ya están guardadas y, cuando esté
 * listo, llega un correo.
 *
 * Si alguna categoría falló tras los 3 intentos (RN-021), avisa y deja
 * reintentar solo esas (CA-004); el Administrador ya recibió un correo.
 *
 * Suele tardar 1 o 2 minutos. Pasados 3 minutos sin terminar, avisa que está
 * tardando más de lo normal y a quién escribir. Las animaciones se apagan si
 * la persona pidió reducir el movimiento (motion-safe).
 */
import { Head, router } from '@inertiajs/vue3';
import {
    CircleAlert,
    CircleCheck,
    Clock,
    LoaderCircle,
    Sparkles,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Aviso from '@/components/base/Aviso.vue';
import Boton from '@/components/base/Boton.vue';
import BarraResponder from '@/components/responder/BarraResponder.vue';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import { empresa as inicioEmpresa } from '@/routes/inicio';
import type { CabeceraMedicion } from '@/types/responder';

type EstadoCategoria = 'terminado' | 'fallido' | 'pendiente';

const props = defineProps<{
    medicion: CabeceraMedicion;
    estado: string;
    enviadaEn: string | null;
    analisis: {
        total: number;
        terminadas: number;
        puntajes_listos: boolean;
        /** Nombres de las categorías que fallaron tras los 3 intentos. */
        fallidas: string[];
        /** Cada categoría en el orden del diagnóstico, con su estado. */
        categorias?: { nombre: string; estado: EstadoCategoria }[];
    };
    /** Correo de soporte, por si tarda más de lo normal. */
    soporte?: string | null;
}>();

/** Pasados estos minutos sin terminar, se avisa que tarda más de lo normal. */
const MINUTOS_NORMALES = 3;
const ahora = ref(Date.now());

const minutosEsperando = computed(() =>
    props.enviadaEn
        ? Math.max(
              0,
              Math.floor(
                  (ahora.value - new Date(props.enviadaEn).getTime()) / 60000,
              ),
          )
        : 0,
);

const esperando = computed(() =>
    minutosEsperando.value < 1
        ? 'Enviado hace menos de un minuto'
        : `Enviado hace ${minutosEsperando.value} ${minutosEsperando.value === 1 ? 'minuto' : 'minutos'}`,
);

const listo = computed(() => props.estado === 'terminada');
const fallo = computed(() => props.analisis.fallidas.length > 0);
const tarda = computed(
    () =>
        !listo.value &&
        !fallo.value &&
        minutosEsperando.value >= MINUTOS_NORMALES,
);

const porcentaje = computed(() =>
    listo.value
        ? 100
        : props.analisis.total
          ? Math.round((props.analisis.terminadas * 100) / props.analisis.total)
          : 0,
);

// Anillo: circunferencia de r = 52 en un viewBox de 120.
const CIRCUNFERENCIA = 2 * Math.PI * 52;
const trazo = computed(
    () => CIRCUNFERENCIA * (1 - Math.max(3, porcentaje.value) / 100),
);

/**
 * Estado que se muestra de cada categoría. La primera pendiente es la que se
 * está analizando; las demás esperan su turno.
 */
const categorias = computed(() => {
    const lista = props.analisis.categorias ?? [];
    const enCurso = lista.findIndex((c) => c.estado === 'pendiente');

    return lista.map((c, i) => ({
        ...c,
        vista:
            c.estado === 'terminado'
                ? ('lista' as const)
                : c.estado === 'fallido'
                  ? ('error' as const)
                  : i === enCurso && props.analisis.puntajes_listos
                    ? ('analizando' as const)
                    : ('espera' as const),
    }));
});

const etiquetas = {
    lista: 'Lista',
    analizando: 'Analizando…',
    espera: 'En espera',
    error: 'No se pudo analizar',
};

// Mientras espera: qué está haciendo la IA, un dato cada 7 segundos.
const DATOS = [
    'La IA lee las respuestas de cada categoría por separado y elige el método que mejor encaja: DOFA, PESTEL, 5 Fuerzas de Porter, VRIO o CAME.',
    'Las preguntas abiertas las califica la IA de 0 a 100 según qué tan definida, medida y mejorada está cada práctica.',
    'Cada recomendación llega como una ficha de acción: pasos, responsable, plazo e indicador para saber si funcionó.',
    'Tu calificación general pondera cada categoría según su importancia en el diagnóstico.',
    'Cuando termine podrás descargar un informe PDF con el diagnóstico, el plan de acción y el cronograma.',
    'Tus resultados quedan guardados en «Mi historial» para compararlos con tus próximas mediciones.',
];
const dato = ref(0);

let reloj: ReturnType<typeof setInterval> | null = null;
let rotacion: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    reloj = setInterval(() => {
        ahora.value = Date.now();

        // Sin "only": si ya terminó, el servidor redirige al resultado.
        if (!listo.value && !fallo.value) {
            router.reload();
        }
    }, 5000);

    rotacion = setInterval(() => {
        dato.value = (dato.value + 1) % DATOS.length;
    }, 7000);
});

onBeforeUnmount(() => {
    if (reloj) {
        clearInterval(reloj);
    }

    if (rotacion) {
        clearInterval(rotacion);
    }
});

const reintentando = ref(false);

function reintentar(): void {
    router.post(
        rutas.responder.reintentar(props.medicion.id),
        {},
        {
            onStart: () => (reintentando.value = true),
            onFinish: () => (reintentando.value = false),
        },
    );
}
</script>

<template>
    <Head title="Diagnóstico enviado" />

    <BarraResponder :con-salir="false" />

    <main
        class="flex min-h-[calc(100dvh-61px)] items-center justify-center px-4 py-8 sm:px-8"
    >
        <section
            class="w-full max-w-3xl overflow-hidden rounded-2xl border border-linea bg-white shadow-sm"
        >
            <!-- Encabezado: anillo de avance y título -->
            <div
                class="relative flex flex-col items-center bg-linear-to-b from-marca-suave/70 to-white px-6 pt-10 pb-6 text-center sm:px-10"
            >
                <p class="font-mono text-sm text-tinta-suave">
                    DIAGNÓSTICO ENVIADO · MEDICIÓN {{ medicion.numero }}
                </p>

                <div class="relative mt-6 size-40" aria-hidden="true">
                    <!-- Halo suave mientras analiza -->
                    <span
                        v-if="!listo && !fallo"
                        class="absolute inset-3 rounded-full bg-marca/15 motion-safe:animate-ping"
                        style="animation-duration: 2.4s"
                    />
                    <svg viewBox="0 0 120 120" class="relative size-40">
                        <circle
                            cx="60"
                            cy="60"
                            r="52"
                            fill="white"
                            stroke="var(--color-lienzo-oscuro)"
                            stroke-width="10"
                        />
                        <circle
                            cx="60"
                            cy="60"
                            r="52"
                            fill="none"
                            :stroke="
                                listo
                                    ? 'var(--color-nivel-sigue)'
                                    : fallo
                                      ? 'var(--color-nivel-critico)'
                                      : 'var(--color-marca)'
                            "
                            stroke-width="10"
                            stroke-linecap="round"
                            :stroke-dasharray="CIRCUNFERENCIA"
                            :stroke-dashoffset="trazo"
                            transform="rotate(-90 60 60)"
                            class="transition-[stroke-dashoffset] duration-700 ease-out"
                        />
                        <!-- Arco que gira mientras trabaja -->
                        <circle
                            v-if="!listo && !fallo"
                            cx="60"
                            cy="60"
                            r="60"
                            fill="none"
                            stroke="var(--color-marca)"
                            stroke-opacity="0.35"
                            stroke-width="2"
                            stroke-dasharray="30 347"
                            stroke-linecap="round"
                            class="origin-center motion-safe:animate-spin"
                            style="animation-duration: 3s"
                        />
                    </svg>
                    <div
                        class="absolute inset-0 flex flex-col items-center justify-center"
                    >
                        <CircleCheck
                            v-if="listo"
                            class="size-14 text-nivel-sigue motion-safe:animate-in motion-safe:zoom-in"
                        />
                        <template v-else>
                            <span
                                class="font-mono text-4xl font-semibold text-tinta"
                                >{{ porcentaje }}%</span
                            >
                            <span class="text-xs text-tinta-suave"
                                >analizado</span
                            >
                        </template>
                    </div>
                </div>
                <span
                    class="sr-only"
                    role="progressbar"
                    :aria-valuenow="porcentaje"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-label="Avance del análisis"
                />

                <h1 class="mt-6 text-3xl font-semibold">
                    {{
                        listo
                            ? 'Tu resultado está listo'
                            : fallo
                              ? 'Faltan algunas categorías'
                              : 'Estamos analizando tus respuestas'
                    }}
                </h1>
                <p class="mt-2 max-w-xl text-base text-tinta-suave">
                    {{
                        listo
                            ? 'Ya puedes ver tu calificación, tu nivel y tu plan de acción.'
                            : 'Suele tardar 1 o 2 minutos. Cuando esté listo te avisamos por correo.'
                    }}
                </p>
                <p
                    v-if="!listo && enviadaEn"
                    class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-sm text-tinta-suave ring-1 ring-linea"
                    aria-live="polite"
                >
                    <Clock class="size-4" aria-hidden="true" />
                    {{ esperando }}
                </p>
                <Boton
                    v-if="listo"
                    class="mt-6"
                    :href="rutas.resultados.ver(medicion.id)"
                >
                    Ver mi resultado
                </Boton>
            </div>

            <div class="px-6 pb-8 sm:px-10">
                <!-- Categorías en vivo -->
                <div v-if="!listo && categorias.length" class="mt-2">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 class="text-base font-semibold">
                            Análisis por categoría
                        </h2>
                        <span class="font-mono text-sm text-tinta-suave"
                            >{{ analisis.terminadas }} de
                            {{ analisis.total }}</span
                        >
                    </div>
                    <p
                        v-if="!analisis.puntajes_listos"
                        class="mt-1 text-sm text-tinta-suave"
                    >
                        Primero calculamos los puntajes de las preguntas de
                        opciones…
                    </p>
                    <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                        <li
                            v-for="c in categorias"
                            :key="c.nombre"
                            :class="
                                cn(
                                    'flex items-center gap-3 rounded-xl border px-4 py-3 transition-colors duration-500',
                                    c.vista === 'lista' &&
                                        'border-nivel-sigue/30 bg-exito-suave/40',
                                    c.vista === 'analizando' &&
                                        'border-marca/40 bg-marca-suave/50',
                                    c.vista === 'espera' &&
                                        'border-linea bg-white',
                                    c.vista === 'error' &&
                                        'border-nivel-critico/40 bg-nivel-critico-suave/50',
                                )
                            "
                        >
                            <CircleCheck
                                v-if="c.vista === 'lista'"
                                class="size-5 shrink-0 text-nivel-sigue"
                                aria-hidden="true"
                            />
                            <LoaderCircle
                                v-else-if="c.vista === 'analizando'"
                                class="size-5 shrink-0 text-marca motion-safe:animate-spin"
                                aria-hidden="true"
                            />
                            <CircleAlert
                                v-else-if="c.vista === 'error'"
                                class="size-5 shrink-0 text-nivel-critico"
                                aria-hidden="true"
                            />
                            <span
                                v-else
                                class="size-5 shrink-0 rounded-full border-2 border-linea-fuerte"
                                aria-hidden="true"
                            />
                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-base font-medium"
                                    >{{ c.nombre }}</span
                                >
                                <span class="block text-xs text-tinta-suave">{{
                                    etiquetas[c.vista]
                                }}</span>
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Mientras esperas -->
                <div
                    v-if="!listo && !fallo"
                    class="mt-6 flex gap-3 rounded-xl bg-lienzo px-4 py-4 sm:px-5"
                >
                    <Sparkles
                        class="mt-0.5 size-5 shrink-0 text-marca"
                        aria-hidden="true"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold">Mientras esperas</p>
                        <Transition
                            mode="out-in"
                            enter-active-class="motion-safe:transition-opacity motion-safe:duration-500"
                            enter-from-class="opacity-0"
                            leave-active-class="motion-safe:transition-opacity motion-safe:duration-300"
                            leave-to-class="opacity-0"
                        >
                            <p :key="dato" class="mt-1 min-h-12 text-base">
                                {{ DATOS[dato] }}
                            </p>
                        </Transition>
                        <div class="mt-2 flex gap-1.5" aria-hidden="true">
                            <span
                                v-for="(_, i) in DATOS"
                                :key="i"
                                :class="
                                    cn(
                                        'h-1.5 rounded-full transition-all duration-500',
                                        i === dato
                                            ? 'w-5 bg-marca'
                                            : 'w-1.5 bg-linea-fuerte',
                                    )
                                "
                            />
                        </div>
                    </div>
                </div>

                <Aviso v-if="tarda" tono="info" class="mt-6 text-base">
                    <p>
                        Está tardando más de lo normal. Tus respuestas están
                        guardadas y el análisis sigue en curso: puedes salir y
                        te avisaremos por correo cuando esté listo.
                    </p>
                    <p v-if="soporte" class="mt-2">
                        Si en un rato no te llega, escríbenos a
                        <a
                            :href="`mailto:${soporte}`"
                            class="font-medium underline"
                            >{{ soporte }}</a
                        >.
                    </p>
                </Aviso>
                <Aviso v-if="fallo" tono="error" class="mt-6 text-base">
                    <p>
                        No pudimos terminar el análisis de algunas categorías:
                        {{ analisis.fallidas.join(', ') }}. Ya le avisamos al
                        equipo de NuevasTIC.
                    </p>
                    <Boton
                        class="mt-3"
                        :cargando="reintentando"
                        @click="reintentar"
                    >
                        Reintentar esas categorías
                    </Boton>
                </Aviso>

                <div
                    class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-linea pt-6"
                >
                    <p class="text-base text-tinta-suave">
                        Puedes cerrar esta ventana: tus respuestas ya están
                        guardadas.
                    </p>
                    <Boton variante="secundario" :href="inicioEmpresa()">
                        Ir a mi inicio
                    </Boton>
                </div>
            </div>
        </section>
    </main>
</template>
