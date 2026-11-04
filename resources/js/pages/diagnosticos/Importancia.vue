<script setup lang="ts">
/**
 * A2.1c · Editar importancia (HU-027, RN-011).
 *
 * - Un porcentaje por categoría; el total se calcula en vivo (CA-001) y
 *   "Guardar importancia" solo se activa con exactamente 100% (CA-003),
 *   diciendo cuánto sobra o falta.
 * - Las que cambiaron se resaltan con el valor anterior ("12 → 14").
 * - "Repartir en partes iguales" (CA-004): la última se lleva el redondeo,
 *   igual que en el servidor.
 * - A la derecha, qué significa la importancia y el efecto del cambio: las
 *   mediciones hechas y las pendientes no cambian.
 *
 * Diseño: arriba, el total en un anillo con su estado (suma exacta, sobran o
 * faltan), cuántas cambiaron, "Repartir en partes iguales" y "Deshacer
 * cambios". Cada categoría es una fila con el número y una barra deslizable;
 * las cambiadas se resaltan con "antes → ahora". A la derecha, qué significa
 * la importancia, el efecto del cambio y los niveles fijos.
 *
 * Envía: PUT /diagnosticos/{id}/importancia con importancias{parte_id: valor}.
 */
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChartNoAxesColumn,
    CircleCheck,
    Clock,
    Equal,
    Info,
    Lightbulb,
    Save,
    Scale,
    TrendingUp,
    Undo2,
    X,
} from '@lucide/vue';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import { niveles } from '@/lib/niveles';
import type { Nivel } from '@/lib/niveles';
import { rutas } from '@/lib/rutas';
import { plural } from '@/lib/texto';
import { cn } from '@/lib/utils';
import type { CabeceraEditor } from '@/types/editor';

const props = defineProps<{
    diagnostico: CabeceraEditor;
    partes: { id: number; nombre: string; importancia: number }[];
    pendientes: number;
}>();

const form = useForm({
    importancias: Object.fromEntries(
        props.partes.map((p) => [p.id, p.importancia]),
    ) as Record<number, number>,
});

const redondear = (n: number) => Math.round(n * 100) / 100;

const total = computed(() =>
    redondear(
        Object.values(form.importancias).reduce(
            (s, v) => s + (Number(v) || 0),
            0,
        ),
    ),
);

const exacto = computed(() => Math.abs(total.value - 100) < 0.005);

const cambiadas = computed(
    () =>
        props.partes.filter(
            (p) => Number(form.importancias[p.id]) !== p.importancia,
        ).length,
);

const mensaje = computed(() => {
    const diferencia = redondear(Math.abs(total.value - 100));

    if (exacto.value) {
        return null;
    }

    return total.value > 100
        ? `Sobran ${diferencia} puntos: baja alguna categoría hasta que el total sea exactamente 100%.`
        : `Faltan ${diferencia} puntos: sube alguna categoría hasta que el total sea exactamente 100%.`;
});

/** Igual que `ContenidoDiagnostico::repartir`: la última se lleva el redondeo. */
function repartir(): void {
    const n = props.partes.length;
    const parte = Math.floor(10000 / n) / 100;

    props.partes.forEach((p, i) => {
        form.importancias[p.id] =
            i === n - 1 ? redondear(100 - parte * (n - 1)) : parte;
    });
}

function guardar(): void {
    form.put(rutas.diagnosticos.importancia(props.diagnostico.id));
}

const fmt = (n: number) => String(redondear(n));

const cambio = (id: number, antes: number) =>
    Number(form.importancias[id]) !== antes;

/** Anillo del total (radio 26). */
const CIRCUNFERENCIA = 2 * Math.PI * 26;
const desfase = computed(
    () => CIRCUNFERENCIA * (1 - Math.min(100, Math.max(0, total.value)) / 100),
);

const estado = computed(() => {
    if (exacto.value) {
        return {
            texto: 'Suma exacta',
            clase: 'bg-exito-suave text-exito',
            anillo: 'stroke-exito',
        };
    }

    return total.value > 100
        ? {
              texto: `Sobran ${fmt(total.value - 100)} puntos`,
              clase: 'bg-aviso-suave text-aviso',
              anillo: 'stroke-aviso',
          }
        : {
              texto: `Faltan ${fmt(100 - total.value)} puntos`,
              clase: 'bg-alerta-suave text-alerta',
              anillo: 'stroke-alerta',
          };
});

const nivelesFijos: Exclude<Nivel, 'sin'>[] = [
    'critico',
    'mejorar',
    'camino',
    'sigue',
];
</script>

