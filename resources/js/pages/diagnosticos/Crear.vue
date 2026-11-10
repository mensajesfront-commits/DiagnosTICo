<script setup lang="ts">
/**
 * A2.5 · Crear diagnóstico (HU-020).
 *
 * Nace como borrador v1 (RN-010). En blanco se eligen las categorías del
 * catálogo y la importancia empieza repartida en partes iguales; copiando, se
 * trae todo un diagnóstico publicado de cualquier sector; pegando (HU-020 CA-005),
 * se escribe todo en la plantilla de texto y el borrador se llena solo.
 *
 * Envía: nombre (máx. 60), sector_id, descripcion (opcional),
 * punto_partida ("blanco" | "copia" | "pegar"), categorias[] (en blanco),
 * copiar_de (en copia) y texto (al pegar). Al crearlo, el backend abre el
 * editor (A2.1), donde todo se puede corregir.
 *
 * Pegar: "Revisar el contenido" lo lee en el servidor sin guardar nada
 * (`DiagnosticosController::revisarPlantilla`) y muestra qué entendió, los
 * errores que impiden crearlo y lo que faltaría para publicarlo, cada uno con
 * su número de línea (un clic lleva a esa línea del texto).
 *
 * Diseño: dos pasos numerados (1 · Datos, 2 · Punto de partida). El punto de
 * partida se elige con dos tarjetas; las categorías, con recuadros que se
 * marcan, y "Elegir todas" / "Quitar todas". A la derecha, "Qué pasa al
 * crearlo" como línea de pasos.
 */
import { Head, useForm } from '@inertiajs/vue3';
import {
    Check,
    ClipboardPaste,
    Copy,
    FilePlus2,
    Lightbulb,
    TriangleAlert,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Entrada from '@/components/base/Entrada.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import { enviarJson } from '@/lib/ia';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { DiagnosticoPublicado, Sector } from '@/types/diagnosticos';

const MAXIMO_NOMBRE = 60;

const props = withDefaults(
    defineProps<{
        /** Sectores activos. */
        sectores: Sector[];
        /** Sector preseleccionado (al venir desde un sector en A2). */
        sectorId?: number | null;
        /** Categorías activas del catálogo. */
        categorias: { id: number; nombre: string }[];
        /** Última versión publicada de cada diagnóstico, de todos los sectores. */
        publicados: DiagnosticoPublicado[];
        /** Plantilla de ejemplo para pegar el contenido (HU-020 CA-005). */
        plantilla: string;
    }>(),
    { sectorId: null },
);

const form = useForm<{
    nombre: string;
    sector_id: number | '';
    descripcion: string;
    punto_partida: 'blanco' | 'copia' | 'pegar';
    categorias: number[];
    copiar_de: number | '';
    texto: string;
}>({
    nombre: '',
    sector_id: props.sectorId ?? '',
    descripcion: '',
    punto_partida: 'blanco',
    categorias: [],
    copiar_de: '',
    texto: '',
});

/** Lo que el servidor entendió del texto pegado (HU-020 CA-005). */
type Hallazgo = { linea: number; mensaje: string };
type Lectura = {
    resumen: {
        categorias: {
            nombre: string;
            importancia: number;
            preguntas: number;
        }[];
        preguntas: number;
        opciones: number;
    };
    errores: Hallazgo[];
    avisos: Hallazgo[];
};

const MAXIMO_TEXTO = 100000;
const lectura = ref<Lectura | null>(null);
/** Texto exacto que se revisó: si cambia, hay que revisar otra vez. */
const textoRevisado = ref('');
const revisando = ref(false);
const errorRevision = ref('');
const plantillaCopiada = ref(false);

const lecturaVigente = computed(
    () => lectura.value !== null && textoRevisado.value === form.texto,
);

async function revisar(): Promise<void> {
    revisando.value = true;
    errorRevision.value = '';
    const texto = form.texto;
    const r = await enviarJson<Lectura>(rutas.diagnosticos.revisarPlantilla(), {
        texto,
    });
    revisando.value = false;

    if (!r.ok) {
        errorRevision.value = r.mensaje;
        lectura.value = null;

        return;
    }

    lectura.value = r.datos;
    textoRevisado.value = texto;
}

async function copiarPlantilla(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.plantilla);
        plantillaCopiada.value = true;
        setTimeout(() => (plantillaCopiada.value = false), 2000);
    } catch {
        // Sin permiso para el portapapeles: se pone el ejemplo en el recuadro.
        usarEjemplo();
    }
}

