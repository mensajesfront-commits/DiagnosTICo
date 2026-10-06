<script setup lang="ts">
/**
 * Opción grande con título y explicación, como "Reasignar las 9 empresas a
 * otro sector" o "Desactivar el sector" en A2.2 y A2.2d. Deshabilitada se ve
 * gris y no responde ("Eliminar sector · no disponible").
 */
import { cn } from '@/lib/utils';

defineProps<{ titulo: string; deshabilitada?: boolean }>();

defineEmits<{ elegir: [] }>();
</script>

<template>
    <button
        type="button"
        :disabled="deshabilitada"
        :class="
            cn(
                'w-full rounded-lg border px-4 py-3 text-left transition-colors focus-visible:ring-2 focus-visible:ring-marca/30 focus-visible:outline-none',
                deshabilitada
                    ? 'cursor-not-allowed border-linea bg-lienzo text-tinta-suave'
                    : 'border-tinta/25 bg-white hover:border-marca hover:bg-marca-suave/30',
            )
        "
        @click="$emit('elegir')"
    >
        <span class="block text-sm font-medium">{{ titulo }}</span>
        <span class="mt-0.5 block text-xs text-tinta-suave">
            <slot />
        </span>
    </button>
</template>
