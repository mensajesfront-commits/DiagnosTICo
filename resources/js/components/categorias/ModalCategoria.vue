<script setup lang="ts">
/**
 * A2.3b · Crear categoría (HU-018).
 *
 * Envía: nombre (obligatorio, máx. 40, único; RN-009), descripcion (la lee la
 * empresa al iniciar la categoría) y diagnosticos[] (borradores donde
 * agregarla; quedan incompletos hasta escribirle preguntas).
 * Editar se hace en otro modal (ModalEditarCategoria).
 */
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';
import type { DiagnosticoBorrador } from '@/types/diagnosticos';

const MAXIMO_NOMBRE = 40;

withDefaults(
    defineProps<{
        /** Diagnósticos en borrador donde se puede agregar al crearla. */
        borradores?: DiagnosticoBorrador[];
    }>(),
    { borradores: () => [] },
);

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm<{
    nombre: string;
    descripcion: string;
    diagnosticos: number[];
}>({ nombre: '', descripcion: '', diagnosticos: [] });

watch(
    abierto,
    (valor) => {
        if (valor) {
            form.reset();
            form.clearErrors();
        }
    },
    { immediate: true },
);

function crear(): void {
    form.post(rutas.categorias.crear(), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}

const textoBorrador = (b: DiagnosticoBorrador) =>
    `${b.nombre} · ${b.sector_nombre} (borrador${b.version > 1 ? ` v${b.version}` : ''})`;
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        titulo="Crear categoría"
        descripcion="Se agrega al catálogo común. Después cada diagnóstico decide si la usa."
        ancho="lg"
    >
        <form
            id="form-categoria"
            class="flex flex-col gap-4"
            @submit.prevent="crear"
        >
            <Campo
                obligatorio
                etiqueta="Nombre"
                para="categoria-nombre"
                ayuda="No puede repetirse en el catálogo."
                :contador="`${form.nombre.length}/${MAXIMO_NOMBRE}`"
                :error="form.errors.nombre"
            >
                <Entrada
                    id="categoria-nombre"
                    v-model="form.nombre"
                    required
                    :maxlength="MAXIMO_NOMBRE"
                    :invalida="!!form.errors.nombre"
                />
            </Campo>

            <Campo
                etiqueta="Descripción para la empresa"
                para="categoria-descripcion"
                ayuda="Se muestra al iniciar la categoría en el diagnóstico."
                :error="form.errors.descripcion"
            >
                <AreaTexto
                    id="categoria-descripcion"
                    v-model="form.descripcion"
                    rows="2"
                />
            </Campo>

            <fieldset class="flex flex-col gap-2">
                <legend class="mb-1 text-xs font-medium">
                    Agregarla ahora a borradores
                    <span class="font-normal text-tinta-suave">(opcional)</span>
                </legend>
                <p
                    v-if="borradores.length === 0"
                    class="text-xs text-tinta-suave"
                >
                    No hay diagnósticos en borrador.
                </p>
                <label
                    v-for="borrador in borradores"
                    :key="borrador.id"
                    class="flex items-center gap-2 text-sm"
                >
                    <input
                        v-model="form.diagnosticos"
                        type="checkbox"
                        :value="borrador.id"
                        class="size-4 accent-marca"
                    />
                    {{ textoBorrador(borrador) }}
                </label>
                <p class="text-xs text-tinta-suave">
                    Solo se ofrecen borradores: una versión publicada no cambia.
                    Puedes agregarla más tarde desde el editor.
                </p>
            </fieldset>

            <p
                class="rounded-md bg-lienzo px-3 py-2.5 text-xs text-tinta-suave"
            >
                La categoría nueva empieza sin preguntas. Los borradores donde
                se agregue quedan incompletos hasta escribirle al menos 1
                pregunta y ajustar la importancia.
            </p>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-categoria"
                :cargando="form.processing"
            >
                Crear categoría
            </Boton>
        </template>
    </Modal>
</template>
