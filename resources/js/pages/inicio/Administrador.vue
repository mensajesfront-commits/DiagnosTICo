<script setup lang="ts">
/**
 * A1 · Inicio del Administrador (HU-006, HU-007).
 *
 * Cuatro indicadores arriba; la tabla "Mediciones" con las pestañas Todas,
 * En curso, No iniciadas, Vencidas y Terminadas; y a la derecha "Empresas
 * por nivel" y "Diagnósticos por sector". Todo llega calculado del backend
 * (`DatosInicio`). En pantallas grandes la página no se desplaza: la tabla
 * va paginada con las filas que caben.
 *
 * Registrar empresas y asignar mediciones no se hace aquí, sino en Empresas
 * (A3).
 */
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import DiagnosticosPorSector from '@/components/inicio/DiagnosticosPorSector.vue';
import EmpresasPorNivel from '@/components/inicio/EmpresasPorNivel.vue';
import TablaMediciones from '@/components/inicio/TablaMediciones.vue';
import type { Nivel } from '@/lib/niveles';
import type {
    FilaMedicion,
    IndicadoresInicio,
    SectorInicio,
} from '@/types/inicio';

const props = defineProps<{
    indicadores: IndicadoresInicio;
    mediciones: FilaMedicion[];
    niveles: Record<Nivel, number>;
    sectores: SectorInicio[];
}>();

// --- Pestañas (HU-007) ---------------------------------------------------------
type Pestana = 'todas' | 'en_curso' | 'no_iniciada' | 'vencida' | 'terminada';

const pestana = ref<Pestana>('todas');

const deLaPestana = (p: Pestana, f: FilaMedicion) =>
    p === 'todas' ||
    f.estado === p ||
    // "Enviada" cuenta como en curso hasta que se publica el resultado.
    (p === 'en_curso' && f.estado === 'enviada');

const opciones = computed(() =>
    (
        [
            ['todas', 'Todas'],
            ['en_curso', 'En curso'],
            ['no_iniciada', 'No iniciadas'],
            ['vencida', 'Vencidas'],
            ['terminada', 'Terminadas'],
        ] as const
    ).map(([valor, etiqueta]) => ({
        valor,
        etiqueta,
        cantidad: props.mediciones.filter((f) => deLaPestana(valor, f)).length,
    })),
);

const filas = computed(() =>
    props.mediciones.filter((f) => deLaPestana(pestana.value, f)),
);

const vacio: Record<Pestana, string> = {
    todas: 'Todavía no hay mediciones. Se asignan desde Empresas.',
    en_curso: 'No hay mediciones en curso.',
    no_iniciada: 'No hay mediciones sin iniciar.',
    vencida: 'No hay mediciones vencidas.',
    terminada: 'Todavía no hay mediciones terminadas.',
};

// --- Indicadores (HU-006 CA-001) -----------------------------------------------
const plural = (n: number, uno: string, varios: string) =>
    `${n} ${n === 1 ? uno : varios}`;

const tarjetas = computed(() => {
    const i = props.indicadores;
    const p = i.pendientes_por_estado;

    return [
        {
            titulo: 'Empresas registradas',
            valor: String(i.empresas),
            detalle: `en ${plural(i.sectores_con_empresas, 'sector', 'sectores')}`,
        },
        {
            titulo: 'Mediciones pendientes',
            valor: String(i.pendientes),
            detalle: [
                plural(p.no_iniciada, 'no iniciada', 'no iniciadas'),
                `${p.en_curso} en curso`,
                plural(p.vencida, 'vencida', 'vencidas'),
            ].join(' · '),
        },
        {
            titulo: 'Diagnósticos completados',
            valor: String(i.completados_mes),
            detalle: 'este mes',
        },
        {
            titulo: 'Puntaje promedio',
            valor:
                i.puntaje_promedio === null ? '—' : String(i.puntaje_promedio),
            detalle: 'último diagnóstico de cada empresa',
        },
    ];
});
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-col gap-5 p-6 lg:h-dvh lg:overflow-hidden">
        <EncabezadoPagina titulo="Inicio" />

        <dl class="grid shrink-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="tarjeta in tarjetas"
                :key="tarjeta.titulo"
                class="rounded-xl border border-linea bg-white px-5 py-4"
            >
                <dt class="text-xs text-tinta-suave">{{ tarjeta.titulo }}</dt>
                <dd class="mt-1 font-mono text-3xl leading-none font-semibold">
                    {{ tarjeta.valor }}
                </dd>
                <dd class="mt-2 text-xs text-tinta-suave">
                    {{ tarjeta.detalle }}
                </dd>
            </div>
        </dl>

        <div
            class="grid items-start gap-5 lg:min-h-0 lg:flex-1 lg:grid-cols-[1fr_300px] lg:grid-rows-[minmax(0,1fr)] lg:items-stretch"
        >
            <section
                class="flex flex-col rounded-xl border border-linea bg-white lg:min-h-0"
            >
                <header
                    class="flex shrink-0 flex-wrap items-center justify-between gap-3 px-5 py-3"
                >
                    <h2 class="text-base font-semibold">Mediciones</h2>
                    <SelectorCompacto
                        v-model="pestana"
                        :opciones="opciones"
                        etiqueta-accesible="Filtrar mediciones por estado"
                    />
                </header>

                <p
                    v-if="filas.length === 0"
                    class="flex flex-1 items-center justify-center border-t border-linea px-5 py-10 text-center text-sm text-tinta-suave"
                >
                    {{ vacio[pestana] }}
                </p>
                <TablaMediciones
                    v-else
                    :key="pestana"
                    class="min-h-0 flex-1"
                    :filas="filas"
                />
            </section>

            <div class="flex flex-col gap-5 lg:min-h-0">
                <EmpresasPorNivel class="shrink-0" :cuenta="niveles" />
                <DiagnosticosPorSector
                    class="lg:min-h-0"
                    :sectores="sectores"
                />
            </div>
        </div>
    </div>
</template>
