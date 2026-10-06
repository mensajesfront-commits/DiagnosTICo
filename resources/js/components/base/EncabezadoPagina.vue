<script setup lang="ts">
/**
 * Título de la pantalla con su descripción y los botones de la derecha
 * ("Diagnósticos" + "Catálogo de categorías" + "+ Crear diagnóstico").
 */
defineProps<{ titulo: string; descripcion?: string }>();
</script>

<template>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
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
