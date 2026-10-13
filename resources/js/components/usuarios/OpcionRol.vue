<script setup lang="ts">
/**
 * Opción de rol con radio, título y explicación (Invitar usuario y A5.5).
 * Un rol inactivo se ve gris, no se puede elegir y lleva la etiqueta
 * "Próxima fase" (HU-050 CA-003).
 */
import Etiqueta from '@/components/base/Etiqueta.vue';
import { cn } from '@/lib/utils';

defineProps<{
    nombre: string;
    valor: number;
    titulo: string;
    descripcion?: string | null;
    deshabilitada?: boolean;
    /** Etiqueta a la derecha ("Rol actual", "Próxima fase"). */
    etiqueta?: string | null;
    tonoEtiqueta?: 'neutro' | 'alerta';
}>();

const modelo = defineModel<number | null>({ required: true });
</script>

<template>
    <label
        :class="
            cn(
                'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm transition-colors',
                deshabilitada
                    ? 'cursor-not-allowed border-linea bg-lienzo/60 text-tinta-suave'
                    : 'cursor-pointer border-linea-fuerte hover:border-marca',
                modelo === valor &&
                    !deshabilitada &&
                    'border-marca bg-marca-suave/40 ring-1 ring-marca',
            )
        "
    >
        <input
            v-model="modelo"
            type="radio"
            :name="nombre"
            :value="valor"
            :disabled="deshabilitada"
            class="mt-0.5 size-4 shrink-0 accent-marca"
        />
        <span class="flex-1">
            <span class="block font-medium">{{ titulo }}</span>
            <span
                v-if="descripcion"
                class="mt-0.5 block text-xs text-tinta-suave"
            >
                {{ descripcion }}
            </span>
        </span>
        <Etiqueta v-if="etiqueta" :tono="tonoEtiqueta ?? 'neutro'">
            {{ etiqueta }}
        </Etiqueta>
    </label>
</template>
