<script setup lang="ts">
/**
 * "Empresas por nivel" de A1 (HU-006 CA-003): cuántas empresas hay en cada
 * nivel según su último diagnóstico, y las que todavía no tienen uno.
 */
import { computed } from 'vue';
import { niveles, ordenNiveles } from '@/lib/niveles';
import type { Nivel } from '@/lib/niveles';

const props = defineProps<{ cuenta: Record<Nivel, number> }>();

const maximo = computed(() =>
    Math.max(1, ...ordenNiveles.map((n) => props.cuenta[n] ?? 0)),
);
</script>

<template>
    <section class="rounded-xl border border-linea bg-white p-4">
        <h2 class="text-sm font-semibold">Empresas por nivel</h2>
        <p class="text-xs text-tinta-suave">Según su último diagnóstico</p>

        <ul class="mt-3 flex flex-col gap-2 text-sm">
            <li
                v-for="nivel in ordenNiveles"
                :key="nivel"
                class="grid grid-cols-[1fr_72px_24px] items-center gap-3"
            >
                <span>{{ niveles[nivel].nombre }}</span>
                <span class="h-2 rounded-full bg-lienzo-oscuro">
                    <span
                        :class="[
                            'block h-2 rounded-full',
                            niveles[nivel].barra,
                        ]"
                        :style="{
                            width: `${((cuenta[nivel] ?? 0) / maximo) * 100}%`,
                        }"
                    />
                </span>
                <span class="text-right font-mono">{{
                    cuenta[nivel] ?? 0
                }}</span>
            </li>
        </ul>
    </section>
</template>
