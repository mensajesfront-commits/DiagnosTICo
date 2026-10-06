<script setup lang="ts">
/**
 * E12.3 · Cambiar la contraseña de un colaborador (HU-078, RN-025).
 *
 * La empresa define la contraseña nueva y se la comparte (E12.2). La
 * anterior deja de funcionar y se cierran las sesiones abiertas del
 * colaborador.
 *
 * Envía: PUT /colaboradores/{id}/contrasena con password.
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import CampoContrasena from '@/components/base/CampoContrasena.vue';
import Modal from '@/components/base/Modal.vue';
import RequisitosContrasena from '@/components/base/RequisitosContrasena.vue';
import type { DatosDeAcceso } from '@/types/colaboradores';
import { revisarContrasena } from '@/lib/contrasena';
import { rutas } from '@/lib/rutas';
import type { Colaborador } from '@/types/colaboradores';

const props = defineProps<{ colaborador: Colaborador }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const emit = defineEmits<{ cambiada: [datos: DatosDeAcceso] }>();

const form = useForm({ password: '' });

watch(abierto, (valor) => {
    if (valor) {
        form.reset();
        form.clearErrors();
    }
});

const primerNombre = computed(() => props.colaborador.nombre.split(/\s+/)[0]);

const listo = computed(
    () =>
        revisarContrasena(form.password, '', { conConfirmacion: false })
            .completa,
);

function guardar(): void {
    const datos: DatosDeAcceso = {
        nombre: props.colaborador.nombre,
        correo: props.colaborador.correo,
        contrasena: form.password,
    };

    form.put(rutas.colaboradores.contrasena(props.colaborador.id), {
        preserveScroll: true,
        onSuccess: () => {
            abierto.value = false;
            form.reset();
            emit('cambiada', datos);
        },
        onError: () => form.reset(),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`Cambiar contraseña de ${colaborador.nombre}`"
        :descripcion="`Define una nueva contraseña para ${colaborador.correo}.`"
    >
        <form
            id="form-contrasena-colaborador"
            class="flex flex-col gap-4"
            @submit.prevent="guardar"
        >
            <Campo
                etiqueta="Contraseña nueva"
                para="colaborador-contrasena-nueva"
                ayuda="La contraseña anterior deja de funcionar."
                :error="form.errors.password"
            >
                <CampoContrasena
                    id="colaborador-contrasena-nueva"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    :invalida="!!form.errors.password"
                />
            </Campo>
            <RequisitosContrasena :contrasena="form.password" />
            <p
                class="rounded-md bg-lienzo-oscuro/60 px-3 py-2.5 text-xs text-tinta-suave"
            >
                Si {{ primerNombre }} tiene la sesión abierta, se cerrará.
                Compártele la nueva contraseña por un medio seguro.
            </p>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-contrasena-colaborador"
                :disabled="!listo"
                :cargando="form.processing"
            >
                Guardar contraseña
            </Boton>
        </template>
    </Modal>
</template>
