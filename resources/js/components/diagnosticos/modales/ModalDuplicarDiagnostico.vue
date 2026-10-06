<script setup lang="ts">
/**
 * A2.7 · Duplicar un diagnóstico (HU-021). Crea un borrador v1 con el mismo
 * contenido (categorías, preguntas, puntajes e importancia), sin mediciones ni
 * empresas, y abre el editor.
 *
 * Envía: nombre (máx. 60) y sector_id.
 */
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import { rutas } from '@/lib/rutas';
import type { FilaDiagnostico, Sector } from '@/types/diagnosticos';

const MAXIMO_NOMBRE = 60;

const props = defineProps<{
    diagnostico: FilaDiagnostico;
    sectores: Sector[];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({ nombre: '', sector_id: 0 });

watch(
    abierto,
    (valor) => {
        if (valor) {
            form.defaults({
                nombre: `${props.diagnostico.nombre} · ${props.diagnostico.sector_nombre} (copia)`.slice(
                    0,
                    MAXIMO_NOMBRE,
                ),
                sector_id: props.diagnostico.sector_id,
            });
            form.reset();
            form.clearErrors();
        }
    },
    { immediate: true },
);

function duplicar(): void {
    form.post(rutas.diagnosticos.duplicar(props.diagnostico.id), {
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`Duplicar “${diagnostico.nombre} · ${diagnostico.sector_nombre}”`"
        descripcion="Se crea una copia en borrador para que puedas cambiarla sin tocar la publicada."
    >
        <form
            id="form-duplicar"
            class="flex flex-col gap-4"
            @submit.prevent="duplicar"
        >
            <Campo
                etiqueta="Nombre de la copia"
                para="copia-nombre"
                ayuda="Máximo 60 caracteres. Puedes cambiarlo después."
                :contador="`${form.nombre.length}/${MAXIMO_NOMBRE}`"
                :error="form.errors.nombre"
            >
                <Entrada
                    id="copia-nombre"
                    v-model="form.nombre"
                    required
                    :maxlength="MAXIMO_NOMBRE"
                    :invalida="!!form.errors.nombre"
                />
            </Campo>

            <Campo
                etiqueta="Sector de la copia"
                para="copia-sector"
                :error="form.errors.sector_id"
                ayuda="Si eliges otro sector, revisa el lenguaje de las preguntas antes de publicar."
            >
                <Seleccion id="copia-sector" v-model="form.sector_id" required>
                    <option
                        v-for="sector in sectores"
                        :key="sector.id"
                        :value="sector.id"
                    >
                        {{ sector.nombre }}
                    </option>
                </Seleccion>
            </Campo>

            <p class="rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave">
                La copia empieza en la <strong>versión 1 (borrador)</strong> con
                las mismas {{ diagnostico.categorias }} categorías,
                {{ diagnostico.preguntas }} preguntas, puntajes e importancia.
                No copia mediciones ni empresas.
            </p>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-duplicar"
                :cargando="form.processing"
            >
                Crear copia y editarla
            </Boton>
        </template>
    </Modal>
</template>
