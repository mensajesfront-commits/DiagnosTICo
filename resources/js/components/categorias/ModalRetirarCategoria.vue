<script setup lang="ts">
/**
 * A2.3c · Eliminar o archivar una categoría (HU-019).
 *
 * Nunca respondida → se elimina. Con respuestas → se archiva: deja de poder
 * elegirse y los resultados anteriores la siguen mostrando (RN-009). En los
 * diagnósticos que la usaban, la importancia se reparte de nuevo y quedan
 * incompletos hasta ajustarla.
 */
import { useForm } from '@inertiajs/vue3';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';
import type { Categoria } from '@/types/diagnosticos';

const props = defineProps<{ categoria: Categoria }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({});

function confirmar(): void {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    };

    if (props.categoria.tiene_respuestas) {
        form.post(rutas.categorias.archivar(props.categoria.id), opciones);
    } else {
        form.delete(rutas.categorias.eliminar(props.categoria.id), opciones);
    }
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="
            categoria.tiene_respuestas
                ? `¿Archivar la categoría “${categoria.nombre}”?`
                : `¿Eliminar la categoría “${categoria.nombre}”?`
        "
    >
        <div class="flex flex-col gap-3 text-sm">
            <p v-if="categoria.tiene_respuestas">
                Ya tiene respuestas de empresas, así que no se borra: se
                archiva. Deja de poder elegirse en diagnósticos nuevos y los
                resultados anteriores la siguen mostrando.
            </p>
            <p v-else>
                Nunca fue respondida, así que se elimina del catálogo.
                <strong>No se puede deshacer.</strong>
            </p>
            <p
                v-if="categoria.diagnosticos > 0"
                class="rounded-md bg-alerta-suave px-3 py-2 text-xs text-alerta"
            >
                ⚠ Está en {{ categoria.diagnosticos }}
                {{
                    categoria.diagnosticos === 1
                        ? 'diagnóstico'
                        : 'diagnósticos'
                }}: la importancia se reparte de nuevo y quedan incompletos
                hasta que la ajustes.
            </p>
        </div>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                :variante="categoria.tiene_respuestas ? 'primario' : 'peligro'"
                :cargando="form.processing"
                @click="confirmar"
            >
                {{
                    categoria.tiene_respuestas
                        ? 'Archivar categoría'
                        : 'Eliminar categoría'
                }}
            </Boton>
        </template>
    </Modal>
</template>
