<script setup lang="ts">
/**
 * A2.1b · Agregar pregunta y A2.1e · Editar pregunta (HU-024, HU-025).
 *
 * - Tipo: abierta, opción única o selección múltiple. Pasar a abierta quita
 *   las opciones.
 * - Opciones con puntaje 0–100, para subir, bajar o quitar; mínimo 2.
 * - Selección múltiple: muestra en vivo cuánto suma si marcan todas, sin
 *   contar la opción que excluye a las demás. Debe sumar exactamente 100
 *   para publicar (RN-012): en verde si da 100, en ámbar si falta y en rojo
 *   si se pasa.
 * - Indicación para responder: obligatoria; la empresa la ve.
 * - "Qué debe tener en cuenta la IA" por opción (máx. 200, opcional): la
 *   empresa no lo ve; solo se usa si elige esa opción (RN-013). En una
 *   abierta, un único criterio de calificación.
 * - Al editar, "Eliminar pregunta" abre la confirmación.
 *
 * Diseño: arriba, el texto y la indicación lado a lado; luego el tipo y
 * "Obligatoria". Cada opción va en su tarjeta: letra, texto a lo ancho,
 * puntaje, subir/bajar/quitar y, debajo, su "Ten en cuenta" para la IA. Al
 * final, la opción que excluye a las demás y la que pide escribir cuál.
 *
 * Envía: POST /diagnosticos/{id}/preguntas o PUT …/preguntas/{pregunta}.
 */
import { useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    Lightbulb,
    MessageSquarePlus,
    Pencil,
    Plus,
    Sparkles,
    X,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type {
    OpcionEditor,
    ParteEditor,
    PreguntaEditor,
    TipoPregunta,
} from '@/types/editor';

const props = defineProps<{
    diagnosticoId: number;
    parte: ParteEditor;
    /** null = agregar. */
    pregunta: PreguntaEditor | null;
}>();

const emit = defineEmits<{ eliminar: [] }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const MAX_TEN_EN_CUENTA = 200;
const MAX_OPCIONES = 10;
const LETRAS = 'ABCDEFGHIJ';

/** En el formulario, la indicación para la IA siempre es texto. */
type OpcionForm = Omit<OpcionEditor, 'ten_en_cuenta'> & {
    ten_en_cuenta: string;
};

const vacia = (): OpcionForm => ({
    id: null,
    texto: '',
    puntaje: 0,
    ten_en_cuenta: '',
    exclusiva: false,
    con_texto: false,
});

/** "Ninguna", "Ninguno", "Nada": se marcan solas como excluyentes. */
const pareceNinguna = (texto: string) =>
    /^(ningun[oa]?|nada)\b/i.test(texto.trim());

/** Índice de la opción que excluye a las demás, o -1. */
const exclusiva = computed({
    get: () => form.opciones.findIndex((o) => o.exclusiva),
    set: (i: number) =>
        form.opciones.forEach((o, j) => (o.exclusiva = j === i)),
});

/** "Otro", "Otra", "Otros": piden escribir cuál. */
const pareceOtro = (texto: string) => /^otr[oa]s?\b/i.test(texto.trim());

/** Índice de la opción "Otro" que pide escribir cuál, o -1. */
const conTexto = computed({
    get: () => form.opciones.findIndex((o) => o.con_texto),
    set: (i: number) =>
        form.opciones.forEach((o, j) => (o.con_texto = j === i)),
});

function revisarNinguna(i: number): void {
    if (
        form.tipo === 'seleccion_multiple' &&
        exclusiva.value === -1 &&
        pareceNinguna(form.opciones[i].texto)
    ) {
        exclusiva.value = i;
    }

    if (conTexto.value === -1 && pareceOtro(form.opciones[i].texto)) {
        conTexto.value = i;
    }
}

const form = useForm({
    parte_id: props.parte.id,
    texto: '',
    tipo: 'opcion_unica' as TipoPregunta,
    indicacion: '',
    criterio_ia: '',
    obligatoria: true,
    opciones: [vacia(), vacia()] as OpcionForm[],
});

function cargar(): void {
    const p = props.pregunta;
    form.clearErrors();
    form.parte_id = props.parte.id;
    form.texto = p?.texto ?? '';
    form.tipo = p?.tipo ?? 'opcion_unica';
    form.indicacion = p?.indicacion ?? '';
    form.criterio_ia = p?.criterio_ia ?? '';
    form.obligatoria = p?.obligatoria ?? true;
    form.opciones = p
        ? p.opciones.map((o) => ({
              ...o,
              ten_en_cuenta: o.ten_en_cuenta ?? '',
          }))
        : [vacia(), vacia()];
}

// `immediate`: el modal se crea ya abierto.
watch(abierto, (valor) => valor && cargar(), { immediate: true });

const tipos = [
    { valor: 'abierta' as const, etiqueta: 'Abierta' },
    { valor: 'opcion_unica' as const, etiqueta: 'Opción única' },
    { valor: 'seleccion_multiple' as const, etiqueta: 'Selección múltiple' },
];

const conOpciones = computed(() => form.tipo !== 'abierta');

watch(
    () => form.tipo,
    (tipo) => {
        if (tipo !== 'abierta' && form.opciones.length < 2) {
            form.opciones = [vacia(), vacia()];
        }
    },
);

const ayudaTipo = computed(() => {
    const n = form.opciones.length;
    const quitar = `Si cambias a Abierta se quitarán las ${n} opciones.`;

    return {
        abierta:
            'La empresa responde con sus palabras (máx. 1000 caracteres); la IA la califica de 0 a 100.',
        opcion_unica: `La empresa elige una opción. ${quitar}`,
        seleccion_multiple: `La empresa puede marcar varias opciones y se suman sus puntajes. ${quitar}`,
    }[form.tipo];
});

/** Con todas marcadas, sin la opción que excluye a las demás («Ninguna»). */
const sumaMaxima = computed(() =>
    form.opciones
        .filter((o) => !o.exclusiva)
        .reduce((s, o) => s + (Number(o.puntaje) || 0), 0),
);

const estadoSuma = computed(() => {
    const suma = sumaMaxima.value;

    if (suma === 100) {
        return {
            clase: 'bg-exito-suave text-exito',
            icono: '✓',
            texto: 'Si marca todas llega justo a 100.',
        };
    }

    return suma < 100
        ? {
              clase: 'bg-alerta-suave text-alerta',
              icono: '⚠',
              texto: `Faltan ${100 - suma} puntos: sube algún puntaje para que sume 100.`,
          }
        : {
              clase: 'bg-aviso-suave text-aviso',
              icono: '✗',
              texto: `Se pasa por ${suma - 100} puntos: baja algún puntaje para que sume 100.`,
          };
});

function mover(i: number, paso: -1 | 1): void {
    const lista = [...form.opciones];
    [lista[i], lista[i + paso]] = [lista[i + paso], lista[i]];
    form.opciones = lista;
}

function quitar(i: number): void {
    form.opciones = form.opciones.filter((_, j) => j !== i);
}

const conIndicacion = computed(
    () => form.opciones.filter((o) => o.ten_en_cuenta.trim()).length,
);

/** La primera opción con indicación, para "Así lo verá la IA". */
const ejemplo = computed(() => {
    const i = form.opciones.findIndex((o) => o.ten_en_cuenta.trim());

    return i >= 0 ? { letra: LETRAS[i], opcion: form.opciones[i] } : null;
});

const numero = computed(() =>
    props.pregunta
        ? props.parte.preguntas.findIndex((p) => p.id === props.pregunta?.id) +
          1
        : props.parte.preguntas.length + 1,
);

const errores = computed(
    () => form.errors as Record<string, string | undefined>,
);

function guardar(): void {
    const opciones = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (abierto.value = false),
    };

    form.transform((d) => ({
        ...d,
        criterio_ia: d.tipo === 'abierta' ? d.criterio_ia.trim() || null : null,
        opciones:
            d.tipo === 'abierta'
                ? []
                : d.opciones.map((o) => ({
                      ...o,
                      exclusiva: d.tipo === 'seleccion_multiple' && o.exclusiva,
                  })),
    }));

    if (props.pregunta) {
        form.put(
            rutas.diagnosticos.pregunta(props.diagnosticoId, props.pregunta.id),
            opciones,
        );
    } else {
        form.post(rutas.diagnosticos.preguntas(props.diagnosticoId), opciones);
    }
}

