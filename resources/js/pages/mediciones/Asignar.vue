<script setup lang="ts">
/**
 * A3.1b · Asignar medición (HU-036) y A3.1c · Reemplazar la pendiente.
 *
 * - Se elige la empresa (con `?empresa=` llega elegida) y un diagnóstico
 *   publicado de su sector (RN-014). Los borradores aparecen pero no se
 *   pueden elegir (CA-004).
 * - Fecha límite y mensaje opcionales (CA-003).
 * - "Así lo verá la empresa": la tarjeta que verá en su inicio (CA-005).
 * - Aviso por correo opcional (CA-006).
 * - Si la empresa ya tiene una medición pendiente, se avisa y al asignar se
 *   pide confirmar que la nueva la reemplaza (CA-002, A3.1c). Si la
 *   pendiente está vencida, también se puede solo darle una nueva fecha y
 *   conservar sus respuestas (HU-009).
 *
 * - "Ver y editar el correo" abre A3.1e (`EditorCorreo`) con la plantilla
 *   general y los datos de esta asignación; "Guardar correo" lo deja para
 *   esta medición (y, si se marca, como plantilla).
 *
 * Envía: POST /mediciones con empresa_id, diagnostico_id, fecha_limite,
 * mensaje, aviso, reemplazar y, si se editó el correo, correo_asunto,
 * correo_cuerpo y guardar_plantilla.
 */
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Entrada from '@/components/base/Entrada.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import Modal from '@/components/base/Modal.vue';
import BuscadorEmpresa from '@/components/empresas/BuscadorEmpresa.vue';
import EditorCorreo from '@/components/empresas/EditorCorreo.vue';
import ModalCambiarFecha from '@/components/empresas/ModalCambiarFecha.vue';
import ModalReemplazarMedicion from '@/components/empresas/ModalReemplazarMedicion.vue';
import { fechaCorreo } from '@/lib/correo';
import type { ValoresCorreo } from '@/lib/correo';
import { diaYMes, fechaLocal, haceCuanto } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { DiagnosticosDelSector, EmpresaAsignable } from '@/types/empresas';

const props = defineProps<{
    empresas: EmpresaAsignable[];
    /** Por sector: publicados y borradores. */
    diagnosticos: Record<string, DiagnosticosDelSector>;
    empresaInicial: number | null;
    /** Fecha límite más cercana antes de elegir la empresa (3 días). */
    fechaMinima: string;
    /** Plantilla general del correo de aviso (A3.1e). */
    plantilla: { asunto: string; cuerpo: string };
}>();

const MAX_MENSAJE = 500;
/** Fecha límite más cercana: 3 días, en la hora de la empresa (servidor). */
const hoy = computed(() => empresa.value?.fecha_minima ?? props.fechaMinima);

const form = useForm({
    empresa_id: (props.empresas.some((e) => e.id === props.empresaInicial)
        ? props.empresaInicial
        : '') as number | '',
    diagnostico_id: '' as number | '',
    fecha_limite: '',
    mensaje: '',
    aviso: true,
    reemplazar: false,
    correo_asunto: '',
    correo_cuerpo: '',
    guardar_plantilla: false,
});

const empresa = computed(
    () => props.empresas.find((e) => e.id === form.empresa_id) ?? null,
);

const delSector = computed<DiagnosticosDelSector>(
    () =>
        (empresa.value && props.diagnosticos[empresa.value.sector_id]) || {
            publicados: [],
            borradores: [],
        },
);

const diagnostico = computed(
    () =>
        delSector.value.publicados.find((d) => d.id === form.diagnostico_id) ??
        null,
);

// Al cambiar de empresa: con un solo diagnóstico publicado, queda elegido.
watch(
    empresa,
    () => {
        const publicados = delSector.value.publicados;
        form.diagnostico_id = publicados.length === 1 ? publicados[0].id : '';
        form.clearErrors();
    },
    { immediate: true },
);

const volver = computed(() =>
    props.empresaInicial
        ? {
              href: rutas.empresas.ver(props.empresaInicial),
              texto: 'Ficha de la empresa',
          }
        : { href: rutas.empresas.lista(), texto: 'Empresas' },
);

// --- Reemplazo (A3.1c) -------------------------------------------------------
const modalReemplazar = ref(false);
const modalFecha = ref(false);

function asignar(reemplazar = false): void {
    if (empresa.value?.pendiente && !reemplazar) {
        modalReemplazar.value = true;

        return;
    }

    form.reemplazar = reemplazar;
    form.transform((d) => ({
        ...d,
        fecha_limite: d.fecha_limite || null,
        mensaje: d.mensaje.trim() || null,
        // Sin editar, el servidor usa la plantilla general.
        correo_asunto: d.aviso && correoEditado.value ? d.correo_asunto : null,
        correo_cuerpo: d.aviso && correoEditado.value ? d.correo_cuerpo : null,
        guardar_plantilla:
            d.aviso && correoEditado.value && d.guardar_plantilla,
    })).post(rutas.mediciones.guardar(), {
        onSuccess: () => (modalReemplazar.value = false),
        onError: (errores) => {
            // Otra persona asignó una pendiente mientras tanto.
            modalReemplazar.value = !!errores.reemplazar;
        },
    });
}

