<script setup lang="ts">
/**
 * A2.3 · Modal "Editar categoría" (HU-017 CA-003 a CA-005). Se abre en el
 * centro de la pantalla para que la tabla del catálogo use todo el ancho.
 *
 * Envía: nombre (máx. 40, único) y descripcion. El cambio se ve en todos los
 * diagnósticos, también en las versiones publicadas y en los resultados y PDF
 * anteriores, porque el nombre vive en el catálogo.
 */
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';
import type { Categoria } from '@/types/diagnosticos';

const MAXIMO_NOMBRE = 40;

const props = defineProps<{
    categoria: Categoria;
    /** Diagnósticos activos en total ("13 de 13"). */
    totalDiagnosticos: number;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const emit = defineEmits<{ retirar: [accion: 'archivar' | 'eliminar'] }>();

const form = useForm({ nombre: '', descripcion: '' });

watch(
    [abierto, () => props.categoria.id],
    ([valor]) => {
        if (valor) {
            form.defaults({
                nombre: props.categoria.nombre,
                descripcion: props.categoria.descripcion ?? '',
            });
            form.reset();
            form.clearErrors();
        }
    },
    { immediate: true },
);

function guardar(): void {
    form.put(rutas.categorias.actualizar(props.categoria.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}

function retirar(accion: 'archivar' | 'eliminar'): void {
    abierto.value = false;
    emit('retirar', accion);
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`Editar categoría · ${categoria.nombre}`"
        ancho="lg"
    >
        <form
            id="form-editar-categoria"
            class="flex flex-col gap-4"
            @submit.prevent="guardar"
        >
            <Campo
                obligatorio
                etiqueta="Nombre"
                para="editar-nombre"
                ayuda="No puede repetirse en el catálogo."
                :contador="`${form.nombre.length}/${MAXIMO_NOMBRE}`"
                :error="form.errors.nombre"
            >
                <Entrada
                    id="editar-nombre"
                    v-model="form.nombre"
                    required
                    :maxlength="MAXIMO_NOMBRE"
                    :invalida="!!form.errors.nombre"
                />
            </Campo>

            <Campo
                etiqueta="Descripción para la empresa"
                para="editar-descripcion"
                ayuda="Se muestra al iniciar la categoría en el diagnóstico."
                :error="form.errors.descripcion"
            >
                <AreaTexto
                    id="editar-descripcion"
                    v-model="form.descripcion"
                    rows="3"
                />
            </Campo>

            <p class="rounded-md bg-marca-suave px-3 py-2.5 text-sm">
                <strong>
                    Usada en {{ categoria.diagnosticos }} de
                    {{ totalDiagnosticos }} diagnósticos.
                </strong>
                Para agregarla o quitarla de un diagnóstico, abre ese
                diagnóstico en el editor.
            </p>

            <p
                v-if="categoria.diagnosticos > 0"
                class="rounded-md bg-alerta-suave px-3 py-2.5 text-xs"
            >
                <strong class="text-alerta">
                    ⚠ El cambio de nombre o descripción se verá en todas partes:
                </strong>
                en los {{ categoria.diagnosticos }} diagnósticos<template
                    v-if="categoria.versiones_publicadas > 0"
                >
                    (también en las
                    {{ categoria.versiones_publicadas }} versiones
                    publicadas)</template
                >
                y en los resultados e informes PDF anteriores.
            </p>

            <p
                class="rounded-md bg-lienzo px-3 py-2.5 text-xs text-tinta-suave"
            >
                <strong class="text-tinta">Al archivar:</strong> deja de poder
                agregarse a diagnósticos y se conserva en los resultados
                anteriores. Puedes restaurarla desde el filtro «Archivadas».
                <template v-if="!categoria.tiene_respuestas">
                    <br />
                    <strong class="text-tinta">Al eliminar:</strong> ninguna
                    empresa la ha respondido, así que se borra del catálogo.
                </template>
            </p>
        </form>

        <template #pie>
            <span class="mr-auto flex gap-2">
                <Boton variante="secundario" @click="retirar('archivar')">
                    Archivar…
                </Boton>
                <Boton
                    v-if="!categoria.tiene_respuestas"
                    variante="secundario"
                    @click="retirar('eliminar')"
                >
                    Eliminar…
                </Boton>
            </span>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-editar-categoria"
                :disabled="!form.isDirty"
                :cargando="form.processing"
            >
                Guardar cambios
            </Boton>
        </template>
    </Modal>
</template>
