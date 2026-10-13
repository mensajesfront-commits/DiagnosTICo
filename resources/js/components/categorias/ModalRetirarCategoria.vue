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
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
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
    >
        <div class="flex flex-col gap-3 text-sm">
            <div
                v-if="categoria.versiones_publicadas > 0"
                class="rounded-md border border-linea px-4 py-3"
            >
                <p class="font-semibold">
                    ✓ Versiones publicadas ({{
                        categoria.versiones_publicadas
                    }}): no cambian
                </p>
                <p class="mt-1 text-tinta-suave">
                    Siguen incluyendo la categoría y se pueden seguir asignando.
                </p>
            </div>

            <div
                v-if="categoria.borradores.length > 0"
                class="rounded-md border border-nivel-mejorar bg-alerta-suave px-4 py-3"
            >
                <p class="font-semibold text-alerta">
                    ⚠ Borradores ({{ categoria.borradores.length }}): la
                    categoría se quita
                </p>
                <p class="mt-1">
                    Su importancia queda pendiente de repartir entre las demás
                    categorías. No podrás publicarlos hasta ajustarla.
                </p>
                <ul class="mt-2 list-disc pl-5">
                    <li
                        v-for="borrador in categoria.borradores"
                        :key="borrador"
                    >
                        {{ borrador }}
                    </li>
                </ul>
            </div>

            <p
                v-if="archivar"
                class="rounded-md bg-lienzo px-3 py-2.5 text-xs text-tinta-suave"
            >
                Puedes restaurarla desde el filtro «Archivadas» del catálogo. Al
                restaurarla vuelve a estar disponible, pero no se agrega sola a
                los borradores.
            </p>
        </div>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                :variante="archivar ? 'primario' : 'peligro'"
                :cargando="form.processing"
                @click="confirmar"
            >
                {{ archivar ? 'Archivar categoría' : 'Eliminar categoría' }}
            </Boton>
        </template>
    </Modal>
</template>
