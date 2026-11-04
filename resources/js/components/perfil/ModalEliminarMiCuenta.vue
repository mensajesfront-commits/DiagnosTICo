<script setup lang="ts">
/**
 * A6 · "Eliminar mi cuenta" de la cuenta principal del Administrador. Nadie
 * más puede eliminarla. Antes elige a otro Administrador activo como nueva
 * cuenta principal y escribe su propio correo (DEC-017). La cuenta se puede
 * recuperar durante 90 días desde Usuarios y roles.
 *
 * Envía: DELETE /mi-perfil/cuenta con sucesor_id y confirmacion.
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import Seleccion from '@/components/base/Seleccion.vue';

const props = defineProps<{
    correo: string;
    sucesores: { id: number; nombre: string; correo: string }[];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm<{ sucesor_id: number | ''; confirmacion: string }>({
    sucesor_id: '',
    confirmacion: '',
});

watch(abierto, (valor) => {
    if (valor) {
        form.reset();
        form.clearErrors();
    }
});

const listo = computed(
    () => form.sucesor_id !== '' && form.confirmacion.trim() === props.correo,
);

function eliminar(): void {
    form.delete('/mi-perfil/cuenta');
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        titulo="Eliminar mi cuenta"
        descripcion="Eres la cuenta principal del Administrador: antes de irte, elige quién queda en tu lugar."
    >
        <form
            id="form-eliminar-mi-cuenta"
            class="flex flex-col gap-4"
            @submit.prevent="listo && eliminar()"
        >
            <Campo
                obligatorio
                etiqueta="Nueva cuenta principal"
                para="sucesor"
                ayuda="Nadie más podrá desactivarla, eliminarla ni quitarle el rol."
                :error="form.errors.sucesor_id"
            >
                <Seleccion
                    id="sucesor"
                    v-model="form.sucesor_id"
                    :invalida="!!form.errors.sucesor_id"
                >
                    <option value="" disabled>Elige un Administrador</option>
                    <option v-for="s in sucesores" :key="s.id" :value="s.id">
                        {{ s.nombre }} · {{ s.correo }}
                    </option>
                </Seleccion>
            </Campo>
            <Campo
                obligatorio
                etiqueta="Escribe tu correo para confirmar"
                para="confirmacion"
                :error="form.errors.confirmacion"
            >
                <Entrada
                    id="confirmacion"
                    v-model="form.confirmacion"
                    autocomplete="off"
                    :placeholder="correo"
                    :invalida="!!form.errors.confirmacion"
                />
            </Campo>
            <p class="rounded-md bg-aviso-suave px-3 py-2.5 text-xs text-aviso">
                Tu sesión se cierra y ya no podrás entrar. Otro Administrador
                puede recuperar la cuenta durante 90 días desde Usuarios y
                roles.
            </p>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-eliminar-mi-cuenta"
                class="bg-aviso hover:bg-aviso/90"
                :disabled="!listo"
                :cargando="form.processing"
            >
                Eliminar mi cuenta
            </Boton>
        </template>
    </Modal>
</template>
