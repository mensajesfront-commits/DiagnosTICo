<script setup lang="ts">
/**
 * A2.4 · Vista previa y estado (HU-028).
 *
 * - Izquierda: "Estado del diagnóstico" con cuántas categorías están
 *   completas, los 5 requisitos para publicar (RN-012) y las categorías que
 *   faltan corregir, con "Corregir" (lleva a la pregunta en el editor).
 * - Derecha: el cuestionario como lo ve la empresa, una categoría a la vez.
 *   Se puede responder para probar, pero nada se guarda (CA-004).
 * - Con `?version=` muestra una versión publicada en solo lectura.
 *
 * [FUNCIONALIDAD POR DEFINIR] "Resultado de ejemplo" (A2.6, HU-030) llega con
 * Resultados (semana 5).
 */
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import ModalVersiones from '@/components/editor/ModalVersiones.vue';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type {
    CabeceraEditor,
    CategoriaVistaPrevia,
    EstadoEditor,
    VersionHistorial,
} from '@/types/editor';

const props = defineProps<{
    diagnostico: CabeceraEditor;
    /** null = el borrador. */
    version: { numero: number; publicada_en: string } | null;
    categorias: CategoriaVistaPrevia[];
    estado: EstadoEditor;
    versiones: VersionHistorial[];
    pendientes: number;
}>();

const indice = ref(
    Math.max(
        0,
        props.categorias.findIndex((c) => c.problemas.length > 0),
    ),
);
const categoria = computed(() => props.categorias[indice.value] ?? null);

/** Respuestas de prueba: no se guardan. */
const respuestas = reactive<Record<string, string[] | string>>({});

function elegir(pregunta: string, opcion: string, multiple: boolean): void {
    const actual = respuestas[pregunta];

    if (!multiple) {
        respuestas[pregunta] = [opcion];

        return;
    }

    const lista = Array.isArray(actual) ? actual : [];
    respuestas[pregunta] = lista.includes(opcion)
        ? lista.filter((o) => o !== opcion)
        : [...lista, opcion];
}

const marcada = (pregunta: string, opcion: string) => {
    const r = respuestas[pregunta];

    return Array.isArray(r) && r.includes(opcion);
};

const porCorregir = computed(() =>
    props.categorias.filter((c) => c.problemas.length > 0),
);
const problemas = computed(() =>
    porCorregir.value.reduce((s, c) => s + c.problemas.length, 0),
);

/** "Falta 1 indicación · 1 selección múltiple suma 80". */
function resumen(c: CategoriaVistaPrevia): string {
    const indicaciones = c.problemas.filter((p) =>
        p.startsWith('Falta la indicación'),
    ).length;
    const otros = c.problemas.filter(
        (p) => !p.startsWith('Falta la indicación'),
    );
    const partes = [
        ...(indicaciones
            ? [
                  indicaciones === 1
                      ? 'Falta 1 indicación'
                      : `Faltan ${indicaciones} indicaciones`,
              ]
            : []),
        ...otros.map((p) =>
            p.replace(
                'La selección múltiple suma solo',
                '1 selección múltiple suma',
            ),
        ),
    ];

    return partes.join(' · ');
}

function corregir(c: CategoriaVistaPrevia): string {
    const pregunta = c.preguntas.find((p) => p.problemas.length > 0);

    return rutas.diagnosticos.editar(
        props.diagnostico.id,
        c.parte_id ?? undefined,
        pregunta?.id,
    );
}

const progreso = computed(() =>
    props.estado.total
        ? (props.estado.completas / props.estado.total) * 100
        : 0,
);

const pistas = {
    abierta: 'Respuesta escrita',
    opcion_unica: 'Elige una',
    seleccion_multiple: 'Marca todas las que apliquen',
};

const modalVersiones = ref(false);
const esBorrador = computed(() => props.version === null);
const nadaQuePublicar = computed(
    () =>
        !props.diagnostico.hay_borrador &&
        props.diagnostico.ultima_publicada !== null,
);
</script>

