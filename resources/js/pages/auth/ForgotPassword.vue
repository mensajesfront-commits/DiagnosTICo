<script setup lang="ts">
/**
 * L3 · ¿Olvidaste tu contraseña? (HU-003).
 *
 * Fortify recibe `email`. Por seguridad, el aviso es el mismo aunque el correo
 * no exista en el sistema (RN-005); aquí se muestra cuando el servidor
 * devuelve `status`. El enlace no vence por tiempo: sirve hasta que se cambie
 * la contraseña (RN-006). El texto no afirma que el correo exista (RN-005).
 */
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Aviso from '@/components/base/Aviso.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: '¿Olvidaste tu contraseña?',
        description:
            'Escribe el correo de tu cuenta. Te enviamos un enlace para crear una contraseña nueva.',
    },
});

defineProps<{
    status?: string;
}>();

const correo = ref('');
</script>

<template>
    <Head title="Recuperar contraseña" />

    <Form
        v-bind="email.form()"
        :options="{ preserveState: true }"
        v-slot="{ errors, processing, submit }"
        class="flex flex-col gap-4"
    >
        <Campo etiqueta="Correo" para="email" :error="errors.email">
            <Entrada
                id="email"
                v-model="correo"
                type="email"
                name="email"
                required
                v-focus
                autocomplete="email"
                placeholder="nombre@empresa.com"
                :invalida="!!errors.email"
            />
        </Campo>

        <Boton
            type="submit"
            class="w-full"
            :cargando="processing"
            data-test="email-password-reset-link-button"
        >
            Enviar enlace
        </Boton>

        <Link
            :href="login()"
            class="text-center text-sm text-marca underline underline-offset-2 hover:text-marca-hover"
        >
            ← Volver a iniciar sesión
        </Link>

        <Aviso v-if="status" tono="exito" titulo="Revisa tu correo">
            Si
            <template v-if="correo"
                ><strong>{{ correo }}</strong> tiene una cuenta</template
            ><template v-else>el correo tiene una cuenta</template>, te enviamos
            un enlace. Sirve hasta que cambies la contraseña. Si no llega,
            revisa spam o
            <button
                type="button"
                class="text-marca underline underline-offset-2"
                :disabled="processing"
                @click="submit"
            >
                reenvía el enlace</button
            >.
        </Aviso>
    </Form>
</template>
