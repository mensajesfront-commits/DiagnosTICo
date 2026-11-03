<script setup lang="ts">
/**
 * L1 · Aviso de cuenta desactivada (RN-004, RN-025) o eliminada hace menos
 * de 90 días (DEC-017; título "Cuenta eliminada").
 *
 * Se abre cuando el servidor devuelve el error `cuenta_desactivada`: al
 * entrar con el correo y la contraseña correctos de una cuenta (o empresa)
 * desactivada, o si la desactivan con la sesión abierta. Con una contraseña
 * equivocada no aparece, para no revelar qué cuentas existen (RN-005).
 *
 * [INFORMACIÓN PENDIENTE] El correo de soporte es el mismo del panel de
 * acceso (soporte@nuevastic.co); falta confirmarlo con NuevasTIC.
 *
 * Diseño: el mensaje del servidor tal cual y una tarjeta de soporte con el
 * correo y el botón "Escribir a soporte" (abre el correo con el asunto).
 */
import { CircleSlash, LifeBuoy, Mail } from '@lucide/vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';

withDefaults(defineProps<{ mensaje: string; titulo?: string }>(), {
    titulo: 'Cuenta desactivada',
});

const abierto = defineModel<boolean>('abierto', { default: false });

const SOPORTE = 'soporte@nuevastic.co';
const enlace = (titulo: string) =>
    `mailto:${SOPORTE}?subject=${encodeURIComponent(`${titulo} en Captter`)}`;
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="titulo"
        ancho="sm"
        :icono="CircleSlash"
        tono="peligro"
    >
        <div class="flex flex-col gap-4">
            <p class="text-sm text-tinta">{{ mensaje }}</p>

            <div class="rounded-2xl border border-linea bg-lienzo/60 p-4">
                <div class="flex items-start gap-3">
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-marca ring-1 ring-linea"
                        aria-hidden="true"
                    >
                        <LifeBuoy class="size-4.5" />
                    </span>
                    <div class="min-w-0 text-sm">
                        <p class="font-semibold">¿Crees que es un error?</p>
                        <p class="mt-0.5 text-tinta-suave">
                            Escríbenos con el correo de tu cuenta y te ayudamos.
                        </p>
                        <p
                            class="mt-1.5 font-medium [overflow-wrap:anywhere] text-tinta"
                        >
                            {{ SOPORTE }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <template #pie>
            <Boton
                variante="secundario"
                class="w-full sm:w-auto"
                :href="enlace(titulo)"
                archivo
            >
                <Mail class="size-4" aria-hidden="true" />
                Escribir a soporte
            </Boton>
            <Boton class="w-full sm:w-auto" @click="abierto = false">
                Entendido
            </Boton>
        </template>
    </Modal>
</template>
