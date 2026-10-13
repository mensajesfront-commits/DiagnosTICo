<script setup lang="ts">
/**
 * A2.2d · No se puede eliminar todavía, y A2.2e · Eliminar sector vacío
 * (HU-016). Un sector con empresas o mediciones no se elimina: se reasigna o
 * se desactiva (RN-008).
 */
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import TarjetaOpcion from '@/components/base/TarjetaOpcion.vue';
import { rutas } from '@/lib/rutas';
import type { ResumenDiagnosticos, Sector } from '@/types/diagnosticos';

const props = defineProps<{
    sector: Sector;
    resumen: ResumenDiagnosticos;
}>();

const emit = defineEmits<{ reasignar: []; desactivar: [] }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const bloqueado = computed(
    () => props.resumen.empresas > 0 || props.resumen.mediciones > 0,
);

const form = useForm({});

function eliminar(): void {
    form.delete(rutas.sectores.eliminar(props.sector.id), {
        onSuccess: () => (abierto.value = false),
    });
}

function ir(opcion: 'reasignar' | 'desactivar'): void {
    abierto.value = false;

    if (opcion === 'reasignar') {
        emit('reasignar');
    } else {
        emit('desactivar');
    }
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="
            bloqueado
                ? `No se puede eliminar “${sector.nombre}” todavía`
                : `¿Eliminar el sector “${sector.nombre}”?`
        "
    >
        <div v-if="bloqueado" class="flex flex-col gap-3 text-sm">
            <p class="rounded-md bg-aviso-suave px-3 py-2 text-aviso">
                ⚠ Tiene
                <strong>
                    {{ resumen.empresas }} empresas,
                    {{ sector.diagnosticos }} diagnósticos y
                    {{ resumen.mediciones }} mediciones</strong
                >. Solo se puede eliminar un sector sin empresas ni mediciones.
            </p>
            <p class="text-xs text-tinta-suave">Qué puedes hacer</p>
            <TarjetaOpcion
                v-if="resumen.empresas > 0"
                :titulo="`Reasignar las ${resumen.empresas} empresas a otro sector`"
                @elegir="ir('reasignar')"
            >
                Al reasignar, las empresas se llevan sus mediciones. Si
                {{ sector.nombre }} queda sin empresas ni mediciones, podrás
                eliminarlo.
            </TarjetaOpcion>
            <TarjetaOpcion
                v-if="sector.activo"
                titulo="Desactivar el sector"
                @elegir="ir('desactivar')"
            >
                Conserva todo; deja de ofrecerse al registrar empresas. Puedes
                reactivarlo cuando quieras.
            </TarjetaOpcion>
        </div>

        <div v-else class="flex flex-col gap-3 text-sm">
            <p>
                Este sector no tiene empresas ni mediciones, así que se puede
                eliminar. <strong>No se puede deshacer.</strong>
            </p>
            <p
                v-if="sector.activo"
                class="rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave"
            >
                Si solo quieres dejar de ofrecerlo al registrar empresas, mejor
                <button
                    type="button"
                    class="text-marca underline"
                    @click="ir('desactivar')"
                >
                    desactívalo</button
                >: se conserva y puedes reactivarlo cuando quieras.
            </p>
        </div>

        <template #pie>
            <template v-if="bloqueado">
                <Boton variante="secundario" @click="abierto = false">
                    Cerrar
                </Boton>
            </template>
            <template v-else>
                <Boton variante="secundario" @click="abierto = false">
                    Cancelar
                </Boton>
                <Boton
                    variante="peligro"
                    :cargando="form.processing"
                    @click="eliminar"
                >
                    Eliminar sector
                </Boton>
            </template>
        </template>
    </Modal>
</template>
