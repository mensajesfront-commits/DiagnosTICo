<script setup lang="ts">
/**
 * Método con el que la IA analizó una categoría (PESTEL, 5 Fuerzas de
 * Porter, VRIO, DOFA o CAME). Lo elige la IA según las respuestas; al pasar
 * el cursor se ve por qué.
 */
import type { MetodoAnalisis } from '@/types/resultados';

defineProps<{ metodo: MetodoAnalisis }>();
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded border border-marca/30 bg-marca-suave/60 px-1.5 py-0.5 text-[11px] font-medium whitespace-nowrap text-marca"
        :title="metodo.motivo || undefined"
    >
        <span class="sr-only">Analizado con</span>{{ metodo.nombre }}
    </span>
</template>
