<script setup lang="ts">
/**
 * E12.1 · Agregar colaborador (HU-077, RN-025).
 *
 * La empresa escribe el nombre, el correo (será su usuario) y la contraseña,
 * y después se los comparte. No se envía correo. Al guardar se abre E12.2
 * con los datos para compartir; la contraseña sale de lo que se escribió
 * aquí, el servidor no la devuelve.
 *
 * Envía: POST /colaboradores con name, email y password.
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import CampoContrasena from '@/components/base/CampoContrasena.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import RequisitosContrasena from '@/components/base/RequisitosContrasena.vue';
import { revisarContrasena } from '@/lib/contrasena';
import { rutas } from '@/lib/rutas';
import type { DatosDeAcceso } from '@/types/colaboradores';

const abierto = defineModel<boolean>('abierto', { default: false });

const emit = defineEmits<{ creado: [datos: DatosDeAcceso] }>();

const form = useForm({ name: '', email: '', password: '' });

watch(abierto, (valor) => {
    if (valor) {
        form.reset();
        form.clearErrors();
    }
});

const listo = computed(
    () =>
        form.name.trim() !== '' &&
        form.email.trim() !== '' &&
        revisarContrasena(form.password, '', { conConfirmacion: false })
            .completa,
);

function crear(): void {
    const datos: DatosDeAcceso = {
        nombre: form.name.trim(),
        correo: form.email.trim().toLowerCase(),
        contrasena: form.password,
    };

    form.post(rutas.colaboradores.crear(), {
        preserveScroll: true,
        onSuccess: () => {
            abierto.value = false;
            form.reset();
            emit('creado', datos);
        },
        onError: () => form.reset('password'),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        titulo="Agregar colaborador"
        descripcion="Crea una cuenta para alguien de tu equipo. Tendrá casi los mismos permisos que tú."
    >
        <form
            id="form-agregar-colaborador"
            class="flex flex-col gap-4"
            @submit.prevent="crear"
        >
            <p class="text-xs text-tinta-suave">
                Todos los campos son obligatorios.
            </p>
            <Campo
                obligatorio
                etiqueta="Nombre"
                para="colaborador-nombre"
                :error="form.errors.name"
            >
                <Entrada
                    id="colaborador-nombre"
                    v-model="form.name"
                    required
                    autocomplete="off"
                    maxlength="255"
                    :invalida="!!form.errors.name"
                />
            </Campo>
            <Campo
                obligatorio
                etiqueta="Correo (será su usuario)"
                para="colaborador-correo"
                ayuda="Con este correo iniciará sesión."
                :error="form.errors.email"
            >
                <Entrada
                    id="colaborador-correo"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="off"
                    :invalida="!!form.errors.email"
                />
            </Campo>
            <Campo
                obligatorio
                etiqueta="Contraseña"
                para="colaborador-contrasena"
                ayuda="Te la mostraremos una sola vez al crear la cuenta."
                :error="form.errors.password"
            >
                <CampoContrasena
                    id="colaborador-contrasena"
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
                No enviamos la contraseña por correo: tú se la compartes. Podrá
                cambiarla desde su perfil.
            </p>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-agregar-colaborador"
                :disabled="!listo"
                :cargando="form.processing"
            >
                Crear cuenta
            </Boton>
        </template>
    </Modal>
</template>
