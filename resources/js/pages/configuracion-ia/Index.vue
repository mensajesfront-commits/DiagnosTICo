<script setup lang="ts">
/**
 * A4 · Configuración de la IA, etapa "Analizar categoría" (HU-041), con sus
 * tres alcances en la misma pantalla:
 *
 * - Todas las empresas: las instrucciones generales (contexto, tarea,
 *   detalles y ejemplos), con los datos que se insertan solos y
 *   "Restaurar texto original".
 * - Solo un sector (A4.5, HU-042) y solo una empresa (A4.6, HU-043): en
 *   cada elemento, usar el de la capa anterior, agregar o reemplazar.
 *
 * El formato de la respuesta se muestra fijo (CA-003). A la derecha: qué
 * instrucciones se usan, "Probar con un ejemplo" (A4.7), "Ver instrucciones
 * completas" (A4.4), cuándo aplica, la última edición y "Quitar ajuste".
 * Cambiar de alcance con cambios sin guardar pide confirmación.
 */
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Combobox from '@/components/base/Combobox.vue';
import Modal from '@/components/base/Modal.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import ElementoPrompt from '@/components/ia/ElementoPrompt.vue';
import ModalProbarPrompt from '@/components/ia/ModalProbarPrompt.vue';
import ModalPromptCompleto from '@/components/ia/ModalPromptCompleto.vue';
import ModalQuitarAjuste from '@/components/ia/ModalQuitarAjuste.vue';
import { diaYMes } from '@/lib/fechas';
import { elementos, variablesUsadas } from '@/lib/ia';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type {
    Alcance,
    Borrador,
    ConfiguracionIa,
    ElementoPrompt as Elemento,
    Modo,
    Textos,
} from '@/types/configuracionIa';

const props = defineProps<ConfiguracionIa>();

const page = usePage();
const puedeEditar = computed(() =>
    page.props.auth.permisos.includes('ia.editar'),
);

const capa = computed(() =>
    props.alcance === 'sector'
        ? props.sector
        : props.alcance === 'empresa'
          ? props.empresa
          : null,
);

// --- Formulario de la capa que se edita --------------------------------------
const vacios = (): Textos => ({
    contexto: '',
    tarea: '',
    detalles: '',
    ejemplos: '',
});
const usarTodo = (): Record<Elemento, Modo> => ({
    contexto: 'usar',
    tarea: 'usar',
    detalles: 'usar',
    ejemplos: 'usar',
});

function inicial(): Borrador {
    if (props.alcance === 'general') {
        return {
            alcance: 'general',
            id: null,
            modos: usarTodo(),
            textos: { ...props.general.textos },
        };
    }

    const ajuste = capa.value?.ajuste;

    return {
        alcance: props.alcance,
        id: capa.value?.id ?? null,
        modos: ajuste ? { ...ajuste.modos } : usarTodo(),
        textos: ajuste ? { ...vacios(), ...ajuste.textos } : vacios(),
    };
}

const form = useForm<Borrador>(inicial());

watch(
    () => [props.alcance, capa.value?.id, props.ultima_edicion?.fecha],
    () => {
        form.defaults(inicial());
        form.reset();
        form.clearErrors();
    },
);

const errores = computed(
    () => form.errors as Record<string, string | undefined>,
);

function guardar(): void {
    form.put(rutas.ia.guardar(), { preserveScroll: true });
}

// --- Alcance, sector y empresa ---------------------------------------------------
const alcances: { valor: Alcance; etiqueta: string }[] = [
    { valor: 'general', etiqueta: 'Todas las empresas' },
    { valor: 'sector', etiqueta: 'Solo un sector' },
    { valor: 'empresa', etiqueta: 'Solo una empresa' },
];

/** Destino pendiente de confirmar si hay cambios sin guardar. */
const destino = ref<string | null>(null);

function ir(url: string): void {
    if (form.isDirty) {
        destino.value = url;

        return;
    }

    router.get(url, {}, { preserveScroll: false });
}

function confirmarSalida(): void {
    const url = destino.value;
    destino.value = null;
    form.reset();

    if (url) {
        router.get(url);
    }
}

const sectorElegido = computed({
    get: () => props.sector?.id ?? '',
    set: (id) => id && ir(rutas.ia.index('sector', Number(id))),
});

