<script setup lang="ts">
/**
 * A2.3b · Crear categoría (HU-018) y editar una categoría del catálogo
 * (HU-017 CA-003 a CA-005).
 *
 * Envía: nombre (obligatorio, máx. 40, único; RN-009), descripcion (la lee la
 * empresa al iniciar la categoría) y, al crear, diagnosticos[] (borradores
 * donde agregarla; quedan incompletos).
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';
import type { Categoria, DiagnosticoBorrador } from '@/types/diagnosticos';

const MAXIMO_NOMBRE = 40;

const props = withDefaults(
    defineProps<{
        /** Sin categoría se crea una nueva. */
        categoria?: Categoria | null;
        /** Diagnósticos en borrador donde se puede agregar al crearla. */
        borradores?: DiagnosticoBorrador[];
    }>(),
    { categoria: null, borradores: () => [] },
);

const abierto = defineModel<boolean>('abierto', { default: false });

const editando = computed(() => !!props.categoria);

const form = useForm<{
    nombre: string;
    descripcion: string;
    diagnosticos: number[];
}>({ nombre: '', descripcion: '', diagnosticos: [] });

watch(
    abierto,
    (valor) => {
        if (valor) {
            form.defaults({
                nombre: props.categoria?.nombre ?? '',
                descripcion: props.categoria?.descripcion ?? '',
                diagnosticos: [],
            });
            form.reset();
            form.clearErrors();
        }
    },
    { immediate: true },
);

function guardar(): void {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    };

    if (props.categoria) {
        form.put(rutas.categorias.actualizar(props.categoria.id), opciones);
    } else {
        form.post(rutas.categorias.crear(), opciones);
    }
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="
            editando
                ? `Editar categoría · ${categoria?.nombre}`
                : 'Crear categoría'
        "
        :descripcion="
            editando
                ? 'El cambio se ve en todos los diagnósticos que usan esta categoría.'
                : 'Se agrega al catálogo para que cualquier diagnóstico pueda usarla.'
        "
        ancho="lg"
    >
        <form
            id="form-categoria"
            class="flex flex-col gap-4"
            @submit.prevent="guardar"
        >
            <Campo
                etiqueta="Nombre de la categoría"
                para="categoria-nombre"
                ayuda="Obligatorio y único en el catálogo."
                :contador="`${form.nombre.length}/${MAXIMO_NOMBRE}`"
                :error="form.errors.nombre"
            >
                <Entrada
                    id="categoria-nombre"
                    v-model="form.nombre"
                    required
                    :maxlength="MAXIMO_NOMBRE"
                    placeholder="Ej. Redes sociales"
                    :invalida="!!form.errors.nombre"
                />
            </Campo>

            <Campo
                etiqueta="Descripción"
                para="categoria-descripcion"
                opcional
                ayuda="La empresa la lee al iniciar esta categoría del diagnóstico."
                :error="form.errors.descripcion"
            >
                <AreaTexto
                    id="categoria-descripcion"
                    v-model="form.descripcion"
                    rows="3"
                />
            </Campo>

            <fieldset v-if="!editando" class="flex flex-col gap-2">
                <legend class="mb-1 text-xs font-medium">
                    Agregarla también a diagnósticos en borrador
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
                    {{ borrador.nombre }}
                    <span class="text-xs text-tinta-suave">
                        · {{ borrador.sector_nombre }}
                    </span>
                </label>
                <p
                    class="rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave"
                >
                    Solo se pueden elegir borradores: las versiones publicadas
                    no cambian. Los diagnósticos elegidos quedan incompletos
                    hasta que agregues preguntas a la categoría y ajustes la
                    importancia.
                </p>
            </fieldset>
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
                {{ editando ? 'Guardar cambios' : 'Crear categoría' }}
            </Boton>
        </template>
    </Modal>
</template>
