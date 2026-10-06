<script setup lang="ts">
/**
 * L4 · Crea una contraseña nueva (HU-004).
 *
 * Fortify recibe `token`, `email`, `password` y `password_confirmation`. El
 * botón se activa cuando se cumplen todos los requisitos (RN-001). Al guardar
 * se vuelve a L1 con el aviso de contraseña actualizada.
 *
 * Si el enlace ya se usó, el servidor responde con un error en `email`; se
 * muestra "Este enlace ya no es válido" con el botón "Pedir otro enlace".
 */
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Aviso from '@/components/base/Aviso.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import CampoContrasena from '@/components/base/CampoContrasena.vue';
import RequisitosContrasena from '@/components/base/RequisitosContrasena.vue';
import { revisarContrasena } from '@/lib/contrasena';
import { request, update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Crea una contraseña nueva',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules?: string;
}>();

const contrasena = ref('');
const confirmacion = ref('');

const completa = computed(
    () => revisarContrasena(contrasena.value, confirmacion.value).completa,
);
const totalRequisitos = computed(
    () => revisarContrasena('', '').requisitos.length,
);
</script>

<template>
    <Head title="Crea una contraseña nueva" />

    <p class="-mt-3 mb-5 text-sm text-tinta-suave">
        Para la cuenta <strong class="text-tinta">{{ props.email }}</strong
        >.
    </p>

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
    >
        <Aviso
            v-if="errors.email"
            tono="error"
            titulo="Este enlace ya no es válido"
        >
            <p>{{ errors.email }}</p>
            <Link
                :href="request()"
                class="mt-2 inline-block font-medium text-marca underline underline-offset-2"
            >
                Pedir otro enlace
            </Link>
        </Aviso>

        <Campo
            etiqueta="Contraseña nueva"
            para="password"
            :error="errors.password"
        >
            <CampoContrasena
                id="password"
                v-model="contrasena"
                name="password"
                required
                v-focus
                autocomplete="new-password"
                :passwordrules="passwordRules"
                :invalida="!!errors.password"
            />
        </Campo>

        <Campo
            etiqueta="Confirmar contraseña"
            para="password_confirmation"
            :error="errors.password_confirmation"
        >
            <CampoContrasena
                id="password_confirmation"
                v-model="confirmacion"
                name="password_confirmation"
                required
                autocomplete="new-password"
                :passwordrules="passwordRules"
            />
        </Campo>

        <RequisitosContrasena
            :contrasena="contrasena"
            :confirmacion="confirmacion"
        />

        <div>
            <Boton
                type="submit"
                class="w-full"
                :disabled="!completa"
                :cargando="processing"
                data-test="reset-password-button"
            >
                Guardar contraseña
            </Boton>
            <p v-if="!completa" class="mt-2 text-xs text-tinta-suave">
                Se activa cuando cumplas los {{ totalRequisitos }} requisitos.
            </p>
        </div>
    </Form>
</template>
