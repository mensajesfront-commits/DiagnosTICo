<script setup lang="ts">
/**
 * Campos de la empresa y de su usuario principal: A3.2 · Registrar empresa
 * y A3.1d · Datos de la cuenta (HU-032, HU-034).
 *
 * - Al registrar solo se ofrecen sectores activos (RN-003). Al editar
 *   también aparece el que ya tiene, aunque esté inactivo; cambiarlo no toca
 *   las mediciones hechas (HU-034 CA-002).
 * - La actividad económica depende del sector (DEC-015, DEC-018).
 * - El correo del usuario no puede ser de otra cuenta (RN-002). No hay campo
 *   de contraseña: el usuario la crea con el enlace del correo (RN-006).
 *
 * Los obligatorios llevan asterisco y los demás "(opcional)", como en el
 * resto de formularios (pedido del equipo, 8 oct). `sectorActual` indica que
 * se está editando (A3.1d).
 */
import { computed, watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Campo from '@/components/base/Campo.vue';
import CampoTelefono from '@/components/base/CampoTelefono.vue';
import Entrada from '@/components/base/Entrada.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import SelectorUbicacion from '@/components/ubicacion/SelectorUbicacion.vue';
import type { OpcionesEmpresa } from '@/types/empresas';

export type FormularioEmpresa = {
    nombre: string;
    sector_id: number | '';
    actividad_economica_id: number | '';
    descripcion: string;
    pais: string;
    departamento: string;
    ciudad: string;
    telefono: string;
    sitio_web: string;
    numero_empleados: string;
    usuario_nombre: string;
    cargo: string;
    email: string;
    usuario_telefono: string;
};

const props = withDefaults(
    defineProps<{
        opciones: OpcionesEmpresa;
        errores: Partial<Record<string, string>>;
        /** Prefijo de los id ("registrar", "cuenta"). */
        prefijo: string;
        /** Sector que ya tiene la empresa (se ofrece aunque esté inactivo). */
        sectorActual?: number | null;
        bloqueado?: boolean;
    }>(),
    { sectorActual: null, bloqueado: false },
);

const form = defineModel<FormularioEmpresa>('form', { required: true });

const MAX_DESCRIPCION = 300;

const sectores = computed(() =>
    props.opciones.sectores.filter(
        (s) => s.activo || s.id === props.sectorActual,
    ),
);

const actividades = computed(
    () =>
        props.opciones.sectores.find((s) => s.id === form.value.sector_id)
            ?.actividades ?? [],
);

// Al cambiar de sector, la actividad del sector anterior ya no vale.
watch(
    () => form.value.sector_id,
    () => {
        if (
            !actividades.value.some(
                (a) => a.id === form.value.actividad_economica_id,
            )
        ) {
            form.value.actividad_economica_id = '';
        }
    },
);

const id = (campo: string) => `${props.prefijo}-${campo}`;
</script>

<template>
    <fieldset class="flex flex-col gap-3" :disabled="bloqueado">
        <legend
            class="mb-1 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
        >
            {{ sectorActual ? 'Empresa' : 'La empresa' }}
        </legend>
        <div class="grid gap-x-4 gap-y-3 sm:grid-cols-2 xl:grid-cols-3">
            <Campo
                obligatorio
                etiqueta="Nombre de la empresa"
                :para="id('nombre')"
                :error="errores.nombre"
            >
                <Entrada
                    :id="id('nombre')"
                    v-model="form.nombre"
                    required
                    maxlength="255"
                    autocomplete="off"
                    :invalida="!!errores.nombre"
                />
            </Campo>
            <Campo
                obligatorio
                etiqueta="Sector"
                :para="id('sector')"
                :ayuda="
                    sectorActual
                        ? 'Cambiar el sector afecta las próximas mediciones.'
                        : 'Define qué diagnósticos se le pueden asignar.'
                "
                :error="errores.sector_id"
            >
                <Seleccion
                    :id="id('sector')"
                    v-model="form.sector_id"
                    required
                    :invalida="!!errores.sector_id"
                >
                    <option value="" disabled>Selecciona el sector</option>
                    <option v-for="s in sectores" :key="s.id" :value="s.id">
                        {{ s.nombre }}{{ s.activo ? '' : ' (inactivo)' }}
                    </option>
                </Seleccion>
            </Campo>
            <Campo
                :obligatorio="actividades.length > 0"
                etiqueta="Actividad económica"
                :para="id('actividad')"
                :error="errores.actividad_economica_id"
            >
                <Seleccion
                    :id="id('actividad')"
                    v-model="form.actividad_economica_id"
                    :required="actividades.length > 0"
                    :disabled="actividades.length === 0"
                    :invalida="!!errores.actividad_economica_id"
                >
                    <option value="" disabled>
                        {{
                            form.sector_id === ''
                                ? 'Elige primero el sector'
                                : actividades.length === 0
                                  ? 'El sector no tiene subsectores'
                                  : 'Selecciona la actividad'
                        }}
                    </option>
                    <option v-for="a in actividades" :key="a.id" :value="a.id">
                        {{ a.codigo }} · {{ a.nombre }}
                    </option>
                </Seleccion>
            </Campo>

            <SelectorUbicacion
                v-model:pais="form.pais"
                v-model:departamento="form.departamento"
                v-model:ciudad="form.ciudad"
                class="sm:col-span-2 xl:col-span-3"
                :prefijo="prefijo"
                :paises="opciones.paises"
                :bloqueado="bloqueado"
                :errores="{
                    pais: errores.pais,
                    departamento: errores.departamento,
                    ciudad: errores.ciudad,
                }"
            />

            <Campo
                etiqueta="Teléfono de la empresa"
                opcional
                :para="id('telefono')"
                :error="errores.telefono"
            >
                <CampoTelefono
                    :id="id('telefono')"
                    v-model="form.telefono"
                    :pais="form.pais"
                    :invalida="!!errores.telefono"
                />
            </Campo>
            <Campo
                etiqueta="Sitio web"
                opcional
                :para="id('sitio')"
                :error="errores.sitio_web"
            >
                <Entrada
                    :id="id('sitio')"
                    v-model="form.sitio_web"
                    type="url"
                    placeholder="https://"
                    :invalida="!!errores.sitio_web"
                />
            </Campo>
            <Campo
                etiqueta="Número de empleados"
                opcional
                :para="id('empleados')"
                :error="errores.numero_empleados"
            >
                <Seleccion
                    :id="id('empleados')"
                    v-model="form.numero_empleados"
                >
                    <option value="">Sin indicar</option>
                    <option v-for="r in opciones.empleados" :key="r" :value="r">
                        {{ r }}
                    </option>
                </Seleccion>
            </Campo>

            <Campo
                etiqueta="Descripción corta"
                :para="id('descripcion')"
                opcional
                :contador="`${form.descripcion.length}/${MAX_DESCRIPCION}`"
                :error="errores.descripcion"
                class="sm:col-span-2 xl:col-span-3"
            >
                <AreaTexto
                    :id="id('descripcion')"
                    v-model="form.descripcion"
                    rows="1"
                    class="min-h-10"
                    :maxlength="MAX_DESCRIPCION"
                    placeholder="Qué hace la empresa y a quién le vende."
                    :invalida="!!errores.descripcion"
                />
            </Campo>
        </div>
    </fieldset>

    <fieldset class="flex flex-col gap-3" :disabled="bloqueado">
        <legend
            class="mb-1 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
        >
            Usuario principal
        </legend>
        <div class="grid gap-x-4 gap-y-3 sm:grid-cols-2 xl:grid-cols-4">
            <Campo
                obligatorio
                etiqueta="Nombre"
                :para="id('usuario-nombre')"
                :error="errores.usuario_nombre"
            >
                <Entrada
                    :id="id('usuario-nombre')"
                    v-model="form.usuario_nombre"
                    required
                    maxlength="255"
                    autocomplete="off"
                    :invalida="!!errores.usuario_nombre"
                />
            </Campo>
            <Campo
                etiqueta="Cargo"
                opcional
                :para="id('cargo')"
                :error="errores.cargo"
            >
                <Entrada
                    :id="id('cargo')"
                    v-model="form.cargo"
                    maxlength="255"
                    placeholder="Ej. Gerente"
                />
            </Campo>
            <Campo
                obligatorio
                etiqueta="Correo"
                :para="id('correo')"
                :ayuda="
                    sectorActual
                        ? 'Con este correo inicia sesión y recibe los avisos. Si lo cambias, se cierran sus sesiones abiertas.'
                        : 'Aquí llega la invitación para crear su contraseña.'
                "
                :error="errores.email"
            >
                <Entrada
                    :id="id('correo')"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="off"
                    placeholder="nombre@empresa.com"
                    :invalida="!!errores.email"
                />
            </Campo>
            <Campo
                etiqueta="Teléfono"
                opcional
                :para="id('usuario-telefono')"
                :error="errores.usuario_telefono"
            >
                <CampoTelefono
                    :id="id('usuario-telefono')"
                    v-model="form.usuario_telefono"
                    :pais="form.pais"
                    :invalida="!!errores.usuario_telefono"
                />
            </Campo>
        </div>
    </fieldset>
</template>
