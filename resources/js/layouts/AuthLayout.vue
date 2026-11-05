<script setup lang="ts">
/**
 * Estructura de las pantallas de acceso (L1–L4).
 *
 * - A la izquierda (42 %), el panel oscuro: la marca, el mensaje del
 *   sistema, los tres pasos (responder, recibir el resultado, medir otra
 *   vez) con ícono.
 *   El panel es `fixed` y ocupa todo el alto: solo se desplaza el
 *   formulario (el registro es largo). La barra de desplazamiento no se
 *   ve (clase `sin-barra` en <html> mientras la pantalla está abierta),
 *   pero la página baja igual con la rueda, el dedo o el teclado.
 * - A la derecha, el formulario en una tarjeta blanca sobre el fondo gris
 *   cálido, con el título y la descripción que manda cada página.
 * - En pantallas pequeñas el panel se reduce a la marca arriba.
 */
import { ClipboardList, LifeBuoy, Sparkles, TrendingUp } from '@lucide/vue';
import { onBeforeUnmount, onMounted } from 'vue';
import Logo from '@/components/marca/Logo.vue';
import { useCorreoSoporte } from '@/lib/soporte';

const soporte = useCorreoSoporte();

const {
    title = '',
    description = '',
    ancho = 'sm',
} = defineProps<{
    title?: string;
    description?: string;
    /** "lg" para el registro (L2), que tiene el formulario en dos columnas. */
    ancho?: 'sm' | 'lg';
}>();

const pasos = [
    {
        icono: ClipboardList,
        titulo: 'Responde a tu ritmo',
        texto: 'Un diagnóstico por categorías; tu avance se guarda solo.',
    },
    {
        icono: Sparkles,
        titulo: 'Recibe tu resultado',
        texto: 'Puntaje, nivel y recomendaciones al terminar, con informe en PDF.',
    },
    {
        icono: TrendingUp,
        titulo: 'Mide tu evolución',
        texto: 'Repite la medición y compara cómo avanzas.',
    },
];

onMounted(() => document.documentElement.classList.add('sin-barra'));
onBeforeUnmount(() => document.documentElement.classList.remove('sin-barra'));
</script>

<template>
    <div class="flex min-h-screen bg-lienzo text-tinta">
        <aside
            class="fixed inset-y-0 left-0 hidden w-[42%] overflow-hidden bg-menu text-menu-texto lg:block"
        >
            <!-- Fondo decorativo -->
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0"
            >
                <div
                    class="absolute -top-32 -right-24 size-96 rounded-full bg-marca/60 blur-3xl"
                />
                <div
                    class="absolute -bottom-40 -left-20 size-96 rounded-full bg-menu-marca/20 blur-3xl"
                />
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgb(255_255_255/0.06)_1px,transparent_0)] bg-size-[22px_22px]"
                />
            </div>

            <div
                class="sin-barra relative flex h-full flex-col justify-between gap-10 overflow-y-auto px-12 py-12 xl:px-20"
            >
                <p
                    class="flex items-center gap-3 text-base font-semibold text-white"
                >
                    <Logo class="size-10" />
                    <span class="text-xl tracking-wide">CAPTTER</span>
                </p>

                <div class="max-w-lg">
                    <p
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white ring-1 ring-white/15"
                    >
                        <Sparkles class="size-3.5" aria-hidden="true" />
                        Diagnóstico de marketing digital
                    </p>
                    <p
                        class="mt-4 text-3xl leading-snug font-semibold text-white xl:text-4xl xl:leading-tight"
                    >
                        Conoce en qué punto está el marketing digital de tu
                        empresa.
                    </p>

                    <ul class="mt-8 flex flex-col gap-4">
                        <li
                            v-for="p in pasos"
                            :key="p.titulo"
                            class="flex items-start gap-3"
                        >
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white ring-1 ring-white/15"
                                aria-hidden="true"
                            >
                                <component :is="p.icono" class="size-5" />
                            </span>
                            <span>
                                <span class="block font-semibold text-white">{{
                                    p.titulo
                                }}</span>
                                <span class="block text-sm">{{ p.texto }}</span>
                            </span>
                        </li>
                    </ul>
                </div>

                <p class="flex items-center gap-2 text-xs">
                    <LifeBuoy class="size-4 shrink-0" aria-hidden="true" />
                    <span>
                        ¿Necesitas ayuda? Escribe al equipo de NuevasTIC:
                        <a
                            :href="`mailto:${soporte}`"
                            class="text-white underline underline-offset-2"
                        >
                            {{ soporte }}
                        </a>
                    </span>
                </p>
            </div>
        </aside>

        <main
            class="flex min-h-screen flex-1 items-center justify-center px-4 py-8 sm:px-6 lg:ml-[42%]"
        >
            <div :class="['w-full', ancho === 'lg' ? 'max-w-2xl' : 'max-w-md']">
                <p
                    class="mb-6 flex items-center justify-center gap-2.5 text-base font-semibold lg:hidden"
                >
                    <Logo class="size-8" />
                    <span class="text-xl tracking-wide">CAPTTER</span>
                </p>

                <div
                    class="rounded-2xl border border-linea bg-white p-5 shadow-[0_12px_40px_-16px_rgb(30_41_59/0.18)] sm:p-8"
                >
                    <header v-if="title" class="mb-6">
                        <h1 class="text-2xl font-semibold">{{ title }}</h1>
                        <p
                            v-if="description"
                            class="mt-1 text-sm text-tinta-suave"
                        >
                            {{ description }}
                        </p>
                    </header>

                    <slot />
                </div>

                <p
                    class="mt-6 flex items-center justify-center gap-1.5 text-center text-xs text-tinta-suave lg:hidden"
                >
                    <LifeBuoy class="size-3.5 shrink-0" aria-hidden="true" />
                    <span>
                        ¿Necesitas ayuda? Escribe a
                        <a :href="`mailto:${soporte}`" class="underline">
                            {{ soporte }}
                        </a>
                    </span>
                </p>
            </div>
        </main>
    </div>
</template>
