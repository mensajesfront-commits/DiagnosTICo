<script setup lang="ts">
/**
 * Archivar un diagnóstico publicado (HU-022 CA-001). Deja de ofrecerse para
 * asignar y conserva sus resultados; se puede deshacer.
 *
 * [INFORMACIÓN PENDIENTE] El modal no está diseñado en el prototipo (PA-005).
 * Esta versión sigue el estilo de los demás modales de confirmación.
 */
import { useForm } from '@inertiajs/vue3';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
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
        :titulo="`¿Archivar “${diagnostico.nombre} · ${diagnostico.sector_nombre}”?`"
    >
        <div class="flex flex-col gap-3 text-sm">
            <p>
                Sale de la lista de diagnósticos activos y deja de ofrecerse
                para asignar mediciones nuevas.
            </p>
            <p class="rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave">
                Las {{ diagnostico.empresas }} empresas que lo usan, sus
                {{ diagnostico.mediciones }} mediciones y sus resultados se
                conservan. Se puede deshacer.
            </p>
        </div>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton :cargando="form.processing" @click="archivar">
                Archivar diagnóstico
            </Boton>
        </template>
    </Modal>
</template>