function usarEjemplo(): void {
    form.texto = props.plantilla;
}

/** Lleva el cursor a la línea del texto donde está el problema. */
function irALinea(linea: number): void {
    const area = document.getElementById('texto');

    if (!(area instanceof HTMLTextAreaElement)) {
        return;
    }

    const lineas = form.texto.split('\n');
    const inicio = lineas
        .slice(0, linea - 1)
        .reduce((total, l) => total + l.length + 1, 0);
    const fin = inicio + (lineas.at(linea - 1)?.length ?? 0);
    area.focus();
    area.setSelectionRange(inicio, fin);
    const alto = parseFloat(getComputedStyle(area).lineHeight) || 16;
    area.scrollTop = Math.max(0, (linea - 3) * alto);
}

const sectorElegido = computed(() =>
    props.sectores.find((s) => s.id === form.sector_id),
);

const origen = computed(() =>
    props.publicados.find((d) => d.id === form.copiar_de),
);

const publicadosPorSector = computed(() => {
    const grupos = new Map<string, DiagnosticoPublicado[]>();

    for (const diagnostico of props.publicados) {
        const lista = grupos.get(diagnostico.sector_nombre) ?? [];
        lista.push(diagnostico);
        grupos.set(diagnostico.sector_nombre, lista);
    }

    return [...grupos.entries()];
});

const importanciaInicial = computed(() =>
    form.categorias.length > 0
        ? Math.round((100 / form.categorias.length) * 10) / 10
        : 0,
);

const todasElegidas = computed(
    () =>
        props.categorias.length > 0 &&
        form.categorias.length === props.categorias.length,
);

function alternarTodas(): void {
    form.categorias = todasElegidas.value
        ? []
        : props.categorias.map((c) => c.id);
}

const puntos = [
    {
        valor: 'blanco' as const,
        titulo: 'En blanco',
        texto: 'Eliges las categorías y escribes las preguntas.',
        icono: FilePlus2,
    },
    {
        valor: 'copia' as const,
        titulo: 'Copiar uno publicado',
        texto: 'Traes todo un diagnóstico de cualquier sector.',
        icono: Copy,
    },
    {
        valor: 'pegar' as const,
        titulo: 'Pegar el contenido',
        texto: 'Pegas todo en la plantilla y se llena solo.',
        icono: ClipboardPaste,
    },
];

const pasos = [
    {
        titulo: 'Se crea como borrador',
        texto: 'Ninguna empresa lo ve todavía.',
    },
    {
        titulo: 'Importancia inicial',
        texto: 'Si copias o pegas, se traen la importancia y las preguntas. En blanco (o si la plantilla no la trae), empieza repartida en partes iguales (suma 100%).',
    },
    {
        titulo: 'Se abre el editor',
        texto: 'Escribes preguntas, puntajes e indicaciones.',
    },
    {
        titulo: 'Se publica',
        texto: 'Cuando cumple los 5 requisitos, ya se puede asignar en una medición.',
    },
];

const listo = computed(
    () =>
        form.nombre.trim() !== '' &&
        form.sector_id !== '' &&
        (form.punto_partida === 'blanco'
            ? form.categorias.length > 0
            : form.punto_partida === 'copia'
              ? form.copiar_de !== ''
              : lecturaVigente.value &&
                lectura.value !== null &&
                lectura.value.errores.length === 0),
);

