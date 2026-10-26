<script setup lang="ts">
/**
 * E12.1 y E12.3 · Páginas que puede ver el colaborador. La cuenta principal
 * marca cada una; Inicio y Mi perfil (solo lectura) siempre se ven. Una
 * página que necesita otra (el PDF necesita los resultados) se desmarca y se
 * bloquea si falta la otra. El servidor revisa lo mismo.
 */
import { watch } from 'vue';
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
    <fieldset class="flex flex-col gap-2">
        <legend class="text-sm font-medium">
            Páginas que puede ver <span class="text-aviso">*</span>
        </legend>
        <p class="-mt-1 text-xs text-tinta-suave">
            Inicio y Mi perfil siempre los ve. Su perfil y su contraseña solo
            los cambias tú.
        </p>
        <label
            v-for="pagina in paginas"
            :key="pagina.clave"
            :for="`${prefijo}-${pagina.clave}`"
            class="flex items-start gap-2.5 rounded-md border border-linea px-3 py-2.5 text-sm has-[:checked]:border-marca has-[:checked]:bg-marca-suave/40 has-[:disabled]:opacity-60"
        >
            <input
                :id="`${prefijo}-${pagina.clave}`"
                v-model="elegidas"
                type="checkbox"
                :value="pagina.clave"
                :disabled="falta(pagina)"
                class="mt-0.5 size-4 shrink-0 rounded border-linea-fuerte accent-marca"
            />
            <span>
                <span class="block font-medium">{{ pagina.titulo }}</span>
                <span class="text-xs text-tinta-suave">
                    {{ pagina.descripcion }}
                </span>
            </span>
        </label>
        <p v-if="error" class="text-xs text-aviso" role="alert">
            {{ error }}
        </p>
    </fieldset>
</template>
