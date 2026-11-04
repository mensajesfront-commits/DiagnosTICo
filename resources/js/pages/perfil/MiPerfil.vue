<script setup lang="ts">
/**
 * A6 · Mi perfil del Administrador y E11 · Mi perfil de la empresa.
 *
 * Una sola pantalla para todas las cuentas:
 * - Cuentas internas: datos personales con ciudad, país, zona horaria e
 *   idioma, el rol bloqueado, avisos por correo y "Cambiar contraseña" en
 *   línea (A6).
 * - Empresa y Colaborador: datos personales, "Información de empresa" (solo
 *   la cuenta principal la cambia; el sector, solo NuevasTIC), preferencias
 *   y "Cambiar contraseña" en un modal (E11).
 * - Sin "Editar su perfil" (el colaborador) la pantalla queda de solo
 *   lectura, y sin "Cambiar su contraseña" no aparece ese botón: los cambia
 *   la cuenta principal desde Colaboradores (E12.3).
 *
 * Diseño: tarjeta de identidad con franja de marca, chips de rol y sector;
 * tarjetas de Seguridad, Sesión y Cuenta principal con ícono; secciones del
 * formulario con encabezado e ícono; la barra de guardar queda fija abajo
 * solo cuando hay cambios sin guardar.
 *
 * Envía a PATCH /mi-perfil: name, email, cargo, telefono, pais,
 * departamento, ciudad,
 * zona_horaria, idioma, avisos{} y, si edita la empresa, empresa{nombre,
 * actividad_economica_id, descripcion, pais, departamento, ciudad,
 * sitio_web, numero_empleados}.
 */
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Bell,
    Building2,
    ChevronRight,
    Crown,
    ImageUp,
    KeyRound,
    Lock,
    LogOut,
    MonitorCheck,
    ShieldCheck,
    Trash2,
    UserRound,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ModalVerificarCorreo from '@/components/auth/ModalVerificarCorreo.vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import CampoTelefono from '@/components/base/CampoTelefono.vue';
import Entrada from '@/components/base/Entrada.vue';
import Interruptor from '@/components/base/Interruptor.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import FormularioContrasena from '@/components/perfil/FormularioContrasena.vue';
import ModalEliminarMiCuenta from '@/components/perfil/ModalEliminarMiCuenta.vue';
import { usePermisos } from '@/composables/usePermisos';
import SelectorUbicacion from '@/components/ubicacion/SelectorUbicacion.vue';
import { haceCuanto, mesYAnio, momento } from '@/lib/fechas';
import { estiloRol } from '@/lib/roles';
import { rutas } from '@/lib/rutas';
import { iniciales } from '@/lib/usuarios';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { foto as subirFoto } from '@/routes/perfil';
import { edit as perfil, update as guardarPerfil } from '@/routes/profile';
import type { Pais } from '@/types/ubicaciones';

type Usuario = {
    name: string;
    email: string;
    cargo: string | null;
    telefono: string | null;
    ciudad: string | null;
    departamento: string | null;
    pais: string | null;
    zona_horaria: string;
    idioma: string;
    avisos: Record<string, boolean>;
    foto_url: string | null;
    ultimo_acceso_en: string | null;
    creado_en: string | null;
    contrasena_actualizada_en: string | null;
    /** Correo nuevo que espera el código (DEC-020). */
    email_pendiente: string | null;
    /** Teléfono nuevo que espera el código al correo. */
    telefono_pendiente: string | null;
};

type Empresa = {
    nombre: string;
    sector: string | null;
    /** Subsector: actividad económica CIIU del sector (DEC-015). */
    actividad_economica_id: number | null;
    descripcion: string | null;
    ciudad: string;
    departamento: string | null;
    pais: string;
    sitio_web: string | null;
    numero_empleados: string | null;
};

const props = defineProps<{
    /** Aviso del servidor ("Te enviamos un código nuevo"). */
    status?: string;
    usuario: Usuario;
    rol: string | null;
    empresa: Empresa | null;
    /** Consultor: las empresas que acompaña (RN-030); null en otros roles. */
    acompanadas: { id: number; nombre: string }[] | null;
    /** Solo la cuenta principal de la empresa cambia sus datos (RN-025). */
    editaEmpresa: boolean;
    /**
     * Cuenta principal del Administrador: puede eliminarse a sí misma,
     * eligiendo a otro Administrador activo (sucesores) en su lugar.
     */
    principal: {
        sucesores: { id: number; nombre: string; correo: string }[];
    } | null;
    opciones: {
        /** Los 18 países de Hispanoamérica (DEC-016). */
        paises: Pais[];
        zonas: Record<string, string>;
        idiomas: Record<string, string>;
        empleados: string[];
        /** Actividades CIIU del sector de la empresa (vacío en A6). */
        actividades: { id: number; codigo: string; nombre: string }[];
        /** Clave del aviso → texto. */
        avisos: Record<string, string>;
    };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Mi perfil', href: perfil() }] },
});

