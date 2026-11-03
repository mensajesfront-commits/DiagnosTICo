<script setup lang="ts">
/**
 * Encabezado de una sección dentro de un formulario o modal: ícono en un
 * cuadro de marca, título y, si se pasa, una línea de ayuda.
 */
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        icono: Component;
        titulo: string;
        descripcion?: string;
        /** Etiqueta del título; `legend` dentro de un fieldset. */
        como?: 'h3' | 'legend' | 'p';
        id?: string;
    }>(),
    { descripcion: undefined, como: 'h3', id: undefined },
);
</script>

<template>
    <div class="flex items-start gap-2.5">
        <span
            class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-marca-suave text-marca"
            aria-hidden="true"
        >
            <component :is="icono" class="size-4" />
        </span>
        <div class="min-w-0">
            <component :is="como" :id="id" class="text-sm font-semibold">
                {{ titulo }}<slot name="titulo" />
            </component>
            <p v-if="descripcion" class="text-xs text-tinta-suave">
                {{ descripcion }}
            </p>
        </div>
    </div>
</template>
