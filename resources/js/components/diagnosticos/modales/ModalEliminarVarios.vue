<script setup lang="ts">
/**
 * Eliminar varios diagnósticos a la vez (A2). Es la segunda confirmación:
 * la primera es "Eliminar seleccionados" en la barra de la tabla.
 *
 * Solo llegan diagnósticos que nunca se publicaron; se borran con sus
 * categorías y preguntas. Envía: ids[].
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
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
            <p
                v-if="form.errors.ids"
                class="rounded-md bg-aviso-suave px-3 py-2 text-aviso"
            >
                {{ form.errors.ids }}
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
                {{
                    cantidad === 1
                        ? 'Sí, eliminar 1 diagnóstico'
                        : `Sí, eliminar ${cantidad} diagnósticos`
                }}
            </Boton>
        </template>
    </Modal>
</template>
