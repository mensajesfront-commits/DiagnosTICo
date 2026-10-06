<script setup lang="ts">
/**
 * Logo del sistema (aro azul con la "C" y el punto verde). El mismo dibujo
 * está en public/favicon.svg. Se le da el tamaño con la clase (`size-8`).
 */
defineProps<{ titulo?: string }>();
</script>

<template>
    <svg
        viewBox="0 0 110 110"
        xmlns="http://www.w3.org/2000/svg"
        :role="titulo ? 'img' : undefined"
        :aria-hidden="titulo ? undefined : 'true'"
        class="shrink-0"
    >
        <title v-if="titulo">{{ titulo }}</title>
        <circle cx="55" cy="55" r="55" fill="#14154d" />
        <circle cx="55" cy="55" r="44" fill="#e6e8ea" />
        <circle cx="55" cy="55" r="28" fill="#14154d" />
        <path d="M55 35H70L98 24V87L70 76H55Z" fill="#14154d" />
        <circle cx="57" cy="55" r="14" fill="#6ae45b" />
        <path d="M85 51L100 43Q104 55 100 67L85 59Z" fill="#6ae45b" />
    </svg>
</template>
