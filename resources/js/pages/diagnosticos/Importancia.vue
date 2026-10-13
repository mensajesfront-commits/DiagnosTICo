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
 * Envía: PUT /diagnosticos/{id}/importancia con importancias{parte_id: valor}.
 */
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import { rutas } from '@/lib/rutas';
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

const ancho = computed(() => `${Math.min(100, total.value)}%`);
const fmt = (n: number) => String(redondear(n));
</script>

<template>
    <Head :title="`Importancia · ${diagnostico.nombre}`" />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
        <header
            class="flex shrink-0 flex-wrap items-start justify-between gap-4"
        >
            <div>
                <Link
                    :href="rutas.diagnosticos.editar(diagnostico.id)"
                    class="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-tinta-suave hover:text-marca"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    {{ diagnostico.nombre }} · {{ diagnostico.sector.nombre }}
                </Link>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold">Editar importancia</h1>
                    <Etiqueta tono="alerta">
                        Borrador · v{{ diagnostico.version }}
                    </Etiqueta>
                </div>
            </div>
            <div class="flex flex-col items-end gap-1">
                <div class="flex gap-3">
                    <Boton
                        variante="secundario"
                        :href="rutas.diagnosticos.editar(diagnostico.id)"
                    >
                        Cancelar
                    </Boton>
                    <Boton
                        :disabled="!exacto || cambiadas === 0"
                        :cargando="form.processing"
                        @click="guardar"
                    >
                        Guardar importancia
                    </Boton>
                </div>
                <p class="text-xs text-tinta-suave">
                    El total debe sumar 100% (ahora {{ fmt(total) }}%)
                </p>
            </div>
        </header>

        <div
            class="grid items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[minmax(0,1.2fr)_1fr_1fr] lg:grid-rows-[minmax(0,1fr)]"
        >
            <section
                class="flex flex-col rounded-xl border-2 border-marca bg-white lg:max-h-full lg:min-h-0"
            >
                <div
                    class="flex shrink-0 items-baseline justify-between px-4 pt-4 pb-2"
                >
                    <h2 class="text-sm font-semibold">
                        Importancia por categoría
                    </h2>
                    <span class="text-xs text-tinta-suave">
                        Antes → ahora · {{ cambiadas }}
                        {{ cambiadas === 1 ? 'cambiada' : 'cambiadas' }}
                    </span>
                </div>
                <ul class="min-h-0 flex-1 overflow-y-auto px-2">
                    <li
                        v-for="p in partes"
                        :key="p.id"
                        :class="
                            cn(
                                'flex items-center gap-2 rounded-md px-2 py-1.5 text-sm',
                                Number(form.importancias[p.id]) !==
                                    p.importancia && 'bg-alerta-suave/50',
                            )
                        "
                    >
                        <label :for="`importancia-${p.id}`" class="flex-1">
                            {{ p.nombre }}
                        </label>
                        <span
                            v-if="
                                Number(form.importancias[p.id]) !==
                                p.importancia
                            "
                            class="font-mono text-xs text-tinta-suave line-through"
                        >
                            {{ fmt(p.importancia) }}
                        </span>
                        <span
                            v-if="
                                Number(form.importancias[p.id]) !==
                                p.importancia
                            "
                            class="text-tinta-suave"
                            aria-hidden="true"
                            >→</span
                        >
                        <input
                            :id="`importancia-${p.id}`"
                            v-model.number="form.importancias[p.id]"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            class="h-9 w-20 rounded-md border border-linea-fuerte bg-white px-2 text-right font-mono text-sm focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none"
                        />
                        <span class="text-xs text-tinta-suave">%</span>
                    </li>
                </ul>
                <div
                    class="flex shrink-0 flex-col gap-2 border-t border-linea p-4"
                >
                    <p class="flex justify-between text-sm font-semibold">
                        Total de importancia
                        <span
                            :class="
                                cn(
                                    'font-mono',
                                    exacto ? 'text-exito' : 'text-aviso',
                                )
                            "
                        >
                            {{ fmt(total) }}% {{ exacto ? '✓' : '✗' }}
                        </span>
                    </p>
                    <div
                        class="h-2 rounded-full bg-lienzo-oscuro"
                        aria-hidden="true"
                    >
                        <div
                            :class="
                                cn(
                                    'h-2 rounded-full',
                                    exacto ? 'bg-exito' : 'bg-aviso',
                                )
                            "
                            :style="{ width: ancho }"
                        />
                    </div>
                    <p v-if="mensaje" class="text-xs text-aviso" role="status">
                        ⚠ {{ mensaje }}
                    </p>
                    <p
                        v-if="form.errors.importancias"
                        class="text-xs text-aviso"
                        role="alert"
                    >
                        {{ form.errors.importancias }}
                    </p>
                    <Boton variante="secundario" @click="repartir">
                        Repartir en partes iguales
                    </Boton>
                    <p class="text-xs text-tinta-suave">
                        Pone {{ fmt(Math.floor(10000 / partes.length) / 100) }}%
                        a cada categoría. Podrás deshacerlo con Cancelar.
                    </p>
                </div>
            </section>

            <section
                class="rounded-xl border border-linea bg-white p-5 text-sm"
            >
                <h2 class="font-semibold">Qué significa la importancia</h2>
                <p class="mt-3">
                    Es cuánto aporta cada categoría al puntaje total del
                    diagnóstico. Ejemplo: si "Presencia en línea" pesa 14% y
                    saca 55, aporta 7,7 puntos al total (55 × 14%).
                </p>
                <p class="mt-3">
                    Cada diagnóstico tiene su propia importancia; empieza
                    repartida en partes iguales y no depende del sector.
                </p>
            </section>

            <section
                class="rounded-xl border border-linea bg-white p-5 text-sm"
            >
                <h2 class="font-semibold">Efecto del cambio</h2>
                <p class="mt-3">
                    <strong>Mediciones ya respondidas:</strong> no cambian. Cada
                    medición guarda la importancia con la que se calculó.
                </p>
                <p class="mt-3">
                    <strong>Mediciones pendientes:</strong> la empresa que aún
                    responde con una versión publicada no cambia. La nueva
                    importancia se aplica al publicar la v{{
                        diagnostico.version
                    }}<template v-if="pendientes">
                        (hoy {{ pendientes }}
                        {{
                            pendientes === 1
                                ? 'empresa tiene'
                                : 'empresas tienen'
                        }}
                        una medición pendiente)</template
                    >.
                </p>
                <p class="mt-3">
                    <strong>Variación:</strong> se compara puntaje contra
                    puntaje; si cambió la importancia entre dos mediciones, el
                    resultado lo avisa con una nota.
                </p>
                <p
                    class="mt-4 rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave"
                >
                    Los niveles de resultado no se editan aquí: son fijos
                    (Crítico 0–29, Se puede mejorar 30–59, Vas en buen camino
                    60–79, Sigue así 80–100).
                </p>
            </section>
        </div>
    </div>
</template>
