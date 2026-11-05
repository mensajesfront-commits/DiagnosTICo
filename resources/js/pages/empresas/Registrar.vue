<script setup lang="ts">
/**
 * A3.2 · Registrar empresa (HU-032).
 *
 * Datos de la empresa y de su usuario principal (`CamposEmpresa`) y, si se
 * quiere, la primera medición con un diagnóstico publicado del sector
 * (RN-014). Al registrar, el usuario recibe un correo para crear su
 * contraseña; el Administrador no la escribe (RN-006).
 *
 * Diseño: la primera medición se activa con un interruptor y el diagnóstico
 * se elige en tarjetas, como en A3.1b. "Qué pasa al registrar" es una línea
 * de pasos.
 *
 * A la derecha, "Qué pasa al registrar". En pantallas grandes la página no
 * se desplaza: los campos van en tres columnas y, si la ventana es baja, el
 * formulario baja y sube en su caja.
 */
import { Head, useForm } from '@inertiajs/vue3';
import {
    Building2,
    Check,
    ClipboardList,
    FileCheck2,
    Lightbulb,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Entrada from '@/components/base/Entrada.vue';
import Interruptor from '@/components/base/Interruptor.vue';
import CamposEmpresa from '@/components/empresas/CamposEmpresa.vue';
import type { FormularioEmpresa } from '@/components/empresas/CamposEmpresa.vue';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
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

const pasos = [
    {
        titulo: 'Se crea la cuenta',
        texto: 'La empresa y su usuario principal, con rol Empresa.',
    },
    {
        titulo: 'Le llega un correo',
        texto: 'Con un enlace para crear su contraseña. Tú no la escribes.',
    },
    {
        titulo: 'Ve su medición',
        texto: 'Si asignaste la primera medición, aparece en su inicio al entrar.',
    },
    {
        titulo: 'Aparece en Empresas',
        texto: 'Sin puntaje, hasta que responda.',
    },
];

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
            descripcion="Crea la empresa con su usuario principal. El usuario crea su contraseña con el enlace del correo."
            :volver="{ href: rutas.empresas.lista(), texto: 'Empresas' }"
        />

        <div
            class="grid items-start gap-5 lg:min-h-0 lg:flex-1 lg:grid-cols-[minmax(0,1fr)_300px] lg:grid-rows-[minmax(0,1fr)]"
        >
            <form
                class="flex flex-col rounded-2xl border border-linea bg-white lg:max-h-full lg:overflow-y-auto"
                novalidate
                @submit.prevent="registrar"
            >
                <div class="flex flex-col gap-5 p-5">
                    <CamposEmpresa
                        v-model:form="form"
                        prefijo="registrar"
                        :opciones="opciones"
                        :errores="form.errors"
                    />
                </div>

                <section class="flex flex-col gap-3 border-t border-linea p-5">
                    <h2
                        id="titulo-primera"
                        class="flex items-center gap-2 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                    >
                        <span
                            class="flex size-6 items-center justify-center rounded-md bg-marca-suave text-marca"
                            aria-hidden="true"
                        >
                            <ClipboardList class="size-3.5" />
                        </span>
                        Primera medición
                        <span class="font-normal normal-case">(opcional)</span>
                    </h2>
                    <div class="rounded-xl border border-linea px-4 py-1">
                        <Interruptor
                            id="registrar-asignar"
                            v-model="form.asignar_medicion"
                        >
                            <span class="font-medium">
                                Asignar la primera medición al registrar
                            </span>
                            <span class="block text-xs text-tinta-suave">
                                {{
                                    form.asignar_medicion
                                        ? 'La verá en su inicio al entrar por primera vez.'
                                        : 'Puedes asignarla después desde la ficha de la empresa.'
                                }}
                            </span>
                        </Interruptor>
                    </div>

                    <template v-if="form.asignar_medicion">
                        <p
                            v-if="form.sector_id === ''"
                            class="rounded-xl border border-dashed border-linea-fuerte px-4 py-4 text-center text-sm text-tinta-suave"
                        >
                            Elige primero el sector de la empresa.
                        </p>
                        <p
                            v-else-if="diagnosticos.length === 0"
                            class="rounded-xl border border-dashed border-linea-fuerte px-4 py-4 text-center text-sm text-tinta-suave"
                        >
                            El sector no tiene diagnósticos publicados. Asígnala
                            después desde la ficha.
                        </p>
                        <div
                            v-else
                            class="grid gap-2.5 sm:grid-cols-2"
                            role="radiogroup"
                            aria-labelledby="titulo-primera"
                        >
                            <label
                                v-for="d in diagnosticos"
                                :key="d.id"
                                :class="
                                    cn(
                                        'flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 text-sm transition has-focus-visible:ring-2 has-focus-visible:ring-marca/30',
                                        form.diagnostico_id === d.id
                                            ? 'border-marca bg-marca-suave/40'
                                            : 'border-linea-fuerte hover:border-marca/40',
                                    )
                                "
                            >
                                <input
                                    v-model="form.diagnostico_id"
                                    type="radio"
                                    name="registrar-diagnostico"
                                    :value="d.id"
                                    class="sr-only"
                                />
                                <span
                                    :class="
                                        cn(
                                            'flex size-9 shrink-0 items-center justify-center rounded-xl',
                                            form.diagnostico_id === d.id
                                                ? 'bg-marca text-white'
                                                : 'bg-exito-suave text-exito',
                                        )
                                    "
                                    aria-hidden="true"
                                >
                                    <Check
                                        v-if="form.diagnostico_id === d.id"
                                        class="size-4"
                                    />
                                    <FileCheck2 v-else class="size-4" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-medium">
                                        {{ d.nombre }}
                                    </span>
                                    <span class="text-xs text-tinta-suave">
                                        v{{ d.version }} publicada
                                    </span>
                                </span>
                            </label>
                        </div>
                        <p
                            v-if="form.errors.diagnostico_id"
                            class="text-xs text-aviso"
                            role="alert"
                        >
                            {{ form.errors.diagnostico_id }}
                        </p>
                        <div v-if="diagnosticos.length" class="sm:w-56">
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
                    </template>
                </section>

                <div
                    class="mt-auto flex flex-wrap justify-end gap-3 rounded-b-2xl border-t border-linea bg-lienzo/40 px-5 py-4"
                >
                    <Boton variante="secundario" :href="rutas.empresas.lista()">
                        Cancelar
                    </Boton>
                    <Boton
                        type="submit"
                        class="gap-1.5"
                        :cargando="form.processing"
                    >
                        <Building2 class="size-4" aria-hidden="true" />
                        Registrar empresa
                    </Boton>
                </div>
            </form>

            <aside
                class="rounded-2xl border border-linea bg-linear-to-br from-marca-suave/70 to-white p-5 text-sm"
            >
                <h2 class="font-semibold">Qué pasa al registrar</h2>
                <ol class="mt-4 flex flex-col">
                    <li
                        v-for="(paso, i) in pasos"
                        :key="paso.titulo"
                        class="relative flex gap-3 pb-4 last:pb-0"
                    >
                        <span
                            v-if="i < pasos.length - 1"
                            class="absolute top-7 bottom-0 left-3 w-px bg-marca/20"
                            aria-hidden="true"
                        />
                        <span
                            class="relative flex size-6 shrink-0 items-center justify-center rounded-full bg-white font-mono text-xs font-semibold text-marca ring-1 ring-marca/30"
                            aria-hidden="true"
                        >
                            {{ i + 1 }}
                        </span>
                        <div>
                            <p class="font-medium">{{ paso.titulo }}</p>
                            <p class="mt-0.5 text-xs text-tinta-suave">
                                {{ paso.texto }}
                            </p>
                        </div>
                    </li>
                </ol>
                <p
                    class="mt-4 flex gap-2 rounded-xl bg-white/70 p-3 text-xs text-tinta-suave"
                >
                    <Lightbulb
                        class="size-4 shrink-0 text-alerta"
                        aria-hidden="true"
                    />
                    <span>
                        Las empresas también pueden registrarse solas desde la
                        pantalla pública «Registra tu empresa». En ese caso
                        quedan en Empresas sin medición asignada.
                    </span>
                </p>
            </aside>
        </div>
    </div>
</template>
