<script setup lang="ts">
/**
 * A2.1f · ¿Eliminar la pregunta? (HU-025 CA-003). Se borran sus opciones e
 * indicaciones; las mediciones ya respondidas no cambian (RN-010).
 *
 * Envía: DELETE /diagnosticos/{id}/preguntas/{pregunta}.
 */
import { useForm } from '@inertiajs/vue3';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';
import type { ParteEditor, PreguntaEditor } from '@/types/editor';

const props = defineProps<{
    diagnosticoId: number;
    parte: ParteEditor;
    pregunta: PreguntaEditor;
    numero: number;
}>();

const emit = defineEmits<{ eliminada: [] }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({});

const tipos = {
    abierta: '✎ Abierta',
    opcion_unica: '○ Opción única',
    seleccion_multiple: '☐ Selección múltiple',
};

function eliminar(): void {
    form.delete(
        rutas.diagnosticos.pregunta(props.diagnosticoId, props.pregunta.id),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                abierto.value = false;
                emit('eliminada');
            },
        },
    );
}

const quedan = () => props.parte.preguntas.length - 1;
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`¿Eliminar la pregunta ${numero}?`"
    >
        <div class="flex flex-col gap-3 text-sm">
            <div class="rounded-md bg-lienzo px-4 py-3">
                <p>“{{ pregunta.texto }}”</p>
                <p class="mt-1 text-xs text-tinta-suave">
                    {{ parte.nombre }} · {{ tipos[pregunta.tipo] }}
                </p>
            </div>
            <p class="text-tinta-suave">
                <template v-if="pregunta.opciones.length">
                    Se borran sus {{ pregunta.opciones.length }} opciones y su
                    indicación.
                </template>
                Las mediciones ya respondidas no cambian.
                {{ parte.nombre }} queda con {{ quedan() }}
                {{ quedan() === 1 ? 'pregunta' : 'preguntas' }}.
            </p>
        </div>
        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                variante="peligro"
                :cargando="form.processing"
                @click="eliminar"
            >
                Eliminar pregunta
            </Boton>
        </template>
    </Modal>
</template>