const botonIcono =
    'flex size-8 items-center justify-center rounded-lg text-tinta-suave transition hover:bg-lienzo hover:text-tinta disabled:pointer-events-none disabled:opacity-30';

const claseSelect =
    'h-9 w-full rounded-lg border border-linea-fuerte bg-white px-2 text-xs focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none';
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        ancho="xl"
        :titulo="
            pregunta
                ? `Editar pregunta ${numero} · ${parte.nombre}`
                : `Agregar pregunta · ${parte.nombre}`
        "
        :icono="pregunta ? Pencil : MessageSquarePlus"
    >
        <template #descripcion>
            <template v-if="!pregunta">
                Será la pregunta {{ numero }} de esta categoría. Escríbela como
                la entendería tu cliente, sin palabras técnicas.
            </template>
            Los campos con <span class="text-aviso">*</span> son obligatorios.
        </template>

        <form
            id="form-pregunta"
            class="flex flex-col gap-5"
            novalidate
            @submit.prevent="guardar"
        >
            <div class="grid gap-4 lg:grid-cols-2">
                <Campo
                    obligatorio
                    etiqueta="Texto de la pregunta"
                    para="pregunta-texto"
                    :error="form.errors.texto"
                >
                    <AreaTexto
                        id="pregunta-texto"
                        v-model="form.texto"
                        rows="3"
                        maxlength="500"
                        :invalida="!!form.errors.texto"
                    />
                </Campo>
                <Campo
                    obligatorio
                    etiqueta="Indicación para responder"
                    para="pregunta-indicacion"
                    ayuda="La empresa la ve debajo de la pregunta: explica qué periodo pensar, qué elegir o cuánto escribir."
                    :error="form.errors.indicacion"
                >
                    <AreaTexto
                        id="pregunta-indicacion"
                        v-model="form.indicacion"
                        rows="3"
                        maxlength="500"
                        :invalida="!!form.errors.indicacion"
                    />
                </Campo>
            </div>

            <div
                class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3 rounded-xl bg-lienzo/60 p-3"
            >
                <div class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-xs font-medium">Tipo de respuesta</span>
                    <SelectorCompacto
                        v-model="form.tipo"
                        estilo="relleno"
                        :opciones="tipos"
                        etiqueta-accesible="Tipo de respuesta"
                    />
                    <p class="text-xs text-tinta-suave">{{ ayudaTipo }}</p>
                </div>
                <label
                    class="flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm"
                >
                    <input
                        v-model="form.obligatoria"
                        type="checkbox"
                        class="size-4 accent-marca"
                    />
                    Respuesta obligatoria para la empresa
                </label>
            </div>

            <section v-if="conOpciones" class="flex flex-col gap-3">
                <div
                    class="flex flex-wrap items-baseline justify-between gap-2"
                >
                    <h3 class="text-sm font-semibold">
                        Opciones
                        <span class="font-normal text-tinta-suave">
                            · puntaje de 0 a 100
                        </span>
                    </h3>
                    <p class="text-xs text-tinta-suave">
                        {{ conIndicacion }} de {{ form.opciones.length }} con
                        «Ten en cuenta» para la IA
                    </p>
                </div>

                <ol class="flex flex-col gap-2">
                    <li
                        v-for="(opcion, i) in form.opciones"
                        :key="i"
                        class="rounded-xl border border-linea p-3"
                    >
                        <div
                            class="flex flex-wrap items-start gap-2 sm:flex-nowrap"
                        >
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-marca-suave font-mono text-xs font-semibold text-marca"
                                aria-hidden="true"
                            >
                                {{ LETRAS[i] }}
                            </span>
                            <div
                                class="min-w-0 flex-1 basis-[calc(100%-2.75rem)] sm:basis-0"
                            >
                                <Entrada
                                    v-model="opcion.texto"
                                    maxlength="255"
                                    class="h-9"
                                    placeholder="Texto de la opción"
                                    @blur="revisarNinguna(i)"
                                    :aria-label="`Opción ${LETRAS[i]}`"
                                    :invalida="!!errores[`opciones.${i}.texto`]"
                                />
                                <p
                                    v-if="errores[`opciones.${i}.texto`]"
                                    class="mt-1 text-xs text-aviso"
                                >
                                    {{ errores[`opciones.${i}.texto`] }}
                                </p>
                            </div>
                            <div class="ml-11 w-20 shrink-0 sm:ml-0">
                                <input
                                    v-model.number="opcion.puntaje"
                                    type="number"
                                    min="0"
                                    max="100"
                                    :aria-label="`Puntaje de la opción ${LETRAS[i]}`"
                                    :aria-invalid="
                                        !!errores[`opciones.${i}.puntaje`] ||
                                        undefined
                                    "
                                    :class="
                                        cn(
                                            'h-9 w-full rounded-lg border border-linea-fuerte bg-white px-2 text-right font-mono text-sm focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none',
                                            errores[`opciones.${i}.puntaje`] &&
                                                'border-aviso',
                                        )
                                    "
                                />
                                <p
                                    v-if="errores[`opciones.${i}.puntaje`]"
                                    class="mt-1 text-xs text-aviso"
                                >
                                    {{ errores[`opciones.${i}.puntaje`] }}
                                </p>
                            </div>
                            <div class="flex shrink-0 gap-0.5 pt-0.5">
                                <button
                                    type="button"
                                    :class="botonIcono"
                                    :disabled="i === 0"
                                    :aria-label="`Subir la opción ${LETRAS[i]}`"
                                    title="Subir"
                                    @click="mover(i, -1)"
                                >
                                    <ArrowUp class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    :class="botonIcono"
                                    :disabled="i === form.opciones.length - 1"
                                    :aria-label="`Bajar la opción ${LETRAS[i]}`"
                                    title="Bajar"
                                    @click="mover(i, 1)"
                                >
                                    <ArrowDown class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    :class="
                                        cn(
                                            botonIcono,
                                            'hover:bg-aviso-suave hover:text-aviso',
                                        )
                                    "
                                    :disabled="form.opciones.length <= 2"
                                    :aria-label="`Quitar la opción ${LETRAS[i]}`"
                                    title="Quitar"
                                    @click="quitar(i)"
                                >
                                    <X class="size-4" />
                                </button>
                            </div>
                        </div>

                        <div class="mt-2 flex items-start gap-2 sm:pl-11">
                            <Lightbulb
                                class="mt-2 size-4 shrink-0 text-alerta"
                                aria-hidden="true"
                            />
                            <div class="min-w-0 flex-1">
                                <AreaTexto
                                    v-model="opcion.ten_en_cuenta"
                                    rows="1"
                                    class="field-sizing-content min-h-9 py-1.5 text-xs"
                                    :maxlength="MAX_TEN_EN_CUENTA"
                                    placeholder="Ten en cuenta para la IA (opcional): qué debe considerar si eligen esta opción"
                                    :aria-label="`Ten en cuenta para la IA de la opción ${LETRAS[i]}`"
                                />
                            </div>
                            <span
                                class="hidden w-14 shrink-0 pt-2 text-right font-mono text-[11px] text-tinta-suave sm:block"
                            >
                                {{ opcion.ten_en_cuenta.length }}/{{
                                    MAX_TEN_EN_CUENTA
                                }}
                            </span>
                        </div>
                    </li>
                </ol>

                <button
                    v-if="form.opciones.length < MAX_OPCIONES"
                    type="button"
                    class="flex h-10 items-center justify-center gap-1.5 rounded-xl border border-dashed border-linea-fuerte text-sm text-tinta-suave transition hover:border-marca hover:bg-marca-suave/40 hover:text-marca"
                    @click="form.opciones.push(vacia())"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    Agregar opción
                </button>
                <p v-if="errores.opciones" class="text-xs text-aviso">
                    {{ errores.opciones }}
                </p>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label
                        v-if="form.tipo === 'seleccion_multiple'"
                        class="flex flex-col gap-1 text-xs"
                    >
                        <span class="font-medium">
                            Opción que excluye a las demás
                        </span>
                        <select v-model.number="exclusiva" :class="claseSelect">
                            <option :value="-1">Ninguna</option>
                            <option
                                v-for="(o, i) in form.opciones"
                                :key="i"
                                :value="i"
                            >
                                {{ LETRAS[i] }} · {{ o.texto || 'sin texto' }}
                            </option>
                        </select>
                        <span class="text-tinta-suave">
                            Como «Ninguna» o «Nada»: si la empresa la marca, se
                            desmarcan las otras.
                        </span>
                    </label>

                    <label class="flex flex-col gap-1 text-xs">
                        <span class="font-medium">
                            Opción que pide escribir cuál
                        </span>
                        <select v-model.number="conTexto" :class="claseSelect">
                            <option :value="-1">Ninguna</option>
                            <option
                                v-for="(o, i) in form.opciones"
                                :key="i"
                                :value="i"
                            >
                                {{ LETRAS[i] }} · {{ o.texto || 'sin texto' }}
                            </option>
                        </select>
                        <span class="text-tinta-suave">
                            Como «Otro»: si la empresa la elige, escribe cuál
                            («¿Cuál otro canal?») y la IA lo lee.
                        </span>
                    </label>
                </div>

                <div
                    v-if="form.tipo === 'seleccion_multiple'"
                    :class="
                        cn('rounded-xl px-3 py-2 text-xs', estadoSuma.clase)
                    "
                    role="status"
                >
                    <p class="flex justify-between font-semibold">
                        Suma con todas marcadas
                        <span class="font-mono">
                            {{ sumaMaxima }} / 100 {{ estadoSuma.icono }}
                        </span>
                    </p>
                    <p class="mt-1">{{ estadoSuma.texto }}</p>
                    <p class="mt-1 opacity-80">
                        Selección múltiple: las opciones deben sumar exactamente
                        100 para publicar. No cuenta la opción que excluye a las
                        demás.
                    </p>
                </div>
                <p v-else class="text-xs text-tinta-suave">
                    Opción única: el puntaje de la pregunta es el de la opción
                    elegida. Usa 0 para la peor respuesta y 100 para la mejor.
                </p>

                <p class="text-xs text-tinta-suave">
                    «Ten en cuenta»: la empresa no lo ve. Se agrega al prompt
                    solo si elige esa opción. Puedes pedir un método, por
                    ejemplo FODA o PESTEL.
                </p>
                <div
                    v-if="ejemplo"
                    class="flex gap-2 rounded-xl border border-marca/20 bg-marca-suave/40 px-3 py-2.5 text-xs"
                >
                    <Sparkles
                        class="mt-px size-4 shrink-0 text-marca"
                        aria-hidden="true"
                    />
                    <div>
                        <p class="font-semibold text-marca">
                            Así lo verá la IA si elige la opción
                            {{ ejemplo.letra }}
                        </p>
                        <p class="mt-1">{{ form.texto || 'La pregunta' }}</p>
                        <p>
                            "{{ ejemplo.opcion.texto }}" →
                            <span class="font-mono"
                                >{{ ejemplo.opcion.puntaje }} pts</span
                            >
                        </p>
                        <p class="mt-1 text-marca">
                            <strong>Ten en cuenta:</strong>
                            {{ ejemplo.opcion.ten_en_cuenta }}
                        </p>
                    </div>
                </div>
            </section>

            <Campo
                v-else
                etiqueta="Criterio para calificarla"
                para="pregunta-criterio"
                opcional
                ayuda="La empresa no lo ve. La IA califica la respuesta de 0 a 100 con este criterio; sin él, usa el de la categoría."
                :error="form.errors.criterio_ia"
            >
                <AreaTexto
                    id="pregunta-criterio"
                    v-model="form.criterio_ia"
                    rows="4"
                    maxlength="1000"
                    placeholder="Ej. 100 si nombra una queja concreta y cómo la resuelve; 0 si no responde."
                />
            </Campo>
        </form>

        <template #pie>
            <Boton
                v-if="pregunta"
                variante="secundario"
                class="mr-auto text-aviso"
                @click="emit('eliminar')"
            >
                Eliminar pregunta
            </Boton>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                type="submit"
                form="form-pregunta"
                :cargando="form.processing"
            >
                {{ pregunta ? 'Guardar pregunta' : 'Agregar pregunta' }}
            </Boton>
        </template>
    </Modal>
</template>
