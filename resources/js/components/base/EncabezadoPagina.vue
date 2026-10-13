<script setup lang="ts">
/**
 * Título de la pantalla con su descripción y los botones de la derecha
 * ("Diagnósticos" + "Catálogo de categorías" + "+ Crear diagnóstico").
 *
 * `volver` dibuja arriba del título una flecha para regresar a la pantalla
 * anterior ("← Diagnósticos"), en las pantallas que dependen de otra.
 */
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';

defineProps<{
    titulo: string;
    descripcion?: string;
    volver?: {
        href: NonNullable<InertiaLinkProps['href']>;
        texto: string;
    };
}>();
</script>

<template>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <Link
                v-if="volver"
                :href="volver.href"
                class="mb-2 inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-tinta-suave hover:text-marca focus-visible:ring-2 focus-visible:ring-marca/40 focus-visible:outline-none"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                {{ volver.texto }}
            </Link>
            <h1 class="text-2xl font-semibold text-tinta">{{ titulo }}</h1>
            <p v-if="descripcion" class="mt-1 text-sm text-tinta-suave">
                {{ descripcion }}
            </p>
        </div>
        <div v-if="$slots.default" class="flex flex-wrap items-center gap-3">
            <slot />
        </div>
    </header>
</template>
