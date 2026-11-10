<script setup lang="ts">
/**
 * A3.1 · Eliminar una empresa desactivada (pedido del 10 de noviembre de
 * 2026; solo con usuarios.gestionar).
 *
 * Igual que eliminar su cuenta principal en A5 (DEC-017): la empresa y todas
 * sus cuentas se eliminan juntas y se recuperan juntas durante 90 días desde
 * Usuarios y roles; después se borran para siempre y sus mediciones
 * terminadas quedan sin nombre para las estadísticas. Se confirma escribiendo
 * el nombre de la empresa a mano (no se puede pegar).
 *
 * Envía: DELETE /empresas/{id} con confirmacion (el nombre).
 */
import { Trash2 } from '@lucide/vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import { rutas } from '@/lib/rutas';

const props = defineProps<{
    empresa: { id: number; nombre: string };
    /** Nombre de la cuenta principal; null si la empresa se quedó sin ella. */
    principal: string | null;
    /** Mediciones de la empresa (no canceladas). */
    mediciones: number;
    /** Colaboradores de la empresa. */
    colaboradores: number;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({ confirmacion: '' });

watch(abierto, (valor) => {
    if (valor) {
        form.reset();
        form.clearErrors();
    }
});

const coincide = computed(
    () => form.confirmacion.trim() === props.empresa.nombre,
);

function eliminar(): void {
    form.delete(rutas.empresas.eliminar(props.empresa.id), {
        onSuccess: () => (abierto.value = false),
    });
}

const plural = (n: number, uno: string, varios: string) =>
    `${n} ${n === 1 ? uno : varios}`;
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`¿Eliminar ${empresa.nombre}?`"
        :icono="Trash2"
        tono="peligro"
    >
        <form
            id="form-eliminar-empresa"
            class="flex flex-col gap-4 text-sm"
            @submit.prevent="coincide && eliminar()"
        >
            <Recuadro tono="peligro">
                <p class="font-medium">
                    Sale de todas las listas y nadie de la empresa vuelve a
                    entrar.
                </p>
                <p class="mt-1">
                    Se eliminan la empresa, su cuenta principal<template
                        v-if="colaboradores > 0"
                    >
                        y
                        {{
                            plural(
                                colaboradores,
                                'colaborador',
                                'colaboradores',
                            )
                        }}</template
                    >. Tienes 90 días para recuperarla; después se borra para
                    siempre y sus
                    {{ plural(mediciones, 'medición', 'mediciones') }}
                    terminadas quedan sin nombre, solo para las estadísticas.
                </p>
                <p v-if="principal" class="mt-1">
                    Para recuperarla, ve a Usuarios y roles, filtra por Estado
                    «Eliminadas» y recupera la cuenta de {{ principal }}.
                </p>
                <p v-else class="mt-1 font-medium">
                    No tiene cuenta principal: después de eliminarla no se puede
                    recuperar.
                </p>
            </Recuadro>
            <Campo
                obligatorio
                etiqueta="Para confirmar, escribe el nombre de la empresa"
                para="confirmar-empresa"
                :error="form.errors.confirmacion"
            >
                <template #accion>
                    <span class="font-mono text-xs text-tinta-suave select-all">
                        {{ empresa.nombre }}
                    </span>
                </template>
                <Entrada
                    id="confirmar-empresa"
                    v-model="form.confirmacion"
                    required
                    autocomplete="off"
                    spellcheck="false"
                    :placeholder="empresa.nombre"
                    :invalida="!!form.errors.confirmacion"
                    @paste.prevent
                />
            </Campo>
            <p class="-mt-2 text-xs text-tinta-suave">
                Escríbelo a mano, tal como aparece (no se puede pegar).
            </p>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-eliminar-empresa"
                variante="peligro"
                :disabled="!coincide"
                :cargando="form.processing"
            >
                Eliminar empresa
            </Boton>
        </template>
    </Modal>
</template>
