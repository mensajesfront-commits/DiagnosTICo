<script setup lang="ts">
/**
 * Etiqueta pequeña con fondo suave ("Publicado · v2", "Borrador · sin
 * publicar", "Activo · se ofrece al registrar").
 */
import { cn } from '@/lib/utils';

type Tono = 'neutro' | 'exito' | 'alerta' | 'aviso' | 'marca';

withDefaults(defineProps<{ tono?: Tono; class?: string }>(), {
    tono: 'neutro',
    class: undefined,
});

const tonos: Record<Tono, string> = {
    neutro: 'bg-lienzo-oscuro text-tinta',
    exito: 'bg-exito-suave text-exito',
    alerta: 'bg-alerta-suave text-alerta',
    aviso: 'bg-aviso-suave text-aviso',
    marca: 'bg-marca-suave text-marca',
};
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                tonos[tono],
                $props.class,
            )
        "
    >
        <slot />
    </span>
</template>
