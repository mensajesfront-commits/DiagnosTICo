<script setup lang="ts">
/**
 * A2.2c · Desactivar un sector (HU-015) y reactivarlo (HU-015 CA-004).
 *
 * Desactivado, el sector deja de ofrecerse al registrar empresas nuevas y
 * conserva sus empresas, diagnósticos y mediciones (RN-003, RN-008).
 *
 * [INFORMACIÓN PENDIENTE] El wireframe A2.2c no está en los PDF recibidos;
 * los textos salen de HU-015.
 */
import { Power, PowerOff } from '@lucide/vue';
import { useForm } from '@inertiajs/vue3';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import { rutas } from '@/lib/rutas';
import { plural } from '@/lib/texto';
import type { ResumenDiagnosticos, Sector } from '@/types/diagnosticos';

const props = defineProps<{
    sector: Sector;
    resumen: ResumenDiagnosticos;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({});

function confirmar(): void {
    const url = props.sector.activo
        ? rutas.sectores.desactivar(props.sector.id)
        : rutas.sectores.reactivar(props.sector.id);

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="
            sector.activo
                ? `¿Desactivar el sector “${sector.nombre}”?`
                : `¿Reactivar el sector “${sector.nombre}”?`
        "
        :icono="sector.activo ? PowerOff : Power"
        :tono="sector.activo ? 'alerta' : 'exito'"
    >
        <div v-if="sector.activo" class="flex flex-col gap-3 text-sm">
            <p>
                Deja de ofrecerse al registrar empresas nuevas. Las empresas que
                ya lo tienen no cambian.
            </p>
            <Recuadro tono="neutro">
                Se conservan sus
                {{ plural(resumen.empresas, 'empresa', 'empresas') }},
                {{ plural(sector.diagnosticos, 'diagnóstico', 'diagnósticos') }}
                y {{ plural(resumen.mediciones, 'medición', 'mediciones') }}.
                Puedes reactivarlo cuando quieras desde «Editar sector».
            </Recuadro>
        </div>
        <p v-else class="text-sm">
            Vuelve a ofrecerse al registrar empresas nuevas.
        </p>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton :cargando="form.processing" @click="confirmar">
                {{ sector.activo ? 'Desactivar sector' : 'Reactivar sector' }}
            </Boton>
        </template>
    </Modal>
</template>
