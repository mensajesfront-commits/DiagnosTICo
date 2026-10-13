<script setup lang="ts">
/**
 * A4.5 / A4.6 · Quitar el ajuste de un sector o de una empresa (HU-042
 * CA-005, HU-043 CA-004). Las mediciones ya analizadas no cambian.
 */
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';

const props = defineProps<{
    alcance: 'sector' | 'empresa';
    id: number;
    nombre: string;
    /** Sector: cuántas empresas vuelven a las generales. */
    empresas?: number;
    /** Sector: empresas con ajuste propio, que no cambian. */
    conAjustePropio?: string[];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });
const enviando = ref(false);

function quitar(): void {
    router.delete(rutas.ia.quitar(), {
        data: { alcance: props.alcance, id: props.id },
        preserveScroll: true,
        onStart: () => (enviando.value = true),
        onFinish: () => (enviando.value = false),
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        ancho="sm"
        :titulo="`¿Quitar el ajuste de ${nombre}?`"
    >
        <div class="flex flex-col gap-3 text-sm">
            <p v-if="alcance === 'sector'">
                <strong
                    >{{ empresas }}
                    {{
                        empresas === 1 ? 'empresa volverá' : 'empresas volverán'
                    }}</strong
                >
                a usar las instrucciones generales. Se perderán los textos de
                este ajuste.
            </p>
            <p v-else>
                {{ nombre }} volverá a usar las instrucciones de su sector. Se
                perderán los textos de este ajuste.
            </p>
            <p class="rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave">
                <template v-if="conAjustePropio?.length">
                    {{ conAjustePropio.join(', ') }} conserva{{
                        conAjustePropio.length > 1 ? 'n' : ''
                    }}
                    su ajuste propio.
                </template>
                Las mediciones ya analizadas no cambian.
            </p>
        </div>
        <template #pie>
            <Boton variante="secundario" @click="abierto = false"
                >Cancelar</Boton
            >
            <Boton variante="peligro" :cargando="enviando" @click="quitar">
                Quitar ajuste
            </Boton>
        </template>
    </Modal>
</template>
