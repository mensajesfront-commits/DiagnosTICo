<script setup lang="ts">
/**
 * Eliminar varios diagnósticos a la vez (A2). Es la segunda confirmación:
 * la primera es "Eliminar seleccionados" en la barra de la tabla.
 *
 * Solo llegan diagnósticos que nunca se publicaron; se borran con sus
 * categorías y preguntas. Envía: ids[].
 */
import { Trash2 } from '@lucide/vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import { rutas } from '@/lib/rutas';
import type { FilaDiagnostico } from '@/types/diagnosticos';

const props = defineProps<{ diagnosticos: FilaDiagnostico[] }>();

const emit = defineEmits<{ eliminados: [] }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm<{ ids: number[] }>({ ids: [] });

const cantidad = computed(() => props.diagnosticos.length);

watch(abierto, (valor) => valor && form.clearErrors());

function eliminar(): void {
    form.ids = props.diagnosticos.map((d) => d.id);
    form.delete(rutas.diagnosticos.eliminarVarios(), {
        preserveScroll: true,
        onSuccess: () => {
            abierto.value = false;
            emit('eliminados');
        },
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="
            cantidad === 1
                ? '¿Eliminar 1 diagnóstico?'
                : `¿Eliminar ${cantidad} diagnósticos?`
        "
        :icono="Trash2"
        tono="peligro"
    >
        <div class="flex flex-col gap-3 text-sm">
            <p>
                Nunca se publicaron, así que se borran del todo con sus
                categorías y preguntas. <strong>No se puede deshacer.</strong>
            </p>
            <ul
                class="max-h-56 overflow-y-auto rounded-md border border-linea bg-lienzo px-3 py-2"
            >
                <li
                    v-for="d in diagnosticos"
                    :key="d.id"
                    class="flex justify-between gap-3 py-1"
                >
                    <span class="font-medium">{{ d.nombre }}</span>
                    <span class="text-xs text-tinta-suave">
                        {{ d.sector_nombre }}
                    </span>
                </li>
            </ul>
            <Recuadro tono="peligro" v-if="form.errors.ids">
                {{ form.errors.ids }}
            </Recuadro>
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
                {{
                    cantidad === 1
                        ? 'Sí, eliminar 1 diagnóstico'
                        : `Sí, eliminar ${cantidad} diagnósticos`
                }}
            </Boton>
        </template>
    </Modal>
</template>
