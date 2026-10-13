<script setup lang="ts">
/**
 * Caja de contraseña con el botón de texto "Mostrar / Ocultar" del wireframe
 * (L1, L2, L4).
 */
import { ref } from 'vue';
import Entrada from '@/components/base/Entrada.vue';

defineOptions({ inheritAttrs: false });

defineProps<{ invalida?: boolean }>();

const modelo = defineModel<string>();
const visible = ref(false);
</script>

<template>
    <div class="relative">
        <Entrada
            v-model="modelo"
            v-bind="$attrs"
            :type="visible ? 'text' : 'password'"
            :invalida="invalida"
            class="pr-20"
        />
        <button
            type="button"
            class="absolute inset-y-0 right-0 px-3 text-xs font-medium text-marca hover:text-marca-hover focus-visible:underline focus-visible:outline-none"
            :aria-pressed="visible"
            @click="visible = !visible"
        >
            {{ visible ? 'Ocultar' : 'Mostrar' }}
        </button>
    </div>
</template>
