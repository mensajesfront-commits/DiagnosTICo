<script setup lang="ts">
/**
 * E12.1 y E12.3 · Páginas que puede ver el colaborador. La cuenta principal
 * marca cada una; Inicio y Mi perfil (solo lectura) siempre se ven. Una
 * página que necesita otra (el PDF necesita los resultados) se desmarca y se
 * bloquea si falta la otra. El servidor revisa lo mismo.
 *
 * Cada página es una tarjeta con su ícono; la marcada se pinta de marca.
 */
import { Check, Eye, Lock } from '@lucide/vue';
import { watch } from 'vue';
import { iconoPagina } from '@/lib/paginasColaborador';
import type { PaginaColaborador } from '@/types/colaboradores';

const props = defineProps<{
    paginas: PaginaColaborador[];
    prefijo: string;
    error?: string;
}>();

const elegidas = defineModel<string[]>({ required: true });

const falta = (pagina: PaginaColaborador) =>
    pagina.necesita !== null && !elegidas.value.includes(pagina.necesita);

watch(
    elegidas,
    () => {
        const sobran = props.paginas.filter(
            (p) => falta(p) && elegidas.value.includes(p.clave),
        );

        if (sobran.length > 0) {
            elegidas.value = elegidas.value.filter(
                (c) => !sobran.some((p) => p.clave === c),
            );
        }
    },
    { deep: true },
);
</script>

<template>
    <div
        role="group"
        :aria-labelledby="`${prefijo}-titulo`"
        class="flex flex-col gap-3"
    >
        <div class="flex items-start gap-2.5">
            <span
                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-marca-suave text-marca"
                aria-hidden="true"
            >
                <Eye class="size-4" />
            </span>
            <span class="min-w-0">
                <span
                    :id="`${prefijo}-titulo`"
                    class="block text-sm font-semibold"
                >
                    Páginas que puede ver
                    <span class="text-aviso" aria-hidden="true">*</span>
                </span>
                <span class="block text-xs font-normal text-tinta-suave">
                    Inicio y Mi perfil siempre los ve. Su perfil y su contraseña
                    solo los cambias tú.
                </span>
            </span>
        </div>
        <div class="grid gap-2">
            <label
                v-for="pagina in paginas"
                :key="pagina.clave"
                :for="`${prefijo}-${pagina.clave}`"
                class="group flex cursor-pointer items-center gap-3 rounded-xl border border-linea bg-white px-3 py-2.5 text-sm transition-colors hover:border-marca/40 has-checked:border-marca has-checked:bg-marca-suave/40 has-focus-visible:ring-2 has-focus-visible:ring-marca/40 has-disabled:cursor-not-allowed has-disabled:opacity-60"
            >
                <input
                    :id="`${prefijo}-${pagina.clave}`"
                    v-model="elegidas"
                    type="checkbox"
                    :value="pagina.clave"
                    :disabled="falta(pagina)"
                    class="sr-only"
                />
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-lienzo text-tinta-suave group-has-checked:bg-white group-has-checked:text-marca"
                    aria-hidden="true"
                >
                    <component :is="iconoPagina(pagina.clave)" class="size-4" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block font-medium">{{ pagina.titulo }}</span>
                    <span class="block text-xs text-tinta-suave">
                        {{ pagina.descripcion }}
                    </span>
                </span>
                <span
                    class="flex size-5 shrink-0 items-center justify-center rounded-md border border-linea-fuerte bg-white text-white group-has-checked:border-marca group-has-checked:bg-marca"
                    aria-hidden="true"
                >
                    <Lock
                        v-if="falta(pagina)"
                        class="size-3 text-tinta-suave"
                    />
                    <Check v-else class="size-3.5" />
                </span>
            </label>
        </div>
        <p v-if="error" class="text-xs text-aviso" role="alert">
            {{ error }}
        </p>
    </div>
</template>
