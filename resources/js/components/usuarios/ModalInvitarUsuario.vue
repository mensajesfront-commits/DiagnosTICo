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
import {
    Building2,
    Mail,
    MessageSquareText,
    Send,
    ShieldCheck,
    UserPlus,
    UserRound,
} from '@lucide/vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import TituloSeccion from '@/components/base/TituloSeccion.vue';
import OpcionRol from '@/components/usuarios/OpcionRol.vue';
import { rutas } from '@/lib/rutas';
import type { Rol } from '@/types/usuarios';

const props = defineProps<{
    /** Roles internos: Administrador y los creados (no Empresa ni Colaborador). */
    roles: Pick<Rol, 'id' | 'nombre' | 'descripcion' | 'activo' | 'aviso'>[];
    /** Empresas que se le pueden vincular a un Consultor (HU-085). */
    empresas: { id: number; nombre: string }[];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm<{
    name: string;
    email: string;
    rol_id: number | null;
    mensaje: string;
    empresas: number[];
}>({ name: '', email: '', rol_id: null, mensaje: '', empresas: [] });

/** Consultor externo: solo mira las empresas que se eligen aquí (RN-030). */
const esConsultor = computed(
    () => props.roles.find((r) => r.id === form.rol_id)?.nombre === 'Consultor',
);

const buscar = ref('');
const empresasFiltradas = computed(() => {
    const texto = buscar.value.trim().toLocaleLowerCase('es');

    return texto === ''
        ? props.empresas
        : props.empresas.filter((e) =>
              e.nombre.toLocaleLowerCase('es').includes(texto),
          );
});

watch(
    abierto,
    (valor) => {
        if (valor) {
            form.reset();
            form.clearErrors();
            buscar.value = '';
            form.rol_id = props.roles.find((r) => r.activo)?.id ?? null;
        }
    },
    { immediate: true },
);

function invitar(): void {
    form.transform((datos) => ({
        ...datos,
        empresas: esConsultor.value ? datos.empresas : [],
    })).post(rutas.usuarios.invitar(), {
        preserveScroll: true,
        onSuccess: () => (abierto.value = false),
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        :titulo="esConsultor ? 'Invitar consultor' : 'Invitar usuario interno'"
        :descripcion="
            esConsultor
                ? 'Para un consultor externo: solo verá los resultados de las empresas que elijas. No responde ni toca diagnósticos.'
                : 'Para alguien del equipo de NuevasTIC. No se asocia a ninguna empresa: las cuentas de empresa se crean al registrar la empresa.'
        "
        ancho="lg"
        :icono="UserPlus"
    >
        <form
            id="form-invitar"
            class="flex flex-col gap-6"
            @submit.prevent="invitar"
        >
            <section class="flex flex-col gap-3">
                <TituloSeccion
                    :icono="UserRound"
                    titulo="Datos de la persona"
                    descripcion="Todos los campos son obligatorios, salvo los marcados como opcionales."
                />
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
            </section>

            <div
                role="radiogroup"
                aria-labelledby="invitar-titulo-rol"
                class="flex flex-col gap-2"
            >
                <TituloSeccion
                    id="invitar-titulo-rol"
                    :icono="ShieldCheck"
                    titulo="Rol"
                    descripcion="Define qué puede hacer. Se cambia después desde Usuarios y roles."
                    class="mb-1"
                />
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
                    :etiqueta="rol.activo ? null : 'Inactivo'"
                    tono-etiqueta="alerta"
                />
                <p v-if="form.errors.rol_id" class="text-xs text-aviso">
                    {{ form.errors.rol_id }}
                </p>
            </div>

            <section v-if="esConsultor" class="flex flex-col gap-3">
                <TituloSeccion
                    :icono="Building2"
                    titulo="Empresas que acompaña"
                    :descripcion="`Opcional: también se vinculan después desde la ficha de cada empresa. ${form.empresas.length} elegidas.`"
                />
                <Entrada
                    v-model="buscar"
                    type="search"
                    placeholder="Buscar empresa…"
                    aria-label="Buscar empresa"
                />
                <ul
                    class="flex max-h-52 flex-col gap-1 overflow-y-auto rounded-xl border border-linea p-1.5"
                    aria-label="Empresas"
                >
                    <li v-for="e in empresasFiltradas" :key="e.id">
                        <label
                            class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm hover:bg-lienzo has-checked:bg-marca-suave/50"
                        >
                            <input
                                v-model="form.empresas"
                                type="checkbox"
                                :value="e.id"
                                class="size-4 rounded border-linea-fuerte accent-marca"
                            />
                            {{ e.nombre }}
                        </label>
                    </li>
                    <li
                        v-if="empresasFiltradas.length === 0"
                        class="px-2.5 py-2 text-sm text-tinta-suave"
                    >
                        Ninguna empresa coincide.
                    </li>
                </ul>
                <p
                    v-if="form.errors.empresas || form.errors['empresas.0']"
                    class="text-xs text-aviso"
                >
                    {{ form.errors.empresas ?? form.errors['empresas.0'] }}
                </p>
            </section>

            <section class="flex flex-col gap-3">
                <TituloSeccion
                    :icono="MessageSquareText"
                    titulo="Mensaje"
                    descripcion="Opcional: se incluye en el correo de invitación."
                />
                <Campo
                    etiqueta="Mensaje para la persona"
                    para="invitar-mensaje"
                    opcional
                    :error="form.errors.mensaje"
                >
                    <AreaTexto
                        id="invitar-mensaje"
                        v-model="form.mensaje"
                        rows="3"
                        placeholder="Ej.: Te invito a apoyar la revisión de diagnósticos."
                    />
                </Campo>
            </section>

            <p
                class="flex items-start gap-2.5 rounded-xl bg-lienzo px-3 py-2.5 text-sm text-tinta-suave"
            >
                <Mail
                    class="mt-0.5 size-4 shrink-0 text-marca"
                    aria-hidden="true"
                />
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
                <Send class="size-4" aria-hidden="true" />
                Enviar invitación
            </Boton>
        </template>
    </Modal>
</template>
