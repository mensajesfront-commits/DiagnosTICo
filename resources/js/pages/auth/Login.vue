<script setup lang="ts">
/**
 * L1 · Iniciar sesión (HU-001).
 *
 * Fortify recibe `email`, `password` y `remember`. Los mensajes de error no
 * dicen si el correo existe (RN-005): llegan del servidor tal cual.
 *
 * Dos errores se muestran en un modal en lugar de bajo el campo:
 * - `cuenta_desactivada` (texto): cuenta o empresa desactivada, solo con la
 *   contraseña correcta (RN-004, RN-025).
 * - `bloqueo` (segundos que faltan): 5 intentos fallidos en un minuto. El
 *   botón queda desactivado hasta que termina la cuenta regresiva.
 */
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import ModalAccesoBloqueado from '@/components/auth/ModalAccesoBloqueado.vue';
import ModalCuentaDesactivada from '@/components/auth/ModalCuentaDesactivada.vue';
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

const page = usePage();

const modalDesactivada = ref(false);
const mensajeDesactivada = ref('');
const tituloDesactivada = ref('Cuenta desactivada');

const modalBloqueo = ref(false);
const segundos = ref(0);
let reloj: ReturnType<typeof setInterval> | undefined;

function contarHacia0(desde: number): void {
    clearInterval(reloj);
    segundos.value = desde;
    reloj = setInterval(() => {
        segundos.value -= 1;

        if (segundos.value <= 0) {
            clearInterval(reloj);
            modalBloqueo.value = false;
        }
    }, 1000);
}

watch(
    () => page.props.errors as Record<string, string> | undefined,
    (errores) => {
        if (errores?.cuenta_desactivada) {
            tituloDesactivada.value = 'Cuenta desactivada';
            mensajeDesactivada.value = errores.cuenta_desactivada;
            modalDesactivada.value = true;
        }

        if (errores?.cuenta_eliminada) {
            tituloDesactivada.value = 'Cuenta eliminada';
            mensajeDesactivada.value = errores.cuenta_eliminada;
            modalDesactivada.value = true;
        }

        const espera = Number(errores?.bloqueo);

        if (espera > 0) {
            contarHacia0(espera);
            modalBloqueo.value = true;
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => clearInterval(reloj));
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
        <Campo obligatorio etiqueta="Correo" para="email" :error="errors.email">
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

        <Campo
            obligatorio
            etiqueta="Contraseña"
            para="password"
            :error="errors.password"
        >
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
            :disabled="segundos > 0"
            data-test="login-button"
        >
            <template v-if="segundos > 0">
                Espera {{ segundos }} s para intentarlo de nuevo
            </template>
            <template v-else>Iniciar sesión</template>
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

    <ModalCuentaDesactivada
        v-model:abierto="modalDesactivada"
        :mensaje="mensajeDesactivada"
        :titulo="tituloDesactivada"
    />
    <ModalAccesoBloqueado v-model:abierto="modalBloqueo" :segundos="segundos" />
</template>