// --- Correo de aviso (A3.1e) ------------------------------------------------
const modalCorreo = ref(false);
const correoEditado = ref(false);
/** Copia que se edita en el modal; "Guardar correo" la pasa al formulario. */
const borrador = reactive({ asunto: '', cuerpo: '', guardar: false });

function abrirCorreo(): void {
    borrador.asunto = correoEditado.value
        ? form.correo_asunto
        : props.plantilla.asunto;
    borrador.cuerpo = correoEditado.value
        ? form.correo_cuerpo
        : props.plantilla.cuerpo;
    borrador.guardar = form.guardar_plantilla;
    modalCorreo.value = true;
}

function guardarCorreo(): void {
    form.correo_asunto = borrador.asunto;
    form.correo_cuerpo = borrador.cuerpo;
    form.guardar_plantilla = borrador.guardar;
    correoEditado.value =
        borrador.asunto !== props.plantilla.asunto ||
        borrador.cuerpo !== props.plantilla.cuerpo;
    modalCorreo.value = false;
}

const para = computed(() =>
    empresa.value?.principal
        ? `${empresa.value.principal.email} (${empresa.value.principal.name} · usuario principal)`
        : 'La empresa no tiene usuario principal',
);

const valoresCorreo = computed<ValoresCorreo>(() => ({
    nombre_usuario: empresa.value?.principal?.name ?? '',
    empresa: empresa.value?.nombre ?? '',
    diagnostico: diagnostico.value?.nombre ?? '',
    numero_medicion: String(numero.value ?? ''),
    mensaje_admin: form.mensaje.trim(),
    fecha_limite: fechaCorreo(form.fecha_limite),
}));

const probando = ref(false);

function probarCorreo(): void {
    router.post(
        rutas.mediciones.probarCorreo(),
        {
            asunto: borrador.asunto,
            cuerpo: borrador.cuerpo,
            variables: valoresCorreo.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (probando.value = true),
            onFinish: () => (probando.value = false),
        },
    );
}

const numero = computed(() =>
    empresa.value
        ? (empresa.value.pendiente?.numero ?? empresa.value.siguiente)
        : null,
);

const estadoPendiente: Record<string, string> = {
    no_iniciada: 'No iniciada',
    en_curso: 'En curso',
    enviada: 'Enviada',
    vencida: 'Vencida',
};
</script>

