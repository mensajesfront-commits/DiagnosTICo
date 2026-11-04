<script setup lang="ts">
/**
 * Estructura de las pantallas para responder (E2–E4): sin menú lateral, para
 * que la empresa se concentre en el diagnóstico. Cada pantalla dibuja su
 * barra de arriba (`BarraResponder`). Arriba, la franja de "Ver como".
 */
import FranjaVerComo from '@/components/base/FranjaVerComo.vue';
</script>

<template>
    <FranjaVerComo />
    <div class="panel-raiz min-h-dvh bg-lienzo text-tinta">
        <slot />
    </div>
</template>
