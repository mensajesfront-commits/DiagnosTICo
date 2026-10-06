<script setup lang="ts">
/**
 * A5.1d · Eliminar un rol creado y sin cuentas (HU-051, RN-027). No se puede
 * deshacer. Los roles del sistema y los que tienen cuentas no llegan aquí:
 * el botón está desactivado.
 *
 * Envía: DELETE /roles/{id}.
 */
import { useForm } from '@inertiajs/vue3';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';
import type { Rol } from '@/types/usuarios';

const props = defineProps<{ rol: Rol }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({});

function eliminar(): void {
    form.delete(rutas.roles.eliminar(props.rol.id), {
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`¿Eliminar el rol “${rol.nombre}”?`"
    >
        <div class="flex flex-col gap-3 text-sm">
            <p>
                Tiene {{ rol.cuentas_total }}
                {{
                    rol.cuentas_total === 1
                        ? 'cuenta asignada'
                        : 'cuentas asignadas'
                }}
                y {{ rol.permisos.length }}
                {{
                    rol.permisos.length === 1
                        ? 'permiso configurado'
                        : 'permisos configurados'
                }}. Se elimina junto con su configuración.
                <strong>No se puede deshacer.</strong>
            </p>
            <p
                class="rounded-md bg-lienzo px-3 py-2.5 text-xs text-tinta-suave"
            >
                Un rol con cuentas no se puede eliminar: primero hay que pasar
                esas cuentas a otro rol. Los roles del sistema (Administrador,
                Empresa y Colaborador) tampoco se eliminan.
            </p>
        </div>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                variante="peligro"
                :cargando="form.processing"
                @click="eliminar"
            >
                Eliminar rol
            </Boton>
        </template>
    </Modal>
</template>
