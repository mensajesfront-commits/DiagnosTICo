<script setup lang="ts">
/**
 * A5 · Usuarios y roles, pestaña Usuarios (HU-046, HU-047, HU-052).
 *
 * Lista de cuentas con búsqueda por nombre o correo y filtros de rol y
 * estado. La búsqueda, los filtros y las páginas de 12 se hacen aquí, con
 * todas las cuentas que entrega el servidor.
 *
 * Acciones por fila:
 * - La propia: "Tu cuenta · Mi perfil" (no se desactiva ni cambia de rol).
 * - Invitación pendiente: "Reenviar invitación · Eliminar".
 * - "Ver como": solo en cuentas que no son de Administrador (respuesta del
 *   equipo, 6 oct; A5.4).
 * - "Cambiar rol": no en colaboradores (los maneja su empresa, RN-025).
 * - "Desactivar / Eliminar" (A5.3b, DEC-017): un modal para elegir;
 *   eliminar pide escribir el correo exacto. Las desactivadas tienen
 *   "Reactivar · Eliminar" y las invitaciones "Reenviar · Eliminar".
 * - No existe "restablecer contraseña" (RN-006).
 */
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import Boton from '@/components/base/Boton.vue';
import Entrada from '@/components/base/Entrada.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import EncabezadoUsuarios from '@/components/usuarios/EncabezadoUsuarios.vue';
import ModalCambiarRol from '@/components/usuarios/ModalCambiarRol.vue';
import ModalDesactivarEliminar from '@/components/usuarios/ModalDesactivarEliminar.vue';
import ModalInvitarUsuario from '@/components/usuarios/ModalInvitarUsuario.vue';
import { diaYMes, ultimoAcceso } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';
import { coincide } from '@/lib/usuarios';
import { edit as perfil } from '@/routes/profile';
import type { Cuenta, EstadoCuenta, Rol } from '@/types/usuarios';

const props = defineProps<{
    cuentas: Cuenta[];
    roles: Pick<
        Rol,
        'id' | 'nombre' | 'descripcion' | 'activo' | 'del_sistema' | 'aviso'
    >[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Usuarios y roles', href: rutas.usuarios.lista() },
        ],
    },
});

const POR_PAGINA = 12;

// "Ver todas en Usuarios" desde un rol llega con ?rol=Empresa.
const page = usePage();
const rolInicial =
    new URL(page.url, 'http://captter.local').searchParams.get('rol') ?? '';

const busqueda = ref('');
const filtroRol = ref(rolInicial);
const filtroEstado = ref<EstadoCuenta | ''>('');
const pagina = ref(1);

const estados: Record<EstadoCuenta, string> = {
    activa: 'Activa',
    desactivada: 'Desactivada',
    invitacion: 'Invitación pendiente',
    eliminada: 'Eliminadas (se pueden recuperar)',
};

const filtradas = computed(() =>
    props.cuentas.filter(
        (c) =>
            coincide(c, busqueda.value) &&
            (filtroRol.value === '' || c.rol === filtroRol.value) &&
            // Las eliminadas solo se ven con su filtro (DEC-017).
            (filtroEstado.value === ''
                ? c.estado !== 'eliminada'
                : c.estado === filtroEstado.value),
    ),
);

watch([busqueda, filtroRol, filtroEstado], () => (pagina.value = 1));

const paginas = computed(() =>
    Math.max(1, Math.ceil(filtradas.value.length / POR_PAGINA)),
);
const desde = computed(() => (pagina.value - 1) * POR_PAGINA);
const visibles = computed(() =>
    filtradas.value.slice(desde.value, desde.value + POR_PAGINA),
);

/** Roles internos para invitar: no Empresa ni Colaborador. */
const rolesInternos = computed(() =>
    props.roles.filter((r) => !['Empresa', 'Colaborador'].includes(r.nombre)),
);

const modalInvitar = ref(false);
const modalDesactivar = ref(false);
const modalCambiarRol = ref(false);
const elegida = ref<Cuenta | null>(null);

const inicioModal = ref<'elegir' | 'eliminar'>('elegir');

function desactivarOEliminar(
    cuenta: Cuenta,
    inicio: 'elegir' | 'eliminar' = 'elegir',
): void {
    elegida.value = cuenta;
    inicioModal.value = inicio;
    modalDesactivar.value = true;
}

function cambiarRol(cuenta: Cuenta): void {
    elegida.value = cuenta;
    modalCambiarRol.value = true;
}

function reactivar(cuenta: Cuenta): void {
    router.post(
        rutas.usuarios.reactivar(cuenta.id),
        {},
        { preserveScroll: true },
    );
}

