<script setup lang="ts">
/**
 * L1 · Demasiados intentos (límite de 5 por minuto por correo e IP,
 * 17_SEGURIDAD.md regla 7).
 *
 * Se abre cuando el servidor devuelve el error `bloqueo` con los segundos
 * que faltan. Muestra la cuenta regresiva y ofrece crear una contraseña
 * nueva. El botón "Iniciar sesión" de L1 queda desactivado mientras tanto.
 */
import { Link } from '@inertiajs/vue3';
import { Clock } from '@lucide/vue';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { request } from '@/routes/password';

const props = defineProps<{ segundos: number }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const reloj = computed(() => {
    const s = Math.max(0, props.segundos);

    return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
});
</script>

<template>
    <Modal v-model:abierto="abierto" titulo="Demasiados intentos" ancho="sm">
        <div class="flex flex-col items-center gap-4 text-center">
            <span
                class="grid size-12 place-items-center rounded-full bg-alerta-suave text-alerta"
                aria-hidden="true"
            >
                <Clock class="size-6" />
            </span>
            <p class="text-sm text-tinta">
                Por tu seguridad, después de 5 intentos fallidos pausamos el
                inicio de sesión con este correo durante un minuto.
            </p>
            <p
                class="font-mono text-3xl font-semibold text-marca"
                aria-live="polite"
                :aria-label="`Podrás intentarlo de nuevo en ${segundos} segundos`"
            >
                {{ reloj }}
            </p>
            <p class="text-xs text-tinta-suave">
                Revisa que el correo esté bien escrito y que no tengas activas
                las mayúsculas. Si no recuerdas tu contraseña, crea una nueva:
                no necesitas esperar.
            </p>
        </div>

        <template #pie>
            <Boton variante="secundario" :href="request()">
                Crear contraseña nueva
            </Boton>
            <Boton @click="abierto = false">Entendido</Boton>
        </template>
    </Modal>
</template>
