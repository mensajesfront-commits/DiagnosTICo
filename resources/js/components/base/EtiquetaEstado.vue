<script setup lang="ts">
/**
 * Estado de una medición (RN-015) con su símbolo, como en el inicio del
 * Administrador: "○ No iniciada", "◐ En curso · 6/10", "⚠ Vencida · 3/10".
 */
import { computed } from 'vue';
import { cn } from '@/lib/utils';

export type EstadoMedicion =
    | 'no_iniciada'
    | 'en_curso'
    | 'enviada'
    | 'terminada'
    | 'vencida';

const props = defineProps<{
    estado: EstadoMedicion;
    /** Categorías respondidas y total, para "En curso · 6/10". */
    avance?: { hechas: number; total: number };
    class?: string;
}>();

const estados: Record<
    EstadoMedicion,
    { simbolo: string; texto: string; clases: string }
> = {
    no_iniciada: {
        simbolo: '○',
        texto: 'No iniciada',
        clases: 'bg-lienzo-oscuro text-tinta-suave',
    },
    en_curso: {
        simbolo: '◐',
        texto: 'En curso',
        clases: 'bg-lienzo-oscuro text-tinta',
    },
    enviada: {
        simbolo: '◔',
        texto: 'Enviada',
        clases: 'bg-marca-suave text-marca',
    },
    terminada: {
        simbolo: '✓',
        texto: 'Terminada',
        clases: 'bg-exito-suave text-exito',
    },
    vencida: {
        simbolo: '⚠',
        texto: 'Vencida',
        clases: 'bg-aviso-suave text-aviso',
    },
};

const info = computed(() => estados[props.estado]);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                info.clases,
                props.class,
            )
        "
    >
        <span aria-hidden="true">{{ info.simbolo }}</span>
        {{ info.texto }}
        <template v-if="avance"
            >· {{ avance.hechas }}/{{ avance.total }}</template
        >
    </span>
</template>
