<script setup lang="ts">
/**
 * Botón del wireframe. Si recibe `href` se dibuja como enlace de Inertia.
 *
 * Variantes:
 * - primario: azul relleno ("+ Crear diagnóstico", "Guardar").
 * - secundario: blanco con borde ("Catálogo de categorías", "Cancelar").
 * - enlace: texto subrayado ("Ver empresa", "Recordatorio").
 * - peligro: rojo relleno ("Eliminar").
 */
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

type Variante = 'primario' | 'secundario' | 'enlace' | 'peligro';
type Tamano = 'sm' | 'md';

const props = withDefaults(
    defineProps<{
        variante?: Variante;
        tamano?: Tamano;
        href?: NonNullable<InertiaLinkProps['href']>;
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
        cargando?: boolean;
        class?: string;
    }>(),
    {
        variante: 'primario',
        tamano: 'md',
        href: undefined,
        type: 'button',
        disabled: false,
        cargando: false,
        class: undefined,
    },
);

const variantes: Record<Variante, string> = {
    primario:
        'bg-marca text-white hover:bg-marca-hover disabled:bg-linea-fuerte disabled:text-white',
    secundario:
        'border border-tinta/25 bg-white text-tinta hover:bg-lienzo disabled:border-linea disabled:bg-lienzo-oscuro disabled:text-tinta-suave disabled:hover:bg-lienzo-oscuro',
    enlace: 'px-0 text-marca underline underline-offset-2 hover:text-marca-hover',
    peligro: 'bg-aviso text-white hover:bg-[#8f2c25]',
};

const tamanos: Record<Tamano, string> = {
    sm: 'h-8 px-3 text-xs',
    md: 'h-10 px-4 text-sm',
};

const clases = computed(() =>
    cn(
        'inline-flex items-center justify-center gap-2 rounded-md font-medium whitespace-nowrap transition-colors focus-visible:ring-2 focus-visible:ring-marca/40 focus-visible:outline-none disabled:cursor-not-allowed',
        variantes[props.variante],
        props.variante === 'enlace' ? 'h-auto text-sm' : tamanos[props.tamano],
        props.class,
    ),
);
</script>

<template>
    <Link v-if="href && !disabled" :href="href" :class="clases">
        <slot />
    </Link>
    <button
        v-else
        :type="type"
        :class="clases"
        :disabled="disabled || cargando"
        :aria-busy="cargando"
    >
        <span
            v-if="cargando"
            class="size-4 animate-spin rounded-full border-2 border-current border-t-transparent"
            aria-hidden="true"
        />
        <slot />
    </button>
</template>
