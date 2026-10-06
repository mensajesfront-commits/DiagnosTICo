<script setup lang="ts">
/**
 * E12.2 · Comparte estos datos (HU-077 CA-003, HU-078).
 *
 * Se abre después de crear un colaborador (E12.1) o de cambiarle la
 * contraseña (E12.3). Muestra el usuario y la contraseña una sola vez: al
 * cerrar se borran de la pantalla.
 */
import { ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import type { DatosDeAcceso } from '@/types/colaboradores';

const props = defineProps<{
    datos: DatosDeAcceso;
    /** Contraseña cambiada (E12.3) en lugar de cuenta nueva. */
    cambio?: boolean;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const copiado = ref(false);

watch(abierto, () => (copiado.value = false));

async function copiar(): Promise<void> {
    const texto = `Usuario: ${props.datos.correo}\nContraseña: ${props.datos.contrasena}`;

    try {
        await navigator.clipboard.writeText(texto);
        copiado.value = true;
    } catch {
        // Sin permiso para el portapapeles (http sin localhost, navegador
        // antiguo): los datos siguen a la vista para copiarlos a mano.
        copiado.value = false;
    }
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        titulo="Comparte estos datos"
        :descripcion="`${datos.nombre} ya puede iniciar sesión con ${cambio ? 'su contraseña nueva' : 'ellos'}.`"
    >
        <div class="flex flex-col gap-4 text-sm">
            <p class="rounded-md bg-exito-suave px-3 py-2.5 text-exito">
                ✓
                {{
                    cambio
                        ? `Contraseña cambiada para ${datos.nombre}`
                        : `Cuenta creada para ${datos.nombre}`
                }}
            </p>
            <dl class="border-y border-linea">
                <div
                    class="flex items-center justify-between gap-4 border-b border-linea py-3"
                >
                    <dt class="text-tinta-suave">Usuario</dt>
                    <dd class="font-mono break-all">{{ datos.correo }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-tinta-suave">Contraseña</dt>
                    <dd class="font-mono break-all">{{ datos.contrasena }}</dd>
                </div>
            </dl>
            <p class="rounded-md bg-alerta-suave px-3 py-2.5 text-alerta">
                ⚠ Esta contraseña no se vuelve a mostrar. Cópiala y compártela
                ahora por un medio seguro. Si se pierde, puedes crear una nueva
                con "Cambiar contraseña".
            </p>
        </div>

        <template #pie>
            <span
                v-if="copiado"
                class="mr-auto text-xs text-exito"
                aria-live="polite"
            >
                ✓ Copiado
            </span>
            <Boton variante="secundario" @click="copiar">Copiar datos</Boton>
            <Boton @click="abierto = false">Listo</Boton>
        </template>
    </Modal>
</template>
