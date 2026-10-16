<script setup lang="ts">
/**
 * Cambiar contraseña (A6 en línea, E11 en un modal).
 *
 * Envía current_password, password y password_confirmation a
 * PUT /mi-perfil/contrasena. La sesión sigue abierta después del cambio.
 *
 * "¿Olvidaste tu contraseña?" manda un código de 6 dígitos al correo de la
 * cuenta (POST /mi-perfil/contrasena/codigo). El campo de la contraseña
 * actual se cambia por el del código y se envía a
 * PUT /mi-perfil/contrasena/con-codigo. Solo sirve con acceso a ese correo.
 */
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
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
    codigo: '',
    password: '',
    password_confirmation: '',
});

const page = usePage();
const correo = computed(
    () => (page.props.auth as { user?: { email?: string } }).user?.email ?? '',
);

/** Con código en lugar de la contraseña actual. */
const conCodigo = ref(false);
const pidiendo = ref(false);
const espera = ref(0);
let reloj: ReturnType<typeof setInterval> | undefined;

function pedirCodigo(): void {
    router.post(
        '/mi-perfil/contrasena/codigo',
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (pidiendo.value = true),
            onFinish: () => (pidiendo.value = false),
            onSuccess: () => {
                conCodigo.value = true;
                form.clearErrors();
                clearInterval(reloj);
                espera.value = 60;
                reloj = setInterval(() => {
                    espera.value -= 1;

                    if (espera.value <= 0) {
                        clearInterval(reloj);
                    }
                }, 1000);
            },
        },
    );
}

function usarContrasenaActual(): void {
    conCodigo.value = false;
    form.codigo = '';
    form.clearErrors();
}

onBeforeUnmount(() => clearInterval(reloj));

function escribirCodigo(evento: Event): void {
    const campo = evento.target as HTMLInputElement;
    form.codigo = campo.value.replace(/\D/g, '').slice(0, 6);
    campo.value = form.codigo;
}

const listo = computed(
    () =>
        (conCodigo.value
            ? form.codigo.length === 6
            : form.current_password !== '') &&
        revisarContrasena(form.password, form.password_confirmation).completa,
);

function enviar(): void {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            conCodigo.value = false;
            emit('actualizada');
        },
        onError: () => form.reset('current_password', 'codigo'),
    };

    if (conCodigo.value) {
        form.transform((d) => ({
            codigo: d.codigo,
            password: d.password,
            password_confirmation: d.password_confirmation,
        })).put('/mi-perfil/contrasena/con-codigo', opciones);

        return;
    }

    form.transform((d) => ({
        current_password: d.current_password,
        password: d.password,
        password_confirmation: d.password_confirmation,
    })).put(actualizarContrasena().url, opciones);
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
                v-if="!conCodigo"
                obligatorio
                etiqueta="Contraseña actual"
                para="contrasena-actual"
                :error="form.errors.current_password"
            >
                <template #accion>
                    <button
                        type="button"
                        class="text-xs text-marca underline underline-offset-2 hover:text-marca-hover disabled:text-tinta-suave"
                        :disabled="pidiendo"
                        @click="pedirCodigo"
                    >
                        ¿Olvidaste tu contraseña?
                    </button>
                </template>
                <CampoContrasena
                    id="contrasena-actual"
                    required
                    v-model="form.current_password"
                    autocomplete="current-password"
                    :invalida="!!form.errors.current_password"
                />
            </Campo>
            <Campo
                v-else
                obligatorio
                etiqueta="Código que enviamos a tu correo"
                para="contrasena-codigo"
                :error="form.errors.codigo"
                :ayuda="`Lo enviamos a ${correo}. Vence en 15 minutos.`"
            >
                <template #accion>
                    <button
                        type="button"
                        class="text-xs text-marca underline underline-offset-2 hover:text-marca-hover"
                        @click="usarContrasenaActual"
                    >
                        Usar mi contraseña actual
                    </button>
                </template>
                <input
                    id="contrasena-codigo"
                    :value="form.codigo"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    placeholder="000000"
                    :aria-invalid="!!form.errors.codigo || undefined"
                    class="h-10 w-full rounded-md border border-linea-fuerte bg-white px-3 text-center font-mono text-lg tracking-[0.4em] text-tinta placeholder:text-tinta-suave/40 focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none"
                    :class="form.errors.codigo && 'border-aviso'"
                    @input="escribirCodigo"
                />
            </Campo>
            <p v-if="conCodigo" class="self-end pb-2 text-xs text-tinta-suave">
                ¿No te llegó?
                <button
                    type="button"
                    class="text-marca underline underline-offset-2 hover:text-marca-hover disabled:text-tinta-suave disabled:no-underline"
                    :disabled="espera > 0 || pidiendo"
                    @click="pedirCodigo"
                >
                    {{
                        espera > 0
                            ? `Reenviar código en ${espera} s`
                            : 'Reenviar código'
                    }}
                </button>
            </p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <Campo
                obligatorio
                etiqueta="Contraseña nueva"
                para="contrasena-nueva"
                :error="form.errors.password"
            >
                <CampoContrasena
                    id="contrasena-nueva"
                    required
                    v-model="form.password"
                    autocomplete="new-password"
                    :invalida="!!form.errors.password"
                />
            </Campo>
            <Campo
                obligatorio
                etiqueta="Confirmar contraseña nueva"
                para="contrasena-confirmacion"
            >
                <CampoContrasena
                    id="contrasena-confirmacion"
                    required
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
                {{
                    conCodigo
                        ? 'Escribe el código y la contraseña nueva para continuar.'
                        : 'Completa los tres campos y cumple los requisitos para continuar.'
                }}
            </p>
            <Boton type="submit" :disabled="!listo" :cargando="form.processing">
                Actualizar contraseña
            </Boton>
        </div>
    </form>
</template>
