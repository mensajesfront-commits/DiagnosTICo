<script setup lang="ts" generic="T extends string | number">
/**
 * Grupo de opciones en una sola pieza, como las pestañas "Todas · 11 · En
 * curso · 3…" del inicio o "Abierta | Opción única | Selección múltiple" del
 * editor de preguntas. Se usa con v-model.
 */
import { cn } from '@/lib/utils';

export type OpcionSelector<V> = {
    valor: V;
    etiqueta: string;
    /** Número que se muestra después de la etiqueta ("Todas · 11"). */
    cantidad?: number;
};

withDefaults(
    defineProps<{
        opciones: OpcionSelector<T>[];
        /** "pestanas": fondo gris y opción activa blanca. "relleno": opción activa azul. */
        estilo?: 'pestanas' | 'relleno';
        etiquetaAccesible?: string;
    }>(),
    { estilo: 'pestanas', etiquetaAccesible: undefined },
);

const modelo = defineModel<T>({ required: true });
</script>

<template>
    <div
        role="radiogroup"
        :aria-label="etiquetaAccesible"
        :class="
            cn(
                'inline-flex rounded-lg p-1',
                estilo === 'pestanas'
                    ? 'bg-lienzo-oscuro'
                    : 'border border-linea-fuerte bg-white p-0',
            )
        "
    >
        <button
            v-for="opcion in opciones"
            :key="opcion.valor"
            type="button"
            role="radio"
            :aria-checked="modelo === opcion.valor"
            :class="
                cn(
                    'px-3 py-1.5 text-xs whitespace-nowrap transition-colors focus-visible:ring-2 focus-visible:ring-marca/40 focus-visible:outline-none',
                    estilo === 'pestanas'
                        ? 'rounded-md text-tinta-suave hover:text-tinta'
                        : 'flex-1 border-r border-linea-fuerte py-2.5 text-sm text-tinta first:rounded-l-lg last:rounded-r-lg last:border-r-0 hover:bg-lienzo',
                    modelo === opcion.valor &&
                        (estilo === 'pestanas'
                            ? 'bg-white font-semibold text-tinta shadow-sm'
                            : 'bg-marca font-medium text-white hover:bg-marca'),
                )
            "
            @click="modelo = opcion.valor"
        >
            {{ opcion.etiqueta
            }}<template v-if="opcion.cantidad !== undefined">
                · {{ opcion.cantidad }}</template
            >
        </button>
    </div>
</template>
