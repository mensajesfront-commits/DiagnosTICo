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
 *
 * Envía a PATCH /mi-perfil: name, email, cargo, telefono, pais,
 * departamento, ciudad,
 * zona_horaria, idioma, avisos{} y, si edita la empresa, empresa{nombre,
 * pais, departamento, ciudad, sitio_web, numero_empleados}.
 */
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Lock, LogOut } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Interruptor from '@/components/base/Interruptor.vue';
import Modal from '@/components/base/Modal.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import FormularioContrasena from '@/components/perfil/FormularioContrasena.vue';
import SelectorUbicacion from '@/components/ubicacion/SelectorUbicacion.vue';
import { haceCuanto, mesYAnio, momento } from '@/lib/fechas';
import { iniciales } from '@/lib/usuarios';
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
};

type Empresa = {
    nombre: string;
    sector: string | null;
    ciudad: string;
    departamento: string | null;
    pais: string;
    sitio_web: string | null;
    numero_empleados: string | null;
};

const props = defineProps<{
    usuario: Usuario;
    rol: string | null;
    empresa: Empresa | null;
    /** Solo la cuenta principal de la empresa cambia sus datos (RN-025). */
    editaEmpresa: boolean;
    opciones: {
        /** Los 18 países de Hispanoamérica (DEC-016). */
        paises: Pais[];
        zonas: Record<string, string>;
        idiomas: Record<string, string>;
        empleados: string[];
        /** Clave del aviso → texto. */
        avisos: Record<string, string>;
    };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Mi perfil', href: perfil() }] },
});

const esEmpresa = computed(() => props.empresa !== null);

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
        empresa: props.editaEmpresa ? datos.empresa : undefined,
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

function cerrarSesion(): void {
    router.flushAll();
}
</script>

