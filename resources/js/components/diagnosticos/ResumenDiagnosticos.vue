<script setup lang="ts">
/**
 * "Resumen del sector" (A2, HU-010 CA-003) o "Resumen general" (A2·T,
 * HU-011 CA-003): totales, estado del sector y la última medición de cada
 * empresa por estado. Con sector, "Ver en Empresas" lleva a la lista de
 * empresas filtrada por ese sector (A3).
 */
import { Link } from '@inertiajs/vue3';
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
    { valor: props.resumen.empresas, texto: 'empresas' },
    { valor: props.resumen.mediciones, texto: 'mediciones' },
    { valor: props.resumen.publicados, texto: 'publicados' },
    {
        valor: props.resumen.borradores,
        texto: props.sector ? 'borrador' : 'borradores',
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
    const maximo = Math.max(1, ...filas.map((f) => f.valor));

    return filas.map((f) => ({ ...f, ancho: (f.valor / maximo) * 100 }));
});
</script>

<template>
    <section class="rounded-xl border border-linea bg-white p-4">
        <h2 class="text-sm font-semibold">
            {{ sector ? 'Resumen del sector' : 'Resumen general' }}
        </h2>

        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-3">
            <div v-for="cifra in cifras" :key="cifra.texto">
                <dt class="sr-only">{{ cifra.texto }}</dt>
                <dd class="font-mono text-xl leading-none font-semibold">
                    {{ cifra.valor }}
                </dd>
                <dd class="mt-1 text-xs text-tinta-suave">{{ cifra.texto }}</dd>
            </div>
        </dl>

        <Link
            v-if="sector && resumen.empresas > 0"
            :href="rutas.empresas.lista(sector.id)"
            class="mt-3 inline-block text-xs text-marca underline"
        >
            Ver las {{ resumen.empresas }} empresas en Empresas
        </Link>

        <div class="mt-3">
            <Etiqueta v-if="sector && sector.activo" tono="exito">
                Activo · se ofrece al registrar
            </Etiqueta>
            <Etiqueta v-else-if="sector" tono="neutro">
                ○ Inactivo · no se ofrece al registrar
            </Etiqueta>
            <Etiqueta v-else tono="exito">
                {{ resumen.sectores_activos ?? 0 }} sectores activos
            </Etiqueta>
        </div>

        <p
            v-if="sector && resumen.publicados === 0"
            class="mt-3 rounded-md bg-alerta-suave px-3 py-2 text-xs text-alerta"
        >
            ⚠ Sin diagnósticos publicados: las empresas que se registren en
            {{ sector.nombre }} todavía no pueden recibir una medición.
        </p>

        <template v-if="resumen.empresas > 0">
            <p class="mt-4 text-xs font-medium">
                Última medición de cada empresa
            </p>
            <ul class="mt-2 flex flex-col gap-1.5 text-xs">
                <li
                    v-for="estado in estados"
                    :key="estado.texto"
                    class="grid grid-cols-[1fr_56px_20px] items-center gap-2"
                >
                    <span
                        :class="
                            estado.aviso && estado.valor > 0 && 'text-aviso'
                        "
                    >
                        {{ estado.texto }}
                    </span>
                    <span class="h-1.5 rounded-full bg-lienzo-oscuro">
                        <span
                            :class="['block h-1.5 rounded-full', estado.color]"
                            :style="{ width: `${estado.ancho}%` }"
                        />
                    </span>
                    <span class="text-right font-mono">{{ estado.valor }}</span>
                </li>
            </ul>
        </template>
    </section>
</template>
