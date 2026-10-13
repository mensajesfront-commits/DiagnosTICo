<script setup lang="ts">
/**
 * E4 · Diagnóstico enviado: espera del análisis (HU-063).
 *
 * Muestra los dos pasos: puntajes de opción única y múltiple, y análisis
 * por categoría ("3 de 10 categorías"). Se actualiza sola cada 5 segundos y,
 * al publicarse el resultado, el servidor la lleva a E6 (CA-002). Se puede
 * salir: las respuestas ya están guardadas y, cuando esté listo, llega un
 * correo.
 *
 * Si alguna categoría falló tras los 3 intentos (RN-021), avisa y deja
 * reintentar solo esas (CA-004); el Administrador ya recibió un correo.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Aviso from '@/components/base/Aviso.vue';
import Boton from '@/components/base/Boton.vue';
import BarraResponder from '@/components/responder/BarraResponder.vue';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import { empresa as inicioEmpresa } from '@/routes/inicio';
import type { CabeceraMedicion } from '@/types/responder';

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
    };
}>();

const listo = computed(() => props.estado === 'terminada');
const fallo = computed(() => props.analisis.fallidas.length > 0);

let reloj: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    reloj = setInterval(() => {
        // Sin "only": si ya terminó, el servidor redirige al resultado.
        if (!listo.value && !fallo.value) {
            router.reload();
        }
    }, 5000);
});

onBeforeUnmount(() => reloj && clearInterval(reloj));

const porcentaje = computed(() =>
    props.analisis.total
        ? Math.round((props.analisis.terminadas * 100) / props.analisis.total)
        : 0,
);

type Paso = {
    texto: string;
    estado: 'listo' | 'curso' | 'espera' | 'error';
    detalle: string;
};

const pasos = computed<Paso[]>(() => [
    {
        texto: 'Calculando puntajes de opción única y múltiple',
        estado: props.analisis.puntajes_listos ? 'listo' : 'curso',
        detalle: props.analisis.puntajes_listos ? 'Listo' : 'En curso…',
    },
    {
        texto: 'Analizando cada categoría: observaciones y recomendaciones',
        estado: fallo.value
            ? 'error'
            : props.analisis.terminadas === props.analisis.total &&
                props.analisis.total > 0
              ? 'listo'
              : props.analisis.puntajes_listos
                ? 'curso'
                : 'espera',
        detalle: `${props.analisis.terminadas} de ${props.analisis.total}`,
    },
]);

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
        class="flex min-h-[calc(100dvh-57px)] items-center justify-center px-6"
    >
        <section
            class="w-full max-w-lg rounded-xl border border-linea bg-white p-8"
        >
            <p class="font-mono text-xs text-tinta-suave">
                DIAGNÓSTICO ENVIADO · MEDICIÓN {{ medicion.numero }}
            </p>
            <h1 class="mt-1 text-2xl font-semibold">
                {{
                    listo
                        ? 'Tu resultado está listo'
                        : 'Estamos analizando tus respuestas'
                }}
            </h1>
            <p class="mt-1 text-sm text-tinta-suave">
                {{
                    listo
                        ? 'Ya puedes ver tu puntaje, tu nivel y tus recomendaciones.'
                        : 'Suele tardar menos de un minuto; si tarda más, te avisamos por correo.'
                }}
            </p>
            <div
                v-if="!listo"
                class="mt-5 h-1.5 overflow-hidden rounded-full bg-lienzo-oscuro"
                role="progressbar"
                :aria-valuenow="porcentaje"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="Avance del análisis"
            >
                <div
                    :class="
                        cn(
                            'h-1.5 rounded-full bg-marca transition-all',
                            !fallo && 'animate-pulse',
                        )
                    "
                    :style="{ width: `${Math.max(8, porcentaje)}%` }"
                />
            </div>
            <ul class="mt-5 flex flex-col gap-4 text-sm">
                <li
                    v-for="paso in pasos"
                    :key="paso.texto"
                    class="flex items-center gap-3"
                >
                    <span
                        :class="
                            cn(
                                'flex size-6 shrink-0 items-center justify-center rounded-full text-xs',
                                paso.estado === 'listo' &&
                                    'bg-exito-suave text-exito',
                                paso.estado === 'curso' &&
                                    'bg-marca text-white',
                                paso.estado === 'espera' &&
                                    'bg-lienzo-oscuro text-tinta-suave',
                                paso.estado === 'error' &&
                                    'bg-nivel-critico-suave text-aviso',
                            )
                        "
                        aria-hidden="true"
                    >
                        {{
                            paso.estado === 'listo'
                                ? '✓'
                                : paso.estado === 'error'
                                  ? '!'
                                  : '•'
                        }}
                    </span>
                    <span class="flex-1">{{ paso.texto }}</span>
                    <span class="font-mono text-xs text-tinta-suave">
                        {{ paso.detalle }}
                    </span>
                </li>
            </ul>
            <Aviso v-if="fallo" tono="error" class="mt-5">
                <p>
                    No pudimos terminar el análisis de algunas categorías:
                    {{ analisis.fallidas.join(', ') }}. Ya le avisamos al equipo
                    de NuevasTIC.
                </p>
                <Boton
                    tamano="sm"
                    class="mt-2"
                    :cargando="reintentando"
                    @click="reintentar"
                >
                    Reintentar esas categorías
                </Boton>
            </Aviso>
            <p class="mt-6 border-t border-linea pt-4 text-xs text-tinta-suave">
                Puedes cerrar esta ventana: tus respuestas ya están guardadas.
                <Link :href="inicioEmpresa()" class="text-marca underline">
                    Ir a mi inicio
                </Link>
            </p>
        </section>
    </main>
</template>
