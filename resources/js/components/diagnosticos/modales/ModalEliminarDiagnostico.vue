<script setup lang="ts">
/**
 * Eliminar un borrador (HU-022 CA-002).
 *
 * - "borrador": el borrador pendiente (v3) sobre una versión publicada (v2).
 *   La versión publicada, sus empresas y resultados no cambian.
 * - "diagnostico": un diagnóstico que nunca se publicó; se elimina entero.
 */
import { useForm } from '@inertiajs/vue3';
import { FileX2, Trash2 } from '@lucide/vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
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
                ? `¿Eliminar el borrador v${diagnostico.borrador_pendiente}?`
                : `¿Eliminar “${diagnostico.nombre}”?`
        "
        :descripcion="
            que === 'borrador'
                ? `${diagnostico.nombre} · ${diagnostico.sector_nombre}`
                : diagnostico.sector_nombre
        "
        :icono="que === 'borrador' ? FileX2 : Trash2"
        tono="peligro"
    >
        <div v-if="que === 'borrador'" class="flex flex-col gap-3 text-sm">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div
                    class="rounded-xl border border-aviso/30 bg-aviso-suave/50 px-3 py-2.5"
                >
                    <p class="text-[11px] font-semibold text-aviso uppercase">
                        Se elimina
                    </p>
                    <p class="mt-0.5 font-medium">
                        Borrador v{{ diagnostico.borrador_pendiente }}
                    </p>
                    <p class="text-xs text-tinta-suave">
                        Los cambios desde la v{{
                            diagnostico.version_publicada
                        }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-exito/30 bg-exito-suave/60 px-3 py-2.5"
                >
                    <p class="text-[11px] font-semibold text-exito uppercase">
                        Se conserva
                    </p>
                    <p class="mt-0.5 font-medium">
                        v{{ diagnostico.version_publicada }} publicada
                    </p>
                    <p class="text-xs text-tinta-suave">
                        {{ diagnostico.empresas }}
                        {{
                            diagnostico.empresas === 1 ? 'empresa' : 'empresas'
                        }}
                        · {{ diagnostico.mediciones }}
                        {{
                            diagnostico.mediciones === 1
                                ? 'medición'
                                : 'mediciones'
                        }}
                    </p>
                </div>
            </div>
            <p>
                Se pierden las preguntas, indicaciones e importancia editadas en
                el borrador. La versión publicada, sus mediciones y sus
                resultados no cambian.
            </p>
            <Recuadro tono="peligro" titulo="No se puede deshacer">
                El borrador eliminado no se puede recuperar.
            </Recuadro>
        </div>
        <div v-else class="flex flex-col gap-3 text-sm">
            <p>Es un borrador que nunca se publicó: ninguna empresa lo usa.</p>
            <Recuadro tono="peligro" titulo="No se puede deshacer">
                Se borran sus {{ diagnostico.categorias }} categorías y
                {{ diagnostico.preguntas }} preguntas.
            </Recuadro>
        </div>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                variante="peligro"
                class="gap-1.5"
                :cargando="form.processing"
                @click="eliminar"
            >
                <Trash2 class="size-4" aria-hidden="true" />
                {{
                    que === 'borrador'
                        ? 'Eliminar borrador'
                        : 'Eliminar diagnóstico'
                }}
            </Boton>
        </template>
    </Modal>
</template>
