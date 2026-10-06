<script setup lang="ts">
/**
 * Cambiar contraseña (A6 en línea, E11 en un modal).
 *
 * Envía current_password, password y password_confirmation a
 * PUT /mi-perfil/contrasena. La sesión sigue abierta después del cambio.
 */
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import CampoContrasena from '@/components/base/CampoContrasena.vue';
import RequisitosContrasena from '@/components/base/RequisitosContrasena.vue';
import { revisarContrasena } from '@/lib/contrasena';
import { update as actualizarContrasena } from '@/routes/user-password';

withDefaults(defineProps<{ conPie?: boolean }>(), { conPie: true });

const emit = defineEmits<{ actualizada: [] }>();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const listo = computed(
    () =>
        form.current_password !== '' &&
        revisarContrasena(form.password, form.password_confirmation).completa,
);

function enviar(): void {
    form.put(actualizarContrasena().url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('actualizada');
        },
        onError: () => form.reset('current_password'),
    });
}

defineExpose({ listo, form, enviar });
</script>

<template>
    <form
        id="form-contrasena"
        class="flex flex-col gap-4"
        @submit.prevent="enviar"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <Campo
                etiqueta="Contraseña actual"
                para="contrasena-actual"
                :error="form.errors.current_password"
            >
                <CampoContrasena
                    id="contrasena-actual"
                    v-model="form.current_password"
                    autocomplete="current-password"
                    :invalida="!!form.errors.current_password"
                />
            </Campo>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <Campo
                etiqueta="Contraseña nueva"
                para="contrasena-nueva"
                :error="form.errors.password"
            >
                <CampoContrasena
                    id="contrasena-nueva"
                    v-model="form.password"
                    autocomplete="new-password"
                    :invalida="!!form.errors.password"
                />
            </Campo>
            <Campo
                etiqueta="Confirmar contraseña nueva"
                para="contrasena-confirmacion"
            >
                <CampoContrasena
                    id="contrasena-confirmacion"
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                />
            </Campo>
        </div>
        <RequisitosContrasena
            :contrasena="form.password"
            :confirmacion="form.password_confirmation"
            columnas
        />

        <div
            v-if="conPie"
            class="-mx-6 -mb-6 flex flex-wrap items-center justify-between gap-3 rounded-b-xl border-t border-linea bg-[#f9f8f4] px-6 py-4"
        >
            <p class="text-xs text-tinta-suave">
                Completa los tres campos y cumple los requisitos para continuar.
            </p>
            <Boton type="submit" :disabled="!listo" :cargando="form.processing">
                Actualizar contraseña
            </Boton>
        </div>
    </form>
</template>
