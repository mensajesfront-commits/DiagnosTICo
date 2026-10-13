<script setup lang="ts">
/**
 * Eliminar un borrador (HU-022 CA-002).
 *
 * - "borrador": el borrador pendiente (v3) sobre una versión publicada (v2).
 *   La versión publicada, sus empresas y resultados no cambian.
 * - "diagnostico": un diagnóstico que nunca se publicó; se elimina entero.
 */
import { useForm } from '@inertiajs/vue3';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';
import type { FilaDiagnostico } from '@/types/diagnosticos';

const props = defineProps<{
    diagnostico: FilaDiagnostico;
    que: 'borrador' | 'diagnostico';
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({});

function eliminar(): void {
    const url =
        props.que === 'borrador'
            ? rutas.diagnosticos.eliminarBorrador(props.diagnostico.id)
            : rutas.diagnosticos.eliminar(props.diagnostico.id);

    form.delete(url, {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="
            que === 'borrador'
                ? `¿Eliminar el borrador “${diagnostico.nombre} · v${diagnostico.borrador_pendiente}”?`
                : `¿Eliminar “${diagnostico.nombre}”?`
        "
    >
        <div v-if="que === 'borrador'" class="flex flex-col gap-3 text-sm">
            <p>
                Se pierden todos los cambios hechos desde la v{{
                    diagnostico.version_publicada
                }}: preguntas, indicaciones e importancia editadas en el
                borrador.
            </p>
            <p class="rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave">
                No afecta a la
                <strong>v{{ diagnostico.version_publicada }} publicada</strong>:
                las {{ diagnostico.empresas }} empresas que la usan, sus
                {{ diagnostico.mediciones }} mediciones y sus resultados no
                cambian. El borrador eliminado no se puede recuperar.
            </p>
        </div>
        <p v-else class="text-sm">
            Es un borrador que nunca se publicó: ninguna empresa lo usa.
            <strong>No se puede deshacer.</strong>
        </p>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                variante="peligro"
                :cargando="form.processing"
                @click="eliminar"
            >
                {{
                    que === 'borrador'
                        ? 'Eliminar borrador'
                        : 'Eliminar diagnóstico'
                }}
            </Boton>
        </template>
    </Modal>
</template>
