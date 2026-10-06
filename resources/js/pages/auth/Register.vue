<script setup lang="ts">
/**
 * L2 · Registra tu empresa (HU-002).
 *
 * Crea en una sola operación la empresa y su usuario principal con el rol
 * Empresa (T-047, backend). Campos que envía el formulario:
 *   empresa_nombre, sector_id, ciudad, pais,
 *   name, cargo (opcional), email, telefono (opcional),
 *   password, password_confirmation, terminos.
 * Contrato completo en docs/14_FRONTEND.md.
 *
 * Solo se ofrecen sectores activos y no existe la opción "Otro" (RN-003).
 */
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import CampoContrasena from '@/components/base/CampoContrasena.vue';
import Entrada from '@/components/base/Entrada.vue';
import RequisitosContrasena from '@/components/base/RequisitosContrasena.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: {
        title: 'Registra tu empresa',
        description:
            'Crea tu cuenta para responder el diagnóstico de marketing digital. Todos los campos son obligatorios salvo los marcados como (opcional).',
        ancho: 'lg',
    },
});

const props = withDefaults(
    defineProps<{
        passwordRules?: string;
        /** Sectores activos (RN-003). Los entrega el backend (T-047). */
        sectores?: { id: number; nombre: string }[];
        /**
         * [INFORMACIÓN PENDIENTE] Lista de países. El wireframe solo muestra
         * "Colombia"; mientras no se defina la lista, es la única opción.
         */
        paises?: string[];
    }>(),
    {
        passwordRules: undefined,
        sectores: () => [],
        paises: () => ['Colombia'],
    },
);

const sector = ref('');
const pais = ref(props.paises[0] ?? '');
const contrasena = ref('');
const confirmacion = ref('');
</script>

<template>
    <Head title="Registra tu empresa" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <fieldset class="grid gap-4 sm:grid-cols-2">
            <legend
                class="mb-3 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
            >
                La empresa
            </legend>

            <Campo
                etiqueta="Nombre de la empresa"
                para="empresa_nombre"
                :error="errors.empresa_nombre"
            >
                <Entrada
                    id="empresa_nombre"
                    name="empresa_nombre"
                    required
                    v-focus
                    autocomplete="organization"
                    :invalida="!!errors.empresa_nombre"
                />
            </Campo>

            <Campo etiqueta="Sector" para="sector_id" :error="errors.sector_id">
                <Seleccion
                    id="sector_id"
                    name="sector_id"
                    v-model="sector"
                    required
                    :invalida="!!errors.sector_id"
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

            <Campo etiqueta="Ciudad" para="ciudad" :error="errors.ciudad">
                <Entrada
                    id="ciudad"
                    name="ciudad"
                    required
                    autocomplete="address-level2"
                    :invalida="!!errors.ciudad"
                />
            </Campo>

            <Campo etiqueta="País" para="pais" :error="errors.pais">
                <Seleccion
                    id="pais"
                    name="pais"
                    v-model="pais"
                    required
                    :invalida="!!errors.pais"
                >
                    <option v-for="pais in paises" :key="pais" :value="pais">
                        {{ pais }}
                    </option>
                </Seleccion>
            </Campo>
        </fieldset>

        <fieldset class="grid gap-4 sm:grid-cols-2">
            <legend
                class="mb-3 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
            >
                Tu usuario
            </legend>

            <Campo
                etiqueta="Nombre del usuario"
                para="name"
                :error="errors.name"
            >
                <Entrada
                    id="name"
                    name="name"
                    required
                    autocomplete="name"
                    :invalida="!!errors.name"
                />
            </Campo>

            <Campo etiqueta="Cargo" para="cargo" opcional :error="errors.cargo">
                <Entrada
                    id="cargo"
                    name="cargo"
                    autocomplete="organization-title"
                    placeholder="Ej. Gerente, dueño"
                />
            </Campo>

            <Campo etiqueta="Correo" para="email" :error="errors.email">
                <Entrada
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="nombre@empresa.com"
                    :invalida="!!errors.email"
                />
            </Campo>

            <Campo
                etiqueta="Teléfono"
                para="telefono"
                opcional
                :error="errors.telefono"
            >
                <Entrada
                    id="telefono"
                    type="tel"
                    name="telefono"
                    autocomplete="tel"
                    placeholder="+57 300 000 0000"
                />
            </Campo>

            <Campo
                etiqueta="Contraseña"
                para="password"
                :error="errors.password"
            >
                <CampoContrasena
                    id="password"
                    v-model="contrasena"
                    name="password"
                    required
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                    :invalida="!!errors.password"
                />
            </Campo>

            <Campo
                etiqueta="Confirmar contraseña"
                para="password_confirmation"
                :error="errors.password_confirmation"
            >
                <CampoContrasena
                    id="password_confirmation"
                    v-model="confirmacion"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                />
            </Campo>
        </fieldset>

        <RequisitosContrasena
            :contrasena="contrasena"
            :confirmacion="confirmacion"
            columnas
        />

        <div>
            <label class="flex items-start gap-2 text-sm text-tinta">
                <input
                    type="checkbox"
                    name="terminos"
                    value="1"
                    required
                    class="mt-0.5 size-4 rounded border-linea-fuerte accent-marca"
                />
                <span>
                    Acepto los
                    <!-- [INFORMACIÓN PENDIENTE] Faltan los enlaces reales de los términos y de la política. -->
                    <a href="#" class="text-marca underline">términos de uso</a>
                    y la
                    <a href="#" class="text-marca underline">
                        política de tratamiento de datos
                    </a>
                </span>
            </label>
            <p
                v-if="errors.terminos"
                class="mt-1 text-xs text-aviso"
                role="alert"
            >
                {{ errors.terminos }}
            </p>
        </div>

        <Boton
            type="submit"
            class="w-full"
            :cargando="processing"
            data-test="register-user-button"
        >
            Crear cuenta
        </Boton>

        <p class="text-xs text-tinta-suave">
            Tu cuenta queda lista al instante y entras a tu inicio, donde
            aparece tu primer diagnóstico.
        </p>

        <p class="text-center text-sm text-tinta-suave">
            ¿Ya tienes cuenta?
            <Link
                :href="login()"
                class="font-medium text-marca underline underline-offset-2 hover:text-marca-hover"
            >
                Iniciar sesión
            </Link>
        </p>
    </Form>
</template>
