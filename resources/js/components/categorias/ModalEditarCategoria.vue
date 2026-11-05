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
import { Archive, Pencil, Trash2 } from '@lucide/vue';
import { watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
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
        :icono="Pencil"
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

            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-lienzo/70 px-2 py-2.5">
                    <p class="font-mono text-lg leading-none font-semibold">
                        {{ categoria.diagnosticos }}
                        <span class="text-xs font-normal text-tinta-suave">
                            / {{ totalDiagnosticos }}
                        </span>
                    </p>
                    <p class="mt-1 text-[11px] text-tinta-suave">
                        diagnósticos la usan
                    </p>
                </div>
                <div class="rounded-xl bg-lienzo/70 px-2 py-2.5">
                    <p class="font-mono text-lg leading-none font-semibold">
                        {{ categoria.preguntas }}
                    </p>
                    <p class="mt-1 text-[11px] text-tinta-suave">preguntas</p>
                </div>
                <div class="rounded-xl bg-lienzo/70 px-2 py-2.5">
                    <p class="font-mono text-lg leading-none font-semibold">
                        {{ categoria.versiones_publicadas }}
                    </p>
                    <p class="mt-1 text-[11px] text-tinta-suave">
                        versiones publicadas
                    </p>
                </div>
            </div>

            <Recuadro tono="info">
                Para agregarla o quitarla de un diagnóstico, abre ese
                diagnóstico en el editor.
            </Recuadro>

            <Recuadro
                v-if="categoria.diagnosticos > 0"
                tono="alerta"
                titulo="El cambio de nombre o descripción se verá en todas partes"
            >
                En los {{ categoria.diagnosticos }} diagnósticos<template
                    v-if="categoria.versiones_publicadas > 0"
                >
                    (también en las
                    {{ categoria.versiones_publicadas }} versiones
                    publicadas)</template
                >
                y en los resultados e informes PDF anteriores.
            </Recuadro>

            <Recuadro tono="neutro">
                <strong>Al archivar:</strong> deja de poder agregarse a
                diagnósticos y se conserva en los resultados anteriores. Puedes
                restaurarla desde el filtro «Archivadas».
                <template v-if="!categoria.tiene_respuestas">
                    <br />
                    <strong>Al eliminar:</strong> ninguna empresa la ha
                    respondido, así que se borra del catálogo.
                </template>
            </Recuadro>
        </form>

        <template #pie>
            <span class="mr-auto flex gap-2">
                <Boton
                    variante="secundario"
                    class="gap-1.5"
                    @click="retirar('archivar')"
                >
                    <Archive class="size-4" aria-hidden="true" />
                    Archivar…
                </Boton>
                <Boton
                    v-if="!categoria.tiene_respuestas"
                    variante="secundario"
                    class="gap-1.5 text-aviso"
                    @click="retirar('eliminar')"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
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
