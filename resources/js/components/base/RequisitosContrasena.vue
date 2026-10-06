<script setup lang="ts">
/**
 * Lista de requisitos de la contraseña que se marca en vivo (L2, L4).
 * Muestra "✓" en verde lo cumplido y "○" lo pendiente, y el contador "2 de 5".
 */
import { computed } from 'vue';
import { revisarContrasena } from '@/lib/contrasena';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        contrasena: string;
        confirmacion: string;
        /** Dos columnas, como en el registro (L2). */
        columnas?: boolean;
    }>(),
    { columnas: false },
);

const estado = computed(() =>
    revisarContrasena(props.contrasena, props.confirmacion),
);
</script>

<template>
    <div
        class="rounded-md bg-lienzo-oscuro/60 px-3 py-2.5 text-xs"
        aria-live="polite"
    >
        <div class="mb-1.5 flex justify-between text-tinta-suave">
            <span>La contraseña debe tener:</span>
            <span class="font-mono">
                {{ estado.cumplidos }} de {{ estado.requisitos.length }}
            </span>
        </div>
        <ul :class="cn('grid gap-1', columnas && 'sm:grid-cols-2')">
            <li
                v-for="requisito in estado.requisitos"
                :key="requisito.id"
                :class="
                    cn(
                        'flex items-center gap-2',
                        requisito.cumple ? 'text-exito' : 'text-tinta-suave',
                    )
                "
            >
                <span aria-hidden="true" class="w-3 text-center">
                    {{ requisito.cumple ? '✓' : '○' }}
                </span>
                {{ requisito.texto }}
                <span class="sr-only">
                    ({{ requisito.cumple ? 'cumplido' : 'pendiente' }})
                </span>
            </li>
        </ul>
    </div>
</template>
