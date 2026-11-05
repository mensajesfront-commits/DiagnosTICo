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
 *
 * Diseño: el código se ve en 6 casillas, pero es un solo campo (invisible
 * encima de ellas), así pegar o autocompletar el código sigue funcionando.
 * "Reenviar código" es un botón con la cuenta regresiva en un anillo.
 */
import { router, useForm } from '@inertiajs/vue3';
import {
    CircleCheck,
    Clock,
    MailCheck,
    RotateCcw,
    ShieldCheck,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { cn } from '@/lib/utils';
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

// --- Casillas ---------------------------------------------------------------
const enfocado = ref(false);
const casillas = computed(() =>
    Array.from({ length: 6 }, (_, i) => form.codigo[i] ?? ''),
);
/** Casilla donde va el siguiente dígito (la última si ya están los 6). */
const actual = computed(() => Math.min(form.codigo.length, 5));

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

/** Anillo de la cuenta regresiva (radio 9). */
const CIRCUNFERENCIA = 2 * Math.PI * 9;
const desfase = computed(() => CIRCUNFERENCIA * (1 - espera.value / 60));
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
    <Modal
        v-model:abierto="abierto"
        :titulo="titulo"
        ancho="sm"
        :icono="MailCheck"
    >
        <template #descripcion>
            Enviamos un código de 6 dígitos a
            <strong class="[overflow-wrap:anywhere] text-tinta">{{
                correo
            }}</strong
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
            <div>
                <label for="codigo" class="text-xs font-medium text-tinta">
                    Código de verificación
                    <span class="text-aviso" aria-hidden="true">*</span>
                </label>
                <div class="relative mt-2">
                    <!-- Lo que se ve: 6 casillas -->
                    <div
                        class="grid grid-cols-6 gap-1.5 sm:gap-2"
                        aria-hidden="true"
                    >
                        <span
                            v-for="(digito, i) in casillas"
                            :key="i"
                            :class="
                                cn(
                                    'flex h-14 items-center justify-center rounded-xl border bg-white font-mono text-2xl font-semibold text-tinta transition-colors',
                                    form.errors.codigo
                                        ? 'border-aviso bg-aviso-suave/40'
                                        : enfocado && i === actual
                                          ? 'border-marca ring-2 ring-marca/25'
                                          : digito
                                            ? 'border-marca/40 bg-marca-suave/40'
                                            : 'border-linea-fuerte',
                                )
                            "
                        >
                            <template v-if="digito">{{ digito }}</template>
                            <span
                                v-else-if="enfocado && i === actual"
                                class="h-6 w-0.5 animate-pulse rounded-full bg-marca"
                            />
                            <span v-else class="text-tinta-suave/30">·</span>
                        </span>
                    </div>
                    <!-- El campo real, invisible encima -->
                    <input
                        id="codigo"
                        :value="form.codigo"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        :aria-invalid="!!form.errors.codigo || undefined"
                        :aria-describedby="
                            form.errors.codigo ? 'codigo-error' : 'codigo-ayuda'
                        "
                        class="absolute inset-0 h-full w-full cursor-text opacity-0"
                        @input="escribir"
                        @focus="enfocado = true"
                        @blur="enfocado = false"
                    />
                </div>
                <p
                    v-if="form.errors.codigo"
                    id="codigo-error"
                    class="mt-1.5 text-xs text-aviso"
                    role="alert"
                >
                    {{ form.errors.codigo }}
                </p>
                <p
                    v-else
                    id="codigo-ayuda"
                    class="mt-1.5 flex items-center gap-1.5 text-xs text-tinta-suave"
                >
                    <Clock class="size-3.5 shrink-0" aria-hidden="true" />
                    Vence en 15 minutos. Revisa también la carpeta de spam.
                </p>
            </div>

            <p
                v-if="status"
                class="flex items-start gap-2 rounded-xl bg-exito-suave px-3 py-2.5 text-sm text-exito"
                role="status"
            >
                <CircleCheck
                    class="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                {{ status }}
            </p>

            <Boton
                type="submit"
                class="w-full"
                :cargando="form.processing"
                :disabled="form.codigo.length !== 6"
            >
                <ShieldCheck class="size-4" aria-hidden="true" />
                Verificar
            </Boton>

            <div
                class="flex items-center justify-between gap-3 rounded-xl bg-lienzo px-3 py-2.5"
            >
                <p class="text-sm text-tinta-suave">¿No te llegó?</p>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg px-2 py-1 text-sm font-medium text-marca hover:bg-white focus-visible:ring-2 focus-visible:ring-marca/40 focus-visible:outline-none disabled:text-tinta-suave disabled:hover:bg-transparent"
                    :disabled="espera > 0 || reenviando"
                    @click="reenviar"
                >
                    <svg
                        v-if="espera > 0"
                        viewBox="0 0 22 22"
                        class="size-5 -rotate-90"
                        aria-hidden="true"
                    >
                        <circle
                            cx="11"
                            cy="11"
                            r="9"
                            fill="none"
                            stroke-width="2.5"
                            class="stroke-lienzo-oscuro"
                        />
                        <circle
                            cx="11"
                            cy="11"
                            r="9"
                            fill="none"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            class="stroke-marca transition-[stroke-dashoffset] duration-1000 ease-linear"
                            :stroke-dasharray="CIRCUNFERENCIA"
                            :stroke-dashoffset="desfase"
                        />
                    </svg>
                    <RotateCcw v-else class="size-4" aria-hidden="true" />
                    {{
                        espera > 0
                            ? `Reenviar en ${espera} s`
                            : 'Reenviar código'
                    }}
                </button>
            </div>
        </form>
    </Modal>
</template>
