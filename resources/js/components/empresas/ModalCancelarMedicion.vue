<script setup lang="ts">
/**
 * A3.1c · Cancelar la medición pendiente sin asignar otra. La empresa deja
 * de verla en su inicio; si ya respondió algo, se pierde (se avisa).
 *
 * Envía: POST /mediciones/{id}/cancelar.
 */
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';

const props = defineProps<{
    medicion: { id: number; numero: number; tiene_respuestas: boolean };
    empresa: string;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({});

/** "Solo se cambia una medición pendiente…" (ya se envió o se canceló). */
const errorMedicion = computed(
    () => (form.errors as Record<string, string | undefined>).medicion,
);

function cancelar(): void {
    form.post(rutas.mediciones.cancelar(props.medicion.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        ancho="sm"
        :titulo="`¿Cancelar la medición ${medicion.numero}?`"
    >
        <div class="flex flex-col gap-2 text-sm text-tinta-suave">
            <p>{{ empresa }} dejará de verla en su inicio.</p>
            <p
                v-if="medicion.tiene_respuestas"
                class="rounded-md bg-aviso-suave px-3 py-2 text-aviso"
            >
                Ya tiene respuestas guardadas. Al cancelar, no se analizan.
            </p>
            <p v-if="errorMedicion" class="text-aviso" role="alert">
                {{ errorMedicion }}
            </p>
        </div>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Volver
            </Boton>
            <Boton
                variante="peligro"
                :cargando="form.processing"
                @click="cancelar"
            >
                Cancelar medición
            </Boton>
        </template>
    </Modal>
</template>
