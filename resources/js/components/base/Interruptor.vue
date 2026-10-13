<script setup lang="ts">
/**
 * Interruptor de encendido y apagado ("Avisos por correo" en A6 y E11).
 * Se usa con v-model y lleva su texto en el slot.
 */
const modelo = defineModel<boolean>({ default: false });

defineProps<{ id: string }>();
</script>

<template>
    <div class="flex items-center justify-between gap-4 py-2.5">
        <label :for="id" class="text-sm text-tinta"><slot /></label>
        <button
            :id="id"
            type="button"
            role="switch"
            :aria-checked="modelo"
            :class="[
                'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marca',
                modelo ? 'bg-marca' : 'bg-[#8a8a86]',
            ]"
            @click="modelo = !modelo"
        >
            <span
                :class="[
                    'inline-block size-5 rounded-full bg-white shadow transition-transform',
                    modelo ? 'translate-x-5.5' : 'translate-x-0.5',
                ]"
            />
        </button>
    </div>
</template>
