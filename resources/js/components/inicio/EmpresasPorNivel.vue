<script setup lang="ts">
/**
 * "Empresas por nivel" de A1 (HU-006 CA-003): cuántas empresas hay en cada
 * nivel según su último diagnóstico, y las que todavía no tienen uno. Una
 * barra de colores con su leyenda.
 */
import { computed } from 'vue';
import { niveles, ordenNiveles } from '@/lib/niveles';
import type { Nivel } from '@/lib/niveles';

const props = defineProps<{ cuenta: Record<Nivel, number> }>();

const total = computed(() =>
    ordenNiveles.reduce((suma, n) => suma + (props.cuenta[n] ?? 0), 0),
);

const tramos = computed(() =>
    ordenNiveles.map((n) => ({
        nivel: n,
        cantidad: props.cuenta[n] ?? 0,
        ancho: total.value ? ((props.cuenta[n] ?? 0) * 100) / total.value : 0,
    })),
);

const descripcion = computed(() =>
    tramos.value
        .map((t) => `${t.cantidad} en ${niveles[t.nivel].nombre}`)
        .join(', '),
);
</script>

<template>
    <section class="rounded-2xl border border-linea bg-white p-5">
        <div class="flex items-baseline justify-between gap-2">
            <h2 class="text-base font-semibold">Empresas por nivel</h2>
            <span class="text-xs text-tinta-suave"
                >{{ total }} {{ total === 1 ? 'empresa' : 'empresas' }}</span
            >
        </div>
        <p class="text-xs text-tinta-suave">Según su último diagnóstico</p>

        <div
            class="mt-3 flex h-3 overflow-hidden rounded-full bg-lienzo-oscuro"
            role="img"
            :aria-label="descripcion"
        >
            <span
                v-for="t in tramos.filter((x) => x.cantidad > 0)"
                :key="t.nivel"
                :class="niveles[t.nivel].barra"
                :style="{ width: `${t.ancho}%` }"
            />
        </div>

        <ul class="mt-3 grid grid-cols-1 gap-1.5 text-sm">
            <li
                v-for="t in tramos"
                :key="t.nivel"
                class="flex items-center gap-2"
            >
                <span
                    :class="[
                        'size-2.5 shrink-0 rounded-full',
                        niveles[t.nivel].barra,
                    ]"
                    aria-hidden="true"
                />
                <span class="flex-1">{{ niveles[t.nivel].nombre }}</span>
                <span
                    :class="[
                        'font-mono font-semibold',
                        t.cantidad === 0 && 'text-tinta-suave',
                    ]"
                    >{{ t.cantidad }}</span
                >
            </li>
        </ul>
    </section>
</template>
