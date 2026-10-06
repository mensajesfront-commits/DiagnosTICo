<script setup lang="ts">
/**
 * Estructura de las pantallas de acceso (L1–L4): a la izquierda (42%) el panel
 * oscuro con el mensaje del sistema; a la derecha el formulario sobre el
 * fondo gris cálido. En pantallas pequeñas el panel se reduce a la marca.
 */
import Logo from '@/components/marca/Logo.vue';

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
</script>

<template>
    <div class="flex min-h-screen bg-lienzo text-tinta">
        <aside
            class="hidden w-[42%] shrink-0 flex-col justify-between bg-menu px-12 py-12 text-menu-texto lg:flex xl:px-20"
        >
            <p
                class="flex items-center gap-3 text-base font-semibold text-white"
            >
                <Logo class="size-10" />
                <span class="text-xl">Captter</span>
            </p>

            <div class="max-w-lg">
                <p class="text-3xl leading-snug font-semibold text-white">
                    Conoce en qué punto está el marketing digital de tu empresa.
                </p>
                <ul class="mt-6 space-y-2.5 text-base">
                    <li>
                        · Responde un diagnóstico por categorías, a tu ritmo.
                    </li>
                    <li>
                        · Recibe tu resultado y recomendaciones al terminar.
                    </li>
                    <li>· Repite la medición y mira cómo evolucionas.</li>
                </ul>
            </div>

            <p class="text-xs">
                ¿Necesitas ayuda? Escribe al equipo de NuevasTIC:
                <a
                    href="mailto:soporte@nuevastic.co"
                    class="text-white underline underline-offset-2"
                >
                    soporte@nuevastic.co
                </a>
            </p>
        </aside>

        <main class="flex flex-1 items-center justify-center px-4 py-10">
            <div :class="['w-full', ancho === 'lg' ? 'max-w-xl' : 'max-w-sm']">
                <p
                    class="mb-8 flex items-center gap-2.5 text-base font-semibold lg:hidden"
                >
                    <Logo class="size-8" />
                    <span class="text-xl">Captter</span>
                </p>

                <header v-if="title" class="mb-5">
                    <h1 class="text-2xl font-semibold">{{ title }}</h1>
                    <p v-if="description" class="mt-1 text-sm text-tinta-suave">
                        {{ description }}
                    </p>
                </header>

                <slot />

                <p class="mt-10 text-xs text-tinta-suave lg:hidden">
                    ¿Necesitas ayuda? Escribe a
                    <a href="mailto:soporte@nuevastic.co" class="underline">
                        soporte@nuevastic.co
                    </a>
                </p>
            </div>
        </main>
    </div>
</template>
