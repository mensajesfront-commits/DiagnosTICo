<script setup lang="ts">
/**
 * A3.2 · Registrar empresa (HU-032).
 *
 * Datos de la empresa y de su usuario principal (`CamposEmpresa`) y, si se
 * quiere, la primera medición con un diagnóstico publicado del sector
 * (RN-014). Al registrar, el usuario recibe un correo para crear su
 * contraseña; el Administrador no la escribe (RN-006).
 *
 * A la derecha, "Qué pasa al registrar". En pantallas grandes la página no
 * se desplaza: los campos van en tres columnas y, si la ventana es baja, el
 * formulario baja y sube en su caja.
 */
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Entrada from '@/components/base/Entrada.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import CamposEmpresa from '@/components/empresas/CamposEmpresa.vue';
import type { FormularioEmpresa } from '@/components/empresas/CamposEmpresa.vue';
import { rutas } from '@/lib/rutas';
import type { OpcionesEmpresa } from '@/types/empresas';

const props = defineProps<{
    opciones: OpcionesEmpresa;
    /** Fecha límite más cercana que se acepta (3 días). */
    fechaMinima: string;
}>();

const form = useForm<
    FormularioEmpresa & {
        asignar_medicion: boolean;
        diagnostico_id: number | '';
        fecha_limite: string;
    }
>({
    nombre: '',
    sector_id: '',
    actividad_economica_id: '',
    descripcion: '',
    pais: 'Colombia',
    departamento: '',
    ciudad: '',
    telefono: '',
    sitio_web: '',
    numero_empleados: '',
    usuario_nombre: '',
    cargo: '',
    email: '',
    usuario_telefono: '',
    asignar_medicion: false,
    diagnostico_id: '',
    fecha_limite: '',
});

/** Diagnósticos publicados del sector elegido (RN-014). */
const diagnosticos = computed(
    () =>
        props.opciones.sectores.find((s) => s.id === form.sector_id)
            ?.diagnosticos ?? [],
);

// Con un solo diagnóstico publicado, queda elegido.
watch(diagnosticos, (lista) => {
    form.diagnostico_id = lista.length === 1 ? lista[0].id : '';
});

/** Fecha límite más cercana: 3 días desde hoy (la calcula el servidor). */
const hoy = props.fechaMinima;

function registrar(): void {
    form.transform((d) => ({
        ...d,
        actividad_economica_id: d.actividad_economica_id || null,
        diagnostico_id: d.asignar_medicion ? d.diagnostico_id || null : null,
        fecha_limite: d.asignar_medicion ? d.fecha_limite || null : null,
    })).post(rutas.empresas.guardar());
}
</script>

<template>
    <Head title="Registrar empresa" />

    <div class="flex flex-col gap-5 p-6 lg:h-dvh lg:overflow-hidden">
        <EncabezadoPagina
            titulo="Registrar empresa"
            :volver="{ href: rutas.empresas.lista(), texto: 'Empresas' }"
        />

        <div
            class="grid items-start gap-5 lg:min-h-0 lg:flex-1 lg:grid-cols-[1fr_300px] lg:grid-rows-[minmax(0,1fr)]"
        >
            <form
                class="flex flex-col gap-4 rounded-xl border border-linea bg-white p-5 lg:max-h-full lg:overflow-y-auto"
                novalidate
                @submit.prevent="registrar"
            >
                <CamposEmpresa
                    v-model:form="form"
                    prefijo="registrar"
                    :opciones="opciones"
                    :errores="form.errors"
                />

                <fieldset class="flex flex-col gap-3">
                    <legend
                        class="mb-1 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                    >
                        Primera medición
                    </legend>
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="form.asignar_medicion"
                            type="checkbox"
                            class="size-4 accent-marca"
                        />
                        Asignar la primera medición al registrar
                        <span class="text-tinta-suave">(opcional)</span>
                    </label>
                    <div
                        v-if="form.asignar_medicion"
                        class="grid gap-x-4 gap-y-3 sm:grid-cols-[1fr_12rem]"
                    >
                        <Campo
                            obligatorio
                            etiqueta="Diagnóstico"
                            para="registrar-diagnostico"
                            :error="form.errors.diagnostico_id"
                            :ayuda="
                                form.sector_id !== '' &&
                                diagnosticos.length === 0
                                    ? 'El sector no tiene diagnósticos publicados. Asígnala después desde la ficha.'
                                    : 'Se ofrecen los diagnósticos publicados del sector.'
                            "
                        >
                            <Seleccion
                                id="registrar-diagnostico"
                                v-model="form.diagnostico_id"
                                :disabled="diagnosticos.length === 0"
                                :invalida="!!form.errors.diagnostico_id"
                            >
                                <option value="" disabled>
                                    {{
                                        form.sector_id === ''
                                            ? 'Elige primero el sector'
                                            : 'Selecciona el diagnóstico'
                                    }}
                                </option>
                                <option
                                    v-for="d in diagnosticos"
                                    :key="d.id"
                                    :value="d.id"
                                >
                                    {{ d.nombre }} · v{{ d.version }}
                                </option>
                            </Seleccion>
                        </Campo>
                        <Campo
                            etiqueta="Fecha límite"
                            para="registrar-fecha"
                            opcional
                            :error="form.errors.fecha_limite"
                        >
                            <Entrada
                                id="registrar-fecha"
                                v-model="form.fecha_limite"
                                type="date"
                                :min="hoy"
                                :invalida="!!form.errors.fecha_limite"
                            />
                        </Campo>
                    </div>
                </fieldset>

                <div class="mt-auto flex justify-end gap-3 pt-2">
                    <Boton variante="secundario" :href="rutas.empresas.lista()">
                        Cancelar
                    </Boton>
                    <Boton type="submit" :cargando="form.processing">
                        Registrar empresa
                    </Boton>
                </div>
            </form>

            <aside class="rounded-xl border border-linea bg-white p-5 text-sm">
                <h2 class="font-semibold">Qué pasa al registrar</h2>
                <ol class="mt-3 flex flex-col gap-2.5">
                    <li>
                        <strong>1.</strong> Se crea la empresa y su cuenta de
                        usuario con rol Empresa.
                    </li>
                    <li>
                        <strong>2.</strong> El usuario recibe un correo con un
                        enlace para crear su contraseña. Tú no la escribes.
                    </li>
                    <li>
                        <strong>3.</strong> Si asignaste la primera medición,
                        aparece en su inicio al entrar.
                    </li>
                    <li>
                        <strong>4.</strong> La empresa aparece en Empresas, sin
                        puntaje, hasta que responda.
                    </li>
                </ol>
                <p
                    class="mt-4 rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave"
                >
                    Las empresas también pueden registrarse solas desde la
                    pantalla pública "Registra tu empresa". En ese caso quedan
                    en Empresas sin medición asignada.
                </p>
            </aside>
        </div>
    </div>
</template>