const etiquetaEmpresa = (e: { nombre: string; sector: string | null }) =>
    `${e.nombre} · ${e.sector ?? 'sin sector'}`;

const empresaTexto = computed({
    get: () => (props.empresa ? etiquetaEmpresa(props.empresa) : ''),
    set: (texto) => {
        const e = props.empresas.find((x) => etiquetaEmpresa(x) === texto);

        if (e && e.id !== props.empresa?.id) {
            ir(rutas.ia.index('empresa', e.id));
        }
    },
});

// --- Panel derecho -----------------------------------------------------------------
const nombreCapa = computed(() => capa.value?.nombre ?? '');

const titulo = computed(() =>
    props.alcance === 'sector'
        ? `Personalizar por sector${props.sector ? ` · ${props.sector.nombre}` : ''}`
        : props.alcance === 'empresa'
          ? `Ajuste para una empresa${props.empresa ? ` · ${props.empresa.nombre}` : ''}`
          : 'Analizar categoría',
);

/** De qué capa sale cada elemento con lo que hay en pantalla. */
const usoPorCapa = computed(() => {
    const grupos: Record<string, string[]> = {};

    for (const e of elementos) {
        const origen =
            props.alcance !== 'general' && form.modos[e.clave] !== 'usar'
                ? nombreCapa.value
                : props.alcance === 'empresa' &&
                    props.origen_base[e.clave] === 'sector'
                  ? `Sector ${props.empresa?.sector ?? ''}`
                  : 'General';
        (grupos[origen] ??= []).push(
            e.titulo.toLowerCase().replace(' y restricciones', ''),
        );
    }

    return Object.entries(grupos);
});

const ejemplos = computed(() =>
    props.ejemplos.filter((e) =>
        props.alcance === 'sector'
            ? e.sector_id === props.sector?.id
            : props.alcance === 'empresa'
              ? e.empresa_id === props.empresa?.id
              : true,
    ),
);

const ultimaEdicion = computed(() =>
    props.ultima_edicion
        ? `${diaYMes(new Date(props.ultima_edicion.fecha))}${props.ultima_edicion.por ? ` · ${props.ultima_edicion.por}` : ''}`
        : props.alcance === 'general'
          ? 'Sin cambios: se usan los textos originales.'
          : 'Sin ajuste guardado.',
);

const variablesEnUso = computed(() => {
    const todos = new Set(
        elementos.flatMap((e) => variablesUsadas(form.textos[e.clave] ?? '')),
    );

    return props.variables.map((v) => ({ ...v, enUso: todos.has(v.codigo) }));
});

const cuandoAplica = computed(() => {
    const quien = !capa.value
        ? ''
        : props.alcance === 'sector'
          ? ` de las empresas de ${nombreCapa.value}`
          : ` de ${nombreCapa.value}`;

    return `Solo a los análisis${quien} que se hagan después de guardar. Las mediciones ya analizadas conservan el análisis que tienen.`;
});

const marca = (codigo: string) => '{' + codigo + '}';

const modalCompleto = ref(false);
const modalProbar = ref(false);
const modalQuitar = ref(false);

const listoParaEditar = computed(
    () => props.alcance === 'general' || capa.value !== null,
);
</script>

