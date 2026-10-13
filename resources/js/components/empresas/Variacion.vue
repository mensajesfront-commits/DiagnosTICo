<script setup lang="ts">
/**
 * Variación frente a la medición anterior (RN-020): "▲ +8" en verde,
 * "▼ −3" en rojo, "= 0" en gris y "—" si no hay (primera medición).
 * Llega calculada del backend.
 */
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ valor: number | null; class?: string }>();

const vista = computed(() => {
    const v = props.valor;

    if (v === null) {
        return {
            texto: '—',
            clases: 'text-tinta-suave',
            titulo: 'Sin medición anterior',
        };
    }

    if (v > 0) {
        return {
            texto: `▲ +${v}`,
            clases: 'text-exito',
            titulo: `Subió ${v} puntos`,
        };
    }

    if (v < 0) {
        return {
            texto: `▼ −${Math.abs(v)}`,
            clases: 'text-aviso',
            titulo: `Bajó ${Math.abs(v)} puntos`,
        };
    }

    return { texto: '= 0', clases: 'text-tinta-suave', titulo: 'Sin cambio' };
});
</script>

<template>
    <span
        :class="
            cn('font-mono text-sm whitespace-nowrap', vista.clases, props.class)
        "
        :title="vista.titulo"
    >
        {{ vista.texto }}
    </span>
</template>
