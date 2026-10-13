<script setup lang="ts">
/**
 * A5.1 · Usuarios y roles, pestaña Roles (HU-048 a HU-052).
 *
 * A la izquierda la lista de roles: los del sistema (Administrador, Empresa,
 * Colaborador) con la etiqueta "Del sistema", y los creados, con "Inactivo"
 * si no se pueden asignar. A la derecha el detalle del rol elegido
 * (PanelRol). "+ Crear rol" abre A5.2.
 */
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import EncabezadoUsuarios from '@/components/usuarios/EncabezadoUsuarios.vue';
import ModalAsignarRol from '@/components/usuarios/ModalAsignarRol.vue';
import ModalCambiarRol from '@/components/usuarios/ModalCambiarRol.vue';
import ModalCrearRol from '@/components/usuarios/ModalCrearRol.vue';
import ModalEliminarRol from '@/components/usuarios/ModalEliminarRol.vue';
import ModalInvitarUsuario from '@/components/usuarios/ModalInvitarUsuario.vue';
import PanelRol from '@/components/usuarios/PanelRol.vue';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { BloquePermisos, Cuenta, Rol } from '@/types/usuarios';

const props = withDefaults(
    defineProps<{
        roles: Rol[];
        /** Los 15 permisos de A5.2 en sus 6 bloques. */
        bloques: BloquePermisos[];
        /** Todas las cuentas, para asignar un rol o cambiarlo. */
        cuentas: Cuenta[];
        /** Rol abierto al entrar (?rol=id); sin él, el primero. */
        rolId?: number | null;
    }>(),
    { rolId: null },
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Usuarios y roles', href: rutas.usuarios.lista() },
            { title: 'Roles', href: rutas.usuarios.roles() },
        ],
    },
});

const elegidoId = ref<number | null>(props.rolId ?? props.roles[0]?.id ?? null);
const elegido = computed(
    () => props.roles.find((r) => r.id === elegidoId.value) ?? null,
);

function elegir(rol: Rol): void {
    elegidoId.value = rol.id;
}

const cuentasSinEsteRol = computed(() =>
    props.cuentas.filter(
        (c) =>
            !c.es_tuya &&
            c.estado !== 'invitacion' &&
            c.estado !== 'eliminada' &&
            c.rol !== elegido.value?.nombre,
    ),
);

const modalCrear = ref(false);
const modalAsignar = ref(false);
const modalEliminar = ref(false);
const modalInvitar = ref(false);
const modalCambiarRol = ref(false);
const cuentaACambiar = ref<Cuenta | null>(null);

function cambiarRol(cuentaId: number): void {
    cuentaACambiar.value = props.cuentas.find((c) => c.id === cuentaId) ?? null;
    modalCambiarRol.value = cuentaACambiar.value !== null;
}

const rolesInternos = computed(() =>
    props.roles.filter((r) => !['Empresa', 'Colaborador'].includes(r.nombre)),
);
</script>

<template>
    <Head title="Roles" />

    <div class="flex flex-col gap-5 p-6">
        <EncabezadoUsuarios pestana="roles">
            <Boton @click="modalCrear = true">+ Crear rol</Boton>
        </EncabezadoUsuarios>

        <div class="grid items-start gap-5 lg:grid-cols-[20rem_1fr]">
            <nav
                aria-label="Roles"
                class="rounded-xl border border-linea bg-white p-3"
            >
                <p class="px-3 pt-1 pb-2 text-xs text-tinta-suave">Roles</p>
                <ul class="flex flex-col gap-1">
                    <li v-for="rol in roles" :key="rol.id">
                        <button
                            type="button"
                            :aria-current="
                                rol.id === elegidoId ? 'true' : undefined
                            "
                            :class="
                                cn(
                                    'flex w-full items-start justify-between gap-3 rounded-lg px-3 py-2.5 text-left',
                                    rol.id === elegidoId
                                        ? 'bg-marca-suave'
                                        : 'hover:bg-lienzo',
                                )
                            "
                            @click="elegir(rol)"
                        >
                            <span>
                                <span
                                    :class="
                                        cn(
                                            'block text-sm',
                                            rol.id === elegidoId &&
                                                'font-medium text-marca',
                                        )
                                    "
                                >
                                    {{ rol.nombre }}
                                </span>
                                <span class="block text-xs text-tinta-suave">
                                    {{ rol.resumen }} ·
                                    {{ rol.cuentas_total }}
                                    {{
                                        rol.cuentas_total === 1
                                            ? 'cuenta'
                                            : 'cuentas'
                                    }}
                                </span>
                            </span>
                            <Etiqueta v-if="rol.del_sistema"
                                >Del sistema</Etiqueta
                            >
                            <Etiqueta v-else-if="!rol.activo" tono="alerta">
                                Inactivo
                            </Etiqueta>
                        </button>
                    </li>
                </ul>
                <p class="px-3 pt-3 pb-1 text-xs text-tinta-suave">
                    Administrador, Empresa y Colaborador son roles del sistema:
                    no se eliminan ni cambian sus permisos.
                </p>
            </nav>

            <PanelRol
                v-if="elegido"
                :rol="elegido"
                :bloques="bloques"
                @asignar="modalAsignar = true"
                @eliminar="modalEliminar = true"
                @invitar="modalInvitar = true"
                @cambiar-rol="cambiarRol"
            />
        </div>
    </div>

    <ModalCrearRol
        v-model:abierto="modalCrear"
        :bloques="bloques"
        :cuentas="
            cuentas.filter(
                (c) =>
                    !c.es_tuya &&
                    c.estado !== 'invitacion' &&
                    c.estado !== 'eliminada',
            )
        "
    />
    <template v-if="elegido">
        <ModalAsignarRol
            v-model:abierto="modalAsignar"
            :rol="elegido"
            :cuentas="cuentasSinEsteRol"
        />
        <ModalEliminarRol v-model:abierto="modalEliminar" :rol="elegido" />
    </template>
    <ModalInvitarUsuario
        v-model:abierto="modalInvitar"
        :roles="rolesInternos"
    />
    <ModalCambiarRol
        v-if="cuentaACambiar"
        v-model:abierto="modalCambiarRol"
        :cuenta="cuentaACambiar"
        :roles="roles"
    />
</template>
