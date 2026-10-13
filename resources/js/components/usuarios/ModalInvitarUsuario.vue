<script setup lang="ts">
/**
 * Invitar usuario interno (HU-046). Para alguien del equipo de NuevasTIC: no
 * se asocia a ninguna empresa. Recibe un correo con un enlace para crear su
 * contraseña, que sirve hasta que la cree (RN-006).
 *
 * [INFORMACIÓN PENDIENTE] El wireframe no le da código a este modal.
 *
 * Envía: POST /usuarios/invitar con name, email, rol_id y mensaje (opcional).
 */
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import OpcionRol from '@/components/usuarios/OpcionRol.vue';
import { rutas } from '@/lib/rutas';
import type { Rol } from '@/types/usuarios';

const props = defineProps<{
    /** Roles internos: Administrador y los creados (no Empresa ni Colaborador). */
    roles: Pick<Rol, 'id' | 'nombre' | 'descripcion' | 'activo' | 'aviso'>[];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm<{
    name: string;
    email: string;
    rol_id: number | null;
    mensaje: string;
}>({ name: '', email: '', rol_id: null, mensaje: '' });

watch(
    abierto,
    (valor) => {
        if (valor) {
            form.reset();
            form.clearErrors();
            form.rol_id = props.roles.find((r) => r.activo)?.id ?? null;
        }
    },
    { immediate: true },
);

function invitar(): void {
    form.post(rutas.usuarios.invitar(), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        titulo="Invitar usuario interno"
        descripcion="Para alguien del equipo de NuevasTIC. No se asocia a ninguna empresa: las cuentas de empresa se crean al registrar la empresa."
        ancho="lg"
    >
        <form
            id="form-invitar"
            class="flex flex-col gap-4"
            @submit.prevent="invitar"
        >
            <p class="text-xs text-tinta-suave">
                Todos los campos son obligatorios, salvo los marcados como
                opcionales.
            </p>
            <div class="grid gap-4 sm:grid-cols-2">
                <Campo
                    obligatorio
                    etiqueta="Nombre"
                    para="invitar-nombre"
                    :error="form.errors.name"
                >
                    <Entrada
                        id="invitar-nombre"
                        v-model="form.name"
                        required
                        :invalida="!!form.errors.name"
                    />
                </Campo>
                <Campo
                    obligatorio
                    etiqueta="Correo"
                    para="invitar-correo"
                    :error="form.errors.email"
                >
                    <Entrada
                        id="invitar-correo"
                        v-model="form.email"
                        type="email"
                        required
                        :invalida="!!form.errors.email"
                    />
                </Campo>
            </div>

            <fieldset class="flex flex-col gap-2">
                <legend class="mb-1 text-xs font-medium">Rol</legend>
                <OpcionRol
                    v-for="rol in roles"
                    :key="rol.id"
                    v-model="form.rol_id"
                    nombre="invitar-rol"
                    :valor="rol.id"
                    :titulo="rol.nombre"
                    :descripcion="
                        rol.activo
                            ? rol.descripcion
                            : (rol.aviso ?? rol.descripcion)
                    "
                    :deshabilitada="!rol.activo"
                    :etiqueta="rol.activo ? null : 'Próxima fase'"
                    tono-etiqueta="alerta"
                />
                <p v-if="form.errors.rol_id" class="text-xs text-aviso">
                    {{ form.errors.rol_id }}
                </p>
            </fieldset>

            <Campo
                etiqueta="Mensaje"
                para="invitar-mensaje"
                opcional
                ayuda="Se incluye en el correo de invitación."
                :error="form.errors.mensaje"
            >
                <AreaTexto
                    id="invitar-mensaje"
                    v-model="form.mensaje"
                    rows="3"
                    placeholder="Ej.: Te invito a apoyar la revisión de diagnósticos."
                />
            </Campo>

            <p
                class="rounded-md bg-lienzo px-3 py-2.5 text-xs text-tinta-suave"
            >
                Recibirá un correo para crear su contraseña. El enlace sirve
                hasta que la cree.
            </p>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-invitar"
                :disabled="form.rol_id === null"
                :cargando="form.processing"
            >
                Enviar invitación
            </Boton>
        </template>
    </Modal>
</template>
