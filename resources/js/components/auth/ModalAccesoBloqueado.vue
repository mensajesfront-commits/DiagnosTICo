<script setup lang="ts">
/**
 * L1 · Demasiados intentos (límite de 5 por minuto por correo e IP,
 * 17_SEGURIDAD.md regla 7).
 *
 * Se abre cuando el servidor devuelve el error `bloqueo` con los segundos
 * que faltan. Muestra la cuenta regresiva y ofrece crear una contraseña
 * nueva. El botón "Iniciar sesión" de L1 queda desactivado mientras tanto.
 *
 * Diseño: la cuenta regresiva en un anillo que se vacía (sobre el minuto
 * de la pausa) y tres consejos con ícono: el correo, las mayúsculas
 * y crear una contraseña nueva sin esperar.
 */
import { AtSign, CaseUpper, Clock, KeyRound } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { request } from '@/routes/password';

const props = defineProps<{ segundos: number }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const reloj = computed(() => {
    const s = Math.max(0, props.segundos);

    return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
});

/** La pausa dura un minuto: el anillo se vacía sobre 60 s (o más, si llega más). */
const total = ref(Math.max(60, props.segundos));
watch(
    () => props.segundos,
    (s) => {
        if (s > total.value) {
            total.value = s;
        }
    },
);
watch(abierto, (valor) => {
    if (valor) {
        total.value = Math.max(60, props.segundos);
    }
});

const RADIO = 42;
const CIRCUNFERENCIA = 2 * Math.PI * RADIO;
const desfase = computed(
    () => CIRCUNFERENCIA * (1 - Math.max(0, props.segundos) / total.value),
);

const consejos = [
    { icono: AtSign, texto: 'Revisa que el correo esté bien escrito.' },
    {
        icono: CaseUpper,
        texto: 'Comprueba que no tengas activas las mayúsculas.',
    },
    {
        icono: KeyRound,
        texto: '¿No la recuerdas? Crea una contraseña nueva: no necesitas esperar.',
    },
];
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        titulo="Demasiados intentos"
        ancho="sm"
        :icono="Clock"
        tono="alerta"
    >
        <div class="flex flex-col gap-4">
            <p class="text-sm text-tinta">
                Por tu seguridad, después de 5 intentos fallidos pausamos el
                inicio de sesión con este correo durante un minuto.
            </p>

            <div
                class="flex items-center gap-4 rounded-2xl bg-alerta-suave/60 p-4"
            >
                <div class="relative size-24 shrink-0">
                    <svg
                        viewBox="0 0 100 100"
                        class="size-24 -rotate-90"
                        aria-hidden="true"
                    >
                        <circle
                            cx="50"
                            cy="50"
                            :r="RADIO"
                            fill="none"
                            stroke-width="8"
                            class="stroke-white"
                        />
                        <circle
                            cx="50"
                            cy="50"
                            :r="RADIO"
                            fill="none"
                            stroke-width="8"
                            stroke-linecap="round"
                            class="stroke-alerta transition-[stroke-dashoffset] duration-1000 ease-linear"
                            :stroke-dasharray="CIRCUNFERENCIA"
                            :stroke-dashoffset="desfase"
                        />
                    </svg>
                    <p
                        class="absolute inset-0 flex items-center justify-center font-mono text-2xl font-semibold text-tinta"
                        aria-live="polite"
                        :aria-label="`Podrás intentarlo de nuevo en ${segundos} segundos`"
                    >
                        {{ reloj }}
                    </p>
                </div>
                <div class="text-sm">
                    <p class="font-semibold">
                        Vuelve a intentarlo en un momento
                    </p>
                    <p class="mt-0.5 text-tinta-suave">
                        El botón «Iniciar sesión» se activa solo cuando termine
                        la cuenta.
                    </p>
                </div>
            </div>

            <ul class="flex flex-col gap-2 text-sm">
                <li
                    v-for="c in consejos"
                    :key="c.texto"
                    class="flex items-start gap-2.5"
                >
                    <span
                        class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-lienzo text-tinta-suave"
                        aria-hidden="true"
                    >
                        <component :is="c.icono" class="size-4" />
                    </span>
                    <span class="pt-1 text-tinta-suave">{{ c.texto }}</span>
                </li>
            </ul>
        </div>

        <template #pie>
            <Boton
                variante="secundario"
                class="w-full sm:w-auto"
                :href="request()"
            >
                <KeyRound class="size-4" aria-hidden="true" />
                Crear contraseña nueva
            </Boton>
            <Boton class="w-full sm:w-auto" @click="abierto = false">
                Entendido
            </Boton>
        </template>
    </Modal>
</template>