const esEmpresa = computed(() => props.empresa !== null);
const estilo = computed(() => estiloRol(props.rol));

const { puede } = usePermisos();
const editaPerfil = computed(() => puede('perfil.editar'));
const cambiaContrasena = computed(() => puede('contrasena.cambiar'));
const esColaborador = computed(() => props.rol === 'Colaborador');
const esConsultor = computed(() => props.rol === 'Consultor');

/** Cuántas empresas se listan antes de "Ver todas". */
const ACOMPANADAS_VISIBLES = 5;

const datosIniciales = () => ({
    name: props.usuario.name,
    email: props.usuario.email,
    cargo: props.usuario.cargo ?? '',
    telefono: props.usuario.telefono ?? '',
    pais: props.usuario.pais ?? '',
    departamento: props.usuario.departamento ?? '',
    ciudad: props.usuario.ciudad ?? '',
    zona_horaria: props.usuario.zona_horaria,
    idioma: props.usuario.idioma,
    avisos: { ...props.usuario.avisos },
    empresa: {
        nombre: props.empresa?.nombre ?? '',
        actividad_economica_id: (props.empresa?.actividad_economica_id ??
            '') as number | '',
        descripcion: props.empresa?.descripcion ?? '',
        pais: props.empresa?.pais ?? '',
        departamento: props.empresa?.departamento ?? '',
        ciudad: props.empresa?.ciudad ?? '',
        sitio_web: props.empresa?.sitio_web ?? '',
        numero_empleados: props.empresa?.numero_empleados ?? '',
    },
});

const form = useForm(datosIniciales());

// Al guardar, el servidor devuelve los datos nuevos: pasan a ser los
// "sin cambios" del formulario.
// El correo del formulario vuelve al guardado (al cancelar o confirmar el cambio).
watch(
    () => [props.usuario.email, props.usuario.email_pendiente] as const,
    ([correo, pendiente]) => {
        if (pendiente === null) {
            form.email = correo;
        }
    },
);

// Igual con el teléfono: vuelve al guardado al cancelar o confirmar.
watch(
    () => [props.usuario.telefono, props.usuario.telefono_pendiente] as const,
    ([telefono, pendiente]) => {
        if (pendiente === null) {
            form.telefono = telefono ?? '';
        }
    },
);

const cambiaTelefono = computed(
    () =>
        form.telefono.trim() !== '' &&
        form.telefono.trim() !== (props.usuario.telefono ?? ''),
);

watch(
    () => props.usuario,
    () => {
        form.defaults(datosIniciales());
        form.reset();
    },
);

function guardar(): void {
    form.transform((datos) => ({
        ...datos,
        empresa: props.editaEmpresa
            ? {
                  ...datos.empresa,
                  actividad_economica_id:
                      datos.empresa.actividad_economica_id || null,
              }
            : undefined,
    })).patch(guardarPerfil().url, { preserveScroll: true });
}

const nombreDelCirculo = computed(() =>
    esEmpresa.value && props.editaEmpresa
        ? (props.empresa?.nombre ?? '')
        : props.usuario.name,
);

// Foto (A6) o logo de la empresa (E11).
const archivo = ref<HTMLInputElement | null>(null);
const subiendo = ref(false);
const errorFoto = ref<string | null>(null);

function elegirFoto(evento: Event): void {
    const elegido = (evento.target as HTMLInputElement).files?.[0];

    if (!elegido) {
        return;
    }

    errorFoto.value = null;
    router.post(
        subirFoto().url,
        { foto: elegido },
        {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => (subiendo.value = true),
            onFinish: () => {
                subiendo.value = false;

                if (archivo.value) {
                    archivo.value.value = '';
                }
            },
            onError: (errores) => (errorFoto.value = errores.foto ?? null),
        },
    );
}

const modalContrasena = ref(false);
const modalEliminar = ref(false);

function cerrarSesion(): void {
    router.flushAll();
}
</script>

