<script setup lang="ts">
/**
 * A2.7 · Duplicar un diagnóstico (HU-021). Crea un borrador v1 con el mismo
 * contenido (categorías, preguntas, puntajes e importancia), sin mediciones ni
 * empresas, y abre el editor.
 *
 * Envía: nombre (máx. 60) y sector_id.
 */
import { useForm } from '@inertiajs/vue3';
import { Copy, TriangleAlert } from '@lucide/vue';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
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
                nombre: `${props.diagnostico.nombre.slice(0, MAXIMO_NOMBRE - 8)} (copia)`,
                sector_id: props.diagnostico.sector_id,
            });
            form.reset();
            form.clearErrors();
        }
    },
    { immediate: true },
);

const otroSector = computed(
    () =>
        form.sector_id !== 0 && form.sector_id !== props.diagnostico.sector_id,
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
        :titulo="`Duplicar “${diagnostico.nombre}”`"
        descripcion="Se crea una copia en borrador para que puedas cambiarla sin tocar la publicada."
        :icono="Copy"
    >
        <form
            id="form-duplicar"
            class="flex flex-col gap-4"
            @submit.prevent="duplicar"
        >
            <div
                class="flex items-center gap-3 rounded-xl border border-linea bg-lienzo/50 px-3 py-2.5"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-marca shadow-sm"
                    aria-hidden="true"
                >
                    <Copy class="size-4" />
                </span>
                <div class="min-w-0 text-sm">
                    <p class="truncate font-medium">{{ diagnostico.nombre }}</p>
                    <p class="text-xs text-tinta-suave">
                        {{ diagnostico.sector_nombre }} ·
                        {{ diagnostico.categorias }} categorías ·
                        {{ diagnostico.preguntas }} preguntas
                    </p>
                </div>
            </div>

            <Campo
                obligatorio
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
                obligatorio
                etiqueta="Sector de la copia"
                para="copia-sector"
                :error="form.errors.sector_id"
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

            <p
                v-if="otroSector"
                class="flex items-start gap-2 rounded-xl bg-alerta-suave px-3 py-2.5 text-xs text-alerta"
            >
                <TriangleAlert
                    class="mt-px size-3.5 shrink-0"
                    aria-hidden="true"
                />
                Las preguntas están escritas para
                {{ diagnostico.sector_nombre }}: revisa el lenguaje antes de
                publicar.
            </p>

            <Recuadro tono="info">
                La copia empieza en la <strong>versión 1 (borrador)</strong> con
                las mismas {{ diagnostico.categorias }} categorías,
                {{ diagnostico.preguntas }} preguntas, puntajes e importancia.
                No copia mediciones ni empresas.
            </Recuadro>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-duplicar"
                class="gap-1.5"
                :cargando="form.processing"
            >
                <Copy class="size-4" aria-hidden="true" />
                Crear copia y editarla
            </Boton>
        </template>
    </Modal>
</template>
