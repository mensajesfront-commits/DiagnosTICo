<script setup lang="ts">
/**
 * E12.2 · Comparte estos datos (HU-077 CA-003, HU-078).
 *
 * Se abre después de crear un colaborador (E12.1) o de cambiarle la
 * contraseña (E12.3). Muestra el usuario y la contraseña una sola vez: al
 * cerrar se borran de la pantalla.
 *
 * Diseño: una credencial con el usuario y la contraseña, cada uno con su
 * botón de copiar, y "Copiar datos" para los dos juntos.
 */
import { AtSign, Check, Copy, KeyRound } from '@lucide/vue';
import { ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import type { DatosDeAcceso } from '@/types/colaboradores';

const props = defineProps<{
    datos: DatosDeAcceso;
    /** Contraseña cambiada (E12.3) en lugar de cuenta nueva. */
    cambio?: boolean;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

/** Qué se copió por último: los dos datos, el usuario o la contraseña. */
const copiado = ref<'todo' | 'correo' | 'contrasena' | null>(null);

watch(abierto, () => (copiado.value = null));

async function copiar(
    que: 'todo' | 'correo' | 'contrasena' = 'todo',
): Promise<void> {
    const texto =
        que === 'correo'
            ? props.datos.correo
            : que === 'contrasena'
              ? props.datos.contrasena
              : `Usuario: ${props.datos.correo}\nContraseña: ${props.datos.contrasena}`;

    try {
        await navigator.clipboard.writeText(texto);
        copiado.value = que;
    } catch {
        // Sin permiso para el portapapeles (http sin localhost, navegador
        // antiguo): los datos siguen a la vista para copiarlos a mano.
        copiado.value = null;
    }
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        titulo="Comparte estos datos"
        :descripcion="`${datos.nombre} ya puede iniciar sesión con ${cambio ? 'su contraseña nueva' : 'ellos'}.`"
        :icono="KeyRound"
        tono="exito"
    >
        <div class="flex flex-col gap-4 text-sm">
            <Recuadro tono="exito">
                {{
                    cambio
                        ? `Contraseña cambiada para ${datos.nombre}`
                        : `Cuenta creada para ${datos.nombre}`
                }}
            </Recuadro>

            <dl
                class="overflow-hidden rounded-xl border border-linea bg-linear-to-br from-marca-suave/60 to-white"
            >
                <div
                    v-for="fila in [
                        {
                            clave: 'correo' as const,
                            etiqueta: 'Usuario',
                            valor: datos.correo,
                            icono: AtSign,
                        },
                        {
                            clave: 'contrasena' as const,
                            etiqueta: 'Contraseña',
                            valor: datos.contrasena,
                            icono: KeyRound,
                        },
                    ]"
                    :key="fila.clave"
                    class="flex items-center gap-3 border-b border-linea px-4 py-3 last:border-b-0"
                >
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white text-marca ring-1 ring-linea"
                        aria-hidden="true"
                    >
                        <component :is="fila.icono" class="size-4" />
                    </span>
                    <div class="flex min-w-0 flex-1 flex-col-reverse">
                        <dd
                            class="font-mono text-base font-semibold [overflow-wrap:anywhere]"
                        >
                            {{ fila.valor }}
                        </dd>
                        <dt class="text-xs text-tinta-suave">
                            {{ fila.etiqueta }}
                        </dt>
                    </div>
                    <button
                        type="button"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg text-tinta-suave hover:bg-white hover:text-marca focus-visible:ring-2 focus-visible:ring-marca/40 focus-visible:outline-none"
                        :aria-label="`Copiar ${fila.etiqueta.toLowerCase()}`"
                        @click="copiar(fila.clave)"
                    >
                        <Check
                            v-if="copiado === fila.clave"
                            class="size-4 text-exito"
                            aria-hidden="true"
                        />
                        <Copy v-else class="size-4" aria-hidden="true" />
                    </button>
                </div>
            </dl>

            <Recuadro tono="alerta">
                Esta contraseña no se vuelve a mostrar. Cópiala y compártela
                ahora por un medio seguro. Si se pierde, puedes crear una nueva
                con "Cambiar contraseña".
            </Recuadro>
        </div>

        <template #pie>
            <span class="mr-auto text-xs text-exito" aria-live="polite">
                <template v-if="copiado">✓ Copiado</template>
            </span>
            <Boton variante="secundario" @click="copiar('todo')">
                <Copy class="size-4" aria-hidden="true" />
                Copiar datos
            </Boton>
            <Boton @click="abierto = false">Listo</Boton>
        </template>
    </Modal>
</template>
