<script setup lang="ts">
/**
 * E9 · Mis respuestas (HU-065) y A3.5 · las mismas respuestas vistas por el
 * Administrador. Solo lectura (RN-017).
 *
 * A la izquierda, las categorías con su puntaje; a la derecha, las preguntas
 * de la elegida con la respuesta, el puntaje y la calificación de la IA en
 * las abiertas. "Volver al resultado" y "Ver informe PDF".
 */
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import PreguntaResultado from '@/components/resultados/PreguntaResultado.vue';
import { usePermisos } from '@/composables/usePermisos';
import { diaYMes } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { CabeceraResultado, CategoriaResultado } from '@/types/resultados';

const props = defineProps<{
    medicion: CabeceraResultado;
    categorias: CategoriaResultado[];
    /** Categoría que se abre (?categoria=c3). */
    inicial: string | null;
    interno: boolean;
}>();

const { puede } = usePermisos();

const elegida = ref(
    props.categorias.some((c) => c.ref === props.inicial)
        ? (props.inicial as string)
        : (props.categorias[0]?.ref ?? ''),
);

const categoria = computed(
    () =>
        props.categorias.find((c) => c.ref === elegida.value) ??
        props.categorias[0],
);

const porcentaje = (n: number) =>
    `${Number.isInteger(n) ? n : n.toFixed(2).replace(/\.?0+$/, '')}%`;

const subtitulo = computed(() =>
    [
        props.medicion.empresa.nombre,
        props.medicion.sector,
        props.medicion.enviada_en
            ? `enviada el ${diaYMes(new Date(props.medicion.enviada_en))}`
            : null,
        `${props.medicion.diagnostico} v${props.medicion.version}`,
    ]
        .filter(Boolean)
        .join(' · '),
);
</script>

<template>
    <Head :title="`Respuestas · Medición ${medicion.numero}`" />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
        <header class="flex shrink-0 flex-wrap items-end justify-between gap-4">
            <div>
                <Link
                    :href="rutas.resultados.ver(medicion.id)"
                    class="mb-2 inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-tinta-suave hover:text-marca"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Volver al resultado
                </Link>
                <h1 class="text-2xl font-semibold">
                    {{ interno ? 'Respuestas' : 'Mis respuestas' }} · Medición
                    {{ medicion.numero }}
                </h1>
                <p class="mt-0.5 text-sm text-tinta-suave">{{ subtitulo }}</p>
            </div>
            <Boton
                v-if="puede('resultados.pdf')"
                archivo
                :href="rutas.resultados.pdf(medicion.id)"
            >
                Ver informe PDF
            </Boton>
        </header>

        <div
            class="grid gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[280px_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)]"
        >
            <nav
                class="self-start rounded-xl border border-linea bg-white p-2 lg:max-h-full lg:overflow-y-auto"
                aria-label="Categorías"
            >
                <p class="px-2 py-1.5 text-xs text-tinta-suave">
                    Categorías · puntaje
                </p>
                <ul>
                    <li v-for="c in categorias" :key="c.ref">
                        <button
                            type="button"
                            :class="
                                cn(
                                    'flex w-full items-center justify-between gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-lienzo',
                                    c.ref === elegida &&
                                        'bg-marca-suave font-medium text-marca hover:bg-marca-suave',
                                )
                            "
                            :aria-current="
                                c.ref === elegida ? 'true' : undefined
                            "
                            @click="elegida = c.ref"
                        >
                            <span>{{ c.nombre }}</span>
                            <span class="font-mono text-xs text-tinta-suave">{{
                                c.puntaje
                            }}</span>
                        </button>
                    </li>
                </ul>
            </nav>

            <section
                v-if="categoria"
                class="flex flex-col self-start rounded-xl border border-linea bg-white lg:max-h-full lg:min-h-0"
            >
                <header
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-linea px-5 py-3"
                >
                    <h2 class="flex items-center gap-2 font-semibold">
                        {{ categoria.nombre }}
                        <EtiquetaNivel :nivel="categoria.nivel" />
                    </h2>
                    <span class="text-xs text-tinta-suave">
                        Puntaje {{ categoria.puntaje }} · Importancia
                        {{ porcentaje(categoria.importancia) }} ·
                        {{ categoria.preguntas.length }}
                        {{
                            categoria.preguntas.length === 1
                                ? 'pregunta'
                                : 'preguntas'
                        }}
                    </span>
                </header>
                <div
                    class="flex flex-col gap-4 p-5 lg:min-h-0 lg:overflow-y-auto"
                >
                    <PreguntaResultado
                        v-for="(p, i) in categoria.preguntas"
                        :key="p.ref"
                        :pregunta="p"
                        :numero="i + 1"
                        :interno="interno"
                    />
                </div>
                <p
                    class="border-t border-linea px-5 py-3 text-xs text-tinta-suave"
                >
                    Solo lectura.
                    {{
                        interno
                            ? 'Las respuestas enviadas no se cambian.'
                            : 'Para cambiar tus respuestas, espera tu siguiente medición.'
                    }}
                </p>
            </section>
        </div>
    </div>
</template>
