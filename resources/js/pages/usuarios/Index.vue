<script setup lang="ts">
/**
 * A5 · Usuarios y roles, pestaña Usuarios (HU-046, HU-047, HU-052).
 *
 * Lista de cuentas con búsqueda por nombre o correo y filtros de rol y
 * estado. La búsqueda, los filtros y el paginado se hacen aquí, con todas
 * las cuentas que entrega el servidor. La página queda fija sin importar
 * cuántas cuentas haya: cada página muestra las filas que caben.
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
 *
 * Diseño: accesos rápidos por estado con su conteo (como A3), búsqueda con
 * lupa, cada cuenta con un círculo de iniciales del color de su rol, el rol
 * en una píldora, la empresa con ícono, el estado con un punto y las
 * acciones como botones con ícono.
 */
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Building2,
    Eye,
    Plus,
    RotateCcw,
    Search,
    Send,
    ShieldCheck,
    Trash2,
    UserX,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import Boton from '@/components/base/Boton.vue';
import Entrada from '@/components/base/Entrada.vue';
import Paginacion from '@/components/base/Paginacion.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import EncabezadoUsuarios from '@/components/usuarios/EncabezadoUsuarios.vue';
import ModalCambiarRol from '@/components/usuarios/ModalCambiarRol.vue';
import ModalDesactivarEliminar from '@/components/usuarios/ModalDesactivarEliminar.vue';
import ModalInvitarUsuario from '@/components/usuarios/ModalInvitarUsuario.vue';
import { usePermisos } from '@/composables/usePermisos';
import { diaYMes } from '@/lib/fechas';
import { useFilasQueCaben, usePaginacion } from '@/lib/paginacion';
import { rutas } from '@/lib/rutas';
import { coincide } from '@/lib/usuarios';
import { cn } from '@/lib/utils';
import { edit as perfil } from '@/routes/profile';
import type { Cuenta, EstadoCuenta, Rol } from '@/types/usuarios';

const props = defineProps<{
    cuentas: Cuenta[];
    roles: Pick<
        Rol,
        'id' | 'nombre' | 'descripcion' | 'activo' | 'del_sistema' | 'aviso'
    >[];
    /** Para elegir las empresas de un Consultor al invitarlo (HU-085). */
    empresas: { id: number; nombre: string }[];
}>();

const { puede } = usePermisos();

// "Ver todas en Usuarios" desde un rol llega con ?rol=Empresa.
const page = usePage();
const rolInicial =
    new URL(page.url, 'http://captter.local').searchParams.get('rol') ?? '';

const busqueda = ref('');
const filtroRol = ref(rolInicial);
const filtroEstado = ref<EstadoCuenta | ''>('');

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

// --- Paginado con las filas que caben -------------------------------------------
const contenedor = ref<HTMLElement | null>(null);
const { porPagina, medir, ajustar } = useFilasQueCaben(contenedor);
const { pagina, paginas, total, desde, visibles } = usePaginacion(
    filtradas,
    porPagina,
);

watch(
    () => filtradas.value.length > 0,
    () => medir(),
);
watch(visibles, ajustar);

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

const accion =
    'inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-medium text-tinta-suave transition hover:bg-lienzo hover:text-marca';
const accionPeligro =
    'inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-medium text-tinta-suave transition hover:bg-aviso-suave hover:text-aviso';

/** Accesos rápidos por estado, con su conteo. */
const rapidos = computed(() => {
    const c = props.cuentas;
    const n = (e: EstadoCuenta) => c.filter((x) => x.estado === e).length;

    return [
        {
            clave: '' as const,
            texto: 'Todas',
            n: c.filter((x) => x.estado !== 'eliminada').length,
            tono: null,
        },
        {
            clave: 'activa' as const,
            texto: 'Activas',
            n: n('activa'),
            tono: 'bg-exito',
        },
        {
            clave: 'invitacion' as const,
            texto: 'Invitaciones',
            n: n('invitacion'),
            tono: 'bg-alerta',
        },
        {
            clave: 'desactivada' as const,
            texto: 'Desactivadas',
            n: n('desactivada'),
            tono: 'bg-nivel-sin',
        },
        {
            clave: 'eliminada' as const,
            texto: 'Eliminadas',
            n: n('eliminada'),
            tono: 'bg-aviso',
        },
    ];
});

