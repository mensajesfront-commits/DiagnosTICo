<script setup lang="ts">
/**
 * L2 · Registra tu empresa (HU-002), en dos pasos:
 *
 * 1. Mi empresa: nombre, sector, actividad económica (CIIU, ligada al
 *    sector), descripción corta (máx. 300) y país → departamento → ciudad
 *    (SelectorUbicacion).
 * 2. Tu usuario: nombre, cargo (obligatorio, para saber quién registra la
 *    empresa), correo, teléfono (opcional), contraseña y términos.
 *
 * "Siguiente" revisa en el navegador los campos del paso 1; el servidor
 * vuelve a validar todo al registrar y, si el error es de un campo del
 * paso 1, la pantalla vuelve a ese paso.
 *
 * Crea en una sola operación la empresa y su usuario principal con el rol
 * Empresa. Envía: empresa_nombre, sector_id, actividad_economica_id,
 * descripcion, ciudad, pais, name, cargo, email, telefono (opcional),
 * password, password_confirmation, terminos. Contrato en
 * docs/14_FRONTEND.md.
 *
 * Solo se ofrecen sectores activos y no existe la opción "Otro" (RN-003).
 */
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import CampoContrasena from '@/components/base/CampoContrasena.vue';
import Entrada from '@/components/base/Entrada.vue';
import RequisitosContrasena from '@/components/base/RequisitosContrasena.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import SelectorUbicacion from '@/components/ubicacion/SelectorUbicacion.vue';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { store } from '@/routes/register';
import type { Pais } from '@/types/ubicaciones';

defineOptions({
    layout: {
        title: 'Registra tu empresa',
        description:
            'Crea tu cuenta para responder el diagnóstico de marketing digital. Los campos con * son obligatorios.',
        ancho: 'lg',
    },
});

type Actividad = { id: number; codigo: string; nombre: string };
type SectorRegistro = { id: number; nombre: string; actividades: Actividad[] };

const props = withDefaults(
    defineProps<{
        passwordRules?: string;
        /** Sectores activos (RN-003), cada uno con sus actividades CIIU. */
        sectores?: SectorRegistro[];
        /** Los 18 países de Hispanoamérica (DEC-016). */
        paises?: Pais[];
    }>(),
    {
        passwordRules: undefined,
        sectores: () => [],
        paises: () => [],
    },
);

const MAX_DESCRIPCION = 300;

const CAMPOS_PASO_1 = [
    'empresa_nombre',
    'sector_id',
    'actividad_economica_id',
    'descripcion',
    'pais',
    'departamento',
    'ciudad',
] as const;

const form = useForm({
    empresa_nombre: '',
    sector_id: '' as number | '',
    actividad_economica_id: '' as number | '',
    descripcion: '',
    pais: '',
    departamento: '',
    ciudad: '',
    name: '',
    cargo: '',
    email: '',
    telefono: '',
    password: '',
    password_confirmation: '',
    terminos: false,
});

const paso = ref<1 | 2>(1);
const pasoUno = ref<HTMLElement | null>(null);
const pasoDos = ref<HTMLElement | null>(null);

const actividades = computed(
    () =>
        props.sectores.find((s) => s.id === form.sector_id)?.actividades ?? [],
);

// La actividad depende del sector: al cambiarlo, se elige de nuevo.
watch(
    () => form.sector_id,
    () => (form.actividad_economica_id = ''),
);

const ayudaActividad = computed(() => {
    if (form.sector_id === '') {
        return 'Primero elige el sector.';
    }

    return actividades.value.length === 0
        ? 'Este sector todavía no tiene actividades para elegir.'
        : 'Código CIIU con el que aparece tu empresa en el RUT.';
});

/** Revisa los campos de un paso con las reglas del navegador. */
function pasoValido(contenedor: HTMLElement | null): boolean {
    const campos = contenedor?.querySelectorAll<
        HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
    >('input, select, textarea');

    for (const campo of campos ?? []) {
        if (!campo.checkValidity()) {
            campo.reportValidity();

            return false;
        }
    }

    return true;
}

function siguiente(): void {
    if (!pasoValido(pasoUno.value)) {
        return;
    }

    paso.value = 2;
    void nextTick(() => document.getElementById('name')?.focus());
}

function atras(): void {
    paso.value = 1;
}

