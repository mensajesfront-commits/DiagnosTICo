<script setup lang="ts">
/**
 * "Resumen del sector" (A2, HU-010 CA-003) o "Resumen general" (A2·T,
 * HU-011 CA-003): totales, estado del sector y la última medición de cada
 * empresa por estado. Con sector, "Ver en Empresas" lleva a la lista de
 * empresas filtrada por ese sector (A3).
 */
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CircleCheck,
    ClipboardList,
    PencilLine,
} from '@lucide/vue';
import { computed } from 'vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import { rutas } from '@/lib/rutas';
import type { ResumenDiagnosticos, Sector } from '@/types/diagnosticos';

const props = defineProps<{
    resumen: ResumenDiagnosticos;
    /** El sector elegido; sin sector es el resumen general de "Todos". */
    sector?: Sector | null;
}>();

const cifras = computed(() => [
    {
        valor: props.resumen.empresas,
        texto: props.resumen.empresas === 1 ? 'empresa' : 'empresas',
        icono: Building2,
        tono: 'bg-marca-suave text-marca',
    },
    {
        valor: props.resumen.mediciones,
        texto: props.resumen.mediciones === 1 ? 'medición' : 'mediciones',
        icono: ClipboardList,
        tono: 'bg-nivel-camino-suave text-nivel-camino-texto',
    },
    {
        valor: props.resumen.publicados,
        texto: props.resumen.publicados === 1 ? 'publicado' : 'publicados',
        icono: CircleCheck,
        tono: 'bg-exito-suave text-exito',
    },
    {
        valor: props.resumen.borradores,
        texto: props.resumen.borradores === 1 ? 'borrador' : 'borradores',
        icono: PencilLine,
        tono: 'bg-alerta-suave text-alerta',
    },
]);

const estados = computed(() => {
    const m = props.resumen.ultima_medicion;
    const filas = [
        { texto: 'En curso', valor: m.en_curso, color: 'bg-marca' },
        { texto: 'No iniciada', valor: m.no_iniciada, color: 'bg-nivel-sin' },
        {
            texto: 'Vencida',
            valor: m.vencida,
            color: 'bg-nivel-critico',
            aviso: true,
        },
        { texto: 'Terminada', valor: m.terminada, color: 'bg-nivel-sigue' },
    ];
    const total = filas.reduce((suma, f) => suma + f.valor, 0);

    return filas.map((f) => ({
        ...f,
        ancho: total > 0 ? (f.valor / total) * 100 : 0,
    }));
});
</script>

<template>
    <section class="rounded-2xl border border-linea bg-white p-4">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-sm font-semibold">
                {{ sector ? 'Resumen del sector' : 'Resumen general' }}
            </h2>
            <Etiqueta
                v-if="sector && sector.activo"
                tono="exito"
                class="rounded-full"
                title="Se ofrece al registrar"
            >
                ● Activo
            </Etiqueta>
            <Etiqueta
                v-else-if="sector"
                tono="neutro"
                class="rounded-full"
                title="No se ofrece al registrar"
            >
                ○ Inactivo
            </Etiqueta>
            <Etiqueta v-else tono="exito" class="rounded-full">
                {{ resumen.sectores_activos ?? 0 }} activos
            </Etiqueta>
        </div>

        <ul class="mt-3 grid grid-cols-2 gap-2">
            <li
                v-for="cifra in cifras"
                :key="cifra.texto"
                class="rounded-xl bg-lienzo/60 p-2.5"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="font-mono text-lg leading-none font-semibold">
                        {{ cifra.valor }}
                    </span>
                    <span
                        :class="[
                            'flex size-6 shrink-0 items-center justify-center rounded-md',
                            cifra.tono,
                        ]"
                        aria-hidden="true"
                    >
                        <component :is="cifra.icono" class="size-3.5" />
                    </span>
                </div>
                <p class="mt-1 text-[11px] text-tinta-suave">
                    {{ cifra.texto }}
                </p>
            </li>
        </ul>

        <p
            v-if="sector && resumen.publicados === 0"
            class="mt-3 rounded-lg bg-alerta-suave px-3 py-2 text-xs text-alerta"
        >
            ⚠ Sin diagnósticos publicados: las empresas que se registren en
            {{ sector.nombre }} todavía no pueden recibir una medición.
        </p>

        <template v-if="resumen.empresas > 0">
            <p class="mt-4 text-xs font-medium">
                Última medición de cada empresa
            </p>
            <div
                class="mt-2 flex h-2 overflow-hidden rounded-full bg-lienzo-oscuro"
                aria-hidden="true"
            >
                <span
                    v-for="estado in estados"
                    :key="estado.texto"
                    :class="['h-full', estado.color]"
                    :style="{ width: `${estado.ancho}%` }"
                />
            </div>
            <ul class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                <li
                    v-for="estado in estados"
                    :key="estado.texto"
                    class="flex items-center gap-1.5"
                >
                    <span
                        :class="['size-2 shrink-0 rounded-full', estado.color]"
                        aria-hidden="true"
                    />
                    <span
                        :class="[
                            'flex-1',
                            estado.aviso && estado.valor > 0 && 'text-aviso',
                        ]"
                    >
                        {{ estado.texto }}
                    </span>
                    <span class="font-mono">{{ estado.valor }}</span>
                </li>
            </ul>
        </template>

        <Link
            v-if="sector && resumen.empresas > 0"
            :href="rutas.empresas.lista(sector.id)"
            class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-marca hover:underline"
        >
            Ver
            {{
                resumen.empresas === 1
                    ? 'la empresa'
                    : `las ${resumen.empresas} empresas`
            }}
            en Empresas
            <ArrowRight class="size-3.5" aria-hidden="true" />
        </Link>
    </section>
</template>