<template>
    <Head title="Configuración IA" />

    <div class="flex flex-col lg:h-dvh lg:overflow-hidden">
        <header
            class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-linea bg-white px-6 py-3"
        >
            <div>
                <p class="text-xs text-tinta-suave">
                    Configuración IA · Analizar categoría
                </p>
                <h1 class="text-xl font-semibold">{{ titulo }}</h1>
            </div>
            <div
                v-if="puedeEditar && listoParaEditar"
                class="flex items-center gap-2"
            >
                <span class="text-xs text-tinta-suave" aria-live="polite">
                    {{
                        form.isDirty
                            ? 'Cambios sin guardar'
                            : 'Sin cambios pendientes'
                    }}
                </span>
                <Boton
                    variante="secundario"
                    :disabled="!form.isDirty || form.processing"
                    @click="form.reset()"
                >
                    Descartar
                </Boton>
                <Boton
                    :disabled="!form.isDirty"
                    :cargando="form.processing"
                    @click="guardar"
                >
                    {{
                        alcance === 'general'
                            ? 'Guardar instrucciones'
                            : 'Guardar ajuste'
                    }}
                </Boton>
            </div>
        </header>

        <div
            class="grid gap-5 p-6 lg:min-h-0 lg:flex-1 lg:grid-cols-[minmax(0,1fr)_300px] lg:overflow-hidden"
        >
            <!-- Columna principal -->
            <div
                class="flex flex-col gap-4 lg:min-h-0 lg:overflow-y-auto lg:pr-2"
            >
                <p class="text-sm text-tinta-suave">
                    Se ejecuta una vez por categoría, con todas sus respuestas,
                    y hace todo en un solo análisis: una observación por
                    pregunta, la calificación de las abiertas, la observación de
                    la categoría y su recomendación. No necesitas cambiar nada
                    para que funcione: ya viene listo.
                </p>

                <section>
                    <h2 class="text-sm font-semibold">
                        ¿A quién se aplica lo que escribas aquí?
                    </h2>
                    <div
                        class="mt-2 grid grid-cols-3 overflow-hidden rounded-lg border border-linea-fuerte bg-white text-sm"
                        role="radiogroup"
                        aria-label="Alcance"
                    >
                        <button
                            v-for="a in alcances"
                            :key="a.valor"
                            type="button"
                            role="radio"
                            :aria-checked="alcance === a.valor"
                            :class="
                                cn(
                                    'px-3 py-2.5 transition-colors',
                                    alcance === a.valor
                                        ? 'bg-marca font-medium text-white'
                                        : 'hover:bg-lienzo',
                                )
                            "
                            @click="
                                alcance !== a.valor &&
                                ir(rutas.ia.index(a.valor))
                            "
                        >
                            {{ a.etiqueta }}
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-tinta-suave">
                        <template v-if="alcance === 'general'">
                            Lo que escribes aquí son las
                            <strong>instrucciones generales para la IA</strong>
                            (lo que en términos técnicos se llama “prompt”). Un
                            sector o una empresa pueden ajustarlas aparte;
                            siempre se usa lo más específico.
                        </template>
                        <template v-else-if="alcance === 'sector'">
                            Lo que escribas aquí ajusta las instrucciones
                            generales solo para el sector elegido.
                        </template>
                        <template v-else>
                            Lo que escribas aquí ajusta las instrucciones solo
                            para la empresa elegida; tiene prioridad sobre las
                            de su sector.
                        </template>
                    </p>
                </section>

                <!-- Sector o empresa -->
                <section
                    v-if="alcance === 'sector'"
                    class="rounded-xl border border-linea bg-white p-5"
                >
                    <label for="ia-sector" class="text-sm font-semibold"
                        >¿Para qué sector?</label
                    >
                    <Seleccion
                        id="ia-sector"
                        v-model="sectorElegido"
                        class="mt-2"
                    >
                        <option v-for="s in sectores" :key="s.id" :value="s.id">
                            {{ s.nombre
                            }}{{ s.con_ajuste ? ' · con ajuste' : '' }}
                        </option>
                    </Seleccion>
                    <p v-if="sector" class="mt-2 text-sm text-tinta-suave">
                        Este ajuste aplica a
                        <strong>
                            {{
                                sector.empresas -
                                sector.empresas_con_ajuste.length
                            }}
                            de las {{ sector.empresas }} empresas</strong
                        >
                        del sector {{ sector.nombre }}.
                        <template v-if="sector.empresas_con_ajuste.length">
                            {{ sector.empresas_con_ajuste.join(', ') }}
                            {{
                                sector.empresas_con_ajuste.length === 1
                                    ? 'tiene su propio ajuste y se salta'
                                    : 'tienen su propio ajuste y se saltan'
                            }}
                            este.
                        </template>
                        Los sectores sin ajuste usan las instrucciones generales
                        tal cual.
                    </p>
                    <p v-else class="mt-2 text-sm text-tinta-suave">
                        No hay sectores creados.
                    </p>
                </section>

                <section
                    v-if="alcance === 'empresa'"
                    class="rounded-xl border border-linea bg-white p-5"
                >
                    <label for="ia-empresa" class="text-sm font-semibold"
                        >¿Para qué empresa?</label
                    >
                    <Combobox
                        id="ia-empresa"
                        v-model="empresaTexto"
                        class="mt-2"
                        placeholder="Busca por nombre"
                        :opciones="empresas.map(etiquetaEmpresa)"
                    />
                    <p
                        v-if="empresa"
                        class="mt-2 flex flex-wrap items-center gap-2 text-sm text-tinta-suave"
                    >
                        <span
                            v-if="empresa.ajuste"
                            class="rounded bg-exito-suave px-1.5 py-0.5 text-xs text-exito"
                            >✓ Ajuste activo</span
                        >
                        <span>
                            Hereda del sector
                            <strong>{{ empresa.sector }}</strong
                            >{{
                                empresa.sector_con_ajuste
                                    ? ', que a su vez hereda de las instrucciones generales.'
                                    : ', que usa las instrucciones generales tal cual.'
                            }}
                        </span>
                    </p>
                    <p v-else class="mt-2 text-sm text-tinta-suave">
                        Elige una empresa para ver o crear su ajuste.
                    </p>
                </section>

                <!-- Datos que se insertan solos -->
                <section
                    v-if="alcance === 'general'"
                    class="rounded-xl border border-linea bg-white p-5"
                >
                    <h2 class="text-sm font-semibold">
                        Datos que se insertan solos en esta etapa
                    </h2>
                    <p class="text-xs text-tinta-suave">
                        El sistema reemplaza cada código por el dato real de la
                        empresa. Escríbelos tal cual, con sus llaves: si borras
                        uno, la IA no recibe ese dato en el texto.
                    </p>
                    <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                        <li
                            v-for="v in variablesEnUso"
                            :key="v.codigo"
                            class="flex items-center gap-3 rounded-md bg-lienzo px-3 py-2"
                        >
                            <span
                                :class="
                                    cn(
                                        'rounded px-1.5 py-0.5 text-[11px] whitespace-nowrap',
                                        v.enUso
                                            ? 'bg-exito-suave text-exito'
                                            : 'bg-lienzo-oscuro text-tinta-suave',
                                    )
                                "
                                >{{ v.enUso ? '✓ en uso' : 'sin usar' }}</span
                            >
                            <span class="text-sm">
                                {{ v.nombre }}
                                <span
                                    class="block font-mono text-[11px] text-tinta-suave"
                                    >{{ marca(v.codigo) }} · en
                                    {{
                                        elementos.find(
                                            (e) => e.clave === v.elemento,
                                        )?.titulo
                                    }}</span
                                >
                            </span>
                        </li>
                    </ul>
                </section>

                <template v-if="listoParaEditar">
                    <template v-for="(e, i) in elementos" :key="e.clave">
                        <!-- 4 · Formato fijo, antes de los ejemplos -->
                        <section
                            v-if="e.clave === 'ejemplos'"
                            class="rounded-xl border border-dashed border-linea-fuerte bg-lienzo p-5"
                        >
                            <h2 class="flex items-center gap-2 font-semibold">
                                <span
                                    class="flex size-7 items-center justify-center rounded-full bg-lienzo-oscuro text-xs"
                                    aria-hidden="true"
                                    >4</span
                                >
                                Formato de la respuesta
                                <span
                                    class="rounded bg-white px-1.5 py-0.5 text-[11px] font-normal text-tinta-suave"
                                    >🔒 Fijo</span
                                >
                            </h2>
                            <p class="mt-2 text-sm">
                                La IA devuelve
                                <strong
                                    >una observación por cada pregunta</strong
                                >, <strong>un puntaje de 0 a 100</strong> en las
                                abiertas y, al final,
                                <strong>una observación de la categoría</strong>
                                y <strong>una recomendación</strong> con título
                                y detalle.
                            </p>
                            <p class="mt-1 text-xs text-tinta-suave">
                                Esta parte no se cambia: el sistema la necesita
                                para leer lo que responde la IA y mostrarlo en
                                el resultado.
                            </p>
                            <details class="mt-2 text-xs">
                                <summary class="cursor-pointer text-marca">
                                    Ver detalle técnico
                                </summary>
                                <p class="mt-2">{{ formato.texto }}</p>
                                <pre
                                    class="mt-2 max-h-48 overflow-auto rounded bg-white p-2 font-mono text-[11px]"
                                    >{{ formato.esquema }}</pre>
                            </details>
                        </section>

                        <ElementoPrompt
                            v-model:texto="form.textos[e.clave]"
                            v-model:modo="form.modos[e.clave]"
                            :numero="e.clave === 'ejemplos' ? 5 : i + 1"
                            :clave="e.clave"
                            :titulo="e.titulo"
                            :ayuda="e.ayuda"
                            :alcance="alcance"
                            :base="base[e.clave]"
                            :origen-base="
                                alcance === 'empresa' ? 'sector' : 'general'
                            "
                            :nombre-capa="nombreCapa"
                            :original="
                                alcance === 'general'
                                    ? general.originales[e.clave]
                                    : undefined
                            "
                            :variables="variables"
                            :solo-lectura="!puedeEditar"
                            :error="errores[`textos.${e.clave}`]"
                        />
                    </template>
                </template>

                <p class="pb-2 text-xs text-tinta-suave">
                    Si la IA falla, la categoría se reintenta hasta 3 veces;
                    después la empresa puede reintentarla y te llega un correo.
                </p>
            </div>

            <!-- Panel derecho -->
            <aside class="flex flex-col gap-4 lg:min-h-0 lg:overflow-y-auto">
                <section
                    class="rounded-xl border border-linea bg-white p-4 text-sm"
                >
                    <h2 class="font-semibold">¿Qué instrucciones se usan?</h2>
                    <template v-if="alcance === 'general'">
                        <p class="mt-1 text-tinta-suave">
                            Estas son las
                            <strong class="text-tinta"
                                >instrucciones generales</strong
                            >: las usan todas las empresas, salvo lo que un
                            sector o una empresa ajuste.
                        </p>
                        <ul class="mt-2 flex flex-col gap-1">
                            <li class="flex items-center gap-2">
                                <span
                                    class="size-2 rounded-full bg-tinta-suave"
                                />
                                <strong>General</strong> · todas las empresas
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-marca" />
                                <Link
                                    :href="rutas.ia.index('sector')"
                                    class="underline underline-offset-2"
                                >
                                    {{ resumen.sectores_con_ajuste }}
                                    {{
                                        resumen.sectores_con_ajuste === 1
                                            ? 'sector'
                                            : 'sectores'
                                    }}</Link
                                >
                                con ajuste
                            </li>
                            <li class="flex items-center gap-2">
                                <span
                                    class="size-2 rounded-full bg-violet-500"
                                />
                                <Link
                                    :href="rutas.ia.index('empresa')"
                                    class="underline underline-offset-2"
                                >
                                    {{ resumen.empresas_con_ajuste }}
                                    {{
                                        resumen.empresas_con_ajuste === 1
                                            ? 'empresa'
                                            : 'empresas'
                                    }}</Link
                                >
                                con ajuste
                            </li>
                        </ul>
                    </template>
                    <template v-else>
                        <ol class="mt-1 list-decimal pl-5 text-tinta-suave">
                            <li>Si la empresa tiene ajuste propio, ese.</li>
                            <li>Si no, el ajuste de su sector.</li>
                            <li>
                                Si el sector no tiene, las instrucciones
                                generales.
                            </li>
                        </ol>
                        <p
                            class="mt-2 border-t border-linea pt-2 text-xs text-tinta-suave"
                        >
                            <strong class="text-tinta">Además, siempre:</strong>
                            el “Ten en cuenta” de cada respuesta elegida y el
                            criterio de las abiertas. Se editan en el editor de
                            cada diagnóstico.
                        </p>
                        <template v-if="capa">
                            <p class="mt-2 text-xs text-tinta-suave">
                                Para {{ nombreCapa }} se usa:
                            </p>
                            <ul class="mt-1 flex flex-col gap-1 text-xs">
                                <li
                                    v-for="[origen, partes] in usoPorCapa"
                                    :key="origen"
                                >
                                    <strong>{{ origen }}</strong> ·
                                    {{ partes.join(', ') }}
                                </li>
                            </ul>
                        </template>
                    </template>
                </section>

                <section
                    class="rounded-xl border border-linea bg-white p-4 text-sm"
                >
                    <h2 class="font-semibold">¿Quieres ver cómo queda?</h2>
                    <p class="mt-1 text-tinta-suave">
                        Prueba lo que escribiste con un ejemplo real. Probar no
                        afecta resultados reales.
                    </p>
                    <div class="mt-3 flex flex-col gap-2">
                        <Boton
                            v-if="puedeEditar"
                            :disabled="
                                !listoParaEditar || ejemplos.length === 0
                            "
                            @click="modalProbar = true"
                        >
                            Probar con un ejemplo
                        </Boton>
                        <Boton
                            variante="secundario"
                            :disabled="
                                !listoParaEditar || ejemplos.length === 0
                            "
                            @click="modalCompleto = true"
                        >
                            Ver instrucciones completas
                        </Boton>
                    </div>
                    <p
                        v-if="listoParaEditar && ejemplos.length === 0"
                        class="mt-2 text-xs text-tinta-suave"
                    >
                        No hay mediciones con respuestas
                        {{
                            alcance === 'general'
                                ? 'todavía'
                                : `de ${nombreCapa}`
                        }}
                        para usar de ejemplo.
                    </p>
                    <Link
                        :href="rutas.ia.comoSeArma()"
                        class="mt-3 block text-center text-xs text-marca underline underline-offset-2"
                    >
                        ¿Cómo se arma el prompt final?
                    </Link>
                </section>

                <section
                    class="rounded-xl border border-linea bg-white p-4 text-sm"
                >
                    <h2 class="font-semibold">¿Cuándo aplica?</h2>
                    <p class="mt-1 text-tinta-suave">
                        {{ cuandoAplica }}
                    </p>
                </section>

                <section
                    class="rounded-xl border border-linea bg-white p-4 text-sm"
                >
                    <h2 class="font-semibold">Última edición</h2>
                    <p class="mt-1 text-tinta-suave">{{ ultimaEdicion }}</p>
                </section>

                <section
                    v-if="puedeEditar && capa?.ajuste"
                    class="rounded-xl border border-linea bg-white p-4 text-sm"
                >
                    <h2 class="font-semibold">Quitar el ajuste</h2>
                    <p class="mt-1 text-tinta-suave">
                        {{
                            alcance === 'sector'
                                ? 'Las empresas del sector vuelven a usar las instrucciones generales.'
                                : 'La empresa vuelve a usar las instrucciones de su sector.'
                        }}
                    </p>
                    <Boton
                        variante="secundario"
                        class="mt-3 w-full"
                        @click="modalQuitar = true"
                    >
                        {{
                            alcance === 'sector'
                                ? 'Quitar ajuste del sector'
                                : 'Quitar ajuste propio'
                        }}
                    </Boton>
                </section>
            </aside>
        </div>
    </div>

    <ModalPromptCompleto
        v-if="listoParaEditar"
        v-model:abierto="modalCompleto"
        :ejemplos="ejemplos"
        :borrador="form.data()"
    />
    <ModalProbarPrompt
        v-if="listoParaEditar && puedeEditar"
        v-model:abierto="modalProbar"
        :ejemplos="ejemplos"
        :borrador="form.data()"
    />
    <ModalQuitarAjuste
        v-if="capa && alcance !== 'general'"
        v-model:abierto="modalQuitar"
        :alcance="alcance"
        :id="capa.id"
        :nombre="capa.nombre"
        :empresas="
            sector
                ? sector.empresas - sector.empresas_con_ajuste.length
                : undefined
        "
        :con-ajuste-propio="sector?.empresas_con_ajuste"
    />

    <Modal
        :abierto="destino !== null"
        ancho="sm"
        titulo="¿Descartar los cambios?"
        @update:abierto="(a: boolean) => !a && (destino = null)"
    >
        <p class="text-sm text-tinta-suave">
            Tienes cambios sin guardar en
            {{
                alcance === 'general'
                    ? 'las instrucciones generales'
                    : `el ajuste de ${nombreCapa}`
            }}. Si sigues, se pierden.
        </p>
        <template #pie>
            <Boton variante="secundario" @click="destino = null"
                >Seguir editando</Boton
            >
            <Boton variante="peligro" @click="confirmarSalida"
                >Descartar y seguir</Boton
            >
        </template>
    </Modal>
</template>