function enviar(): void {
    if (paso.value === 1) {
        siguiente();

        return;
    }

    if (!pasoValido(pasoDos.value)) {
        return;
    }

    form.transform((datos) => ({
        ...datos,
        actividad_economica_id: datos.actividad_economica_id || null,
        terminos: datos.terminos ? '1' : '',
    })).post(store.url(), {
        onError: (errores) => {
            if (CAMPOS_PASO_1.some((campo) => campo in errores)) {
                paso.value = 1;
            }
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}

const pasos = [
    { numero: 1, titulo: 'Mi empresa' },
    { numero: 2, titulo: 'Tu usuario' },
] as const;
</script>

<template>
    <Head title="Registra tu empresa" />

    <form class="flex flex-col gap-5" novalidate @submit.prevent="enviar">
        <!-- Indicador de pasos -->
        <ol class="grid grid-cols-2 gap-3" aria-label="Pasos del registro">
            <li
                v-for="p in pasos"
                :key="p.numero"
                :aria-current="paso === p.numero ? 'step' : undefined"
                class="flex flex-col gap-1.5"
            >
                <span
                    :class="
                        cn(
                            'h-1 rounded-full',
                            paso >= p.numero ? 'bg-marca' : 'bg-linea',
                        )
                    "
                />
                <span
                    :class="
                        cn(
                            'text-xs',
                            paso === p.numero
                                ? 'font-semibold text-marca'
                                : 'text-tinta-suave',
                        )
                    "
                >
                    Paso {{ p.numero }} de 2 · {{ p.titulo }}
                </span>
            </li>
        </ol>

        <!-- Paso 1 · Mi empresa -->
        <div
            v-show="paso === 1"
            ref="pasoUno"
            class="grid gap-4 sm:grid-cols-2"
        >
            <h2 class="text-base font-semibold sm:col-span-2">Mi empresa</h2>

            <Campo
                obligatorio
                etiqueta="Nombre de la empresa"
                para="empresa_nombre"
                :error="form.errors.empresa_nombre"
                class="sm:col-span-2"
            >
                <Entrada
                    id="empresa_nombre"
                    v-model="form.empresa_nombre"
                    required
                    v-focus
                    maxlength="255"
                    autocomplete="organization"
                    :invalida="!!form.errors.empresa_nombre"
                />
            </Campo>

            <Campo
                obligatorio
                etiqueta="Sector"
                para="sector_id"
                :error="form.errors.sector_id"
                class="sm:col-span-2"
            >
                <Seleccion
                    id="sector_id"
                    v-model="form.sector_id"
                    required
                    :invalida="!!form.errors.sector_id"
                >
                    <option value="" disabled>Selecciona tu sector</option>
                    <option
                        v-for="sector in sectores"
                        :key="sector.id"
                        :value="sector.id"
                    >
                        {{ sector.nombre }}
                    </option>
                </Seleccion>
                <template #ayuda>
                    Define qué diagnóstico recibirás.
                    <strong>No podrás cambiarlo después</strong>; si te
                    equivocas, escribe a NuevasTIC.
                </template>
            </Campo>

            <Campo
                :obligatorio="form.sector_id === '' || actividades.length > 0"
                etiqueta="Actividad económica"
                para="actividad_economica_id"
                :ayuda="ayudaActividad"
                :error="form.errors.actividad_economica_id"
                class="sm:col-span-2"
            >
                <Seleccion
                    id="actividad_economica_id"
                    v-model="form.actividad_economica_id"
                    :required="actividades.length > 0"
                    :disabled="actividades.length === 0"
                    :invalida="!!form.errors.actividad_economica_id"
                >
                    <option value="" disabled>
                        {{
                            form.sector_id === ''
                                ? 'Elige primero el sector'
                                : 'Selecciona la actividad'
                        }}
                    </option>
                    <option
                        v-for="actividad in actividades"
                        :key="actividad.id"
                        :value="actividad.id"
                    >
                        {{ actividad.codigo }} · {{ actividad.nombre }}
                    </option>
                </Seleccion>
            </Campo>

            <Campo
                obligatorio
                etiqueta="Descripción corta"
                para="descripcion"
                ayuda="Qué hace tu empresa y a quién le vende."
                :contador="`${form.descripcion.length}/${MAX_DESCRIPCION}`"
                :error="form.errors.descripcion"
                class="sm:col-span-2"
            >
                <AreaTexto
                    id="descripcion"
                    v-model="form.descripcion"
                    required
                    rows="3"
                    :maxlength="MAX_DESCRIPCION"
                    placeholder="Ej. Restaurante de comida casera con almuerzos del día y domicilios en el barrio."
                    :invalida="!!form.errors.descripcion"
                />
            </Campo>

            <SelectorUbicacion
                v-model:pais="form.pais"
                v-model:departamento="form.departamento"
                v-model:ciudad="form.ciudad"
                prefijo="registro"
                :paises="paises"
                :errores="{
                    pais: form.errors.pais,
                    departamento: form.errors.departamento,
                    ciudad: form.errors.ciudad,
                }"
            />

            <Boton type="submit" class="w-full sm:col-span-2">
                Siguiente
            </Boton>
        </div>

        <!-- Paso 2 · Tu usuario -->
        <div v-if="paso === 2" ref="pasoDos" class="flex flex-col gap-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <h2 class="text-base font-semibold sm:col-span-2">
                    Tu usuario
                </h2>

                <Campo
                    obligatorio
                    etiqueta="Nombre del usuario"
                    para="name"
                    :error="form.errors.name"
                >
                    <Entrada
                        id="name"
                        v-model="form.name"
                        required
                        maxlength="255"
                        autocomplete="name"
                        :invalida="!!form.errors.name"
                    />
                </Campo>

                <Campo
                    obligatorio
                    etiqueta="Cargo"
                    para="cargo"
                    ayuda="Así sabemos quién registra la empresa."
                    :error="form.errors.cargo"
                >
                    <Entrada
                        id="cargo"
                        v-model="form.cargo"
                        required
                        maxlength="255"
                        autocomplete="organization-title"
                        placeholder="Ej. Gerente, dueño"
                        :invalida="!!form.errors.cargo"
                    />
                </Campo>

                <Campo
                    obligatorio
                    etiqueta="Correo"
                    para="email"
                    :error="form.errors.email"
                >
                    <Entrada
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="nombre@empresa.com"
                        :invalida="!!form.errors.email"
                    />
                </Campo>

                <Campo
                    etiqueta="Teléfono"
                    para="telefono"
                    opcional
                    :error="form.errors.telefono"
                >
                    <Entrada
                        id="telefono"
                        v-model="form.telefono"
                        type="tel"
                        autocomplete="tel"
                        placeholder="+57 300 000 0000"
                    />
                </Campo>

                <Campo
                    obligatorio
                    etiqueta="Contraseña"
                    para="password"
                    :error="form.errors.password"
                >
                    <CampoContrasena
                        id="password"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                        :invalida="!!form.errors.password"
                    />
                </Campo>

                <Campo
                    obligatorio
                    etiqueta="Confirmar contraseña"
                    para="password_confirmation"
                    :error="form.errors.password_confirmation"
                >
                    <CampoContrasena
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                    />
                </Campo>
            </div>

            <RequisitosContrasena
                :contrasena="form.password"
                :confirmacion="form.password_confirmation"
                columnas
            />

            <div>
                <label class="flex items-start gap-2 text-sm text-tinta">
                    <input
                        v-model="form.terminos"
                        type="checkbox"
                        class="mt-0.5 size-4 rounded border-linea-fuerte accent-marca"
                    />
                    <span>
                        Acepto los
                        <!-- [INFORMACIÓN PENDIENTE] Faltan los enlaces reales de los términos y de la política. -->
                        <a href="#" class="text-marca underline"
                            >términos de uso</a
                        >
                        y la
                        <a href="#" class="text-marca underline">
                            política de tratamiento de datos
                        </a>
                    </span>
                </label>
                <p
                    v-if="form.errors.terminos"
                    class="mt-1 text-xs text-aviso"
                    role="alert"
                >
                    {{ form.errors.terminos }}
                </p>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row">
                <Boton variante="secundario" class="sm:w-40" @click="atras">
                    Atrás
                </Boton>
                <Boton
                    type="submit"
                    class="flex-1"
                    :disabled="!form.terminos"
                    :cargando="form.processing"
                    data-test="register-user-button"
                >
                    Registrar empresa
                </Boton>
            </div>

            <p class="text-xs text-tinta-suave">
                Tu cuenta queda lista al instante y entras a tu inicio, donde
                aparece tu primer diagnóstico.
            </p>
        </div>

        <p class="text-center text-sm text-tinta-suave">
            ¿Ya tienes cuenta?
            <Link
                :href="login()"
                class="font-medium text-marca underline underline-offset-2 hover:text-marca-hover"
            >
                Iniciar sesión
            </Link>
        </p>
    </form>
</template>