<template>
    <Head title="Asignar medición" />

    <div class="flex flex-col gap-5 p-6 lg:h-dvh lg:overflow-hidden">
        <EncabezadoPagina titulo="Asignar medición" :volver="volver" />

        <div
            class="grid items-start gap-5 lg:min-h-0 lg:flex-1 lg:grid-cols-[1fr_320px] lg:grid-rows-[minmax(0,1fr)]"
        >
            <form
                class="flex flex-col gap-4 rounded-xl border border-linea bg-white p-5 lg:max-h-full lg:overflow-y-auto"
                novalidate
                @submit.prevent="asignar()"
            >
                <Campo
                    obligatorio
                    etiqueta="Empresa"
                    para="asignar-empresa"
                    ayuda="Escribe para buscar por nombre. Junto a cada empresa ves su sector y si ya tiene una medición pendiente."
                    :error="form.errors.empresa_id"
                >
                    <BuscadorEmpresa
                        id="asignar-empresa"
                        v-model="form.empresa_id"
                        :empresas="empresas"
                        :invalida="!!form.errors.empresa_id"
                    />
                </Campo>

                <fieldset class="flex flex-col gap-2">
                    <legend class="mb-1.5 text-xs font-medium">
                        Diagnóstico
                        <span class="text-aviso" aria-hidden="true">*</span
                        ><template v-if="empresa">
                            · publicados del sector
                            {{ empresa.sector }}</template
                        >
                    </legend>
                    <p v-if="!empresa" class="text-sm text-tinta-suave">
                        Elige primero la empresa.
                    </p>
                    <p
                        v-else-if="
                            delSector.publicados.length === 0 &&
                            delSector.borradores.length === 0
                        "
                        class="text-sm text-tinta-suave"
                    >
                        El sector {{ empresa.sector }} todavía no tiene
                        diagnósticos.
                        <a
                            :href="rutas.diagnosticos.sector(empresa.sector_id)"
                            class="text-marca underline"
                            >Crear uno en Diagnósticos</a
                        >.
                    </p>
                    <template v-else>
                        <label
                            v-for="d in delSector.publicados"
                            :key="d.id"
                            :class="
                                cn(
                                    'flex cursor-pointer items-start gap-3 rounded-lg border px-4 py-3 text-sm',
                                    form.diagnostico_id === d.id
                                        ? 'border-2 border-marca'
                                        : 'border-linea-fuerte hover:bg-lienzo',
                                )
                            "
                        >
                            <input
                                v-model="form.diagnostico_id"
                                type="radio"
                                name="diagnostico"
                                :value="d.id"
                                class="mt-0.5 size-4 accent-marca"
                            />
                            <span>
                                <span class="block font-medium">
                                    {{ d.nombre }} · v{{ d.version }}
                                </span>
                                <span class="text-xs text-tinta-suave">
                                    {{ d.categorias }} categorías ·
                                    {{ d.preguntas }} preguntas
                                </span>
                            </span>
                        </label>
                        <div
                            v-for="d in delSector.borradores"
                            :key="`b-${d.id}`"
                            class="flex items-start gap-3 rounded-lg border border-dashed border-linea-fuerte bg-lienzo px-4 py-3 text-sm text-tinta-suave"
                        >
                            <input
                                type="radio"
                                name="diagnostico"
                                disabled
                                class="mt-0.5 size-4"
                                :aria-label="`${d.nombre} (borrador)`"
                            />
                            <span>
                                <span class="block font-medium">
                                    {{ d.nombre }} · borrador
                                </span>
                                <span class="text-xs">
                                    🔒 No se puede asignar hasta que se publique
                                </span>
                            </span>
                        </div>
                    </template>
                    <p
                        v-if="form.errors.diagnostico_id"
                        class="text-xs text-aviso"
                        role="alert"
                    >
                        {{ form.errors.diagnostico_id }}
                    </p>
                </fieldset>

                <div class="grid gap-x-4 gap-y-3 sm:grid-cols-[12rem_1fr]">
                    <Campo
                        etiqueta="Fecha límite"
                        para="asignar-fecha"
                        opcional
                        :error="form.errors.fecha_limite"
                    >
                        <Entrada
                            id="asignar-fecha"
                            v-model="form.fecha_limite"
                            type="date"
                            :min="hoy"
                            :invalida="!!form.errors.fecha_limite"
                        />
                    </Campo>
                    <Campo
                        etiqueta="Mensaje para la empresa"
                        para="asignar-mensaje"
                        opcional
                        :contador="`${form.mensaje.length}/${MAX_MENSAJE}`"
                        :error="form.errors.mensaje"
                    >
                        <AreaTexto
                            id="asignar-mensaje"
                            v-model="form.mensaje"
                            rows="2"
                            :maxlength="MAX_MENSAJE"
                            placeholder="Ej. Han pasado 6 meses desde tu último diagnóstico."
                        />
                    </Campo>
                </div>

                <div
                    class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm"
                >
                    <label class="flex items-center gap-2">
                        <input
                            v-model="form.aviso"
                            type="checkbox"
                            class="size-4 accent-marca"
                        />
                        Enviar también aviso por correo
                    </label>
                    <button
                        v-if="form.aviso"
                        type="button"
                        class="text-marca underline underline-offset-2 hover:text-marca-hover disabled:text-tinta-suave disabled:no-underline"
                        :disabled="!empresa || !diagnostico"
                        :title="
                            !empresa || !diagnostico
                                ? 'Elige la empresa y el diagnóstico'
                                : undefined
                        "
                        @click="abrirCorreo"
                    >
                        Ver y editar el correo
                    </button>
                    <span
                        v-if="form.aviso && correoEditado"
                        class="text-xs text-tinta-suave"
                    >
                        · editado para esta medición<template
                            v-if="form.guardar_plantilla"
                        >
                            y como plantilla</template
                        >
                    </span>
                </div>

                <p
                    v-if="empresa?.solicitud"
                    class="rounded-md bg-aviso-suave px-3 py-2 text-xs text-aviso"
                    role="status"
                >
                    <strong
                        >{{ empresa.nombre }} pidió una medición nueva
                        {{ haceCuanto(empresa.solicitud) }}.</strong
                    >
                    Al asignarla, la solicitud queda atendida.
                </p>
                <p
                    v-if="empresa?.pendiente"
                    class="rounded-md bg-lienzo px-3 py-2 text-xs text-tinta"
                    role="status"
                >
                    ⚠ Solo puede haber una medición pendiente por empresa.
                    {{ empresa.nombre }} ya tiene la
                    <strong
                        >medición {{ empresa.pendiente.numero }} ({{
                            estadoPendiente[empresa.pendiente.estado]
                        }}<template v-if="empresa.pendiente.vence"
                            >, vence
                            {{
                                diaYMes(fechaLocal(empresa.pendiente.vence))
                            }}</template
                        >)</strong
                    >: si continúas, la nueva la reemplaza. Te pediremos
                    confirmación.
                    <template v-if="empresa.pendiente.estado === 'vencida'">
                        Si solo necesita más tiempo,
                        <button
                            type="button"
                            class="text-marca underline underline-offset-2"
                            @click="modalFecha = true"
                        >
                            dale una nueva fecha</button
                        >.
                    </template>
                </p>
                <p
                    v-if="form.errors.reemplazar"
                    class="text-xs text-aviso"
                    role="alert"
                >
                    {{ form.errors.reemplazar }}
                </p>

                <div class="mt-auto flex justify-end gap-3 pt-2">
                    <Boton variante="secundario" :href="volver.href">
                        Cancelar
                    </Boton>
                    <Boton
                        type="submit"
                        :disabled="!empresa || !diagnostico"
                        :cargando="form.processing && !modalReemplazar"
                    >
                        {{
                            empresa?.pendiente
                                ? `Reemplazar medición ${empresa.pendiente.numero}…`
                                : 'Asignar medición'
                        }}
                    </Boton>
                </div>
            </form>

            <aside class="rounded-xl border border-linea bg-white p-5 text-sm">
                <h2 class="flex items-center gap-2 font-semibold">
                    Así lo verá la empresa
                    <span
                        class="rounded bg-lienzo-oscuro px-2 py-0.5 text-xs font-normal text-tinta-suave"
                    >
                        Vista previa
                    </span>
                </h2>
                <div class="mt-3 rounded-lg bg-lienzo p-4">
                    <template v-if="empresa && diagnostico">
                        <span
                            class="inline-flex rounded bg-white px-2 py-0.5 text-xs text-tinta-suave"
                        >
                            ○ Medición {{ numero }} · No iniciada
                        </span>
                        <p class="mt-2 font-semibold">
                            {{ diagnostico.nombre }}
                        </p>
                        <p
                            v-if="form.mensaje.trim()"
                            class="mt-2 text-xs whitespace-pre-line text-tinta"
                        >
                            “{{ form.mensaje.trim() }}”
                        </p>
                        <p class="mt-2 text-xs text-tinta-suave">
                            {{
                                form.fecha_limite
                                    ? `Fecha límite: ${diaYMes(fechaLocal(form.fecha_limite))}`
                                    : 'Sin fecha límite'
                            }}
                        </p>
                        <span
                            class="mt-3 inline-flex h-9 items-center rounded-md bg-marca px-4 text-xs font-medium text-white"
                            aria-hidden="true"
                        >
                            Iniciar diagnóstico
                        </span>
                    </template>
                    <p v-else class="text-xs text-tinta-suave">
                        Elige la empresa y el diagnóstico para verla.
                    </p>
                </div>
                <p class="mt-3 text-xs text-tinta-suave">
                    Vista previa (no interactiva). Aparece en el inicio de la
                    empresa. Tú le avisas por tu canal habitual, o por correo si
                    marcas la opción.
                </p>
            </aside>
        </div>
    </div>

    <ModalReemplazarMedicion
        v-if="empresa?.pendiente && diagnostico"
        v-model:abierto="modalReemplazar"
        :empresa="empresa.nombre"
        :actual="empresa.pendiente"
        :nueva="{
            diagnostico: diagnostico.nombre,
            version: diagnostico.version,
            fecha_limite: form.fecha_limite,
        }"
        :procesando="form.processing"
        @confirmar="asignar(true)"
    />

    <Modal
        v-model:abierto="modalCorreo"
        ancho="xl"
        titulo="Correo de aviso de la medición"
    >
        <EditorCorreo
            v-model:asunto="borrador.asunto"
            v-model:cuerpo="borrador.cuerpo"
            v-model:guardar-plantilla="borrador.guardar"
            :para="para"
            :valores="valoresCorreo"
        />
        <template #pie>
            <Boton
                variante="secundario"
                class="mr-auto"
                :disabled="!borrador.asunto || !borrador.cuerpo"
                :cargando="probando"
                @click="probarCorreo"
            >
                Enviarme un correo de prueba
            </Boton>
            <Boton variante="secundario" @click="modalCorreo = false">
                Volver
            </Boton>
            <Boton
                :disabled="!borrador.asunto.trim() || !borrador.cuerpo.trim()"
                @click="guardarCorreo"
            >
                Guardar correo
            </Boton>
        </template>
    </Modal>

    <ModalCambiarFecha
        v-if="empresa?.pendiente"
        v-model:abierto="modalFecha"
        :medicion="empresa.pendiente"
        :empresa="empresa.nombre"
    />
</template>