function recuperar(cuenta: Cuenta): void {
    router.post(
        rutas.usuarios.recuperar(cuenta.id),
        {},
        {
            preserveScroll: true,
            onError: (errores) => {
                if (errores.recuperar) {
                    toast.error(errores.recuperar);
                }
            },
        },
    );
}

function reenviar(cuenta: Cuenta): void {
    router.post(
        rutas.usuarios.reenviarInvitacion(cuenta.id),
        {},
        { preserveScroll: true },
    );
}

function verComo(cuenta: Cuenta): void {
    router.post(rutas.usuarios.verComo(cuenta.id));
}

const enlace = 'text-marca hover:underline';
const enlaceEliminar = 'text-aviso hover:underline';
</script>

<template>
    <Head title="Usuarios y roles" />

    <div class="flex flex-col gap-5 p-6">
        <EncabezadoUsuarios pestana="usuarios">
            <Boton @click="modalInvitar = true">+ Invitar usuario</Boton>
        </EncabezadoUsuarios>

        <div class="grid gap-3 md:grid-cols-[1fr_13rem_13rem]">
            <div class="flex flex-col gap-1.5">
                <label for="buscar-cuenta" class="text-xs font-medium">
                    Buscar
                </label>
                <Entrada
                    id="buscar-cuenta"
                    v-model="busqueda"
                    type="search"
                    placeholder="Nombre o correo"
                    autocomplete="off"
                />
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="filtro-rol" class="text-xs font-medium">Rol</label>
                <Seleccion id="filtro-rol" v-model="filtroRol">
                    <option value="">Todos los roles</option>
                    <option
                        v-for="rol in roles"
                        :key="rol.id"
                        :value="rol.nombre"
                    >
                        {{ rol.nombre }}
                    </option>
                </Seleccion>
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="filtro-estado" class="text-xs font-medium">
                    Estado
                </label>
                <Seleccion id="filtro-estado" v-model="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option
                        v-for="(texto, clave) in estados"
                        :key="clave"
                        :value="clave"
                    >
                        {{ texto }}
                    </option>
                </Seleccion>
            </div>
        </div>

        <p class="-mt-2 text-xs text-tinta-suave">
            Las cuentas de empresa se crean al registrar la empresa en
            <Link :href="rutas.empresas.lista()" class="text-marca underline">
                Empresas</Link
            >; los colaboradores los crea cada empresa desde su menú. Para sumar
            a alguien del equipo de NuevasTIC, usa
            <button
                type="button"
                class="text-marca underline"
                @click="modalInvitar = true"
            >
                Invitar usuario</button
            >. Para cambiar el rol de una cuenta, usa "Cambiar rol" en su fila.
        </p>

        <section class="rounded-xl border border-linea bg-white">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-linea text-left">
                            <th
                                v-for="columna in [
                                    'Cuenta',
                                    'Rol',
                                    'Empresa asociada',
                                    'Estado',
                                    'Último acceso',
                                    'Acciones',
                                ]"
                                :key="columna"
                                scope="col"
                                class="px-5 py-3 text-xs font-normal text-tinta-suave"
                            >
                                {{ columna }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="cuenta in visibles"
                            :key="cuenta.id"
                            class="border-b border-linea last:border-b-0"
                        >
                            <td class="px-5 py-3">
                                <p class="font-medium">{{ cuenta.nombre }}</p>
                                <p class="text-xs text-tinta-suave">
                                    {{ cuenta.correo }}
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                <Etiqueta
                                    :tono="
                                        cuenta.rol === 'Administrador'
                                            ? 'marca'
                                            : 'neutro'
                                    "
                                >
                                    {{ cuenta.rol ?? 'Sin rol' }}
                                </Etiqueta>
                            </td>
                            <td class="px-5 py-3">
                                {{ cuenta.empresa ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <Etiqueta
                                    v-if="cuenta.estado === 'activa'"
                                    tono="exito"
                                >
                                    ✓ Activa
                                </Etiqueta>
                                <Etiqueta
                                    v-else-if="cuenta.estado === 'invitacion'"
                                    tono="alerta"
                                >
                                    ✉ Invitación pendiente
                                </Etiqueta>
                                <Etiqueta
                                    v-else-if="cuenta.estado === 'eliminada'"
                                    tono="aviso"
                                >
                                    ✕ Eliminada
                                </Etiqueta>
                                <Etiqueta v-else>○ Desactivada</Etiqueta>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span
                                    v-if="
                                        cuenta.estado === 'eliminada' &&
                                        cuenta.se_borra_el
                                    "
                                    class="text-aviso"
                                >
                                    Se borra el
                                    {{ diaYMes(new Date(cuenta.se_borra_el)) }}
                                </span>
                                <template
                                    v-else-if="
                                        cuenta.estado === 'invitacion' &&
                                        cuenta.invitacion_enviada_en
                                    "
                                >
                                    Enviada el
                                    {{
                                        diaYMes(
                                            new Date(
                                                cuenta.invitacion_enviada_en,
                                            ),
                                        )
                                    }}
                                </template>
                                <template v-else>
                                    {{ ultimoAcceso(cuenta.ultimo_acceso) }}
                                </template>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <template v-if="cuenta.es_tuya">
                                    Tu cuenta ·
                                    <Link :href="perfil()" :class="enlace">
                                        Mi perfil
                                    </Link>
                                </template>
                                <button
                                    v-else-if="cuenta.estado === 'eliminada'"
                                    type="button"
                                    :class="enlace"
                                    @click="recuperar(cuenta)"
                                >
                                    Recuperar
                                </button>
                                <span
                                    v-else-if="cuenta.estado === 'invitacion'"
                                    class="inline-flex flex-wrap items-center gap-x-2"
                                >
                                    <button
                                        type="button"
                                        :class="enlace"
                                        @click="reenviar(cuenta)"
                                    >
                                        Reenviar invitación
                                    </button>
                                    <span class="text-tinta-suave">·</span>
                                    <button
                                        type="button"
                                        :class="enlaceEliminar"
                                        @click="
                                            desactivarOEliminar(
                                                cuenta,
                                                'eliminar',
                                            )
                                        "
                                    >
                                        Eliminar
                                    </button>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex flex-wrap items-center gap-x-2"
                                >
                                    <template
                                        v-if="cuenta.rol !== 'Administrador'"
                                    >
                                        <button
                                            type="button"
                                            :class="enlace"
                                            @click="verComo(cuenta)"
                                        >
                                            Ver como
                                        </button>
                                        <span class="text-tinta-suave">·</span>
                                    </template>
                                    <template
                                        v-if="cuenta.rol !== 'Colaborador'"
                                    >
                                        <button
                                            type="button"
                                            :class="enlace"
                                            @click="cambiarRol(cuenta)"
                                        >
                                            Cambiar rol
                                        </button>
                                        <span class="text-tinta-suave">·</span>
                                    </template>
                                    <button
                                        v-if="cuenta.estado === 'activa'"
                                        type="button"
                                        :class="enlace"
                                        @click="desactivarOEliminar(cuenta)"
                                    >
                                        Desactivar / Eliminar
                                    </button>
                                    <template v-else>
                                        <button
                                            type="button"
                                            :class="enlace"
                                            @click="reactivar(cuenta)"
                                        >
                                            Reactivar
                                        </button>
                                        <span class="text-tinta-suave">·</span>
                                        <button
                                            type="button"
                                            :class="enlaceEliminar"
                                            @click="
                                                desactivarOEliminar(
                                                    cuenta,
                                                    'eliminar',
                                                )
                                            "
                                        >
                                            Eliminar
                                        </button>
                                    </template>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="visibles.length === 0">
                            <td
                                colspan="6"
                                class="px-5 py-10 text-center text-sm text-tinta-suave"
                            >
                                Ninguna cuenta coincide con la búsqueda o los
                                filtros.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer
                class="flex flex-wrap items-center justify-between gap-3 border-t border-linea px-5 py-3"
            >
                <p class="text-sm text-tinta-suave">
                    <template v-if="filtradas.length > 0">
                        Mostrando {{ desde + 1 }}–{{
                            desde + visibles.length
                        }}
                        de {{ filtradas.length }}
                    </template>
                    <template v-else>Sin resultados</template>
                </p>
                <div class="flex gap-2">
                    <Boton
                        variante="secundario"
                        tamano="sm"
                        :disabled="pagina === 1"
                        @click="pagina--"
                    >
                        Anterior
                    </Boton>
                    <Boton
                        variante="secundario"
                        tamano="sm"
                        :disabled="pagina >= paginas"
                        @click="pagina++"
                    >
                        Siguiente
                    </Boton>
                </div>
            </footer>
        </section>
    </div>

    <ModalInvitarUsuario
        v-model:abierto="modalInvitar"
        :roles="rolesInternos"
    />
    <ModalDesactivarEliminar
        v-if="elegida"
        v-model:abierto="modalDesactivar"
        :cuenta="elegida"
        :inicio="inicioModal"
    />
    <ModalCambiarRol
        v-if="elegida"
        v-model:abierto="modalCambiarRol"
        :cuenta="elegida"
        :roles="roles"
    />
</template>
