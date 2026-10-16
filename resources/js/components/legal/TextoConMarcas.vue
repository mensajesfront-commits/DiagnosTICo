<script setup lang="ts">
/** Un texto legal con sus marcas [INFORMACIÓN PENDIENTE] resaltadas. */
import { partesConMarcas } from '@/lib/legales';

defineProps<{ texto: string }>();
</script>

<template>
    <template v-for="(parte, i) in partesConMarcas(texto)" :key="i">
        <mark
            v-if="parte.marca"
            class="rounded bg-amber-100 px-1 text-amber-900"
            >{{ parte.texto }}</mark
        >
        <template v-else>{{ parte.texto }}</template>
    </template>
</template>
