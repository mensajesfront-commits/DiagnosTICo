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
import { Check, Shapes } from '@lucide/vue';
import { watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
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

const textoVersion = (b: DiagnosticoBorrador) =>
    `Borrador${b.version > 1 ? ` v${b.version}` : ''}`;
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        titulo="Crear categoría"
        descripcion="Se agrega al catálogo común. Después cada diagnóstico decide si la usa."
        ancho="lg"
        :icono="Shapes"
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
                    class="rounded-xl border border-dashed border-linea-fuerte px-3 py-3 text-center text-xs text-tinta-suave"
                >
                    No hay diagnósticos en borrador.
                </p>
                <div v-else class="grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="borrador in borradores"
                        :key="borrador.id"
                        :class="
                            cn(
                                'flex cursor-pointer items-start gap-2.5 rounded-xl border px-3 py-2.5 text-sm transition has-focus-visible:ring-2 has-focus-visible:ring-marca/30',
                                form.diagnosticos.includes(borrador.id)
                                    ? 'border-marca bg-marca-suave/40'
                                    : 'border-linea hover:border-marca/40',
                            )
                        "
                    >
                        <input
                            v-model="form.diagnosticos"
                            type="checkbox"
                            :value="borrador.id"
                            class="sr-only"
                        />
                        <span
                            :class="
                                cn(
                                    'mt-0.5 flex size-4 shrink-0 items-center justify-center rounded border',
                                    form.diagnosticos.includes(borrador.id)
                                        ? 'border-marca bg-marca text-white'
                                        : 'border-linea-fuerte bg-white',
                                )
                            "
                            aria-hidden="true"
                        >
                            <Check
                                v-if="form.diagnosticos.includes(borrador.id)"
                                class="size-3"
                            />
                        </span>
                        <span class="min-w-0">
                            <span class="block font-medium">
                                {{ borrador.nombre }}
                            </span>
                            <span class="block text-xs text-tinta-suave">
                                {{ borrador.sector_nombre }} ·
                                {{ textoVersion(borrador) }}
                            </span>
                        </span>
                    </label>
                </div>
                <p class="text-xs text-tinta-suave">
                    Solo se ofrecen borradores: una versión publicada no cambia.
                    Puedes agregarla más tarde desde el editor.
                </p>
            </fieldset>

            <Recuadro tono="neutro">
                La categoría nueva empieza sin preguntas. Los borradores donde
                se agregue quedan incompletos hasta escribirle al menos 1
                pregunta y ajustar la importancia.
            </Recuadro>
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