<template>
    <Head title="Mi perfil" />

    <div class="flex flex-col gap-5 p-6">
        <header>
            <h1 class="text-2xl font-semibold">Mi perfil</h1>
            <p class="mt-1 text-sm text-tinta-suave">
                {{
                    esEmpresa
                        ? 'Gestiona tu información personal y la información de tu empresa.'
                        : 'Gestiona tus datos y la seguridad de tu cuenta.'
                }}
            </p>
            <p
                v-if="esEmpresa"
                class="mt-1 flex items-center gap-1.5 text-xs text-tinta-suave"
            >
                <span aria-hidden="true">ⓘ</span>
                Algunos datos solo puede modificarlos el equipo de NuevasTIC.
            </p>
        </header>

        <div class="grid items-start gap-5 lg:grid-cols-[290px_1fr]">
            <!-- Columna izquierda: identidad, seguridad y sesión -->
            <div class="flex flex-col gap-5">
                <section
                    class="flex flex-col items-center rounded-xl border border-linea bg-white px-5 py-6 text-center"
                >
                    <img
                        v-if="usuario.foto_url"
                        :src="usuario.foto_url"
                        alt=""
                        class="size-20 rounded-full object-cover"
                    />
                    <span
                        v-else
                        class="flex size-20 items-center justify-center rounded-full bg-marca-suave text-2xl font-semibold text-marca"
                        aria-hidden="true"
                    >
                        {{ iniciales(nombreDelCirculo) }}
                    </span>

                    <template v-if="esEmpresa">
                        <p class="mt-4 text-lg font-semibold">
                            {{ empresa?.nombre }}
                        </p>
                        <p class="text-sm text-tinta-suave">
                            {{ usuario.name
                            }}<template v-if="usuario.cargo">
                                · {{ usuario.cargo }}</template
                            >
                        </p>
                        <p class="mt-2 text-xs text-tinta-suave">
                            {{ rol }} · {{ empresa?.sector }}
                        </p>
                    </template>
                    <template v-else>
                        <p class="mt-4 text-lg font-semibold">
                            {{ usuario.name }}
                        </p>
                        <p
                            v-if="usuario.cargo"
                            class="text-sm text-tinta-suave"
                        >
                            {{ usuario.cargo }}
                        </p>
                        <p class="mt-2 text-xs text-tinta-suave">
                            {{ rol }} · NuevasTIC
                        </p>
                    </template>

                    <input
                        ref="archivo"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        class="sr-only"
                        aria-label="Elegir imagen"
                        @change="elegirFoto"
                    />
                    <button
                        type="button"
                        class="mt-3 text-sm text-marca underline underline-offset-2 hover:text-marca-hover disabled:opacity-50"
                        :disabled="subiendo"
                        @click="archivo?.click()"
                    >
                        {{
                            subiendo
                                ? 'Subiendo…'
                                : editaEmpresa
                                  ? 'Cambiar logo'
                                  : 'Cambiar foto'
                        }}
                    </button>
                    <p
                        v-if="errorFoto"
                        class="mt-1 text-xs text-aviso"
                        role="alert"
                    >
                        {{ errorFoto }}
                    </p>
                    <p v-else class="mt-1 text-xs text-tinta-suave">
                        JPG, PNG o WebP, hasta 2 MB.
                    </p>
                </section>

                <section
                    class="flex flex-col gap-5 rounded-xl border border-linea bg-white p-5 text-sm"
                >
                    <div v-if="esEmpresa" class="border-b border-linea pb-5">
                        <h2 class="font-semibold">Seguridad</h2>
                        <p class="mt-1 text-xs font-medium">Contraseña</p>
                        <p
                            v-if="usuario.contrasena_actualizada_en"
                            class="text-xs text-tinta-suave"
                        >
                            Última actualización:
                            {{ haceCuanto(usuario.contrasena_actualizada_en) }}
                        </p>
                        <Boton
                            variante="secundario"
                            tamano="sm"
                            class="mt-3"
                            @click="modalContrasena = true"
                        >
                            Cambiar contraseña
                        </Boton>
                    </div>

                    <div>
                        <h2 class="font-semibold">Sesión</h2>
                        <p class="mt-1">
                            Sesión activa como
                            <strong class="break-all">{{
                                usuario.email
                            }}</strong>
                        </p>
                        <p
                            v-if="!esEmpresa && usuario.ultimo_acceso_en"
                            class="mt-1 text-xs text-tinta-suave"
                        >
                            Último acceso: {{ momento(usuario.ultimo_acceso_en)
                            }}<template v-if="usuario.creado_en">
                                · Cuenta creada:
                                {{ mesYAnio(usuario.creado_en) }}</template
                            >
                        </p>
                        <Link
                            :href="logout()"
                            as="button"
                            class="mt-3 inline-flex h-9 items-center gap-2 rounded-md border border-linea-fuerte px-3 text-sm hover:bg-lienzo"
                            @click="cerrarSesion"
                        >
                            <LogOut class="size-4" aria-hidden="true" />
                            Cerrar sesión
                        </Link>
                    </div>
                </section>
            </div>

            <!-- Columna derecha: datos y contraseña -->
            <div class="flex flex-col gap-5">
                <form
                    class="rounded-xl border border-linea bg-white"
                    @submit.prevent="guardar"
                >
                    <section class="flex flex-col gap-4 p-6">
                        <h2 class="text-base font-semibold">
                            Información personal
                        </h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Campo
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
                            </Campo>
                            <Campo
                                etiqueta="Teléfono"
                                para="perfil-telefono"
                                :error="form.errors.telefono"
                            >
                                <Entrada
                                    id="perfil-telefono"
                                    v-model="form.telefono"
                                    type="tel"
                                    autocomplete="tel"
                                />
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
                                        departamento: form.errors.departamento,
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
                                    ayuda="Solo otro administrador puede cambiarlo, desde Usuarios y roles."
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
                        class="flex flex-col gap-4 border-t border-linea p-6"
                    >
                        <h2 class="text-base font-semibold">
                            Información de empresa
                        </h2>
                        <p
                            v-if="!editaEmpresa"
                            class="-mt-2 text-xs text-tinta-suave"
                        >
                            Solo la cuenta principal de la empresa puede cambiar
                            estos datos.
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Campo
                                etiqueta="Nombre de la empresa"
                                para="empresa-nombre"
                                :error="form.errors['empresa.nombre']"
                            >
                                <Entrada
                                    id="empresa-nombre"
                                    v-model="form.empresa.nombre"
                                    required
                                    :disabled="!editaEmpresa"
                                    :invalida="!!form.errors['empresa.nombre']"
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
                            <SelectorUbicacion
                                v-model:pais="form.empresa.pais"
                                v-model:departamento="form.empresa.departamento"
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
                                :error="form.errors['empresa.numero_empleados']"
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

                    <section class="border-t border-linea px-6 pt-5 pb-3">
                        <h2 class="text-base font-semibold">
                            {{
                                esEmpresa ? 'Preferencias' : 'Avisos por correo'
                            }}
                        </h2>
                        <div class="mt-2">
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

                    <footer
                        class="flex flex-wrap items-center justify-between gap-3 rounded-b-xl border-t border-linea bg-[#f9f8f4] px-6 py-4"
                    >
                        <p class="text-xs text-tinta-suave" aria-live="polite">
                            {{
                                form.isDirty
                                    ? 'Tienes cambios sin guardar'
                                    : 'Sin cambios pendientes'
                            }}
                        </p>
                        <div class="flex gap-3">
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
                    v-if="!esEmpresa"
                    class="rounded-xl border border-linea bg-white p-6"
                >
                    <h2 class="text-base font-semibold">Cambiar contraseña</h2>
                    <p class="mt-1 mb-4 text-xs text-tinta-suave">
                        <template v-if="usuario.contrasena_actualizada_en">
                            Última actualización:
                            {{ haceCuanto(usuario.contrasena_actualizada_en) }}.
                        </template>
                        Tu sesión sigue abierta después del cambio.
                    </p>
                    <FormularioContrasena />
                </section>
            </div>
        </div>
    </div>

    <Modal
        v-if="esEmpresa"
        v-model:abierto="modalContrasena"
        titulo="Cambiar contraseña"
        descripcion="Tu sesión sigue abierta después del cambio."
        ancho="lg"
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
</template>
