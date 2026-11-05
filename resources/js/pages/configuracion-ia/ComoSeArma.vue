<script setup lang="ts">
/**
 * A4.9 · Cómo se arma el prompt final (HU-080). Solo lectura: las cinco
 * capas, qué aporta cada una y la regla de lo más específico (RN-022).
 *
 * Diseño: las capas en una línea de pasos con ícono; la etiqueta dice quién
 * la escribe (azul: tú, verde: el sistema con tus datos, gris: fija). Al
 * final, "= Prompt final que lee la IA".
 */
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Building2,
    FileJson,
    Globe,
    Lightbulb,
    ListOrdered,
    MessagesSquare,
    Sparkles,
    Store,
} from '@lucide/vue';
import { cn } from '@/lib/utils';
import { rutas } from '@/lib/rutas';

const capas = [
    {
        titulo: 'Instrucciones generales',
        icono: Globe,
        tono: 'marca',
        etiqueta: 'Tú lo escribes',
        texto: 'Contexto, tarea, detalles y ejemplos. Aquí también puedes pedir un método de análisis, por ejemplo “analiza con FODA” o “usa PESTEL”. Aplica a todas las empresas.',
        enlace: { texto: 'Editar las instrucciones', href: rutas.ia.index() },
    },
    {
        titulo: 'Ajuste del sector',
        icono: Store,
        tono: 'marca',
        etiqueta: 'Tú lo escribes · opcional',
        texto: 'Lo que la IA debe saber de un sector. Se suma a lo general o lo reemplaza, solo para las empresas de ese sector.',
        enlace: {
            texto: 'Editar un ajuste de sector',
            href: rutas.ia.index('sector'),
        },
    },
    {
        titulo: 'Ajuste de la empresa',
        icono: Building2,
        tono: 'marca',
        etiqueta: 'Tú lo escribes · opcional',
        texto: 'Lo más específico: solo para una empresa. Si hay ajuste de empresa, manda sobre el del sector.',
        enlace: {
            texto: 'Ver un ajuste de empresa',
            href: rutas.ia.index('empresa'),
        },
    },
    {
        titulo: 'Preguntas y respuestas',
        icono: MessagesSquare,
        tono: 'exito',
        etiqueta: 'Se arma solo',
        texto: 'Por cada pregunta respondida, el sistema inserta el texto, lo que eligió o escribió la empresa y su puntaje. Si la opción elegida tiene un “Ten en cuenta”, lo agrega; en las abiertas, el criterio para calificarla. Las opcionales sin responder no se envían.',
        enlace: null,
    },
    {
        titulo: 'Formato de la respuesta',
        icono: FileJson,
        tono: 'fijo',
        etiqueta: 'Fijo del sistema',
        texto: 'Le pide a la IA devolver, para que el sistema lo lea: observación por pregunta, calificación de las abiertas, observación de la categoría y recomendación con título y detalle. No se edita.',
        enlace: null,
    },
];