const iniciales = (nombre: string) =>
    nombre
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase())
        .join('');

/** Color del círculo y de la píldora según el rol. */
function tonoRol(rol: string | null): { avatar: string; pildora: string } {
    if (rol === 'Administrador') {
        return {
            avatar: 'bg-marca text-white',
            pildora: 'bg-marca-suave text-marca',
        };
    }

    if (rol === 'Empresa') {
        return {
            avatar: 'bg-exito-suave text-exito',
            pildora: 'bg-exito-suave text-exito',
        };
    }

    if (rol === 'Colaborador') {
        return {
            avatar: 'bg-nivel-camino-suave text-nivel-camino-texto',
            pildora: 'bg-nivel-camino-suave text-nivel-camino-texto',
        };
    }

    return {
        avatar: 'bg-lienzo-oscuro text-tinta',
        pildora: 'bg-lienzo-oscuro text-tinta',
    };
}

const estadoCuenta: Record<EstadoCuenta, { texto: string; punto: string }> = {
    activa: { texto: 'Activa', punto: 'bg-exito' },
    invitacion: { texto: 'Invitación pendiente', punto: 'bg-alerta' },
    desactivada: { texto: 'Desactivada', punto: 'bg-nivel-sin' },
    eliminada: { texto: 'Eliminada', punto: 'bg-aviso' },
};
</script>

