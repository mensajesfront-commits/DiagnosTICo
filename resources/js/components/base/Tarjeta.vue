<script setup lang="ts">
/**
 * Tarjeta blanca con borde y esquinas redondeadas, como los bloques del
 * wireframe ("Mediciones", "Empresas por nivel", "Resumen del sector").
 * El encabezado es opcional: `titulo`, `subtitulo` y el slot `acciones`.
 */
import { cn } from '@/lib/utils';

defineProps<{
    titulo?: string;
    subtitulo?: string;
    /** Quita el relleno interno, para tarjetas que contienen una tabla. */
    sinRelleno?: boolean;
    class?: string;
}>();
</script>

<template>
    <section
        :class="
            cn(
                'rounded-xl border border-linea bg-white',
                !sinRelleno && 'p-5',
                $props.class,
            )
        "
    >
        <header
            v-if="titulo || $slots.acciones"
            :class="
                cn(
                    'flex flex-wrap items-start justify-between gap-3',
                    sinRelleno ? 'px-5 pt-5 pb-4' : 'mb-4',
                )
            "
        >
            <div>
                <h2 v-if="titulo" class="text-base font-semibold text-tinta">
                    {{ titulo }}
                </h2>
                <p v-if="subtitulo" class="text-xs text-tinta-suave">
                    {{ subtitulo }}
                </p>
            </div>
            <div v-if="$slots.acciones" class="flex items-center gap-3">
                <slot name="acciones" />
            </div>
        </header>
        <slot />
    </section>
</template>
