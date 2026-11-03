<script setup lang="ts">
/**
 * Archivar un diagnóstico publicado (HU-022 CA-001). Deja de ofrecerse para
 * asignar y conserva sus resultados; se puede deshacer.
 *
 * [INFORMACIÓN PENDIENTE] El modal no está diseñado en el prototipo (PA-005).
 * Esta versión sigue el estilo de los demás modales de confirmación.
 */
import { useForm } from '@inertiajs/vue3';
import { Archive } from '@lucide/vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import { rutas } from '@/lib/rutas';
import type { FilaDiagnostico } from '@/types/diagnosticos';

const props = defineProps<{ diagnostico: FilaDiagnostico }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({});

function archivar(): void {
    form.post(rutas.diagnosticos.archivar(props.diagnostico.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`¿Archivar “${diagnostico.nombre}”?`"
        :descripcion="`Sector ${diagnostico.sector_nombre}${diagnostico.version_publicada ? ` · v${diagnostico.version_publicada} publicada` : ''}`"
        :icono="Archive"
        tono="alerta"
    >
        <div class="flex flex-col gap-3 text-sm">
            <p>
                Sale de la lista de diagnósticos activos y deja de ofrecerse
                para asignar mediciones nuevas.
            </p>
            <div class="grid grid-cols-2 gap-2 text-center">
                <div class="rounded-xl bg-lienzo/70 px-2 py-2.5">
                    <p class="font-mono text-lg leading-none font-semibold">
                        {{ diagnostico.empresas }}
                    </p>
                    <p class="mt-1 text-[11px] text-tinta-suave">
                        {{
                            diagnostico.empresas === 1
                                ? 'empresa lo usa'
                                : 'empresas lo usan'
                        }}
                    </p>
                </div>
                <div class="rounded-xl bg-lienzo/70 px-2 py-2.5">
                    <p class="font-mono text-lg leading-none font-semibold">
                        {{ diagnostico.mediciones }}
                    </p>
                    <p class="mt-1 text-[11px] text-tinta-suave">
                        {{
                            diagnostico.mediciones === 1
                                ? 'medición'
                                : 'mediciones'
                        }}
                    </p>
                </div>
            </div>
            <Recuadro tono="exito" titulo="Se conserva todo">
                Las empresas, sus mediciones y sus resultados no cambian. Lo
                puedes restaurar desde «Archivados» cuando quieras.
            </Recuadro>
        </div>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                class="gap-1.5"
                :cargando="form.processing"
                @click="archivar"
            >
                <Archive class="size-4" aria-hidden="true" />
                Archivar diagnóstico
            </Boton>
        </template>
    </Modal>
</template>
