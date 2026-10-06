<script setup lang="ts">
/**
 * L1 · Iniciar sesión (HU-001).
 *
 * Fortify recibe `email`, `password` y `remember`. Los mensajes de error no
 * dicen si el correo existe (RN-005): llegan del servidor tal cual.
 */
import { Form, Head, Link } from '@inertiajs/vue3';
import Aviso from '@/components/base/Aviso.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import CampoContrasena from '@/components/base/CampoContrasena.vue';
import Entrada from '@/components/base/Entrada.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Iniciar sesión',
        description: 'Entra con tu correo y contraseña.',
    },
});

defineProps<{
    /** Aviso que deja el servidor, por ejemplo "Contraseña actualizada" (HU-004). */
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Iniciar sesión" />

    <Aviso v-if="status" tono="exito" class="mb-5">{{ status }}</Aviso>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
    >
        <Campo etiqueta="Correo" para="email" :error="errors.email">
            <Entrada
                id="email"
                type="email"
                name="email"
                required
                v-focus
                autocomplete="email"
                placeholder="nombre@empresa.com"
                :invalida="!!errors.email"
            />
        </Campo>

        <Campo etiqueta="Contraseña" para="password" :error="errors.password">
            <template v-if="canResetPassword" #accion>
                <Link
                    :href="request()"
                    class="text-xs text-marca underline underline-offset-2 hover:text-marca-hover"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </template>
            <CampoContrasena
                id="password"
                name="password"
                required
                autocomplete="current-password"
                :invalida="!!errors.password"
            />
        </Campo>

        <label class="flex items-center gap-2 text-sm text-tinta">
            <input
                type="checkbox"
                name="remember"
                class="size-4 rounded border-linea-fuerte accent-marca"
            />
            Mantener la sesión iniciada en este equipo
        </label>

        <Boton
            type="submit"
            class="mt-2 w-full"
            :cargando="processing"
            data-test="login-button"
        >
            Iniciar sesión
        </Boton>

        <p class="text-center text-sm text-tinta-suave">
            ¿Aún no tienes cuenta?
            <Link
                :href="register()"
                class="font-medium text-marca underline underline-offset-2 hover:text-marca-hover"
            >
                Registrar mi empresa
            </Link>
        </p>
    </Form>
</template>