<template>
    <Head :title="`Importancia · ${diagnostico.nombre}`" />

    <div class="flex flex-col gap-5 p-4 sm:p-6 lg:h-dvh lg:overflow-hidden">
        <header
            class="flex shrink-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div class="min-w-0">
                <Link
                    :href="rutas.diagnosticos.editar(diagnostico.id)"
                    class="mb-3 inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-tinta-suave hover:text-marca"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Volver al editor
                </Link>
                <div class="flex items-start gap-3">
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                        aria-hidden="true"
                    >
                        <Scale class="size-5" />
                    </span>
                    <div class="min-w-0">
                        <h1
                            class="flex flex-wrap items-center gap-2 text-2xl font-semibold"
                        >
                            Editar importancia
                            <Etiqueta tono="alerta">
                                Borrador · v{{ diagnostico.version }}
                            </Etiqueta>
                        </h1>
                        <p class="mt-0.5 truncate text-sm text-tinta-suave">
                            {{ diagnostico.nombre }} ·
                            {{ diagnostico.sector.nombre }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 gap-2 sm:gap-3">
                <Boton
                    variante="secundario"
                    class="flex-1 sm:flex-none"
                    :href="rutas.diagnosticos.editar(diagnostico.id)"
                >
                    <X class="size-4" aria-hidden="true" />
                    Cancelar
                </Boton>
                <Boton
                    class="flex-1 sm:flex-none"
                    :disabled="!exacto || cambiadas === 0"
                    :cargando="form.processing"
                    @click="guardar"
                >
                    <Save class="size-4" aria-hidden="true" />
                    Guardar importancia
                </Boton>
            </div>
        </header>

        <div
            class="grid items-start gap-5 lg:min-h-0 lg:flex-1 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)]"
        >
            <section
                class="flex flex-col overflow-hidden rounded-2xl border border-linea bg-white lg:max-h-full lg:min-h-0"
                aria-labelledby="titulo-importancia"
            >
                <!-- Resumen del total -->
                <div
                    class="flex shrink-0 flex-col gap-4 border-b border-linea bg-linear-to-br from-marca-suave/70 to-white p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5"
                >
                    <div class="flex items-center gap-4">
                        <div class="relative size-20 shrink-0">
                            <svg
                                viewBox="0 0 64 64"
                                class="size-20 -rotate-90"
                                aria-hidden="true"
                            >
                                <circle
                                    cx="32"
                                    cy="32"
                                    r="26"
                                    fill="none"
                                    stroke-width="6"
                                    class="stroke-lienzo-oscuro"
                                />
                                <circle
                                    cx="32"
                                    cy="32"
                                    r="26"
                                    fill="none"
                                    stroke-width="6"
                                    stroke-linecap="round"
                                    :class="[
                                        estado.anillo,
                                        'transition-[stroke-dashoffset] duration-300',
                                    ]"
                                    :stroke-dasharray="CIRCUNFERENCIA"
                                    :stroke-dashoffset="desfase"
                                />
                            </svg>
                            <span
                                class="absolute inset-0 flex items-center justify-center font-mono text-xs font-semibold"
                                >{{ fmt(total) }}%</span
                            >
                        </div>
                        <div>
                            <h2 id="titulo-importancia" class="font-semibold">
                                Importancia por categoría
                            </h2>
                            <div
                                class="mt-1 flex flex-wrap items-center gap-1.5 text-xs"
                                role="status"
                            >
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium',
                                        estado.clase,
                                    ]"
                                >
                                    <CircleCheck
                                        v-if="exacto"
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                    {{ estado.texto }}
                                </span>
                                <span
                                    class="rounded-full bg-white px-2.5 py-1 text-tinta-suave ring-1 ring-linea"
                                >
                                    {{
                                        plural(
                                            cambiadas,
                                            'cambiada',
                                            'cambiadas',
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Boton
                            variante="secundario"
                            tamano="sm"
                            :disabled="!form.isDirty"
                            @click="form.reset()"
                        >
                            <Undo2 class="size-3.5" aria-hidden="true" />
                            Deshacer cambios
                        </Boton>
                        <Boton
                            variante="secundario"
                            tamano="sm"
                            @click="repartir"
                        >
                            <Equal class="size-3.5" aria-hidden="true" />
                            Repartir en partes iguales
                        </Boton>
                    </div>
                </div>

                <p
                    v-if="mensaje"
                    class="shrink-0 border-b border-linea bg-aviso-suave/50 px-4 py-2.5 text-sm text-aviso sm:px-5"
                >
                    {{ mensaje }}
                </p>
                <p
                    v-if="form.errors.importancias"
                    class="shrink-0 border-b border-linea bg-aviso-suave/50 px-4 py-2.5 text-sm text-aviso sm:px-5"
                    role="alert"
                >
                    {{ form.errors.importancias }}
                </p>

                <!-- Categorías -->
                <ul
                    class="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto p-3 sm:p-4"
                >
                    <li
                        v-for="(p, i) in partes"
                        :key="p.id"
                        :class="
                            cn(
                                'rounded-xl border px-3 py-3 transition-colors sm:px-4',
                                cambio(p.id, p.importancia)
                                    ? 'border-alerta/40 bg-alerta-suave/40'
                                    : 'border-linea',
                            )
                        "
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-lienzo font-mono text-xs font-semibold text-tinta-suave"
                                aria-hidden="true"
                                >{{ i + 1 }}</span
                            >
                            <label
                                :for="`importancia-${p.id}`"
                                class="min-w-0 flex-1 text-sm font-medium"
                            >
                                {{ p.nombre }}
                                <span
                                    v-if="cambio(p.id, p.importancia)"
                                    class="mt-0.5 block text-xs font-normal text-tinta-suave"
                                >
                                    Antes
                                    <span class="font-mono line-through">{{
                                        fmt(p.importancia)
                                    }}</span>
                                    →
                                    <span class="font-mono text-tinta">{{
                                        fmt(
                                            Number(form.importancias[p.id]) ||
                                                0,
                                        )
                                    }}</span>
                                </span>
                            </label>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <input
                                    :id="`importancia-${p.id}`"
                                    v-model.number="form.importancias[p.id]"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    class="h-9 w-20 rounded-lg border border-linea-fuerte bg-white px-2 text-right font-mono text-sm focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none"
                                />
                                <span class="text-sm text-tinta-suave">%</span>
                            </div>
                        </div>
                        <input
                            v-model.number="form.importancias[p.id]"
                            type="range"
                            min="0"
                            max="100"
                            step="1"
                            :aria-label="`Importancia de ${p.nombre}`"
                            class="mt-2.5 h-1.5 w-full cursor-pointer accent-marca"
                        />
                    </li>
                </ul>
            </section>

            <aside class="flex flex-col gap-4 lg:max-h-full lg:overflow-y-auto">
                <section
                    class="rounded-2xl border border-linea bg-white p-5 text-sm"
                >
                    <h2 class="flex items-center gap-2 font-semibold">
                        <span
                            class="flex size-8 items-center justify-center rounded-lg bg-marca-suave text-marca"
                            aria-hidden="true"
                        >
                            <Info class="size-4" />
                        </span>
                        Qué significa la importancia
                    </h2>
                    <p class="mt-3 text-tinta-suave">
                        Es cuánto aporta cada categoría al puntaje total del
                        diagnóstico. Cada diagnóstico tiene la suya; empieza
                        repartida en partes iguales y no depende del sector.
                    </p>
                    <p
                        class="mt-3 flex items-start gap-2 rounded-xl bg-lienzo px-3 py-2.5 text-xs text-tinta-suave"
                    >
                        <Lightbulb
                            class="mt-px size-3.5 shrink-0 text-marca"
                            aria-hidden="true"
                        />
                        <span>
                            Ejemplo: si "Presencia en línea" pesa 14% y saca 55,
                            aporta 7,7 puntos al total (55 × 14%).
                        </span>
                    </p>
                </section>

                <section
                    class="rounded-2xl border border-linea bg-white p-5 text-sm"
                >
                    <h2 class="flex items-center gap-2 font-semibold">
                        <span
                            class="flex size-8 items-center justify-center rounded-lg bg-marca-suave text-marca"
                            aria-hidden="true"
                        >
                            <ChartNoAxesColumn class="size-4" />
                        </span>
                        Efecto del cambio
                    </h2>
                    <ul class="mt-3 flex flex-col gap-3">
                        <li class="flex items-start gap-2.5">
                            <CircleCheck
                                class="mt-0.5 size-4 shrink-0 text-exito"
                                aria-hidden="true"
                            />
                            <p>
                                <strong>Mediciones ya respondidas:</strong>
                                no cambian. Cada medición guarda la importancia
                                con la que se calculó.
                            </p>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <Clock
                                class="mt-0.5 size-4 shrink-0 text-alerta"
                                aria-hidden="true"
                            />
                            <p>
                                <strong>Mediciones pendientes:</strong> la
                                empresa que aún responde con una versión
                                publicada no cambia. La nueva importancia se
                                aplica al publicar la v{{ diagnostico.version
                                }}<template v-if="pendientes">
                                    (hoy
                                    {{
                                        pendientes === 1
                                            ? '1 empresa tiene'
                                            : `${pendientes} empresas tienen`
                                    }}
                                    una medición pendiente)</template
                                >.
                            </p>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <TrendingUp
                                class="mt-0.5 size-4 shrink-0 text-marca"
                                aria-hidden="true"
                            />
                            <p>
                                <strong>Variación:</strong> se compara puntaje
                                contra puntaje; si cambió la importancia entre
                                dos mediciones, el resultado lo avisa con una
                                nota.
                            </p>
                        </li>
                    </ul>
                </section>

                <section
                    class="rounded-2xl border border-linea bg-white p-5 text-sm"
                >
                    <h2 class="font-semibold">Niveles de resultado</h2>
                    <p class="mt-1 text-xs text-tinta-suave">
                        Son fijos: no se editan aquí.
                    </p>
                    <ul class="mt-3 grid grid-cols-2 gap-2">
                        <li
                            v-for="n in nivelesFijos"
                            :key="n"
                            class="flex items-center gap-2 rounded-lg bg-lienzo px-2.5 py-2 text-xs"
                        >
                            <span
                                :class="[
                                    'size-2.5 shrink-0 rounded-full',
                                    niveles[n].barra,
                                ]"
                                aria-hidden="true"
                            />
                            <span class="min-w-0">
                                <span class="block font-medium">{{
                                    niveles[n].nombre
                                }}</span>
                                <span class="font-mono text-tinta-suave">{{
                                    niveles[n].rango
                                }}</span>
                            </span>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>
