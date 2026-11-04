<script setup lang="ts">
/**
 * L3 · ¿Olvidaste tu contraseña? (HU-003).
 *
 * Fortify recibe `email`. Por seguridad, el aviso es el mismo aunque el correo
 * no exista en el sistema (RN-005); aquí se muestra cuando el servidor
 * devuelve `status`. El enlace vence a los 10 minutos (RN-006, ERR-09). El
 * texto no afirma que el correo exista (RN-005).
 *
 * Dos estados en la misma tarjeta:
 * - Formulario: el correo, "Enviar enlace" y "Volver a iniciar sesión".
 * - Enviado (llega `status`): ícono de correo, el aviso, los tres pasos
 *   (abre el correo, toca el enlace, crea la contraseña), "Reenviar enlace"
 *   (mismo formulario, oculto) y "Usar otro correo".
 */
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Info,
    MailCheck,
    PenLine,
    RotateCcw,
    Send,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
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

const props = defineProps<{
    status?: string;
}>();

const correo = ref('');

/** "Usar otro correo" vuelve al formulario aunque haya llegado `status`. */
const otroCorreo = ref(false);
const enviado = computed(() => !!props.status && !otroCorreo.value);

watch(
    () => props.status,
    () => (otroCorreo.value = false),
);

const pasos = [
    'Abre el correo «Cambia tu contraseña de Captter».',
    'Toca el botón «Cambiar contraseña» antes de 10 minutos.',
    'Escribe tu contraseña nueva y entra.',
];
</script>

<template>
    <Head title="Recuperar contraseña" />

    <Form
        v-bind="email.form()"
        :options="{ preserveState: true }"
        v-slot="{ errors, processing, submit }"
        class="flex flex-col gap-4"
    >
        <!-- Formulario (se oculta, sin desmontarse, al enviar: "Reenviar" lo usa) -->
        <div v-show="!enviado" class="flex flex-col gap-4">
            <Campo
                obligatorio
                etiqueta="Correo"
                para="email"
                :error="errors.email"
            >
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

            <p
                class="flex items-start gap-2 rounded-xl bg-lienzo px-3 py-2.5 text-xs text-tinta-suave"
            >
                <Info class="mt-px size-3.5 shrink-0" aria-hidden="true" />
                El enlace vence a los 10 minutos. Si se te pasa el tiempo, pide
                otro aquí mismo.
            </p>

            <Boton
                type="submit"
                class="w-full"
                :cargando="processing"
                data-test="email-password-reset-link-button"
            >
                <Send class="size-4" aria-hidden="true" />
                Enviar enlace
            </Boton>
        </div>

        <!-- Enviado -->
        <div
            v-if="enviado"
            class="flex flex-col items-center gap-4 text-center"
            role="status"
        >
            <span
                class="flex size-14 items-center justify-center rounded-2xl bg-exito-suave text-exito"
                aria-hidden="true"
            >
                <MailCheck class="size-7" />
            </span>
            <div>
                <p class="text-lg font-semibold">Revisa tu correo</p>
                <p class="mt-1 text-sm text-tinta-suave">
                    Si
                    <template v-if="correo"
                        ><strong class="break-all text-tinta">{{
                            correo
                        }}</strong>
                        tiene una cuenta</template
                    ><template v-else>el correo tiene una cuenta</template>, te
                    enviamos un enlace. Sirve hasta que cambies la contraseña.
                </p>
            </div>

            <ol
                class="flex w-full flex-col gap-2 rounded-xl bg-lienzo p-3 text-left text-sm"
            >
                <li
                    v-for="(p, i) in pasos"
                    :key="p"
                    class="flex items-start gap-2.5"
                >
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-white font-mono text-xs font-semibold text-marca ring-1 ring-linea"
                        aria-hidden="true"
                        >{{ i + 1 }}</span
                    >
                    <span class="pt-0.5">{{ p }}</span>
                </li>
            </ol>

            <p class="text-xs text-tinta-suave">
                ¿No llega? Revisa la carpeta de spam o pide otro enlace (solo
                funciona el último).
            </p>

            <div class="grid w-full gap-2 sm:grid-cols-2">
                <Boton
                    variante="secundario"
                    :cargando="processing"
                    @click="submit"
                >
                    <RotateCcw class="size-4" aria-hidden="true" />
                    Reenviar enlace
                </Boton>
                <Boton variante="secundario" @click="otroCorreo = true">
                    <PenLine class="size-4" aria-hidden="true" />
                    Usar otro correo
                </Boton>
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
