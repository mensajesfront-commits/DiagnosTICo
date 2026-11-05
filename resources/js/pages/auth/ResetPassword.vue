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
 *
 * Diseño: la cuenta en un recuadro con ícono; los requisitos con el aviso de
 * las últimas 5 contraseñas; "Guardar contraseña" con ícono y, debajo,
 * "Volver a iniciar sesión". Con el enlace vencido, el formulario se cambia
 * por una vista de error (ícono rojo, mensaje y "Pedir otro enlace").
 */
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    History,
    Link2Off,
    Lock,
    Send,
    ShieldCheck,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import CampoContrasena from '@/components/base/CampoContrasena.vue';
import RequisitosContrasena from '@/components/base/RequisitosContrasena.vue';
import { revisarContrasena } from '@/lib/contrasena';
import { login } from '@/routes';
import { request, update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Crea una contraseña nueva',
        description: 'Elige una contraseña segura que no hayas usado antes.',
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
// Con el correo, igual que la lista: así cuenta también "Sin partes de tu correo".
const totalRequisitos = computed(
    () => revisarContrasena('', '', { correo: props.email }).requisitos.length,
);
</script>

<template>
    <Head title="Crea una contraseña nueva" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
    >
        <!-- Enlace vencido o ya usado -->
        <div
            v-if="errors.email"
            class="flex flex-col items-center gap-4 text-center"
            role="alert"
        >
            <span
                class="flex size-14 items-center justify-center rounded-2xl bg-aviso-suave text-aviso"
                aria-hidden="true"
            >
                <Link2Off class="size-7" />
            </span>
            <div>
                <p class="text-lg font-semibold">Este enlace ya no es válido</p>
                <!-- Texto fijo: el del servidor repite el título o, si el correo
                     no existe, habla de enviar un enlace (RN-005). -->
                <p class="mt-1 text-sm text-tinta-suave">
                    Puede que ya lo hayas usado o que hayas pedido otro después:
                    solo funciona el último. Pide uno nuevo y usa el más
                    reciente.
                </p>
            </div>
            <Boton :href="request()" class="w-full">
                <Send class="size-4" aria-hidden="true" />
                Pedir otro enlace
            </Boton>
        </div>

        <div v-show="!errors.email" class="flex flex-col gap-4">
            <div
                class="flex items-center gap-3 rounded-xl bg-lienzo px-3 py-2.5"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white text-marca ring-1 ring-linea"
                    aria-hidden="true"
                >
                    <UserRound class="size-4" />
                </span>
                <span class="min-w-0 text-sm">
                    <span class="block text-xs text-tinta-suave"
                        >Para la cuenta</span
                    >
                    <strong class="block truncate font-medium">{{
                        props.email
                    }}</strong>
                </span>
            </div>

            <Campo
                obligatorio
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
                obligatorio
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
                :correo="props.email"
            />
            <p class="-mt-2 flex items-start gap-2 text-xs text-tinta-suave">
                <History class="mt-px size-3.5 shrink-0" aria-hidden="true" />
                Tampoco puede ser ninguna de tus últimas 5 contraseñas, aunque
                cambies mayúsculas y minúsculas.
            </p>

            <div>
                <Boton
                    type="submit"
                    class="w-full"
                    :disabled="!completa"
                    :cargando="processing"
                    data-test="reset-password-button"
                >
                    <ShieldCheck class="size-4" aria-hidden="true" />
                    Guardar contraseña
                </Boton>
                <p
                    v-if="!completa"
                    class="mt-2 flex items-center justify-center gap-1.5 text-xs text-tinta-suave"
                >
                    <Lock class="size-3.5" aria-hidden="true" />
                    Se activa cuando cumplas los {{ totalRequisitos }}
                    requisitos.
                </p>
            </div>
        </div>

        <div class="border-t border-linea pt-4 text-center">
            <Link
                :href="login()"
                class="inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-marca hover:text-marca-hover"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Volver a iniciar sesión
            </Link>
        </div>
    </Form>
</template>
