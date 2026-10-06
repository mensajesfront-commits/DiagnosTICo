<script setup lang="ts">
/**
 * A5.3b · Desactivar una cuenta (HU-047, RN-004).
 *
 * No borra nada: la persona no puede entrar y se puede reactivar desde la
 * lista. Si es la cuenta principal de una empresa, avisa que la empresa y sus
 * colaboradores se quedan sin acceso (RN-025).
 *
 * Envía: POST /usuarios/{id}/desactivar (sin campos).
 */
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';
import { avisoCuentaPrincipal } from '@/lib/usuarios';
import type { Cuenta } from '@/types/usuarios';

const props = defineProps<{ cuenta: Cuenta }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({});

const aviso = computed(() => avisoCuentaPrincipal(props.cuenta, 'desactivar'));

const detalle = computed(() =>
    [
        props.cuenta.correo,
        `Rol ${props.cuenta.rol ?? 'sin rol'}`,
        props.cuenta.empresa,
    ]
        .filter(Boolean)
        .join(' · '),
);

function desactivar(): void {
    form.post(rutas.usuarios.desactivar(props.cuenta.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`¿Desactivar la cuenta de ${cuenta.nombre}?`"
        :descripcion="detalle"
    >
        <div class="flex flex-col gap-3 text-sm">
            <p>
                No podrá iniciar sesión. Su información no se borra y puedes
                reactivarla desde esta misma lista.
            </p>
            <p
                v-if="aviso"
                class="rounded-md bg-alerta-suave px-3 py-2.5 text-alerta"
            >
                ⚠ {{ aviso }}
            </p>
        </div>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton :cargando="form.processing" @click="desactivar">
                Desactivar cuenta
            </Boton>
        </template>
    </Modal>
</template>