function crear(): void {
    form.transform((datos) => ({
        ...datos,
        categorias: datos.punto_partida === 'blanco' ? datos.categorias : [],
        copiar_de: datos.punto_partida === 'copia' ? datos.copiar_de : null,
        texto: datos.punto_partida === 'pegar' ? datos.texto : null,
    })).post(rutas.diagnosticos.guardar());
}
</script>

<template>
    <Head title="Crear diagnóstico" />

    <div class="flex flex-col gap-5 p-6">
        <EncabezadoPagina
            titulo="Crear diagnóstico"
            descripcion="Nace como borrador: ninguna empresa lo ve hasta que lo publiques."
            :volver="{
                href: sectorId
                    ? rutas.diagnosticos.sector(sectorId)
                    : rutas.diagnosticos.todos(),
                texto: 'Diagnósticos',
            }"
        />

        <div class="grid items-start gap-5 xl:grid-cols-[1fr_320px]">
            <form
                class="flex flex-col rounded-2xl border border-linea bg-white"
                @submit.prevent="crear"
            >
                <!-- 1 · Datos -->
                <section class="flex flex-col gap-4 p-5">
                    <header class="flex items-center gap-3">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-marca font-mono text-sm font-semibold text-white"
                            aria-hidden="true"
                        >
                            1
                        </span>
                        <div>
                            <h2 class="text-base leading-tight font-semibold">
                                Datos del diagnóstico
                            </h2>
                            <p class="text-xs text-tinta-suave">
                                Todos los campos son obligatorios salvo los
                                marcados “(opcional)”.
                            </p>
                        </div>
                    </header>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <Campo
                            obligatorio
                            etiqueta="Nombre del diagnóstico"
                            para="nombre"
                            ayuda="Máximo 60 caracteres. Puedes cambiarlo después."
                            :contador="`${form.nombre.length}/${MAXIMO_NOMBRE}`"
                            :error="form.errors.nombre"
                        >
                            <Entrada
                                id="nombre"
                                v-model="form.nombre"
                                required
                                placeholder="Ej. Diagnóstico general"
                                :maxlength="MAXIMO_NOMBRE"
                                :invalida="!!form.errors.nombre"
                            />
                        </Campo>

                        <Campo
                            obligatorio
                            etiqueta="Sector"
                            para="sector"
                            :error="form.errors.sector_id"
                            :ayuda="
                                sectorElegido
                                    ? `Las empresas de ${sectorElegido.nombre} lo recibirán cuando lo publiques.`
                                    : 'Las empresas del sector lo recibirán cuando lo publiques.'
                            "
                        >
                            <Seleccion
                                id="sector"
                                v-model="form.sector_id"
                                required
                                :invalida="!!form.errors.sector_id"
                            >
                                <option value="" disabled>
                                    Selecciona el sector
                                </option>
                                <option
                                    v-for="sector in sectores"
                                    :key="sector.id"
                                    :value="sector.id"
                                >
                                    {{ sector.nombre }}
                                </option>
                            </Seleccion>
                        </Campo>
                    </div>

                    <Campo
                        etiqueta="Descripción"
                        para="descripcion"
                        opcional
                        ayuda="Solo la ve el administrador."
                        :error="form.errors.descripcion"
                    >
                        <AreaTexto
                            id="descripcion"
                            v-model="form.descripcion"
                            rows="2"
                            placeholder="Para qué es este diagnóstico y a quién va dirigido."
                        />
                    </Campo>
                </section>

                <!-- 2 · Punto de partida -->
                <section class="flex flex-col gap-4 border-t border-linea p-5">
                    <header class="flex items-center gap-3">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-marca font-mono text-sm font-semibold text-white"
                            aria-hidden="true"
                        >
                            2
                        </span>
                        <h2
                            id="titulo-punto-partida"
                            class="text-base font-semibold"
                        >
                            Punto de partida
                        </h2>
                    </header>

                    <div
                        class="grid gap-3 sm:grid-cols-3"
                        role="radiogroup"
                        aria-labelledby="titulo-punto-partida"
                    >
                        <label
                            v-for="punto in puntos"
                            :key="punto.valor"
                            :class="
                                cn(
                                    'relative flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition has-focus-visible:ring-2 has-focus-visible:ring-marca/30',
                                    form.punto_partida === punto.valor
                                        ? 'border-marca bg-marca-suave/40'
                                        : 'border-linea hover:border-marca/40',
                                )
                            "
                        >
                            <input
                                v-model="form.punto_partida"
                                type="radio"
                                :value="punto.valor"
                                class="sr-only"
                            />
                            <span
                                :class="
                                    cn(
                                        'flex size-10 shrink-0 items-center justify-center rounded-xl',
                                        form.punto_partida === punto.valor
                                            ? 'bg-marca text-white'
                                            : 'bg-lienzo text-tinta-suave',
                                    )
                                "
                                aria-hidden="true"
                            >
                                <component :is="punto.icono" class="size-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold">
                                    {{ punto.titulo }}
                                </span>
                                <span
                                    class="mt-0.5 block text-xs text-tinta-suave"
                                >
                                    {{ punto.texto }}
                                </span>
                            </span>
                            <span
                                :class="
                                    cn(
                                        'flex size-5 shrink-0 items-center justify-center rounded-full border',
                                        form.punto_partida === punto.valor
                                            ? 'border-marca bg-marca text-white'
                                            : 'border-linea-fuerte bg-white',
                                    )
                                "
                                aria-hidden="true"
                            >
                                <Check
                                    v-if="form.punto_partida === punto.valor"
                                    class="size-3"
                                />
                            </span>
                        </label>
                    </div>

                    <div
                        v-if="form.punto_partida === 'blanco'"
                        class="flex flex-col gap-3"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <p class="text-xs font-medium">
                                Categorías del catálogo
                                <span class="font-normal text-tinta-suave">
                                    · {{ form.categorias.length }} de
                                    {{ categorias.length }} elegidas
                                </span>
                            </p>
                            <button
                                v-if="categorias.length > 0"
                                type="button"
                                class="text-xs font-medium text-marca hover:underline"
                                @click="alternarTodas"
                            >
                                {{
                                    todasElegidas
                                        ? 'Quitar todas'
                                        : 'Elegir todas'
                                }}
                            </button>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <label
                                v-for="categoria in categorias"
                                :key="categoria.id"
                                :class="
                                    cn(
                                        'flex cursor-pointer items-center gap-2.5 rounded-lg border px-3 py-2.5 text-sm transition has-focus-visible:ring-2 has-focus-visible:ring-marca/30',
                                        form.categorias.includes(categoria.id)
                                            ? 'border-marca bg-marca-suave/40 font-medium'
                                            : 'border-linea hover:border-marca/40',
                                    )
                                "
                            >
                                <input
                                    v-model="form.categorias"
                                    type="checkbox"
                                    :value="categoria.id"
                                    class="sr-only"
                                />
                                <span
                                    :class="
                                        cn(
                                            'flex size-4 shrink-0 items-center justify-center rounded border',
                                            form.categorias.includes(
                                                categoria.id,
                                            )
                                                ? 'border-marca bg-marca text-white'
                                                : 'border-linea-fuerte bg-white',
                                        )
                                    "
                                    aria-hidden="true"
                                >
                                    <Check
                                        v-if="
                                            form.categorias.includes(
                                                categoria.id,
                                            )
                                        "
                                        class="size-3"
                                    />
                                </span>
                                {{ categoria.nombre }}
                            </label>
                        </div>
                        <p
                            :class="
                                cn(
                                    'rounded-lg px-3 py-2 text-xs',
                                    form.categorias.length > 0
                                        ? 'bg-exito-suave text-exito'
                                        : 'bg-lienzo text-tinta-suave',
                                )
                            "
                        >
                            <template v-if="form.categorias.length > 0">
                                {{ form.categorias.length }}
                                {{
                                    form.categorias.length === 1
                                        ? 'categoría'
                                        : 'categorías'
                                }}
                                · cada una empieza valiendo
                                <strong>{{ importanciaInicial }}%</strong>
                                (suma 100%). La ajustas en el editor.
                            </template>
                            <template v-else>
                                Elige al menos una categoría.
                            </template>
                        </p>
                        <p
                            v-if="form.errors.categorias"
                            class="text-xs text-aviso"
                        >
                            {{ form.errors.categorias }}
                        </p>
                    </div>

                    <div
                        v-else-if="form.punto_partida === 'copia'"
                        class="flex flex-col gap-3"
                    >
                        <Campo
                            obligatorio
                            etiqueta="Copiar de"
                            para="copiar-de"
                            ayuda="Agrupados por sector. Solo aparecen diagnósticos publicados."
                            :error="form.errors.copiar_de"
                        >
                            <Seleccion
                                id="copiar-de"
                                v-model="form.copiar_de"
                                required
                            >
                                <option value="" disabled>
                                    Selecciona un diagnóstico
                                </option>
                                <optgroup
                                    v-for="[
                                        sector,
                                        lista,
                                    ] in publicadosPorSector"
                                    :key="sector"
                                    :label="sector"
                                >
                                    <option
                                        v-for="diagnostico in lista"
                                        :key="diagnostico.id"
                                        :value="diagnostico.id"
                                    >
                                        {{ diagnostico.nombre }} · v{{
                                            diagnostico.version
                                        }}
                                    </option>
                                </optgroup>
                            </Seleccion>
                        </Campo>

                        <div
                            v-if="origen"
                            class="rounded-xl border border-linea bg-lienzo/60 p-4 text-sm"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-exito-suave text-exito"
                                    aria-hidden="true"
                                >
                                    <Copy class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="font-semibold">
                                        Se copia todo de
                                        {{ origen.sector_nombre }} · v{{
                                            origen.version
                                        }}
                                    </p>
                                    <p class="text-xs text-tinta-suave">
                                        <span class="font-mono">
                                            {{ origen.categorias.length }}
                                        </span>
                                        categorías ·
                                        <span class="font-mono">
                                            {{ origen.preguntas }}
                                        </span>
                                        preguntas, con sus opciones, puntajes,
                                        indicaciones e importancia. Luego puedes
                                        editarlo.
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <Etiqueta
                                    v-for="categoria in origen.categorias"
                                    :key="categoria"
                                    class="rounded-full border border-linea bg-white font-normal"
                                >
                                    {{ categoria }}
                                </Etiqueta>
                            </div>
                            <p
                                v-if="
                                    sectorElegido &&
                                    sectorElegido.id !== origen.sector_id
                                "
                                class="mt-3 rounded-lg bg-alerta-suave px-3 py-2 text-xs text-alerta"
                            >
                                ⚠ Las preguntas están escritas para
                                {{ origen.sector_nombre }}: revisa el lenguaje
                                para {{ sectorElegido.nombre }} antes de
                                publicar.
                            </p>
                        </div>
                    </div>

                    <!-- Pegar el contenido (HU-020 CA-005) -->
                    <div v-else class="flex flex-col gap-3">
                        <div
                            class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-linea bg-lienzo/60 p-4 text-xs text-tinta-suave"
                        >
                            <p class="max-w-prose">
                                Escribe el diagnóstico con la
                                <strong class="text-tinta">plantilla</strong>:
                                cada categoría con «CATEGORÍA:», cada pregunta
                                con «PREGUNTA:», «TIPO:», «INDICACIÓN:» y sus
                                opciones como «- puntaje | texto». Los nombres
                                de las categorías deben ser los del catálogo.
                            </p>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <Boton
                                    type="button"
                                    variante="secundario"
                                    tamano="sm"
                                    @click="copiarPlantilla"
                                >
                                    <Check
                                        v-if="plantillaCopiada"
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    <Copy
                                        v-else
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    {{
                                        plantillaCopiada
                                            ? 'Plantilla copiada'
                                            : 'Copiar la plantilla'
                                    }}
                                </Boton>
                                <Boton
                                    type="button"
                                    variante="secundario"
                                    tamano="sm"
                                    @click="usarEjemplo"
                                >
                                    Usar el ejemplo
                                </Boton>
                            </div>
                        </div>

                        <Campo
                            obligatorio
                            etiqueta="Contenido del diagnóstico"
                            para="texto"
                            ayuda="Pega aquí todo el diagnóstico. Antes de crearlo, revísalo: verás qué se entendió y lo que hay que corregir."
                            :contador="`${form.texto.length.toLocaleString('es-CO')}/${MAXIMO_TEXTO.toLocaleString('es-CO')}`"
                            :error="form.errors.texto"
                        >
                            <AreaTexto
                                id="texto"
                                v-model="form.texto"
                                rows="16"
                                spellcheck="false"
                                :maxlength="MAXIMO_TEXTO"
                                :invalida="!!form.errors.texto"
                                class="font-mono text-xs leading-5"
                                placeholder="CATEGORÍA: Presencia en línea&#10;IMPORTANCIA: 50&#10;&#10;PREGUNTA: ¿Tu negocio tiene página web propia?&#10;TIPO: Opción única&#10;INDICACIÓN: Cuenta solo una página con dominio propio.&#10;OPCIONES:&#10;- 100 | Sí&#10;- 0 | No"
                            />
                        </Campo>

                        <div class="flex flex-wrap items-center gap-3">
                            <Boton
                                type="button"
                                variante="secundario"
                                :disabled="form.texto.trim() === ''"
                                :cargando="revisando"
                                @click="revisar"
                            >
                                Revisar el contenido
                            </Boton>
                            <p
                                v-if="lectura && !lecturaVigente"
                                class="text-xs text-alerta"
                            >
                                Cambiaste el texto: revísalo otra vez antes de
                                crear.
                            </p>
                            <p
                                v-if="errorRevision"
                                class="text-xs text-aviso"
                                role="alert"
                            >
                                {{ errorRevision }}
                            </p>
                        </div>

                        <div
                            v-if="lectura && lecturaVigente"
                            class="flex flex-col gap-3"
                            aria-live="polite"
                        >
                            <div
                                class="rounded-xl border border-linea bg-white p-4 text-sm"
                            >
                                <p class="font-semibold">
                                    Se entendió:
                                    <span class="font-mono">{{
                                        lectura.resumen.categorias.length
                                    }}</span>
                                    {{
                                        lectura.resumen.categorias.length === 1
                                            ? 'categoría'
                                            : 'categorías'
                                    }}
                                    ·
                                    <span class="font-mono">{{
                                        lectura.resumen.preguntas
                                    }}</span>
                                    {{
                                        lectura.resumen.preguntas === 1
                                            ? 'pregunta'
                                            : 'preguntas'
                                    }}
                                    ·
                                    <span class="font-mono">{{
                                        lectura.resumen.opciones
                                    }}</span>
                                    {{
                                        lectura.resumen.opciones === 1
                                            ? 'opción'
                                            : 'opciones'
                                    }}
                                </p>
                                <ul
                                    v-if="lectura.resumen.categorias.length > 0"
                                    class="mt-3 flex flex-col divide-y divide-linea text-xs"
                                >
                                    <li
                                        v-for="categoria in lectura.resumen
                                            .categorias"
                                        :key="categoria.nombre"
                                        class="flex items-center justify-between gap-3 py-1.5"
                                    >
                                        <span class="font-medium">{{
                                            categoria.nombre
                                        }}</span>
                                        <span class="text-tinta-suave">
                                            <span class="font-mono">{{
                                                categoria.preguntas
                                            }}</span>
                                            {{
                                                categoria.preguntas === 1
                                                    ? 'pregunta'
                                                    : 'preguntas'
                                            }}
                                            ·
                                            <span class="font-mono"
                                                >{{
                                                    categoria.importancia
                                                }}%</span
                                            >
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <div
                                v-if="lectura.errores.length > 0"
                                class="rounded-xl bg-aviso-suave p-4 text-xs text-aviso"
                                role="alert"
                            >
                                <p
                                    class="flex items-center gap-2 text-sm font-semibold"
                                >
                                    <TriangleAlert
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                    {{
                                        lectura.errores.length === 1
                                            ? '1 error'
                                            : `${lectura.errores.length} errores`
                                    }}
                                    · corrígelos en el texto para poder crearlo
                                </p>
                                <ul class="mt-2 flex flex-col gap-1">
                                    <li
                                        v-for="(error, i) in lectura.errores"
                                        :key="`e${i}`"
                                    >
                                        <button
                                            type="button"
                                            class="mr-1 font-mono font-semibold underline underline-offset-2"
                                            @click="irALinea(error.linea)"
                                        >
                                            Línea {{ error.linea }}
                                        </button>
                                        {{ error.mensaje }}
                                    </li>
                                </ul>
                            </div>

                            <div
                                v-if="lectura.avisos.length > 0"
                                class="rounded-xl bg-alerta-suave p-4 text-xs text-alerta"
                            >
                                <p class="text-sm font-semibold">
                                    Se puede crear, pero falta esto para
                                    publicarlo
                                </p>
                                <p class="mt-0.5">
                                    Corrígelo ahora en el texto o después en el
                                    editor.
                                </p>
                                <ul class="mt-2 flex flex-col gap-1">
                                    <li
                                        v-for="(aviso, i) in lectura.avisos"
                                        :key="`a${i}`"
                                    >
                                        <button
                                            type="button"
                                            class="mr-1 font-mono font-semibold underline underline-offset-2"
                                            @click="irALinea(aviso.linea)"
                                        >
                                            Línea {{ aviso.linea }}
                                        </button>
                                        {{ aviso.mensaje }}
                                    </li>
                                </ul>
                            </div>

                            <p
                                v-if="
                                    lectura.errores.length === 0 &&
                                    lectura.avisos.length === 0
                                "
                                class="rounded-lg bg-exito-suave px-3 py-2 text-xs text-exito"
                            >
                                Todo en orden: cumple los requisitos para
                                publicarse. Al crearlo se abre el editor para
                                que lo revises.
                            </p>
                        </div>
                    </div>
                </section>

                <div
                    class="flex flex-wrap items-center justify-end gap-3 rounded-b-2xl border-t border-linea bg-lienzo/40 px-5 py-4"
                >
                    <Boton
                        variante="secundario"
                        :href="
                            sectorElegido
                                ? rutas.diagnosticos.sector(sectorElegido.id)
                                : rutas.diagnosticos.todos()
                        "
                    >
                        Cancelar
                    </Boton>
                    <Boton
                        type="submit"
                        :disabled="!listo"
                        :cargando="form.processing"
                    >
                        Crear y abrir el editor
                    </Boton>
                </div>
            </form>

            <aside
                class="rounded-2xl border border-linea bg-linear-to-br from-marca-suave/70 to-white p-5 text-sm"
            >
                <h2 class="font-semibold">Qué pasa al crearlo</h2>
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
                        ¿Quieres copiar uno concreto con otro nombre? También
                        puedes usar <strong>Duplicar</strong> en su fila de la
                        lista de diagnósticos.
                    </span>
                </p>
            </aside>
        </div>
    </div>
</template>
