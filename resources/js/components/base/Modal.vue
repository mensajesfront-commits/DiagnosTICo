<script setup lang="ts">
/**
 * Ventana sobre la vista (A2.1c, A2.2a–e, A2.3b, A5.3…): título, texto de
 * ayuda, contenido y pie con botones. Se abre con v-model:abierto.
 *
 * Slots: default (contenido), `descripcion` (en lugar de la prop, si lleva
 * enlaces), `pie` (botones; a la izquierda queda el slot `nota`, como "Elige
 * un sector destino").
 */
import { X } from '@lucide/vue';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import { ref } from 'vue';
import { cn } from '@/lib/utils';

type Ancho = 'sm' | 'md' | 'lg' | 'xl';

withDefaults(
    defineProps<{
        titulo: string;
        descripcion?: string;
        ancho?: Ancho;
    }>(),
    { descripcion: undefined, ancho: 'md' },
);

const abierto = defineModel<boolean>('abierto', { default: false });

const cuerpo = ref<HTMLElement | null>(null);

// Al abrir, el foco va al primer campo del formulario en lugar del botón de
// cerrar. Si no hay campos, Reka UI decide (primer elemento enfocable).
function enfocarPrimerCampo(evento: Event): void {
    const campo = cuerpo.value?.querySelector<HTMLElement>(
        'input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled])',
    );

    if (campo) {
        evento.preventDefault();
        campo.focus();
    }
}

const anchos: Record<Ancho, string> = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
};
</script>

<template>
    <DialogRoot v-model:open="abierto">
        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 bg-menu/45 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
            />
            <DialogContent
                :class="
                    cn(
                        'fixed top-1/2 left-1/2 z-50 flex max-h-[calc(100vh-2rem)] w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 flex-col rounded-xl bg-white shadow-xl focus:outline-none data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95',
                        anchos[ancho],
                    )
                "
                @open-auto-focus="enfocarPrimerCampo"
            >
                <header
                    class="flex items-start justify-between gap-4 px-6 pt-5"
                >
                    <div>
                        <DialogTitle class="text-lg font-semibold text-tinta">
                            {{ titulo }}
                        </DialogTitle>
                        <DialogDescription
                            v-if="descripcion || $slots.descripcion"
                            class="mt-1 text-sm text-tinta-suave"
                        >
                            <slot name="descripcion">{{ descripcion }}</slot>
                        </DialogDescription>
                    </div>
                    <DialogClose
                        class="rounded p-1 text-tinta-suave hover:bg-lienzo hover:text-tinta"
                        aria-label="Cerrar"
                    >
                        <X class="size-4" />
                    </DialogClose>
                </header>

                <div ref="cuerpo" class="overflow-y-auto px-6 py-4">
                    <slot />
                </div>

                <footer
                    v-if="$slots.pie"
                    class="flex flex-wrap items-center justify-end gap-3 border-t border-linea px-6 py-4"
                >
                    <p
                        v-if="$slots.nota"
                        class="mr-auto text-xs text-tinta-suave"
                    >
                        <slot name="nota" />
                    </p>
                    <slot name="pie" />
                </footer>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