<template>
    <Head title="Usuarios" />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
        <EncabezadoUsuarios class="shrink-0" pestana="usuarios">
            <Boton
                v-if="puede('usuarios.gestionar')"
                class="gap-1.5"
                @click="modalInvitar = true"
            >
                <Plus class="size-4" aria-hidden="true" />
                Invitar usuario
            </Boton>
        </EncabezadoUsuarios>

        <div
            class="flex shrink-0 flex-wrap gap-2"
            role="group"
            aria-label="Filtrar por estado"
        >
            <button
                v-for="r in rapidos"
                :key="r.clave || 'todas'"
                type="button"
                :aria-pressed="filtroEstado === r.clave"
                :class="
                    cn(
                        'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm transition',
                        filtroEstado === r.clave
                            ? 'border-marca bg-marca text-white'
                            : 'border-linea bg-white hover:border-marca/40',
                    )
                "
                @click="filtroEstado = r.clave"
            >
                <span
                    v-if="r.tono"
                    :class="cn('size-2 rounded-full', r.tono)"
                    aria-hidden="true"
                />
                {{ r.texto }}
                <span
                    :class="
                        cn(
                            'rounded-full px-1.5 font-mono text-xs font-semibold',
                            filtroEstado === r.clave
                                ? 'bg-white/20'
                                : 'bg-lienzo-oscuro',
                        )
                    "
                >
                    {{ r.n }}
                </span>
            </button>
        </div>

        <section
            class="flex flex-col rounded-2xl border border-linea bg-white lg:min-h-0 lg:flex-1"
        >
            <div class="flex shrink-0 flex-wrap items-end gap-3 px-5 pt-4 pb-3">
                <div class="relative min-w-60 flex-1">
                    <label for="buscar-cuenta" class="sr-only">Buscar</label>
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-tinta-suave"
                        aria-hidden="true"
                    />
                    <Entrada
                        id="buscar-cuenta"
                        v-model="busqueda"
                        type="search"
                        placeholder="Buscar por nombre o correo"
                        autocomplete="off"
                        class="h-9 pl-9"
                    />
                </div>
                <div class="w-full sm:w-52">
                    <label for="filtro-rol" class="sr-only">Rol</label>
                    <Seleccion
                        id="filtro-rol"
                        v-model="filtroRol"
                        class="h-9 text-sm"
                    >
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
                <p
                    v-if="puede('usuarios.gestionar')"
                    class="w-full text-xs text-tinta-suave"
                >
                    Las cuentas de empresa se crean al registrar la empresa en
                    <Link
                        :href="rutas.empresas.lista()"
                        class="text-marca underline"
                    >
                        Empresas</Link
                    >; los colaboradores los crea cada empresa desde su menú.
                    Para sumar a alguien del equipo de NuevasTIC, usa
                    <button
                        type="button"
                        class="text-marca underline"
                        @click="modalInvitar = true"
                    >
                        Invitar usuario</button
                    >.
                </p>
            </div>

            <div
                ref="contenedor"
                class="min-h-0 flex-1 overflow-x-auto overflow-y-hidden"
            >
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr
                            class="border-y border-linea bg-lienzo/60 text-left"
                        >
                            <th
                                v-for="columna in [
                                    'Cuenta',
                                    'Rol',
                                    'Empresa',
                                    'Estado',
                                    'Acciones',
                                ]"
                                :key="columna"
                                scope="col"
                                class="px-5 py-2.5 text-xs font-medium text-tinta-suave"
                            >
                                {{ columna }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="cuenta in visibles"
                            :key="cuenta.id"
                            :class="
                                cn(
                                    'border-b border-linea transition-colors last:border-b-0 hover:bg-lienzo/40',
                                    cuenta.estado !== 'activa' &&
                                        cuenta.estado !== 'invitacion' &&
                                        'text-tinta-suave',
                                )
                            "
                        >
                            <td class="px-5 py-3">
                                <div class="flex min-w-56 items-center gap-3">
                                    <span
                                        :class="
                                            cn(
                                                'flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                                tonoRol(cuenta.rol).avatar,
                                                // Gris sin bajar el contraste del texto (ERR-11).
                                                cuenta.estado !== 'activa' &&
                                                    'grayscale',
                                            )
                                        "
                                        aria-hidden="true"
                                    >
                                        {{ iniciales(cuenta.nombre) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-tinta">
                                            {{ cuenta.nombre }}
                                            <span
                                                v-if="cuenta.es_tuya"
                                                class="ml-1 rounded-full bg-marca-suave px-1.5 py-0.5 text-[10px] font-medium text-marca"
                                                >Tú</span
                                            >
                                        </p>
                                        <p
                                            v-if="cuenta.es_admin_principal"
                                            class="text-xs font-medium text-marca"
                                        >
                                            Administrador principal
                                        </p>
                                        <p class="text-xs text-tinta-suave">
                                            {{ cuenta.correo }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    :class="
                                        cn(
                                            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
                                            tonoRol(cuenta.rol).pildora,
                                        )
                                    "
                                >
                                    <ShieldCheck
                                        v-if="cuenta.rol === 'Administrador'"
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    {{ cuenta.rol ?? 'Sin rol' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    v-if="cuenta.empresa"
                                    class="inline-flex items-center gap-1.5"
                                >
                                    <Building2
                                        class="size-3.5 text-tinta-suave"
                                        aria-hidden="true"
                                    />
                                    {{ cuenta.empresa }}
                                </span>
                                <span v-else class="text-xs text-tinta-suave">
                                    Equipo NuevasTIC
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs font-medium whitespace-nowrap"
                                >
                                    <span
                                        :class="
                                            cn(
                                                'size-2 rounded-full',
                                                estadoCuenta[cuenta.estado]
                                                    .punto,
                                            )
                                        "
                                        aria-hidden="true"
                                    />
                                    {{ estadoCuenta[cuenta.estado].texto }}
                                </span>
                                <p
                                    v-if="cuenta.rol_inactivo"
                                    class="mt-1 text-xs text-aviso"
                                >
                                    Rol inactivo: no puede entrar
                                </p>
                                <p
                                    v-if="
                                        cuenta.estado === 'eliminada' &&
                                        cuenta.se_borra_el
                                    "
                                    class="mt-1 text-xs whitespace-nowrap text-aviso"
                                >
                                    Se borra el
                                    {{ diaYMes(new Date(cuenta.se_borra_el)) }}
                                </p>
                                <p
                                    v-else-if="
                                        cuenta.estado === 'invitacion' &&
                                        cuenta.invitacion_enviada_en
                                    "
                                    class="mt-1 text-xs whitespace-nowrap text-tinta-suave"
                                >
                                    Enviada el
                                    {{
                                        diaYMes(
                                            new Date(
                                                cuenta.invitacion_enviada_en,
                                            ),
                                        )
                                    }}
                                </p>
                            </td>
                            <td class="px-5 py-2 whitespace-nowrap">
                                <Link
                                    v-if="cuenta.es_tuya"
                                    :href="perfil()"
                                    :class="accion"
                                >
                                    Ir a Mi perfil
                                </Link>
                                <span
                                    v-else-if="!puede('usuarios.gestionar')"
                                    class="text-xs text-tinta-suave"
                                >
                                    Solo lectura
                                </span>
                                <!-- Nadie le quita el rol, la desactiva ni la elimina. -->
                                <span
                                    v-else-if="cuenta.es_admin_principal"
                                    class="text-xs text-tinta-suave"
                                >
                                    Cuenta principal: no se modifica
                                </span>
                                <button
                                    v-else-if="cuenta.estado === 'eliminada'"
                                    type="button"
                                    :class="accion"
                                    @click="recuperar(cuenta)"
                                >
                                    <RotateCcw
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    Recuperar
                                </button>
                                <span
                                    v-else-if="cuenta.estado === 'invitacion'"
                                    class="inline-flex flex-wrap items-center gap-0.5"
                                >
                                    <button
                                        type="button"
                                        :class="accion"
                                        @click="reenviar(cuenta)"
                                    >
                                        <Send
                                            class="size-3.5"
                                            aria-hidden="true"
                                        />
                                        Reenviar invitación
                                    </button>
                                    <button
                                        type="button"
                                        :class="accionPeligro"
                                        @click="
                                            desactivarOEliminar(
                                                cuenta,
                                                'eliminar',
                                            )
                                        "
                                    >
                                        <Trash2
                                            class="size-3.5"
                                            aria-hidden="true"
                                        />
                                        Eliminar
                                    </button>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex flex-wrap items-center gap-0.5"
                                >
                                    <button
                                        v-if="cuenta.rol !== 'Administrador'"
                                        type="button"
                                        :class="accion"
                                        @click="verComo(cuenta)"
                                    >
                                        <Eye
                                            class="size-3.5"
                                            aria-hidden="true"
                                        />
                                        Ver como
                                    </button>
                                    <button
                                        v-if="cuenta.rol !== 'Colaborador'"
                                        type="button"
                                        :class="accion"
                                        @click="cambiarRol(cuenta)"
                                    >
                                        <ShieldCheck
                                            class="size-3.5"
                                            aria-hidden="true"
                                        />
                                        Cambiar rol
                                    </button>
                                    <button
                                        v-if="cuenta.estado === 'activa'"
                                        type="button"
                                        :class="accionPeligro"
                                        @click="desactivarOEliminar(cuenta)"
                                    >
                                        <UserX
                                            class="size-3.5"
                                            aria-hidden="true"
                                        />
                                        Desactivar / Eliminar
                                    </button>
                                    <template v-else>
                                        <button
                                            type="button"
                                            :class="accion"
                                            @click="reactivar(cuenta)"
                                        >
                                            <RotateCcw
                                                class="size-3.5"
                                                aria-hidden="true"
                                            />
                                            Reactivar
                                        </button>
                                        <button
                                            type="button"
                                            :class="accionPeligro"
                                            @click="
                                                desactivarOEliminar(
                                                    cuenta,
                                                    'eliminar',
                                                )
                                            "
                                        >
                                            <Trash2
                                                class="size-3.5"
                                                aria-hidden="true"
                                            />
                                            Eliminar
                                        </button>
                                    </template>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="visibles.length === 0">
                            <td
                                colspan="5"
                                class="px-5 py-10 text-center text-sm text-tinta-suave"
                            >
                                <Search
                                    class="mx-auto mb-2 size-6"
                                    aria-hidden="true"
                                />
                                Ninguna cuenta coincide con la búsqueda o los
                                filtros.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Paginacion
                v-model:pagina="pagina"
                class="shrink-0"
                :desde="desde"
                :cantidad="visibles.length"
                :total="total"
                :paginas="paginas"
            />
        </section>
    </div>

    <ModalInvitarUsuario
        v-model:abierto="modalInvitar"
        :roles="rolesInternos"
        :empresas="empresas"
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
