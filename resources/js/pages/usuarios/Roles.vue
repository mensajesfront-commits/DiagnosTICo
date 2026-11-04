<script setup lang="ts">
/**
 * A5.1 · Usuarios y roles, pestaña Roles (HU-048 a HU-052).
 *
 * A la izquierda la lista de roles: los del sistema (Administrador, Empresa,
 * Colaborador) con la etiqueta "Del sistema", y los creados, con "Inactivo"
 * si no se pueden asignar. A la derecha el detalle del rol elegido
 * (PanelRol). "+ Crear rol" abre A5.2.
 *
 * Diseño: cada rol con su ícono y color (lib/roles), el elegido resaltado en
 * azul, el número de cuentas en una píldora y "Del sistema" con candado.
 */
import { Head } from '@inertiajs/vue3';
import { Lock, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EncabezadoUsuarios from '@/components/usuarios/EncabezadoUsuarios.vue';
import ModalAsignarRol from '@/components/usuarios/ModalAsignarRol.vue';
import ModalCambiarRol from '@/components/usuarios/ModalCambiarRol.vue';
import ModalCrearRol from '@/components/usuarios/ModalCrearRol.vue';
import ModalEliminarRol from '@/components/usuarios/ModalEliminarRol.vue';
import ModalInvitarUsuario from '@/components/usuarios/ModalInvitarUsuario.vue';
import PanelRol from '@/components/usuarios/PanelRol.vue';
import { usePermisos } from '@/composables/usePermisos';
import { estiloRol } from '@/lib/roles';
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
        /** Para elegir las empresas de un Consultor al invitarlo (HU-085). */
        empresas?: { id: number; nombre: string }[];
    }>(),
    { rolId: null, empresas: () => [] },
);

const { puede } = usePermisos();

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

    <div class="flex flex-col gap-5 p-6 lg:h-dvh lg:overflow-hidden">
        <EncabezadoUsuarios class="shrink-0" pestana="roles">
            <Boton
                v-if="puede('usuarios.gestionar')"
                class="gap-1.5"
                @click="modalCrear = true"
            >
                <Plus class="size-4" aria-hidden="true" />
                Crear rol
            </Boton>
        </EncabezadoUsuarios>

        <!-- Página fija: la lista de roles y el panel bajan cada uno en su espacio. -->
        <div
            class="grid items-start gap-5 lg:min-h-0 lg:flex-1 lg:grid-cols-[20rem_1fr] lg:grid-rows-[minmax(0,1fr)]"
        >
            <nav
                aria-label="Roles"
                class="rounded-2xl border border-linea bg-white p-3 lg:max-h-full lg:overflow-y-auto"
            >
                <p
                    class="flex items-center justify-between px-3 pt-1 pb-2 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                >
                    Roles
                    <span class="font-mono font-normal normal-case">
                        {{ roles.length }}
                    </span>
                </p>
                <ul class="flex flex-col gap-1">
                    <li v-for="rol in roles" :key="rol.id">
                        <button
                            type="button"
                            :aria-current="
                                rol.id === elegidoId ? 'true' : undefined
                            "
                            :class="
                                cn(
                                    'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition-colors',
                                    rol.id === elegidoId
                                        ? 'bg-marca text-white'
                                        : 'hover:bg-lienzo',
                                    // Gris sin bajar el contraste del texto (ERR-11).
                                    !rol.activo &&
                                        rol.id !== elegidoId &&
                                        'grayscale',
                                )
                            "
                            @click="elegir(rol)"
                        >
                            <span
                                :class="
                                    cn(
                                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                                        rol.id === elegidoId
                                            ? 'bg-white/15'
                                            : estiloRol(rol.nombre).suave,
                                    )
                                "
                                aria-hidden="true"
                            >
                                <component
                                    :is="estiloRol(rol.nombre).icono"
                                    class="size-4"
                                />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="flex items-center gap-1.5 text-sm font-medium"
                                >
                                    {{ rol.nombre }}
                                    <Lock
                                        v-if="rol.del_sistema"
                                        class="size-3 opacity-60"
                                        aria-label="Del sistema"
                                    />
                                </span>
                                <span
                                    :class="
                                        cn(
                                            'block truncate text-xs',
                                            rol.id === elegidoId
                                                ? 'text-white/75'
                                                : 'text-tinta-suave',
                                        )
                                    "
                                >
                                    {{ rol.resumen }}
                                </span>
                            </span>
                            <span
                                v-if="!rol.activo"
                                :class="
                                    cn(
                                        'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium',
                                        rol.id === elegidoId
                                            ? 'bg-white/20'
                                            : 'bg-alerta-suave text-alerta',
                                    )
                                "
                            >
                                Inactivo
                            </span>
                            <span
                                v-else
                                :class="
                                    cn(
                                        'min-w-6 shrink-0 rounded-full px-1.5 text-center font-mono text-[11px] font-semibold',
                                        rol.id === elegidoId
                                            ? 'bg-white/20'
                                            : 'bg-lienzo-oscuro text-tinta-suave',
                                    )
                                "
                                :title="`${rol.cuentas_total} ${rol.cuentas_total === 1 ? 'cuenta' : 'cuentas'}`"
                            >
                                {{ rol.cuentas_total }}
                            </span>
                        </button>
                    </li>
                </ul>
                <p
                    class="mx-1 mt-3 flex gap-2 rounded-xl bg-lienzo/70 px-3 py-2.5 text-xs text-tinta-suave"
                >
                    <Lock class="mt-px size-3.5 shrink-0" aria-hidden="true" />
                    Administrador, Empresa, Colaborador y Consultor son roles
                    del sistema: no se eliminan ni cambian sus permisos.
                </p>
            </nav>

            <PanelRol
                v-if="elegido"
                class="lg:max-h-full lg:overflow-y-auto"
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
        :empresas="empresas"
    />
    <ModalCambiarRol
        v-if="cuentaACambiar"
        v-model:abierto="modalCambiarRol"
        :cuenta="cuentaACambiar"
        :roles="roles"
    />
</template>