<template>
    <Head :title="`Vista previa · ${diagnostico.nombre}`" />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
        <header
            class="flex shrink-0 flex-wrap items-start justify-between gap-4"
        >
            <div>
                <Link
                    :href="
                        esBorrador
                            ? rutas.diagnosticos.editar(diagnostico.id)
                            : rutas.diagnosticos.sector(diagnostico.sector.id)
                    "
                    class="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-tinta-suave hover:text-marca"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    {{
                        esBorrador
                            ? 'Volver al editor'
                            : `Diagnósticos · ${diagnostico.sector.nombre}`
                    }}
                </Link>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold">
                        Vista previa · {{ diagnostico.nombre }}
                    </h1>
                    <Etiqueta v-if="version" tono="exito">
                        ✓ v{{ version.numero }} publicada · solo lectura
                    </Etiqueta>
                    <Etiqueta
                        v-else
                        :tono="estado.publicable ? 'exito' : 'alerta'"
                    >
                        Borrador v{{ diagnostico.version }} ·
                        {{ estado.publicable ? 'completo' : 'incompleto' }}
                    </Etiqueta>
                </div>
            </div>
            <div v-if="esBorrador" class="flex flex-col items-end gap-1">
                <Boton
                    :disabled="!estado.publicable || nadaQuePublicar"
                    @click="modalVersiones = true"
                >
                    Publicar versión {{ diagnostico.version }}
                </Boton>
                <p v-if="!estado.publicable" class="text-xs text-tinta-suave">
                    Cumple los 5 requisitos para publicar
                </p>
            </div>
        </header>

        <div
            class="grid items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[320px_1fr] lg:grid-rows-[minmax(0,1fr)] lg:items-stretch"
        >
            <aside
                class="flex flex-col gap-4 rounded-xl border border-linea bg-white p-5 text-sm lg:min-h-0 lg:overflow-y-auto"
            >
                <div>
                    <p class="flex items-baseline justify-between">
                        <span class="font-semibold"
                            >Estado del diagnóstico</span
                        >
                        <span class="font-mono text-xs">
                            {{ estado.completas }} de
                            {{ estado.total }} completas
                        </span>
                    </p>
                    <div
                        class="mt-2 h-2 rounded-full bg-lienzo-oscuro"
                        aria-hidden="true"
                    >
                        <div
                            :class="
                                cn(
                                    'h-2 rounded-full',
                                    estado.publicable
                                        ? 'bg-exito'
                                        : 'bg-alerta',
                                )
                            "
                            :style="{ width: `${progreso}%` }"
                        />
                    </div>
                    <p class="mt-2 text-xs text-tinta-suave">
                        Solo se puede publicar cuando se cumplan los 5
                        requisitos.
                    </p>
                </div>

                <div>
                    <h2 class="text-xs font-medium">
                        Requisitos para publicar
                    </h2>
                    <ul class="mt-1 divide-y divide-linea">
                        <li
                            v-for="r in estado.requisitos"
                            :key="r.clave"
                            class="flex items-start gap-2 py-2"
                        >
                            <span
                                :class="
                                    cn(
                                        'flex size-5 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                        r.ok
                                            ? 'bg-exito-suave text-exito'
                                            : 'bg-alerta-suave text-alerta',
                                    )
                                "
                                aria-hidden="true"
                            >
                                {{ r.ok ? '✓' : '!' }}
                            </span>
                            <span>
                                {{ r.texto }}
                                <template v-if="r.detalle">
                                    ·
                                    <span class="font-medium text-alerta">{{
                                        r.detalle
                                    }}</span>
                                </template>
                            </span>
                        </li>
                    </ul>
                </div>

                <div v-if="porCorregir.length">
                    <h2 class="text-xs font-medium">
                        Categorías que faltan corregir ·
                        {{ problemas }}
                        {{ problemas === 1 ? 'problema' : 'problemas' }} en
                        {{ porCorregir.length }}
                        {{
                            porCorregir.length === 1
                                ? 'categoría'
                                : 'categorías'
                        }}
                    </h2>
                    <ul class="mt-2 flex flex-col gap-2">
                        <li
                            v-for="c in porCorregir"
                            :key="c.ref"
                            class="flex items-start justify-between gap-3 rounded-md bg-alerta-suave/60 px-3 py-2"
                        >
                            <span>
                                <span class="font-medium">{{ c.nombre }}</span>
                                <span class="block text-xs text-alerta">
                                    {{ resumen(c) }}
                                </span>
                            </span>
                            <Link
                                v-if="esBorrador"
                                :href="corregir(c)"
                                class="text-xs text-marca underline"
                            >
                                Corregir
                            </Link>
                        </li>
                    </ul>
                </div>
            </aside>

            <section class="flex flex-col gap-3 lg:min-h-0">
                <div
                    class="flex shrink-0 flex-wrap items-center justify-between gap-3"
                >
                    <div
                        class="inline-flex rounded-lg bg-lienzo-oscuro p-1 text-sm"
                    >
                        <span
                            class="rounded-md bg-white px-3 py-1 font-medium shadow-sm"
                        >
                            Cuestionario
                        </span>
                        <span
                            class="px-3 py-1 text-tinta-suave"
                            title="Llega con Resultados (HU-030)"
                        >
                            Resultado de ejemplo · Próximamente
                        </span>
                    </div>
                    <label
                        v-if="categorias.length"
                        class="flex items-center gap-2 text-sm"
                    >
                        Categoría
                        <Seleccion v-model="indice" class="w-72">
                            <option
                                v-for="(c, i) in categorias"
                                :key="c.ref"
                                :value="i"
                            >
                                {{ i + 1 }} de {{ categorias.length }} ·
                                {{ c.nombre
                                }}{{ c.problemas.length ? ' ⚠' : '' }}
                            </option>
                        </Seleccion>
                    </label>
                </div>

                <div
                    class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto rounded-xl border border-dashed border-linea-fuerte bg-white/60 p-5"
                >
                    <p class="text-xs text-tinta-suave">
                        Así lo verá la empresa · las respuestas de esta vista no
                        se guardan
                    </p>
                    <template v-if="categoria">
                        <div>
                            <p class="font-mono text-xs text-tinta-suave">
                                CATEGORÍA {{ indice + 1 }} /
                                {{ categorias.length }}
                            </p>
                            <h2 class="text-xl font-semibold">
                                {{ categoria.nombre }}
                            </h2>
                        </div>
                        <p
                            v-if="categoria.preguntas.length === 0"
                            class="rounded-md border border-dashed border-alerta bg-alerta-suave/40 px-4 py-6 text-center text-sm text-alerta"
                        >
                            Esta categoría no tiene preguntas: la empresa no
                            vería nada aquí.
                        </p>
                        <article
                            v-for="(p, i) in categoria.preguntas"
                            :key="p.ref"
                            :class="
                                cn(
                                    'rounded-lg border bg-white px-4 py-3',
                                    p.problemas.length
                                        ? 'border-2 border-dashed border-alerta'
                                        : 'border-linea',
                                )
                            "
                        >
                            <div class="flex items-start justify-between gap-4">
                                <p class="font-medium">
                                    {{ i + 1 }}. {{ p.texto }}
                                    <span
                                        v-if="!p.obligatoria"
                                        class="text-xs font-normal text-tinta-suave"
                                    >
                                        (opcional)
                                    </span>
                                </p>
                                <span class="shrink-0 text-xs text-tinta-suave">
                                    {{ pistas[p.tipo] }}
                                </span>
                            </div>
                            <p v-if="p.indicacion.trim()" class="mt-1 text-xs">
                                <strong class="text-marca"
                                    >Cómo responder</strong
                                >
                                · {{ p.indicacion }}
                            </p>
                            <p
                                v-for="problema in p.problemas"
                                :key="problema"
                                class="mt-2 flex flex-wrap items-center justify-between gap-2 rounded bg-alerta-suave px-3 py-1.5 text-xs text-alerta"
                            >
                                ⚠ {{ problema }}. La empresa la vería así.
                                <Link
                                    v-if="esBorrador && categoria.parte_id"
                                    :href="
                                        rutas.diagnosticos.editar(
                                            diagnostico.id,
                                            categoria.parte_id,
                                            p.id,
                                        )
                                    "
                                    class="text-marca underline"
                                >
                                    Corregir
                                </Link>
                            </p>
                            <div
                                v-if="p.tipo !== 'abierta'"
                                class="mt-2 flex flex-wrap gap-2"
                            >
                                <button
                                    v-for="o in p.opciones"
                                    :key="o.ref"
                                    type="button"
                                    :aria-pressed="marcada(p.ref, o.ref)"
                                    :class="
                                        cn(
                                            'rounded-md border px-3 py-1 text-sm',
                                            marcada(p.ref, o.ref)
                                                ? 'border-marca bg-marca text-white'
                                                : 'border-linea-fuerte bg-white hover:bg-lienzo',
                                        )
                                    "
                                    @click="
                                        elegir(
                                            p.ref,
                                            o.ref,
                                            p.tipo === 'seleccion_multiple',
                                        )
                                    "
                                >
                                    <template
                                        v-if="p.tipo === 'seleccion_multiple'"
                                    >
                                        {{ marcada(p.ref, o.ref) ? '☑' : '☐' }}
                                    </template>
                                    {{ o.texto }}
                                </button>
                            </div>
                            <textarea
                                v-else
                                v-model="respuestas[p.ref] as string"
                                rows="2"
                                maxlength="1000"
                                :aria-label="`Respuesta de prueba a la pregunta ${i + 1}`"
                                class="mt-2 w-full rounded-md border border-linea-fuerte px-3 py-2 text-sm focus-visible:border-marca focus-visible:outline-none"
                            />
                        </article>
                    </template>
                    <p
                        v-else
                        class="py-10 text-center text-sm text-tinta-suave"
                    >
                        El diagnóstico todavía no tiene categorías.
                    </p>
                </div>
            </section>
        </div>
    </div>

    <ModalVersiones
        v-if="esBorrador"
        v-model:abierto="modalVersiones"
        :diagnostico="diagnostico"
        :estado="estado"
        :versiones="versiones"
        :pendientes="pendientes"
    />
</template>
