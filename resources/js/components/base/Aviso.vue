<script setup lang="ts">
/**
 * Recuadro de aviso: éxito ("Revisa tu correo"), error ("Este enlace ya no
 * es válido") o información.
 */
import { cn } from '@/lib/utils';

type Tono = 'exito' | 'error' | 'info';

withDefaults(defineProps<{ tono?: Tono; titulo?: string }>(), {
    tono: 'info',
    titulo: undefined,
});

const tonos: Record<Tono, string> = {
    exito: 'border-nivel-sigue/60 bg-exito-suave/60 text-tinta',
    error: 'border-nivel-critico/50 bg-aviso-suave/70 text-tinta',
    info: 'border-nivel-camino/40 bg-marca-suave/60 text-tinta',
};

const titulos: Record<Tono, string> = {
    exito: 'text-exito',
    error: 'text-aviso',
    info: 'text-marca',
};
</script>

<template>
    <div
        :class="cn('rounded-lg border px-4 py-3 text-sm', tonos[tono])"
        :role="tono === 'error' ? 'alert' : 'status'"
    >
        <p v-if="titulo" :class="cn('mb-1 font-semibold', titulos[tono])">
            {{ titulo }}
        </p>
        <slot />
    </div>
</template>
