<script setup lang="ts">
/**
 * A3.1c · Confirmar que la nueva medición reemplaza a la pendiente
 * (HU-036 CA-002, RN-014). Muestra lado a lado la que se cancela y la
 * nueva, que toma su mismo número, y si se pierden respuestas parciales.
 * Lo usan A3.1 ("Asignar otra medición") y A3.1b.
 */
import Boton from '@/components/base/Boton.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import type { EstadoMedicion } from '@/components/base/EtiquetaEstado.vue';
import Modal from '@/components/base/Modal.vue';
import { diaYMes, fechaLocal } from '@/lib/fechas';

defineProps<{
    empresa: string;
    actual: {
        numero: number;
        estado: EstadoMedicion;
        diagnostico: string | null;
        version: number | null;
        vence: string | null;
        tiene_respuestas: boolean;
    };
    nueva: {
        diagnostico: string;
        version: number;
        /** "AAAA-MM-DD" o vacío. */
        fecha_limite: string;
    };
    procesando?: boolean;
}>();

const emit = defineEmits<{ confirmar: [] }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const fecha = (texto: string) => diaYMes(fechaLocal(texto));
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        ancho="lg"
        :titulo="`¿Reemplazar la medición ${actual.numero} de ${empresa}?`"
        :descripcion="`Solo puede haber una medición pendiente por empresa. La medición ${actual.numero} se cancela y la nueva ocupa su lugar.`"
    >
        <div class="grid gap-3 text-sm sm:grid-cols-2">
            <div class="rounded-lg border border-linea-fuerte px-4 py-3">
                <p class="text-xs text-tinta-suave">Se reemplaza</p>
                <p class="mt-1 font-semibold">Medición {{ actual.numero }}</p>
                <EtiquetaEstado class="mt-1.5" :estado="actual.estado" />
                <p class="mt-1.5 text-xs text-tinta-suave">
                    {{ actual.diagnostico
                    }}<template v-if="actual.version">
                        · v{{ actual.version }}</template
                    ><template v-if="actual.vence">
                        · vence {{ fecha(actual.vence) }}</template
                    >
                </p>
            </div>
            <div class="rounded-lg border-2 border-marca px-4 py-3">
                <p class="text-xs text-tinta-suave">Nueva medición</p>
                <p class="mt-1 font-semibold">Medición {{ actual.numero }}</p>
                <p class="mt-1.5 text-xs text-tinta-suave">
                    {{ nueva.diagnostico }} · v{{ nueva.version }}
                </p>
                <p class="mt-1 text-xs text-tinta-suave">
                    {{
                        nueva.fecha_limite
                            ? `Fecha límite: ${fecha(nueva.fecha_limite)}`
                            : 'Sin fecha límite'
                    }}
                </p>
            </div>
        </div>
        <p
            :class="[
                'mt-3 rounded-md px-3 py-2 text-xs',
                actual.tiene_respuestas
                    ? 'bg-aviso-suave text-aviso'
                    : 'bg-lienzo text-tinta-suave',
            ]"
        >
            <template v-if="actual.tiene_respuestas">
                <strong>Tiene respuestas parciales.</strong> Se cancelan con la
                medición {{ actual.numero }} y no se analizan.
            </template>
            <template v-else>
                Respuestas parciales: <strong>ninguna</strong>. La empresa aún
                no había respondido, así que no se pierde nada.
            </template>
        </p>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton :cargando="procesando" @click="emit('confirmar')">
                Reemplazar medición
            </Boton>
        </template>
    </Modal>
</template>
