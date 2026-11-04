<script setup lang="ts">
/**
 * "Cambiar fecha" / "Nueva fecha" de la medición pendiente (HU-009, A1d).
 * La medición conserva sus respuestas; si estaba vencida vuelve a estar
 * pendiente y sale de Vencidas. Sin fecha, queda sin fecha límite.
 *
 * Envía: PUT /mediciones/{id}/fecha con fecha_limite.
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';

const props = defineProps<{
    medicion: {
        id: number;
        numero: number;
        vence: string | null;
        /** La más cercana que se acepta: 3 días, en la hora de la empresa. */
        fecha_minima?: string;
    };
    empresa: string;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({ fecha_limite: '' });
const minima = computed(
    () => props.medicion.fecha_minima ?? new Date().toLocaleDateString('en-CA'),
);

/** "Solo se cambia una medición pendiente…" (ya se envió o se canceló). */
const errorMedicion = computed(
    () => (form.errors as Record<string, string | undefined>).medicion,
);

watch(
    abierto,
    (valor) => {
        if (valor) {
            form.fecha_limite =
                props.medicion.vence && props.medicion.vence >= minima.value
                    ? props.medicion.vence
                    : '';
            form.clearErrors();
        }
    },
    { immediate: true },
);

function guardar(): void {
    form.transform((d) => ({ fecha_limite: d.fecha_limite || null })).put(
        rutas.mediciones.fecha(props.medicion.id),
        { preserveScroll: true, onSuccess: () => (abierto.value = false) },
    );
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        ancho="sm"
        :titulo="`Nueva fecha para la medición ${medicion.numero}`"
        :descripcion="`${empresa} conserva lo que ya respondió.`"
    >
        <form id="form-cambiar-fecha" @submit.prevent="guardar">
            <Campo
                etiqueta="Fecha límite"
                para="cambiar-fecha"
                opcional
                ayuda="Déjala vacía para que no tenga fecha límite."
                :error="form.errors.fecha_limite || errorMedicion"
            >
                <Entrada
                    id="cambiar-fecha"
                    v-model="form.fecha_limite"
                    type="date"
                    :min="minima"
                    :invalida="!!form.errors.fecha_limite"
                />
            </Campo>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-cambiar-fecha"
                :cargando="form.processing"
            >
                Guardar fecha
            </Boton>
        </template>
    </Modal>
</template>
