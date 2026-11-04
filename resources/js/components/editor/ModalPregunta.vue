<script setup lang="ts">
/**
 * A2.1b · Agregar pregunta y A2.1e · Editar pregunta (HU-024, HU-025).
 *
 * - Tipo: abierta, opción única o selección múltiple. Pasar a abierta quita
 *   las opciones.
 * - Opciones con puntaje 0–100, para subir, bajar o quitar; mínimo 2.
 * - Selección múltiple: muestra en vivo cuánto suma si marcan todas (debe
 *   poder llegar a 100 para publicar, RN-012).
 * - Indicación para responder: obligatoria; la empresa la ve.
 * - "Qué debe tener en cuenta la IA" por opción (máx. 200, opcional): la
 *   empresa no lo ve; solo se usa si elige esa opción (RN-013). En una
 *   abierta, un único criterio de calificación.
 * - Al editar, "Eliminar pregunta" abre la confirmación.
 *
 * Envía: POST /diagnosticos/{id}/preguntas o PUT …/preguntas/{pregunta}.
 */
import { useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, X } from '@lucide/vue';
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

const sumaMaxima = computed(() =>
    form.opciones.reduce((s, o) => s + (Number(o.puntaje) || 0), 0),
);

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
    'flex size-8 items-center justify-center rounded-md border border-linea-fuerte text-tinta-suave hover:bg-lienzo disabled:opacity-40';
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
            class="grid gap-6 lg:grid-cols-2"
            novalidate
            @submit.prevent="guardar"
        >
            <div class="flex flex-col gap-4">
                <Campo
                    obligatorio
                    etiqueta="Texto de la pregunta"
                    para="pregunta-texto"
                    :error="form.errors.texto"
                >
                    <AreaTexto
                        id="pregunta-texto"
                        v-model="form.texto"
                        rows="2"
                        maxlength="500"
                        :invalida="!!form.errors.texto"
                    />
                </Campo>

                <div class="flex flex-col gap-1.5">
                    <span class="text-xs font-medium">Tipo de respuesta</span>
                    <SelectorCompacto
                        v-model="form.tipo"
                        estilo="relleno"
                        :opciones="tipos"
                        etiqueta-accesible="Tipo de respuesta"
                    />
                    <p class="text-xs text-tinta-suave">{{ ayudaTipo }}</p>
                </div>

                <div v-if="conOpciones" class="flex flex-col gap-2">
                    <div
                        class="grid grid-cols-[1rem_1fr_6rem_6.5rem] gap-2 text-xs font-medium"
                    >
                        <span />
                        <span>Opciones</span>
                        <span>Puntaje (0–100)</span>
                    </div>
                    <div
                        v-for="(opcion, i) in form.opciones"
                        :key="i"
                        class="grid grid-cols-[1rem_1fr_6rem_6.5rem] items-start gap-2"
                    >
                        <span class="pt-2.5 text-xs text-tinta-suave">
                            {{ LETRAS[i] }}
                        </span>
                        <div>
                            <Entrada
                                v-model="opcion.texto"
                                maxlength="255"
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
                        <div>
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
                                        'h-10 w-full rounded-md border border-linea-fuerte bg-white px-3 text-right font-mono text-sm focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none',
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
                        <div class="flex gap-1 pt-1">
                            <button
                                type="button"
                                :class="botonIcono"
                                :disabled="i === 0"
                                aria-label="Subir opción"
                                @click="mover(i, -1)"
                            >
                                <ArrowUp class="size-3.5" />
                            </button>
                            <button
                                type="button"
                                :class="botonIcono"
                                :disabled="i === form.opciones.length - 1"
                                aria-label="Bajar opción"
                                @click="mover(i, 1)"
                            >
                                <ArrowDown class="size-3.5" />
                            </button>
                            <button
                                type="button"
                                :class="botonIcono"
                                :disabled="form.opciones.length <= 2"
                                aria-label="Quitar opción"
                                @click="quitar(i)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </div>
                    </div>
                    <button
                        v-if="form.opciones.length < MAX_OPCIONES"
                        type="button"
                        class="w-fit text-sm text-marca hover:underline"
                        @click="form.opciones.push(vacia())"
                    >
                        + Agregar opción
                    </button>
                    <p v-if="errores.opciones" class="text-xs text-aviso">
                        {{ errores.opciones }}
                    </p>

                    <label
                        v-if="form.tipo === 'seleccion_multiple'"
                        class="flex flex-wrap items-center gap-2 text-xs"
                    >
                        Opción que excluye a las demás
                        <select
                            v-model.number="exclusiva"
                            class="h-8 rounded-md border border-linea-fuerte bg-white px-2 text-xs"
                        >
                            <option :value="-1">Ninguna</option>
                            <option
                                v-for="(o, i) in form.opciones"
                                :key="i"
                                :value="i"
                            >
                                {{ LETRAS[i] }} · {{ o.texto || 'sin texto' }}
                            </option>
                        </select>
                        <span class="w-full text-tinta-suave">
                            Como «Ninguna» o «Nada»: si la empresa la marca, se
                            desmarcan las otras.
                        </span>
                    </label>

                    <label class="flex flex-wrap items-center gap-2 text-xs">
                        Opción que pide escribir cuál
                        <select
                            v-model.number="conTexto"
                            class="h-8 rounded-md border border-linea-fuerte bg-white px-2 text-xs"
                        >
                            <option :value="-1">Ninguna</option>
                            <option
                                v-for="(o, i) in form.opciones"
                                :key="i"
                                :value="i"
                            >
                                {{ LETRAS[i] }} · {{ o.texto || 'sin texto' }}
                            </option>
                        </select>
                        <span class="w-full text-tinta-suave">
                            Como «Otro»: si la empresa la elige, escribe cuál
                            («¿Cuál otro canal?») y la IA lo lee.
                        </span>
                    </label>

                    <div
                        v-if="form.tipo === 'seleccion_multiple'"
                        :class="
                            cn(
                                'rounded-md px-3 py-2 text-xs',
                                sumaMaxima >= 100
                                    ? 'bg-lienzo'
                                    : 'bg-alerta-suave text-alerta',
                            )
                        "
                    >
                        <p class="flex justify-between font-semibold">
                            Suma máxima
                            <span class="font-mono">
                                {{ sumaMaxima }} / 100
                                {{ sumaMaxima >= 100 ? '✓' : '⚠' }}
                            </span>
                        </p>
                        <p class="mt-1 text-tinta-suave">
                            Selección múltiple: la suma de todas las opciones
                            debe poder llegar a 100 para publicar.
                        </p>
                    </div>
                    <p v-else class="text-xs text-tinta-suave">
                        Opción única: el puntaje de la pregunta es el de la
                        opción elegida. Usa 0 para la peor respuesta y 100 para
                        la mejor.
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-4">
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

                <template v-if="conOpciones">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium">
                            Qué debe tener en cuenta la IA según la respuesta
                            <span class="font-normal text-tinta-suave">
                                (opcional)
                            </span>
                        </span>
                        <p class="text-xs text-tinta-suave">
                            La empresa no lo ve. Se agrega al prompt como "Ten
                            en cuenta: …" solo si elige esa opción. Puedes pedir
                            un método, por ejemplo FODA o PESTEL.
                        </p>
                    </div>
                    <div
                        v-for="(opcion, i) in form.opciones"
                        :key="`t-${i}`"
                        class="grid grid-cols-[1rem_1fr_3rem] items-start gap-2"
                    >
                        <span class="pt-2 text-xs text-tinta-suave">
                            {{ LETRAS[i] }}
                        </span>
                        <AreaTexto
                            v-model="opcion.ten_en_cuenta"
                            rows="2"
                            class="min-h-0"
                            :maxlength="MAX_TEN_EN_CUENTA"
                            :placeholder="`Opcional · ${opcion.texto || `opción ${LETRAS[i]}`}`"
                            :aria-label="`Indicación para la IA de la opción ${LETRAS[i]}`"
                        />
                        <span
                            class="pt-2 text-right font-mono text-xs text-tinta-suave"
                        >
                            {{ opcion.ten_en_cuenta.length }}/{{
                                MAX_TEN_EN_CUENTA
                            }}
                        </span>
                    </div>
                    <p class="text-xs text-tinta-suave">
                        {{ conIndicacion }} de {{ form.opciones.length }}
                        opciones con indicación · máx.
                        {{ MAX_TEN_EN_CUENTA }} caracteres cada una
                    </p>
                    <div
                        v-if="ejemplo"
                        class="rounded-md border border-marca/20 bg-marca-suave/40 px-3 py-2 text-xs"
                    >
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
                </template>

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

                <label class="mt-auto flex items-center gap-2 text-sm">
                    <input
                        v-model="form.obligatoria"
                        type="checkbox"
                        class="size-4 accent-marca"
                    />
                    Respuesta obligatoria para la empresa
                </label>
            </div>
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
