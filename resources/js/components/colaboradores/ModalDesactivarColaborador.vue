<script setup lang="ts">
/**
 * Confirmar antes de desactivar un colaborador (HU-079 CA-001, RN-004).
 *
 * [INFORMACIÓN PENDIENTE] El wireframe E12 no muestra esta confirmación; la
 * pide la historia ("elijo Desactivar y confirmo"). No tiene código propio.
 *
 * Envía: POST /colaboradores/{id}/desactivar (sin campos).
 */
import { useForm } from '@inertiajs/vue3';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';
import type { Colaborador } from '@/types/colaboradores';

const props = defineProps<{ colaborador: Colaborador }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({});

function desactivar(): void {
    form.post(rutas.colaboradores.desactivar(props.colaborador.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`¿Desactivar a ${colaborador.nombre}?`"
        :descripcion="colaborador.correo"
    >
        <p class="text-sm">
            No podrá iniciar sesión desde ahora. Sus respuestas y resultados se
            conservan, y puedes reactivarlo desde esta misma lista.
        </p>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton :cargando="form.processing" @click="desactivar">
                Desactivar
            </Boton>
        </template>
    </Modal>
</template>
