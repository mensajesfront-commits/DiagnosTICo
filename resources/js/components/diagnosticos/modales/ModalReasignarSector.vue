<script setup lang="ts">
/**
 * A2.2b · Reasignar las empresas de un sector (HU-014).
 *
 * Envía: sector_destino_id y avisar (correo a las empresas). Las empresas se
 * llevan sus mediciones (RN-008).
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Modal from '@/components/base/Modal.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import { rutas } from '@/lib/rutas';
import type {
    MedicionesPorEstado,
    ResumenDiagnosticos,
    Sector,
} from '@/types/diagnosticos';

const props = defineProps<{
    sector: Sector;
    resumen: ResumenDiagnosticos & {
        /** Todas las mediciones del sector por estado (no solo la última). */
        mediciones_por_estado?: MedicionesPorEstado;
    };
    sectores: Sector[];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm<{ sector_destino_id: number | ''; avisar: boolean }>({
    sector_destino_id: '',
    avisar: false,
});

watch(abierto, (valor) => valor && form.reset(), { immediate: true });

const destinos = computed(() =>
    props.sectores.filter((s) => s.id !== props.sector.id && s.activo),
);

const porEstado = computed(
    () => props.resumen.mediciones_por_estado ?? props.resumen.ultima_medicion,
);

function reasignar(): void {
    form.post(rutas.sectores.reasignar(props.sector.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`Reasignar las ${resumen.empresas} empresas de ${sector.nombre}`"
        :descripcion="`Al reasignar, las empresas se llevan sus mediciones. Si ${sector.nombre} queda sin empresas ni mediciones, podrás eliminarlo.`"
    >
        <form
            id="form-reasignar"
            class="flex flex-col gap-4"
            @submit.prevent="reasignar"
        >
            <Campo
                etiqueta="Sector destino"
                para="sector-destino"
                :error="form.errors.sector_destino_id"
            >
                <Seleccion
                    id="sector-destino"
                    v-model="form.sector_destino_id"
                    required
                >
                    <option value="" disabled>
                        Selecciona el sector destino
                    </option>
                    <option
                        v-for="destino in destinos"
                        :key="destino.id"
                        :value="destino.id"
                    >
                        {{ destino.nombre }}
                    </option>
                </Seleccion>
            </Campo>

            <div class="rounded-md bg-lienzo px-3 py-2.5 text-xs">
                <p class="mb-1.5 font-semibold">
                    Qué pasa con sus {{ resumen.mediciones }} mediciones
                </p>
                <ul class="flex flex-col gap-1">
                    <li>
                        ✓
                        <strong>Terminadas ({{ porEstado.terminada }}):</strong>
                        se mueven con sus empresas y se conservan con sus
                        resultados.
                    </li>
                    <li>
                        ◐ <strong>En curso ({{ porEstado.en_curso }}):</strong>
                        sigue con el diagnóstico con el que empezó.
                    </li>
                    <li>
                        ○
                        <strong
                            >No iniciadas ({{ porEstado.no_iniciada }}):</strong
                        >
                        las que asignes después usarán un diagnóstico publicado
                        del sector destino.
                    </li>
                    <li class="text-tinta-suave">
                        Puedes volver a reasignarlas cuando quieras.
                    </li>
                </ul>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input
                    v-model="form.avisar"
                    type="checkbox"
                    class="size-4 accent-marca"
                />
                Avisar por correo a las {{ resumen.empresas }} empresas
            </label>
        </form>

        <template v-if="!form.sector_destino_id" #nota>
            Elige un sector destino
        </template>
        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-reasignar"
                :disabled="!form.sector_destino_id"
                :cargando="form.processing"
            >
                Reasignar {{ resumen.empresas }} empresas
            </Boton>
        </template>
    </Modal>
</template>