const tonos: Record<string, { icono: string; etiqueta: string }> = {
    marca: {
        icono: 'bg-marca text-white',
        etiqueta: 'bg-marca-suave text-marca',
    },
    exito: {
        icono: 'bg-exito text-white',
        etiqueta: 'bg-exito-suave text-exito',
    },
    fijo: {
        icono: 'bg-lienzo-oscuro text-tinta-suave',
        etiqueta: 'bg-lienzo-oscuro text-tinta-suave',
    },
};
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
            <div class="flex items-center gap-3">
                <span
                    class="flex size-10 items-center justify-center rounded-xl bg-marca-suave text-marca"
                    aria-hidden="true"
                >
                    <ListOrdered class="size-5" />
                </span>
                <h1 class="text-2xl font-semibold">
                    Cómo se arma el prompt final
                </h1>
            </div>
        </header>

        <div
            class="grid gap-5 lg:min-h-0 lg:flex-1 lg:grid-cols-[minmax(0,1fr)_320px]"
        >
            <div
                class="flex flex-col gap-4 lg:min-h-0 lg:overflow-y-auto lg:pr-2"
            >
                <div
                    class="flex gap-3 rounded-2xl bg-linear-to-br from-marca-suave/70 to-white p-4 text-sm"
                >
                    <Sparkles
                        class="mt-0.5 size-5 shrink-0 text-marca"
                        aria-hidden="true"
                    />
                    <p>
                        Para cada categoría, la IA recibe
                        <strong>un solo texto</strong> armado con estas capas,
                        de arriba abajo. Tú escribes las capas 1, 2 y 3 y las
                        indicaciones de cada pregunta; el sistema une todo.
                    </p>
                </div>

                <ol class="flex flex-col">
                    <li
                        v-for="(c, i) in capas"
                        :key="c.titulo"
                        class="relative flex gap-4 pb-3"
                    >
                        <span
                            class="absolute top-11 bottom-0 left-5 w-px bg-linea-fuerte"
                            aria-hidden="true"
                        />
                        <span
                            :class="
                                cn(
                                    'relative flex size-10 shrink-0 items-center justify-center rounded-xl',
                                    tonos[c.tono].icono,
                                )
                            "
                            aria-hidden="true"
                        >
                            <component :is="c.icono" class="size-5" />
                        </span>
                        <section
                            class="min-w-0 flex-1 rounded-2xl border border-linea bg-white p-4 text-sm"
                        >
                            <h2
                                class="flex flex-wrap items-center gap-2 font-semibold"
                            >
                                <span
                                    class="font-mono text-xs text-tinta-suave"
                                >
                                    {{ i + 1 }}
                                </span>
                                {{ c.titulo }}
                                <span
                                    :class="
                                        cn(
                                            'rounded-full px-2 py-0.5 text-[11px] font-medium',
                                            tonos[c.tono].etiqueta,
                                        )
                                    "
                                    >{{ c.etiqueta }}</span
                                >
                            </h2>
                            <p class="mt-1 text-tinta-suave">{{ c.texto }}</p>
                            <div
                                v-if="i === 3"
                                class="mt-3 rounded-xl border-l-4 border-exito bg-lienzo/70 px-3 py-2 text-xs"
                            >
                                <p class="text-tinta-suave">
                                    3. ¿Qué haces con las reseñas de Google? ·
                                    Selección múltiple
                                </p>
                                <p class="mt-0.5">
                                    “Las respondo todas”
                                    <span class="font-mono text-tinta-suave"
                                        >→ 40 pts</span
                                    >
                                </p>
                                <p class="mt-0.5 flex items-start gap-1">
                                    <Lightbulb
                                        class="mt-px size-3 shrink-0 text-alerta"
                                        aria-hidden="true"
                                    />
                                    <span>
                                        <strong>Ten en cuenta:</strong>
                                        atiende bien a sus clientes; revisa si
                                        lo mide.
                                    </span>
                                </p>
                            </div>
                            <Link
                                v-if="c.enlace"
                                :href="c.enlace.href"
                                class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-marca hover:underline"
                            >
                                {{ c.enlace.texto }}
                                <ArrowRight
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                            </Link>
                        </section>
                    </li>
                    <li class="flex gap-4">
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-menu text-white"
                            aria-hidden="true"
                        >
                            <Sparkles class="size-5" />
                        </span>
                        <div
                            class="flex flex-1 items-center rounded-2xl bg-menu px-4 py-3 text-sm font-semibold text-white"
                        >
                            = Prompt final que lee la IA
                        </div>
                    </li>
                </ol>
            </div>

            <aside
                class="flex flex-col gap-4 text-sm lg:min-h-0 lg:overflow-y-auto"
            >
                <section class="rounded-2xl border border-linea bg-white p-4">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <MessagesSquare
                            class="size-4 text-marca"
                            aria-hidden="true"
                        />
                        Indicaciones por pregunta
                    </h2>
                    <ul class="mt-2 flex flex-col gap-2 text-tinta-suave">
                        <li
                            v-for="punto in [
                                'Se escriben al crear o editar la pregunta, una por respuesta (máx. 200 caracteres). Son opcionales.',
                                'Solo se envía la indicación de lo que la empresa eligió. En selección múltiple, la de cada opción marcada.',
                                'En preguntas abiertas hay una sola: el criterio para calificar lo que escriba.',
                                'Pueden pedir un método, por ejemplo “analiza con PESTEL el factor económico”.',
                                'Los cambios aplican a la versión nueva del diagnóstico que publiques.',
                            ]"
                            :key="punto"
                            class="flex gap-2"
                        >
                            <span
                                class="mt-1.5 size-1.5 shrink-0 rounded-full bg-marca"
                                aria-hidden="true"
                            />
                            {{ punto }}
                        </li>
                    </ul>
                </section>
                <section
                    class="rounded-2xl border border-linea bg-linear-to-br from-marca-suave/70 to-white p-4"
                >
                    <h2 class="flex items-center gap-2 font-semibold">
                        <Lightbulb
                            class="size-4 text-alerta"
                            aria-hidden="true"
                        />
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
                <div
                    class="flex gap-2.5 rounded-2xl bg-lienzo-oscuro/70 p-4 text-xs text-tinta-suave"
                >
                    <ListOrdered
                        class="size-4 shrink-0 text-tinta"
                        aria-hidden="true"
                    />
                    <p>
                        <strong class="text-tinta">Orden de prioridad</strong>
                        cuando hay varios textos: empresa, sector y por último
                        lo general. Las indicaciones de pregunta se suman
                        siempre.
                    </p>
                </div>
            </aside>
        </div>
    </div>
</template>