<template>
    <Head title="Mi perfil" />

    <div class="flex flex-col gap-5 p-4 sm:p-6">
        <header class="flex flex-col gap-3">
            <div class="flex items-start gap-3">
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                    aria-hidden="true"
                >
                    <UserRound class="size-5" />
                </span>
                <div>
                    <h1 class="text-2xl font-semibold">Mi perfil</h1>
                    <p class="mt-1 text-sm text-tinta-suave">
                        {{
                            esEmpresa
                                ? 'Gestiona tu información personal y la información de tu empresa.'
                                : 'Gestiona tus datos y la seguridad de tu cuenta.'
                        }}
                    </p>
                </div>
            </div>
            <Recuadro v-if="!editaPerfil" tono="alerta">
                {{
                    esColaborador
                        ? 'Tus datos y tu contraseña los cambia la cuenta principal de tu empresa. Si necesitas un cambio, pídeselo.'
                        : 'Tu rol no permite cambiar los datos de tu perfil.'
                }}
            </Recuadro>
        </header>

        <div class="grid items-start gap-5 lg:grid-cols-[300px_minmax(0,1fr)]">
            <!-- Columna izquierda: identidad, seguridad y sesión -->
            <div class="flex flex-col gap-4 lg:sticky lg:top-6">
                <section
                    class="overflow-hidden rounded-2xl border border-linea bg-white text-center"
                >
                    <div
                        class="h-20 bg-linear-to-br from-marca to-marca-hover"
                        aria-hidden="true"
                    />
                    <div class="-mt-11 flex flex-col items-center px-5 pb-5">
                        <div>
                            <img
                                v-if="usuario.foto_url"
                                :src="usuario.foto_url"
                                alt=""
                                class="size-22 rounded-full object-cover ring-4 ring-white"
                            />
                            <span
                                v-else
                                class="flex size-22 items-center justify-center rounded-full bg-marca-suave text-2xl font-semibold text-marca ring-4 ring-white"
                                aria-hidden="true"
                            >
                                {{ iniciales(nombreDelCirculo) }}
                            </span>
                        </div>

                        <template v-if="esEmpresa">
                            <p class="mt-3 text-lg font-semibold">
                                {{ empresa?.nombre }}
                            </p>
                            <p class="text-sm text-tinta-suave">
                                {{ usuario.name
                                }}<template v-if="usuario.cargo">
                                    · {{ usuario.cargo }}</template
                                >
                            </p>
                        </template>
                        <template v-else>
                            <p class="mt-3 text-lg font-semibold">
                                {{ usuario.name }}
                            </p>
                            <p
                                v-if="usuario.cargo"
                                class="text-sm text-tinta-suave"
                            >
                                {{ usuario.cargo }}
                            </p>
                        </template>

                        <div
                            class="mt-3 flex flex-wrap justify-center gap-1.5 text-xs"
                        >
                            <span
                                :class="
                                    cn(
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium',
                                        estilo.suave,
                                    )
                                "
                            >
                                <component
                                    :is="estilo.icono"
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                {{ rol ?? 'Sin rol' }}
                            </span>
                            <span
                                class="rounded-full bg-lienzo px-2.5 py-1 text-tinta-suave"
                            >
                                {{
                                    esEmpresa
                                        ? (empresa?.sector ?? 'Sin sector')
                                        : esConsultor
                                          ? 'Externo'
                                          : 'NuevasTIC'
                                }}
                            </span>
                        </div>

                        <template v-if="editaPerfil">
                            <input
                                ref="archivo"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                class="sr-only"
                                aria-label="Elegir imagen"
                                @change="elegirFoto"
                            />
                            <Boton
                                variante="secundario"
                                tamano="sm"
                                class="mt-4"
                                :disabled="subiendo"
                                @click="archivo?.click()"
                            >
                                <ImageUp class="size-3.5" aria-hidden="true" />
                                {{
                                    subiendo
                                        ? 'Subiendo…'
                                        : editaEmpresa
                                          ? 'Cambiar logo'
                                          : 'Cambiar foto'
                                }}
                            </Boton>
                            <p
                                v-if="errorFoto"
                                class="mt-1.5 text-xs text-aviso"
                                role="alert"
                            >
                                {{ errorFoto }}
                            </p>
                            <p v-else class="mt-1.5 text-xs text-tinta-suave">
                                JPG, PNG o WebP, hasta 2 MB.
                            </p>
                        </template>
                    </div>

                    <dl
                        v-if="!esEmpresa && usuario.ultimo_acceso_en"
                        class="grid grid-cols-2 divide-x divide-linea border-t border-linea text-xs"
                    >
                        <div class="flex flex-col-reverse gap-0.5 px-3 py-3">
                            <dd class="font-medium text-tinta">
                                {{ momento(usuario.ultimo_acceso_en) }}
                            </dd>
                            <dt class="text-tinta-suave">Último acceso</dt>
                        </div>
                        <div
                            v-if="usuario.creado_en"
                            class="flex flex-col-reverse gap-0.5 px-3 py-3"
                        >
                            <dd class="font-medium text-tinta">
                                {{ mesYAnio(usuario.creado_en) }}
                            </dd>
                            <dt class="text-tinta-suave">Cuenta creada</dt>
                        </div>
                    </dl>
                </section>

                <section
                    v-if="esEmpresa"
                    class="rounded-2xl border border-linea bg-white p-5 text-sm"
                    aria-labelledby="titulo-seguridad"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                            aria-hidden="true"
                        >
                            <ShieldCheck class="size-4.5" />
                        </span>
                        <div class="min-w-0">
                            <h2 id="titulo-seguridad" class="font-semibold">
                                Seguridad
                            </h2>
                            <p class="text-xs text-tinta-suave">
                                Contraseña<template
                                    v-if="usuario.contrasena_actualizada_en"
                                >
                                    · actualizada
                                    {{
                                        haceCuanto(
                                            usuario.contrasena_actualizada_en,
                                        )
                                    }}</template
                                >
                            </p>
                        </div>
                    </div>
                    <Boton
                        v-if="cambiaContrasena"
                        variante="secundario"
                        tamano="sm"
                        class="mt-3 w-full"
                        @click="modalContrasena = true"
                    >
                        <KeyRound class="size-3.5" aria-hidden="true" />
                        Cambiar contraseña
                    </Boton>
                    <p v-else class="mt-3 text-xs text-tinta-suave">
                        {{
                            esColaborador
                                ? 'Te la cambia la cuenta principal de tu empresa.'
                                : 'Tu rol no permite cambiarla desde aquí.'
                        }}
                    </p>
                </section>

                <!-- Consultor: las empresas que acompaña -->
                <section
                    v-if="acompanadas"
                    class="rounded-2xl border border-linea bg-white p-5 text-sm"
                    aria-labelledby="titulo-acompanadas"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                            aria-hidden="true"
                        >
                            <Building2 class="size-4.5" />
                        </span>
                        <div class="min-w-0">
                            <h2 id="titulo-acompanadas" class="font-semibold">
                                Empresas que acompañas
                            </h2>
                            <p class="text-xs text-tinta-suave">
                                {{
                                    acompanadas.length === 0
                                        ? 'Todavía ninguna: te las vincula NuevasTIC.'
                                        : acompanadas.length === 1
                                          ? '1 empresa vinculada'
                                          : `${acompanadas.length} empresas vinculadas`
                                }}
                            </p>
                        </div>
                    </div>
                    <ul
                        v-if="acompanadas.length > 0"
                        class="mt-3 flex flex-col divide-y divide-linea"
                    >
                        <li
                            v-for="e in acompanadas.slice(
                                0,
                                ACOMPANADAS_VISIBLES,
                            )"
                            :key="e.id"
                        >
                            <Link
                                :href="rutas.empresas.ver(e.id)"
                                class="group flex items-center justify-between gap-2 py-2 hover:text-marca"
                            >
                                <span class="truncate">{{ e.nombre }}</span>
                                <ChevronRight
                                    class="size-4 shrink-0 text-tinta-suave group-hover:text-marca"
                                    aria-hidden="true"
                                />
                            </Link>
                        </li>
                    </ul>
                    <Link
                        v-if="acompanadas.length > ACOMPANADAS_VISIBLES"
                        href="/empresas"
                        class="mt-2 inline-flex text-xs font-medium text-marca hover:text-marca-hover"
                    >
                        Ver las {{ acompanadas.length }} en Mis empresas
                    </Link>
                </section>

                <section
                    class="rounded-2xl border border-linea bg-white p-5 text-sm"
                    aria-labelledby="titulo-sesion"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-exito-suave text-exito"
                            aria-hidden="true"
                        >
                            <MonitorCheck class="size-4.5" />
                        </span>
                        <div class="min-w-0">
                            <h2 id="titulo-sesion" class="font-semibold">
                                Sesión activa
                            </h2>
                            <p class="text-xs break-all text-tinta-suave">
                                {{ usuario.email }}
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="logout()"
                        as="button"
                        class="mt-3 inline-flex h-8 w-full items-center justify-center gap-2 rounded-md border border-tinta/25 bg-white px-3 text-xs font-medium hover:bg-lienzo"
                        @click="cerrarSesion"
                    >
                        <LogOut class="size-3.5" aria-hidden="true" />
                        Cerrar sesión
                    </Link>
                </section>

                <!-- Solo la cuenta principal del Administrador se elimina a sí misma. -->
                <section
                    v-if="principal"
                    class="rounded-2xl border border-aviso/30 bg-white p-5 text-sm"
                    aria-labelledby="titulo-principal"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-aviso-suave text-aviso"
                            aria-hidden="true"
                        >
                            <Crown class="size-4.5" />
                        </span>
                        <div class="min-w-0">
                            <h2 id="titulo-principal" class="font-semibold">
                                Cuenta principal
                            </h2>
                            <p class="mt-0.5 text-xs text-tinta-suave">
                                Ningún otro Administrador puede desactivar ni
                                eliminar esta cuenta. Solo tú puedes eliminarla,
                                eligiendo quién queda en tu lugar.
                            </p>
                        </div>
                    </div>
                    <button
                        v-if="principal.sucesores.length > 0"
                        type="button"
                        class="mt-3 inline-flex h-8 w-full items-center justify-center gap-2 rounded-md border border-aviso/40 bg-white px-3 text-xs font-medium text-aviso hover:bg-aviso-suave focus-visible:ring-2 focus-visible:ring-aviso/40 focus-visible:outline-none"
                        @click="modalEliminar = true"
                    >
                        <Trash2 class="size-3.5" aria-hidden="true" />
                        Eliminar mi cuenta
                    </button>
                    <p v-else class="mt-3 text-xs text-tinta-suave">
                        Para eliminarla, primero invita a otro Administrador y
                        espera a que cree su contraseña.
                    </p>
                </section>
            </div>

            <!-- Columna derecha: datos y contraseña -->
            <div class="flex flex-col gap-5">
                <form
                    class="rounded-2xl border border-linea bg-white"
                    @submit.prevent="editaPerfil && guardar()"
                >
                    <!-- Solo lectura: los campos se ven bloqueados. -->
                    <fieldset
                        :disabled="!editaPerfil"
                        class="contents [&_button:disabled]:opacity-60 [&_input:disabled]:bg-lienzo-oscuro [&_select:disabled]:bg-lienzo-oscuro"
                    >
                        <section class="flex flex-col gap-5 p-4 sm:p-6">
                            <div class="flex items-start gap-3">
                                <span
                                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                                    aria-hidden="true"
                                >
                                    <UserRound class="size-4.5" />
                                </span>
                                <div>
                                    <h2 class="text-base font-semibold">
                                        Información personal
                                    </h2>
                                    <p class="text-xs text-tinta-suave">
                                        Cómo te ven los demás y cómo te
                                        contactamos.
                                    </p>
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <Campo
                                    obligatorio
                                    :etiqueta="
                                        esEmpresa
                                            ? 'Nombre de usuario'
                                            : 'Nombre completo'
                                    "
                                    para="perfil-nombre"
                                    :error="form.errors.name"
                                >
                                    <Entrada
                                        id="perfil-nombre"
                                        v-model="form.name"
                                        required
                                        autocomplete="name"
                                        :invalida="!!form.errors.name"
                                    />
                                </Campo>
                                <Campo
                                    etiqueta="Cargo"
                                    para="perfil-cargo"
                                    :error="form.errors.cargo"
                                >
                                    <Entrada
                                        id="perfil-cargo"
                                        v-model="form.cargo"
                                        autocomplete="organization-title"
                                    />
                                </Campo>
                                <Campo
                                    obligatorio
                                    etiqueta="Correo"
                                    para="perfil-correo"
                                    :error="form.errors.email"
                                    ayuda="Con este correo inicias sesión."
                                >
                                    <Entrada
                                        id="perfil-correo"
                                        v-model="form.email"
                                        type="email"
                                        required
                                        autocomplete="email"
                                        :invalida="!!form.errors.email"
                                    />
                                    <p
                                        v-if="form.email !== usuario.email"
                                        class="text-xs text-tinta-suave"
                                    >
                                        Al guardar te enviaremos un código a
                                        este correo para confirmarlo.
                                    </p>
                                </Campo>
                                <Campo
                                    etiqueta="Teléfono"
                                    para="perfil-telefono"
                                    opcional
                                    :error="form.errors.telefono"
                                >
                                    <CampoTelefono
                                        id="perfil-telefono"
                                        v-model="form.telefono"
                                        :pais="form.pais || form.empresa?.pais"
                                        :invalida="!!form.errors.telefono"
                                    />
                                    <p
                                        v-if="cambiaTelefono"
                                        class="text-xs text-tinta-suave"
                                    >
                                        Al guardar te enviaremos un código a tu
                                        correo para confirmarlo.
                                    </p>
                                </Campo>

                                <template v-if="!esEmpresa">
                                    <SelectorUbicacion
                                        v-model:pais="form.pais"
                                        v-model:departamento="form.departamento"
                                        v-model:ciudad="form.ciudad"
                                        prefijo="perfil"
                                        :paises="opciones.paises"
                                        :requerido="false"
                                        :errores="{
                                            pais: form.errors.pais,
                                            departamento:
                                                form.errors.departamento,
                                            ciudad: form.errors.ciudad,
                                        }"
                                    />
                                    <Campo
                                        etiqueta="Zona horaria"
                                        para="perfil-zona"
                                        :error="form.errors.zona_horaria"
                                    >
                                        <Seleccion
                                            id="perfil-zona"
                                            v-model="form.zona_horaria"
                                        >
                                            <option
                                                v-for="(
                                                    texto, zona
                                                ) in opciones.zonas"
                                                :key="zona"
                                                :value="zona"
                                            >
                                                {{ texto }}
                                            </option>
                                        </Seleccion>
                                    </Campo>
                                    <Campo
                                        etiqueta="Idioma"
                                        para="perfil-idioma"
                                        :error="form.errors.idioma"
                                    >
                                        <Seleccion
                                            id="perfil-idioma"
                                            v-model="form.idioma"
                                        >
                                            <option
                                                v-for="(
                                                    texto, idioma
                                                ) in opciones.idiomas"
                                                :key="idioma"
                                                :value="idioma"
                                            >
                                                {{ texto }}
                                            </option>
                                        </Seleccion>
                                    </Campo>
                                    <Campo
                                        etiqueta="Rol"
                                        para="perfil-rol"
                                        :ayuda="
                                            esConsultor
                                                ? 'Lo asigna el equipo de NuevasTIC.'
                                                : 'Solo otro administrador puede cambiarlo, desde Usuarios y roles.'
                                        "
                                    >
                                        <div class="relative">
                                            <Entrada
                                                id="perfil-rol"
                                                :model-value="rol ?? 'Sin rol'"
                                                disabled
                                                class="bg-lienzo-oscuro pr-10 text-tinta-suave"
                                            />
                                            <Lock
                                                class="absolute top-1/2 right-3 size-4 -translate-y-1/2 text-tinta-suave"
                                                aria-hidden="true"
                                            />
                                        </div>
                                    </Campo>
                                </template>
                            </div>
                        </section>

                        <section
                            v-if="esEmpresa && empresa"
                            class="flex flex-col gap-5 border-t border-linea p-4 sm:p-6"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                                    aria-hidden="true"
                                >
                                    <Building2 class="size-4.5" />
                                </span>
                                <div>
                                    <h2 class="text-base font-semibold">
                                        Información de empresa
                                    </h2>
                                    <p class="text-xs text-tinta-suave">
                                        {{
                                            editaEmpresa
                                                ? 'Algunos datos solo puede modificarlos el equipo de NuevasTIC.'
                                                : 'Solo la cuenta principal de la empresa puede cambiar estos datos.'
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <Campo
                                    obligatorio
                                    etiqueta="Nombre de la empresa"
                                    para="empresa-nombre"
                                    :error="form.errors['empresa.nombre']"
                                >
                                    <Entrada
                                        id="empresa-nombre"
                                        v-model="form.empresa.nombre"
                                        required
                                        :disabled="!editaEmpresa"
                                        :invalida="
                                            !!form.errors['empresa.nombre']
                                        "
                                    />
                                </Campo>
                                <Campo
                                    etiqueta="Sector"
                                    para="empresa-sector"
                                    ayuda="Solo el equipo de NuevasTIC puede modificar este dato."
                                >
                                    <div class="relative">
                                        <Entrada
                                            id="empresa-sector"
                                            :model-value="empresa.sector ?? ''"
                                            disabled
                                            class="bg-lienzo-oscuro pr-10 text-tinta-suave"
                                        />
                                        <Lock
                                            class="absolute top-1/2 right-3 size-4 -translate-y-1/2 text-tinta-suave"
                                            aria-hidden="true"
                                        />
                                    </div>
                                </Campo>
                                <Campo
                                    :obligatorio="
                                        editaEmpresa &&
                                        opciones.actividades.length > 0
                                    "
                                    etiqueta="Actividad económica (subsector)"
                                    para="empresa-actividad"
                                    ayuda="Código CIIU con el que aparece tu empresa en el RUT."
                                    :error="
                                        form.errors[
                                            'empresa.actividad_economica_id'
                                        ]
                                    "
                                    class="sm:col-span-2"
                                >
                                    <Seleccion
                                        id="empresa-actividad"
                                        v-model="
                                            form.empresa.actividad_economica_id
                                        "
                                        :required="
                                            opciones.actividades.length > 0
                                        "
                                        :disabled="
                                            !editaEmpresa ||
                                            opciones.actividades.length === 0
                                        "
                                        :invalida="
                                            !!form.errors[
                                                'empresa.actividad_economica_id'
                                            ]
                                        "
                                    >
                                        <option value="" disabled>
                                            {{
                                                opciones.actividades.length ===
                                                0
                                                    ? 'Tu sector no tiene actividades para elegir'
                                                    : 'Selecciona la actividad'
                                            }}
                                        </option>
                                        <option
                                            v-for="actividad in opciones.actividades"
                                            :key="actividad.id"
                                            :value="actividad.id"
                                        >
                                            {{ actividad.codigo }} ·
                                            {{ actividad.nombre }}
                                        </option>
                                    </Seleccion>
                                </Campo>
                                <Campo
                                    :obligatorio="editaEmpresa"
                                    etiqueta="Descripción corta"
                                    para="empresa-descripcion"
                                    ayuda="Qué hace tu empresa y a quién le vende."
                                    :contador="`${form.empresa.descripcion.length}/300`"
                                    :error="form.errors['empresa.descripcion']"
                                    class="sm:col-span-2"
                                >
                                    <AreaTexto
                                        id="empresa-descripcion"
                                        v-model="form.empresa.descripcion"
                                        required
                                        rows="3"
                                        maxlength="300"
                                        :disabled="!editaEmpresa"
                                        :invalida="
                                            !!form.errors['empresa.descripcion']
                                        "
                                    />
                                </Campo>
                                <SelectorUbicacion
                                    v-model:pais="form.empresa.pais"
                                    v-model:departamento="
                                        form.empresa.departamento
                                    "
                                    v-model:ciudad="form.empresa.ciudad"
                                    prefijo="empresa"
                                    :paises="opciones.paises"
                                    :bloqueado="!editaEmpresa"
                                    :errores="{
                                        pais: form.errors['empresa.pais'],
                                        departamento:
                                            form.errors['empresa.departamento'],
                                        ciudad: form.errors['empresa.ciudad'],
                                    }"
                                />
                                <Campo
                                    etiqueta="Sitio web o red social"
                                    para="empresa-web"
                                    :error="form.errors['empresa.sitio_web']"
                                >
                                    <Entrada
                                        id="empresa-web"
                                        v-model="form.empresa.sitio_web"
                                        placeholder="https://"
                                        :disabled="!editaEmpresa"
                                    />
                                </Campo>
                                <Campo
                                    etiqueta="Número de empleados"
                                    para="empresa-empleados"
                                    :error="
                                        form.errors['empresa.numero_empleados']
                                    "
                                >
                                    <Seleccion
                                        id="empresa-empleados"
                                        v-model="form.empresa.numero_empleados"
                                        :disabled="!editaEmpresa"
                                    >
                                        <option value="">Sin elegir</option>
                                        <option
                                            v-for="rango in opciones.empleados"
                                            :key="rango"
                                            :value="rango"
                                        >
                                            {{ rango }}
                                        </option>
                                    </Seleccion>
                                </Campo>
                            </div>
                        </section>

                        <section
                            v-if="Object.keys(opciones.avisos).length > 0"
                            class="flex flex-col gap-3 border-t border-linea p-4 sm:p-6"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                                    aria-hidden="true"
                                >
                                    <Bell class="size-4.5" />
                                </span>
                                <div>
                                    <h2 class="text-base font-semibold">
                                        {{
                                            esEmpresa
                                                ? 'Preferencias'
                                                : 'Avisos por correo'
                                        }}
                                    </h2>
                                    <p class="text-xs text-tinta-suave">
                                        Elige qué correos quieres recibir.
                                    </p>
                                </div>
                            </div>
                            <div
                                class="divide-y divide-linea rounded-xl border border-linea px-4"
                            >
                                <Interruptor
                                    v-for="(texto, clave) in opciones.avisos"
                                    :id="`aviso-${clave}`"
                                    :key="clave"
                                    v-model="form.avisos[clave]"
                                >
                                    {{ texto }}
                                </Interruptor>
                            </div>
                        </section>
                    </fieldset>
                    <footer
                        v-if="editaPerfil"
                        :class="
                            cn(
                                'flex flex-wrap items-center justify-between gap-3 rounded-b-2xl border-t border-linea bg-[#f9f8f4] px-4 py-3 sm:px-6',
                                form.isDirty &&
                                    'sticky bottom-0 z-10 shadow-[0_-4px_12px_-6px_rgb(0_0_0/0.12)]',
                            )
                        "
                    >
                        <p
                            class="inline-flex items-center gap-2 text-xs text-tinta-suave"
                            aria-live="polite"
                        >
                            <span
                                :class="
                                    cn(
                                        'size-2 rounded-full',
                                        form.isDirty ? 'bg-alerta' : 'bg-exito',
                                    )
                                "
                                aria-hidden="true"
                            />
                            {{
                                form.isDirty
                                    ? 'Tienes cambios sin guardar'
                                    : 'Sin cambios pendientes'
                            }}
                        </p>
                        <div class="flex flex-wrap gap-2 sm:gap-3">
                            <Boton
                                variante="secundario"
                                :disabled="!form.isDirty || form.processing"
                                @click="form.reset()"
                            >
                                Descartar cambios
                            </Boton>
                            <Boton
                                type="submit"
                                :disabled="!form.isDirty"
                                :cargando="form.processing"
                            >
                                Guardar cambios
                            </Boton>
                        </div>
                    </footer>
                </form>

                <section
                    v-if="!esEmpresa && cambiaContrasena"
                    class="rounded-2xl border border-linea bg-white p-4 sm:p-6"
                >
                    <div class="mb-5 flex items-start gap-3">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                            aria-hidden="true"
                        >
                            <KeyRound class="size-4.5" />
                        </span>
                        <div>
                            <h2 class="text-base font-semibold">
                                Cambiar contraseña
                            </h2>
                            <p class="text-xs text-tinta-suave">
                                <template
                                    v-if="usuario.contrasena_actualizada_en"
                                >
                                    Última actualización:
                                    {{
                                        haceCuanto(
                                            usuario.contrasena_actualizada_en,
                                        )
                                    }}.
                                </template>
                                Tu sesión sigue abierta después del cambio.
                            </p>
                        </div>
                    </div>
                    <FormularioContrasena />
                </section>
            </div>
        </div>
    </div>

    <Modal
        v-if="esEmpresa && cambiaContrasena"
        v-model:abierto="modalContrasena"
        titulo="Cambiar contraseña"
        descripcion="Tu sesión sigue abierta después del cambio."
        ancho="lg"
        :icono="KeyRound"
    >
        <FormularioContrasena
            :con-pie="false"
            @actualizada="modalContrasena = false"
        />
        <template #pie>
            <Boton variante="secundario" @click="modalContrasena = false">
                Cancelar
            </Boton>
            <Boton type="submit" form="form-contrasena">
                Actualizar contraseña
            </Boton>
        </template>
    </Modal>

    <ModalEliminarMiCuenta
        v-if="principal"
        v-model:abierto="modalEliminar"
        :correo="usuario.email"
        :sucesores="principal.sucesores"
    />

    <ModalVerificarCorreo
        v-if="usuario.email_pendiente || usuario.telefono_pendiente"
        :key="`${usuario.email_pendiente}|${usuario.telefono_pendiente}`"
        :correo="usuario.email_pendiente ?? usuario.email"
        :telefono="usuario.telefono_pendiente"
        :cambia-correo="usuario.email_pendiente !== null"
        :status="status"
        :rutas="{
            verificar: '/mi-perfil/correo/verificar',
            reenviar: '/mi-perfil/correo/reenviar',
            cancelar: '/mi-perfil/correo/cancelar',
        }"
        cambio
    />
</template>
