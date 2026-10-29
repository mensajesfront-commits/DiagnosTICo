<script setup lang="ts">
/**
 * L2b · Verifica tu correo (DEC-020). Se abre en el registro (L2) después
 * de "Crear cuenta" y en Iniciar sesión (L1) si la cuenta no está
 * verificada. La persona escribe el código de 6 dígitos que le llegó; al
 * acertar pasa a Iniciar sesión con el correo ya escrito.
 *
 * "Reenviar código" se habilita a los 60 segundos. Cerrar la ventana la
 * deja sin verificar (vuelve a abrirse al intentar entrar).
 *
 * Mi perfil (A6, E11) la usa para confirmar un correo nuevo, un teléfono
 * nuevo o los dos: pasa sus propias `rutas`, `cambio` y, si cambia el
 * teléfono, `telefono` (cerrar descarta el cambio). El código llega al
 * correo nuevo o, si solo cambia el teléfono, al actual.
 */
import { router, useForm } from '@inertiajs/vue3';
import { MailCheck } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Aviso from '@/components/base/Aviso.vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';

type Rutas = { verificar: string; reenviar: string; cancelar: string };

const props = withDefaults(
    defineProps<{
        correo: string;
        status?: string;
        rutas?: Rutas;
        /** Confirma un correo nuevo en Mi perfil, no un registro. */
        cambio?: boolean;
        /** Teléfono nuevo de Mi perfil que también se confirma. */
        telefono?: string | null;
        /** Si también cambia el correo (el código va al correo nuevo). */
        cambiaCorreo?: boolean;
    }>(),
    {
        status: undefined,
        rutas: () => rutas.verificacion,
        cambio: false,
        telefono: null,
        cambiaCorreo: true,
    },
);

const titulo = computed(() => {
    if (!props.cambio) {
        return 'Verifica tu correo';
    }

    if (props.telefono && props.cambiaCorreo) {
        return 'Confirma tu nuevo correo y teléfono';
    }

    return props.telefono
        ? 'Confirma tu nuevo teléfono'
        : 'Confirma tu nuevo correo';
});

const abierto = ref(true);
const form = useForm({ codigo: '' });

function verificar(): void {
    form.post(props.rutas.verificar, {
        preserveScroll: true,
        onError: () => form.reset('codigo'),
    });
}

/** Solo números, hasta 6; al completar los 6 se envía solo. */
function escribir(evento: Event): void {
    const campo = evento.target as HTMLInputElement;
    form.codigo = campo.value.replace(/\D/g, '').slice(0, 6);
    campo.value = form.codigo;
    form.clearErrors('codigo');

    if (form.codigo.length === 6 && !form.processing) {
        verificar();
    }
}

// --- Reenviar, con espera de 60 segundos ------------------------------------
const espera = ref(60);
let reloj: ReturnType<typeof setInterval> | undefined;

function contar(): void {
    clearInterval(reloj);
    espera.value = 60;
    reloj = setInterval(() => {
        espera.value -= 1;

        if (espera.value <= 0) {
            clearInterval(reloj);
        }
    }, 1000);
}

onMounted(contar);
onBeforeUnmount(() => clearInterval(reloj));

const reenviando = ref(false);

function reenviar(): void {
    router.post(
        props.rutas.reenviar,
        {},
        {
            preserveScroll: true,
            onStart: () => (reenviando.value = true),
            onFinish: () => (reenviando.value = false),
            onSuccess: contar,
        },
    );
}

// Cerrar: la cuenta queda sin verificar y se vuelve a Iniciar sesión.
watch(abierto, (valor) => {
    if (!valor) {
        router.post(props.rutas.cancelar, {}, { preserveScroll: true });
    }
});
</script>

<template>
    <Modal v-model:abierto="abierto" :titulo="titulo" ancho="sm">
        <template #descripcion>
            Enviamos un código de 6 dígitos a
            <strong class="text-tinta">{{ correo }}</strong
            ><template v-if="!(cambio && telefono)">.</template>
            <template v-if="cambio && telefono && !cambiaCorreo">
                para confirmar tu nuevo teléfono
                <strong class="text-tinta">{{ telefono }}</strong
                >. Hasta confirmarlo se mantiene el actual.
            </template>
            <template v-else-if="cambio && telefono">
                para confirmar tu nuevo correo y tu nuevo teléfono
                <strong class="text-tinta">{{ telefono }}</strong
                >. Hasta confirmarlo sigues con los actuales.
            </template>
            <template v-else-if="cambio">
                Hasta confirmarlo sigues entrando con tu correo actual.
            </template>
        </template>

        <form
            class="flex flex-col gap-4"
            novalidate
            @submit.prevent="verificar"
        >
            <div
                class="flex items-start gap-3 rounded-lg bg-lienzo px-3 py-3 text-xs text-tinta-suave"
            >
                <MailCheck
                    class="mt-0.5 size-5 shrink-0 text-marca"
                    aria-hidden="true"
                />
                <p>
                    Revisa tu bandeja de entrada y la carpeta de spam. El código
                    vence en 15 minutos.
                </p>
            </div>

            <div>
                <label for="codigo" class="text-xs font-medium text-tinta">
                    Código de verificación
                    <span class="text-aviso" aria-hidden="true">*</span>
                </label>
                <input
                    id="codigo"
                    :value="form.codigo"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    placeholder="000000"
                    :aria-invalid="!!form.errors.codigo || undefined"
                    class="mt-1.5 h-14 w-full rounded-md border border-linea-fuerte bg-white text-center font-mono text-3xl tracking-[0.5em] text-tinta placeholder:text-tinta-suave/40 focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none"
                    :class="form.errors.codigo && 'border-aviso'"
                    @input="escribir"
                />
                <p
                    v-if="form.errors.codigo"
                    class="mt-1 text-xs text-aviso"
                    role="alert"
                >
                    {{ form.errors.codigo }}
                </p>
            </div>

            <Aviso v-if="status" tono="exito">{{ status }}</Aviso>

            <Boton
                type="submit"
                class="w-full"
                :cargando="form.processing"
                :disabled="form.codigo.length !== 6"
            >
                Verificar
            </Boton>

            <p class="text-center text-sm text-tinta-suave">
                ¿No te llegó?
                <button
                    type="button"
                    class="text-marca underline underline-offset-2 hover:text-marca-hover disabled:text-tinta-suave disabled:no-underline"
                    :disabled="espera > 0 || reenviando"
                    @click="reenviar"
                >
                    {{
                        espera > 0
                            ? `Reenviar código en ${espera} s`
                            : 'Reenviar código'
                    }}
                </button>
            </p>
        </form>
    </Modal>
</template>
