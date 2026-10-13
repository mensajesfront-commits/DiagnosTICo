<script setup lang="ts">
/**
 * A5.5 · Cambiar el rol de una cuenta (HU-052). Cada cuenta tiene un solo rol
 * (RN-027): el nuevo reemplaza al actual. Los roles inactivos no se ofrecen
 * (HU-050 CA-003).
 *
 * Se ofrecen los roles internos (Administrador y los creados) y, si la cuenta
 * es de una empresa, también "Empresa", como en el wireframe. "Colaborador"
 * no: los colaboradores los crea cada empresa (RN-025).
 *
 * Envía: PUT /usuarios/{id}/rol con rol_id y avisar (aviso por correo).
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import OpcionRol from '@/components/usuarios/OpcionRol.vue';
import { rutas } from '@/lib/rutas';
import { avisoCuentaPrincipal } from '@/lib/usuarios';
import type { Cuenta, Rol } from '@/types/usuarios';

const props = defineProps<{
    cuenta: Cuenta;
    roles: Pick<Rol, 'id' | 'nombre' | 'descripcion' | 'activo'>[];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const rolActual = computed(
    () => props.roles.find((r) => r.nombre === props.cuenta.rol) ?? null,
);

const opciones = computed(() =>
    props.roles.filter(
        (r) =>
            r.id === rolActual.value?.id ||
            (r.nombre !== 'Colaborador' &&
                (r.nombre !== 'Empresa' || props.cuenta.empresa !== null)),
    ),
);

const form = useForm<{ rol_id: number | null; avisar: boolean }>({
    rol_id: null,
    avisar: true,
});

watch(
    abierto,
    (valor) => {
        if (valor) {
            form.rol_id = rolActual.value?.id ?? null;
            form.avisar = true;
            form.clearErrors();
        }
    },
    { immediate: true },
);

const cambia = computed(
    () => form.rol_id !== null && form.rol_id !== rolActual.value?.id,
);

const aviso = computed(() =>
    cambia.value ? avisoCuentaPrincipal(props.cuenta, 'cambiar') : null,
);

const detalle = computed(() =>
    [
        props.cuenta.correo,
        props.cuenta.empresa,
        `Rol actual: ${props.cuenta.rol ?? 'sin rol'}`,
    ]
        .filter(Boolean)
        .join(' · '),
);

function cambiar(): void {
    form.put(rutas.usuarios.cambiarRol(props.cuenta.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`Cambiar el rol de ${cuenta.nombre}`"
        :descripcion="detalle"
    >
        <form
            id="form-cambiar-rol"
            class="flex flex-col gap-3"
            @submit.prevent="cambiar"
        >
            <fieldset class="flex flex-col gap-2">
                <legend class="mb-1 text-xs font-medium">Nuevo rol</legend>
                <OpcionRol
                    v-for="rol in opciones"
                    :key="rol.id"
                    v-model="form.rol_id"
                    nombre="nuevo-rol"
                    :valor="rol.id"
                    :titulo="rol.nombre"
                    :descripcion="rol.descripcion"
                    :deshabilitada="!rol.activo"
                    :etiqueta="
                        rol.id === rolActual?.id
                            ? 'Rol actual'
                            : !rol.activo
                              ? 'Próximamente'
                              : null
                    "
                    :tono-etiqueta="rol.activo ? 'neutro' : 'alerta'"
                />
                <p v-if="form.errors.rol_id" class="text-xs text-aviso">
                    {{ form.errors.rol_id }}
                </p>
            </fieldset>

            <p
                v-if="aviso"
                class="rounded-md bg-alerta-suave px-3 py-2.5 text-sm text-alerta"
            >
                ⚠ {{ aviso }}
            </p>

            <label class="flex items-center gap-2 text-sm">
                <input
                    v-model="form.avisar"
                    type="checkbox"
                    class="size-4 accent-marca"
                />
                Avisarle por correo del cambio de rol
            </label>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-cambiar-rol"
                :disabled="!cambia"
                :cargando="form.processing"
            >
                Cambiar rol
            </Boton>
        </template>
    </Modal>
</template>
