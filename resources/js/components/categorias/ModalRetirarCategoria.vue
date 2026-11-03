<script setup lang="ts">
/**
 * A2.3c · Archivar o eliminar una categoría (HU-019).
 *
 * `accion`:
 * - "archivar" (siempre se puede, RN-009): las versiones publicadas no
 *   cambian, se quita de los borradores (quedan sin publicar hasta repartir
 *   su importancia) y se puede restaurar desde «Archivadas».
 * - "eliminar" (solo si nunca se respondió): se borra del catálogo.
 *   [FUNCIONALIDAD POR DEFINIR] El wireframe solo muestra archivar; eliminar
 *   sale de HU-019.
 */
import { useForm } from '@inertiajs/vue3';
import { Archive, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import { rutas } from '@/lib/rutas';
import type { Categoria } from '@/types/diagnosticos';

const props = withDefaults(
    defineProps<{
        categoria: Categoria;
        accion?: 'archivar' | 'eliminar';
    }>(),
    { accion: 'archivar' },
);

// Con respuestas solo se archiva, aunque se pida eliminar.
const archivar = computed(
    () => props.accion === 'archivar' || props.categoria.tiene_respuestas,
);

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({});

function confirmar(): void {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    };

    if (archivar.value) {
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
            archivar
                ? `¿Archivar la categoría “${categoria.nombre}”?`
                : `¿Eliminar la categoría “${categoria.nombre}”?`
        "
        :descripcion="
            !archivar
                ? 'Ninguna empresa la ha respondido, por eso se puede eliminar del catálogo. No se puede deshacer.'
                : categoria.tiene_respuestas
                  ? 'Ya tiene respuestas de empresas, por eso no se borra: se archiva. Los resultados y mediciones anteriores la conservan.'
                  : 'Deja de ofrecerse para diagnósticos nuevos. No se borra: la puedes restaurar cuando quieras.'
        "
        ancho="lg"
        :icono="archivar ? Archive : Trash2"
        :tono="archivar ? 'alerta' : 'peligro'"
    >
        <div class="flex flex-col gap-3 text-sm">
            <Recuadro
                v-if="categoria.versiones_publicadas > 0"
                tono="exito"
                :titulo="`Versiones publicadas (${categoria.versiones_publicadas}): no cambian`"
            >
                Siguen incluyendo la categoría y se pueden seguir asignando.
            </Recuadro>

            <Recuadro
                v-if="categoria.borradores.length > 0"
                tono="alerta"
                :titulo="`Borradores (${categoria.borradores.length}): la categoría se quita`"
            >
                Su importancia queda pendiente de repartir entre las demás
                categorías. No podrás publicarlos hasta ajustarla.
                <ul class="mt-2 flex flex-col gap-1">
                    <li
                        v-for="borrador in categoria.borradores"
                        :key="borrador"
                        class="rounded-lg bg-white/70 px-2.5 py-1 text-xs"
                    >
                        {{ borrador }}
                    </li>
                </ul>
            </Recuadro>

            <Recuadro v-if="archivar" tono="neutro">
                Puedes restaurarla desde el filtro «Archivadas» del catálogo. Al
                restaurarla vuelve a estar disponible, pero no se agrega sola a
                los borradores.
            </Recuadro>
            <Recuadro v-else tono="peligro" titulo="No se puede deshacer">
                La categoría se borra del catálogo con su descripción.
            </Recuadro>
        </div>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                :variante="archivar ? 'primario' : 'peligro'"
                class="gap-1.5"
                :cargando="form.processing"
                @click="confirmar"
            >
                <Archive v-if="archivar" class="size-4" aria-hidden="true" />
                <Trash2 v-else class="size-4" aria-hidden="true" />
                {{ archivar ? 'Archivar categoría' : 'Eliminar categoría' }}
            </Boton>
        </template>
    </Modal>
</template>
