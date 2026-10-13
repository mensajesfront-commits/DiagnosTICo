<script setup lang="ts">
/**
 * Empresa y categoría de ejemplo para A4.4 y A4.7: la medición más reciente
 * con respuestas de cada empresa.
 */
import { computed, watch } from 'vue';
import Seleccion from '@/components/base/Seleccion.vue';
import type { EjemploIa } from '@/types/configuracionIa';

const props = defineProps<{ ejemplos: EjemploIa[] }>();

const medicion = defineModel<number | ''>('medicion', { required: true });
const categoria = defineModel<string>('categoria', { required: true });

const elegido = computed(() =>
    props.ejemplos.find((e) => e.medicion_id === medicion.value),
);

// Al cambiar de empresa, la primera categoría si la elegida no existe.
watch(
    elegido,
    (e) => {
        if (e && !e.categorias.some((c) => c.ref === categoria.value)) {
            categoria.value = e.categorias[0]?.ref ?? '';
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="flex flex-col gap-1 text-sm font-medium">
            Empresa de ejemplo
            <Seleccion v-model="medicion">
                <option
                    v-for="e in ejemplos"
                    :key="e.medicion_id"
                    :value="e.medicion_id"
                >
                    {{ e.empresa }} · {{ e.sector }} · medición {{ e.numero }}
                </option>
            </Seleccion>
        </label>
        <label class="flex flex-col gap-1 text-sm font-medium">
            Categoría
            <Seleccion v-model="categoria" :disabled="!elegido">
                <option
                    v-for="c in elegido?.categorias ?? []"
                    :key="c.ref"
                    :value="c.ref"
                >
                    {{ c.nombre }}
                </option>
            </Seleccion>
        </label>
    </div>
</template>
