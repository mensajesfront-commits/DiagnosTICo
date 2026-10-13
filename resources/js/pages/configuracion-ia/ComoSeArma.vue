<script setup lang="ts">
/**
 * A4.9 · Cómo se arma el prompt final (HU-080). Solo lectura: las cinco
 * capas, qué aporta cada una y la regla de lo más específico (RN-022).
 */
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { rutas } from '@/lib/rutas';

const capas = [
    {
        titulo: 'Instrucciones generales',
        etiqueta: 'Tú lo escribes',
        texto: 'Contexto, tarea, detalles y ejemplos. Aquí también puedes pedir un método de análisis, por ejemplo “analiza con FODA” o “usa PESTEL”. Aplica a todas las empresas.',
        enlace: { texto: 'Editar las instrucciones', href: rutas.ia.index() },
    },
    {
        titulo: 'Ajuste del sector',
        etiqueta: 'Tú lo escribes · opcional',
        texto: 'Lo que la IA debe saber de un sector. Se suma a lo general o lo reemplaza, solo para las empresas de ese sector.',
        enlace: {
            texto: 'Editar un ajuste de sector',
            href: rutas.ia.index('sector'),
        },
    },
    {
        titulo: 'Ajuste de la empresa',
        etiqueta: 'Tú lo escribes · opcional',
        texto: 'Lo más específico: solo para una empresa. Si hay ajuste de empresa, manda sobre el del sector.',
        enlace: {
            texto: 'Ver un ajuste de empresa',
            href: rutas.ia.index('empresa'),
        },
    },
    {
        titulo: 'Preguntas y respuestas',
        etiqueta: 'Se arma solo',
        texto: 'Por cada pregunta respondida, el sistema inserta el texto, lo que eligió o escribió la empresa y su puntaje. Si la opción elegida tiene un “Ten en cuenta”, lo agrega; en las abiertas, el criterio para calificarla. Las opcionales sin responder no se envían.',
        enlace: null,
    },
    {
        titulo: 'Formato de la respuesta',
        etiqueta: 'Fijo del sistema',
        texto: 'Le pide a la IA devolver, para que el sistema lo lea: observación por pregunta, calificación de las abiertas, observación de la categoría y recomendación con título y detalle. No se edita.',
        enlace: null,
    },
];
</script>

<template>
    <Head title="Cómo se arma el prompt final" />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
        <header class="shrink-0">
            <Link
                :href="rutas.ia.index()"
                class="mb-2 inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-tinta-suave hover:text-marca"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Configuración IA
            </Link>
            <h1 class="text-2xl font-semibold">Cómo se arma el prompt final</h1>
        </header>

        <div
            class="grid gap-5 lg:min-h-0 lg:flex-1 lg:grid-cols-[minmax(0,1fr)_300px]"
        >
            <div
                class="flex flex-col gap-2 lg:min-h-0 lg:overflow-y-auto lg:pr-2"
            >
                <p class="text-sm text-tinta-suave">
                    Para cada categoría, la IA recibe
                    <strong class="text-tinta">un solo texto</strong>
                    armado con estas capas, de arriba abajo. Tú escribes las
                    capas 1, 2 y 3 y las indicaciones de cada pregunta; el
                    sistema une todo.
                </p>
                <template v-for="(c, i) in capas" :key="c.titulo">
                    <section
                        class="flex gap-3 rounded-xl border border-linea bg-white p-4"
                    >
                        <span
                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-lienzo-oscuro text-xs font-semibold"
                            aria-hidden="true"
                            >{{ i + 1 }}</span
                        >
                        <div class="text-sm">
                            <h2
                                class="flex flex-wrap items-center gap-2 font-semibold"
                            >
                                {{ c.titulo }}
                                <span
                                    class="rounded bg-marca-suave px-1.5 py-0.5 text-[11px] font-normal text-marca"
                                    >{{ c.etiqueta }}</span
                                >
                            </h2>
                            <p class="mt-1 text-tinta-suave">{{ c.texto }}</p>
                            <div
                                v-if="i === 3"
                                class="mt-2 border-l-2 border-linea-fuerte bg-lienzo px-3 py-2 text-xs"
                            >
                                <p class="text-tinta-suave">
                                    3. ¿Qué haces con las reseñas de Google? ·
                                    Selección múltiple
                                </p>
                                <p>
                                    “Las respondo todas”
                                    <span class="font-mono text-tinta-suave"
                                        >→ 40 pts</span
                                    >
                                </p>
                                <p>
                                    <strong class="text-marca"
                                        >Ten en cuenta:</strong
                                    >
                                    atiende bien a sus clientes; revisa si lo
                                    mide.
                                </p>
                            </div>
                            <Link
                                v-if="c.enlace"
                                :href="c.enlace.href"
                                class="mt-1 inline-block text-xs text-marca underline underline-offset-2"
                            >
                                {{ c.enlace.texto }} →
                            </Link>
                        </div>
                    </section>
                    <span
                        v-if="i < capas.length - 1"
                        class="text-center text-tinta-suave"
                        aria-hidden="true"
                        >+</span
                    >
                </template>
                <div
                    class="mt-1 rounded-xl bg-tinta px-4 py-3 text-sm font-semibold text-white"
                >
                    = Prompt final que lee la IA
                </div>
            </div>

            <aside
                class="flex flex-col gap-4 text-sm lg:min-h-0 lg:overflow-y-auto"
            >
                <section class="rounded-xl border border-linea bg-white p-4">
                    <h2 class="font-semibold">Indicaciones por pregunta</h2>
                    <ul
                        class="mt-2 flex list-disc flex-col gap-1 pl-5 text-tinta-suave"
                    >
                        <li>
                            Se escriben al crear o editar la pregunta, una por
                            respuesta (máx. 200 caracteres). Son opcionales.
                        </li>
                        <li>
                            Solo se envía la indicación de lo que la empresa
                            eligió. En selección múltiple, la de cada opción
                            marcada.
                        </li>
                        <li>
                            En preguntas abiertas hay una sola: el criterio para
                            calificar lo que escriba.
                        </li>
                        <li>
                            Pueden pedir un método, por ejemplo “analiza con
                            PESTEL el factor económico”.
                        </li>
                        <li>
                            Los cambios aplican a la versión nueva del
                            diagnóstico que publiques.
                        </li>
                    </ul>
                </section>
                <section class="rounded-xl border border-linea bg-white p-4">
                    <h2 class="font-semibold">
                        ¿Dónde pido un FODA, PESTEL u otro método?
                    </h2>
                    <p class="mt-1 text-tinta-suave">
                        No hay una sección fija. Lo escribes en el prompt: en
                        “Detalles y restricciones” (para todas las empresas) o
                        en la indicación de una pregunta o respuesta (solo
                        cuando eso ocurra). Lo que la IA genere con ese método
                        aparece en las observaciones y recomendaciones.
                    </p>
                </section>
                <p
                    class="rounded-xl bg-lienzo-oscuro p-4 text-xs text-tinta-suave"
                >
                    Orden de prioridad cuando hay varios textos: empresa, sector
                    y por último lo general. Las indicaciones de pregunta se
                    suman siempre.
                </p>
            </aside>
        </div>
    </div>
</template>
