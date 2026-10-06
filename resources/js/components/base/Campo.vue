<script setup lang="ts">
/**
 * Envoltura de un campo de formulario: etiqueta, control (slot), ayuda y
 * error, con el estilo del wireframe (etiqueta pequeña encima del control).
 *
 * `opcional` agrega "(opcional)" a la etiqueta, como en L2.
 * El slot `accion` va a la derecha de la etiqueta ("¿Olvidaste tu contraseña?").
 */
defineProps<{
    etiqueta: string;
    para: string;
    ayuda?: string;
    error?: string;
    opcional?: boolean;
    /** Contador a la derecha de la ayuda, por ejemplo "11/40". */
    contador?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <div class="flex items-baseline justify-between gap-2">
            <label :for="para" class="text-xs font-medium text-tinta">
                {{ etiqueta }}
                <span v-if="opcional" class="font-normal text-tinta-suave">
                    (opcional)
                </span>
            </label>
            <slot name="accion" />
        </div>
        <slot />
        <div
            v-if="ayuda || $slots.ayuda || error || contador"
            class="flex items-start justify-between gap-3 text-xs"
        >
            <p v-if="error" class="text-aviso" role="alert">{{ error }}</p>
            <p v-else class="text-tinta-suave">
                <slot name="ayuda">{{ ayuda }}</slot>
            </p>
            <span
                v-if="contador"
                class="shrink-0 font-mono text-tinta-suave"
                aria-live="polite"
            >
                {{ contador }}
            </span>
        </div>
    </div>
</template>
