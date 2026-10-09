<script setup lang="ts">
/**
 * E12.3 · Editar un colaborador (HU-078, RN-025).
 *
 * La empresa cambia el nombre, el cargo y el correo (su usuario) y, si
 * marca "Cambiar su contraseña", define una nueva para compartirla (E12.2).
 * Si cambia el correo o la contraseña, se cierran sus sesiones abiertas.
 *
 * Envía: PUT /colaboradores/{id} con name, cargo, email y password (vacía
 * si no se cambia).
 */
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import CampoContrasena from '@/components/base/CampoContrasena.vue';
import Combobox from '@/components/base/Combobox.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import RequisitosContrasena from '@/components/base/RequisitosContrasena.vue';
import { cargosSugeridos } from '@/lib/cargos';
import { revisarContrasena } from '@/lib/contrasena';
import { rutas } from '@/lib/rutas';
import type { Colaborador, DatosDeAcceso } from '@/types/colaboradores';

const props = defineProps<{ colaborador: Colaborador }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const emit = defineEmits<{
    /** Con datos solo si se cambió la contraseña (para E12.2). */
    guardado: [datos: DatosDeAcceso | null];
}>();

const cambiarContrasena = ref(false);

const form = useForm({ name: '', cargo: '', email: '', password: '' });

watch(
    abierto,
    (valor) => {
        if (valor) {
            form.defaults({
                name: props.colaborador.nombre,
                cargo: props.colaborador.cargo ?? '',
                email: props.colaborador.correo,
                password: '',
            });
            form.reset();
            form.clearErrors();
            cambiarContrasena.value = false;
        }
    },
    { immediate: true },
);

watch(cambiarContrasena, (si) => {
    if (!si) {
        form.password = '';
    }
});

const primerNombre = computed(() => props.colaborador.nombre.split(/\s+/)[0]);

const cambiaCorreo = computed(
    () => form.email.trim().toLowerCase() !== props.colaborador.correo,
);

const listo = computed(
    () =>
        form.name.trim() !== '' &&
        form.cargo.trim() !== '' &&
        form.email.trim() !== '' &&
        form.isDirty &&
        (!cambiarContrasena.value ||
            revisarContrasena(form.password, '', { conConfirmacion: false })
                .completa),
);

function guardar(): void {
    const datos: DatosDeAcceso | null = cambiarContrasena.value
        ? {
              nombre: form.name.trim(),
              correo: form.email.trim().toLowerCase(),
              contrasena: form.password,
          }
        : null;

    form.put(rutas.colaboradores.actualizar(props.colaborador.id), {
        preserveScroll: true,
        onSuccess: () => {
            abierto.value = false;
            emit('guardado', datos);
        },
        onError: () => form.reset('password'),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="`Editar a ${colaborador.nombre}`"
        descripcion="Cambia sus datos o define una contraseña nueva."
    >
        <form
            id="form-editar-colaborador"
            class="flex flex-col gap-4"
            @submit.prevent="listo && guardar()"
        >
            <Campo
                obligatorio
                etiqueta="Nombre"
                para="editar-nombre"
                :error="form.errors.name"
            >
                <Entrada
                    id="editar-nombre"
                    v-model="form.name"
                    required
                    maxlength="255"
                    autocomplete="off"
                    :invalida="!!form.errors.name"
                />
            </Campo>
            <Campo
                obligatorio
                etiqueta="Cargo"
                para="editar-cargo"
                ayuda="Su área en la empresa. Si no aparece, escríbelo y elige «Usar…»."
                :error="form.errors.cargo"
            >
                <Combobox
                    id="editar-cargo"
                    v-model="form.cargo"
                    :opciones="cargosSugeridos"
                    permitir-otro
                    required
                    placeholder="Ej. Marketing, Producción"
                    :invalida="!!form.errors.cargo"
                />
            </Campo>
            <Campo
                obligatorio
                etiqueta="Correo (es su usuario)"
                para="editar-correo"
                :ayuda="
                    cambiaCorreo
                        ? `Desde ahora iniciará sesión con este correo. Si ${primerNombre} tiene la sesión abierta, se cerrará.`
                        : 'Con este correo inicia sesión.'
                "
                :error="form.errors.email"
            >
                <Entrada
                    id="editar-correo"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="off"
                    :invalida="!!form.errors.email"
                />
            </Campo>

            <div class="flex flex-col gap-3 border-t border-linea pt-4">
                <label class="flex items-center gap-2 text-sm font-medium">
                    <input
                        v-model="cambiarContrasena"
                        type="checkbox"
                        class="size-4 rounded border-linea-fuerte accent-marca"
                    />
                    Cambiar su contraseña
                </label>
                <template v-if="cambiarContrasena">
                    <Campo
                        obligatorio
                        etiqueta="Contraseña nueva"
                        para="editar-contrasena"
                        ayuda="La contraseña anterior deja de funcionar."
                        :error="form.errors.password"
                    >
                        <CampoContrasena
                            id="editar-contrasena"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                            :invalida="!!form.errors.password"
                        />
                    </Campo>
                    <RequisitosContrasena :contrasena="form.password" />
                    <p
                        class="rounded-md bg-lienzo-oscuro/60 px-3 py-2.5 text-xs text-tinta-suave"
                    >
                        Si {{ primerNombre }} tiene la sesión abierta, se
                        cerrará. Al guardar te mostramos los datos para que se
                        los compartas por un medio seguro.
                    </p>
                </template>
            </div>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-editar-colaborador"
                :disabled="!listo"
                :cargando="form.processing"
            >
                Guardar cambios
            </Boton>
        </template>
    </Modal>
</template>
