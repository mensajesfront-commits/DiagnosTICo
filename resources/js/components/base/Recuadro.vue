<script setup lang="ts">
/**
 * Recuadro de información dentro de una ventana o formulario, con ícono:
 * info ("Usada en 2 de 3 diagnósticos"), alerta ("⚠ El cambio se verá en
 * todas partes"), peligro ("No se puede deshacer"), exito ("Las versiones
 * publicadas no cambian") o neutro (notas).
 */
import {
    CircleCheck,
    Info,
    Lightbulb,
    OctagonAlert,
    TriangleAlert,
} from '@lucide/vue';
import { cn } from '@/lib/utils';

type Tono = 'info' | 'alerta' | 'peligro' | 'exito' | 'neutro';

withDefaults(defineProps<{ tono?: Tono; titulo?: string }>(), {
    tono: 'info',
    titulo: undefined,
});

const estilos: Record<Tono, { caja: string; icono: string }> = {
    info: { caja: 'bg-marca-suave/60', icono: 'text-marca' },
    alerta: { caja: 'bg-alerta-suave', icono: 'text-alerta' },
    peligro: { caja: 'bg-aviso-suave', icono: 'text-aviso' },
    exito: { caja: 'bg-exito-suave', icono: 'text-exito' },
    neutro: { caja: 'bg-lienzo', icono: 'text-tinta-suave' },
};

const iconos = {
    info: Info,
    alerta: TriangleAlert,
    peligro: OctagonAlert,
    exito: CircleCheck,
    neutro: Lightbulb,
};
</script>

<template>
    <div
        :class="
            cn(
                'flex gap-2.5 rounded-xl px-3.5 py-3 text-sm leading-snug',
                estilos[tono].caja,
            )
        "
    >
        <component
            :is="iconos[tono]"
            :class="cn('mt-px size-4 shrink-0', estilos[tono].icono)"
            aria-hidden="true"
        />
        <div class="min-w-0 flex-1">
            <p v-if="titulo" :class="cn('font-semibold', estilos[tono].icono)">
                {{ titulo }}
            </p>
            <div :class="titulo ? 'mt-0.5 text-tinta' : 'text-tinta'">
                <slot />
            </div>
        </div>
    </div>
</template>
