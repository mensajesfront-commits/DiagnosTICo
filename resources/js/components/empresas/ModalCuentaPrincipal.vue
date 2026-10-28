<script setup lang="ts">
/**
 * A3.1d · Dar una cuenta principal a una empresa que se quedó sin ella (su
 * cuenta pasó al equipo de NuevasTIC). Dos opciones:
 * - Uno de sus colaboradores pasa a ser la cuenta principal.
 * - Una cuenta nueva: le llega el correo para crear su contraseña (RN-006).
 *
 * Envía: POST /empresas/{id}/cuenta-principal con origen ("colaborador" o
 * "nueva") y colaborador_id, o usuario_nombre, cargo, email y
 * usuario_telefono.
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import CampoTelefono from '@/components/base/CampoTelefono.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import OpcionRol from '@/components/usuarios/OpcionRol.vue';
import { rutas } from '@/lib/rutas';
import type { Colaborador } from '@/types/empresas';

const props = defineProps<{
    empresa: { id: number; nombre: string; pais: string; activa: boolean };
    colaboradores: Colaborador[];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const activos = computed(() => props.colaboradores.filter((c) => c.activo));

const form = useForm<{
    origen: 'colaborador' | 'nueva';
    colaborador_id: number | '';
    usuario_nombre: string;
    cargo: string;
    email: string;
    usuario_telefono: string;
}>({
    origen: 'nueva',
    colaborador_id: '',
    usuario_nombre: '',
    cargo: '',
    email: '',
    usuario_telefono: '',
});

watch(abierto, (valor) => {
    if (valor) {
        form.reset();
        form.clearErrors();
        form.origen = activos.value.length > 0 ? 'colaborador' : 'nueva';
    }
});

const listo = computed(() =>
    form.origen === 'colaborador'
        ? form.colaborador_id !== ''
        : form.usuario_nombre.trim() !== '' && form.email.trim() !== '',
);

function guardar(): void {
    form.post(rutas.empresas.cuentaPrincipal(props.empresa.id), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        titulo="Dar una cuenta principal"
        :descripcion="`Con esta cuenta ${empresa.nombre} vuelve a entrar, responde sus diagnósticos y maneja sus colaboradores.`"
        ancho="lg"
    >
        <form
            id="form-cuenta-principal"
            class="flex flex-col gap-4"
            @submit.prevent="listo && guardar()"
        >
            <fieldset class="flex flex-col gap-2">
                <legend class="mb-1 text-xs font-medium">¿Quién será?</legend>
                <OpcionRol
                    v-model="form.origen"
                    nombre="origen"
                    valor="colaborador"
                    titulo="Uno de sus colaboradores"
                    :descripcion="
                        activos.length > 0
                            ? 'Pasa a ser la cuenta principal y sigue entrando con su correo y su contraseña.'
                            : 'La empresa no tiene colaboradores activos.'
                    "
                    :deshabilitada="activos.length === 0"
                />
                <OpcionRol
                    v-model="form.origen"
                    nombre="origen"
                    valor="nueva"
                    titulo="Una cuenta nueva"
                    descripcion="Le enviamos un correo para que cree su contraseña."
                />
            </fieldset>

            <Campo
                v-if="form.origen === 'colaborador'"
                obligatorio
                etiqueta="Colaborador"
                para="principal-colaborador"
                :error="form.errors.colaborador_id"
            >
                <Seleccion
                    id="principal-colaborador"
                    v-model="form.colaborador_id"
                    :invalida="!!form.errors.colaborador_id"
                >
                    <option value="" disabled>Elige un colaborador</option>
                    <option v-for="c in activos" :key="c.id" :value="c.id">
                        {{ c.name }} · {{ c.email }}
                    </option>
                </Seleccion>
            </Campo>

            <div v-else class="grid gap-x-4 gap-y-3 sm:grid-cols-2">
                <Campo
                    obligatorio
                    etiqueta="Nombre"
                    para="principal-nombre"
                    :error="form.errors.usuario_nombre"
                >
                    <Entrada
                        id="principal-nombre"
                        v-model="form.usuario_nombre"
                        required
                        maxlength="255"
                        autocomplete="off"
                        :invalida="!!form.errors.usuario_nombre"
                    />
                </Campo>
                <Campo
                    etiqueta="Cargo"
                    opcional
                    para="principal-cargo"
                    :error="form.errors.cargo"
                >
                    <Entrada
                        id="principal-cargo"
                        v-model="form.cargo"
                        maxlength="255"
                        placeholder="Ej. Gerente"
                    />
                </Campo>
                <Campo
                    obligatorio
                    etiqueta="Correo"
                    para="principal-correo"
                    ayuda="Aquí llega la invitación para crear su contraseña."
                    :error="form.errors.email"
                >
                    <Entrada
                        id="principal-correo"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="off"
                        placeholder="nombre@empresa.com"
                        :invalida="!!form.errors.email"
                    />
                </Campo>
                <Campo
                    etiqueta="Teléfono"
                    opcional
                    para="principal-telefono"
                    :error="form.errors.usuario_telefono"
                >
                    <CampoTelefono
                        id="principal-telefono"
                        v-model="form.usuario_telefono"
                        :pais="empresa.pais"
                        :invalida="!!form.errors.usuario_telefono"
                    />
                </Campo>
            </div>

            <p
                v-if="!empresa.activa"
                class="rounded-md bg-lienzo px-3 py-2.5 text-xs text-tinta-suave"
            >
                La empresa está desactivada: después de darle la cuenta,
                reactívala para que pueda entrar.
            </p>
        </form>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-cuenta-principal"
                :disabled="!listo"
                :cargando="form.processing"
            >
                Dar la cuenta
            </Boton>
        </template>
    </Modal>
</template>
