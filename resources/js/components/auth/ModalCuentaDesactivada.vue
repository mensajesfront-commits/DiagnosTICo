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
 */
import { CircleSlash } from '@lucide/vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';

withDefaults(defineProps<{ mensaje: string; titulo?: string }>(), {
    titulo: 'Cuenta desactivada',
});

const abierto = defineModel<boolean>('abierto', { default: false });
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="titulo"
        ancho="sm"
        :icono="CircleSlash"
        tono="peligro"
    >
        <div class="flex flex-col gap-3">
            <p class="text-sm text-tinta">{{ mensaje }}</p>
            <p class="text-xs text-tinta-suave">
                Escríbenos a
                <a
                    href="mailto:soporte@nuevastic.co?subject=Cuenta%20desactivada%20en%20Captter"
                    class="text-marca underline underline-offset-2"
                >
                    soporte@nuevastic.co</a
                >
                con el correo de tu cuenta.
            </p>
        </div>

        <template #pie>
            <Boton class="w-full" @click="abierto = false">Entendido</Boton>
        </template>
    </Modal>
</template>
