<script setup lang="ts">
/**
 * E3 · Revisa antes de enviar (HU-061, HU-062).
 *
 * - Si falta algo: cada categoría con "N de M respondidas", las incompletas
 *   con "Completar" y el aviso de qué obligatoria falta; "Enviar
 *   diagnóstico" desactivado.
 * - Si está completo: todas las categorías en verde y "Todo listo".
 * - Enviar pide confirmar: después no se cambian las respuestas (RN-017).
 *
 * Mismo diseño que E2 para leer fácil (también con baja visión): hasta
 * 64rem de ancho, letra de 16 a 18 px y filas grandes.
 *
 * Diseño: arriba un resumen con un anillo del avance y tres cifras
 * (categorías completas, preguntas respondidas, obligatorias pendientes);
 * las categorías en tarjetas con su barra; el aviso grande de lo que falta o
 * "Todo listo", y "Qué pasa al enviar" en tres pasos.
 *
 * Envía: POST /mi-diagnostico/{id}/enviar y pasa a E4.
 */
import {
    ArrowRight,
    Check,
    ChevronLeft,
    CircleAlert,
    CircleCheck,
    Mail,
    PartyPopper,
    Pencil,
    Send,
    Sparkles,
} from '@lucide/vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import Recuadro from '@/components/base/Recuadro.vue';
import BarraResponder from '@/components/responder/BarraResponder.vue';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { Avance, CabeceraMedicion } from '@/types/responder';

const props = defineProps<{
    medicion: CabeceraMedicion;
    avance: Avance;
    guardadoEn: string | null;
}>();

const plural = (n: number, uno: string, varios: string) =>
    `${n} ${n === 1 ? uno : varios}`;

const incompletas = computed(() =>
    props.avance.categorias
        .map((c, i) => ({ ...c, indice: i }))
        .filter((c) => c.faltan_obligatorias > 0),
);

const primera = computed(() => incompletas.value[0] ?? null);
const faltan = computed(() =>
    incompletas.value.reduce((s, c) => s + c.faltan_obligatorias, 0),
);

const ultima = computed(
    () => props.avance.categorias[props.avance.categorias.length - 1],
);

const completas = computed(
    () =>
        props.avance.categorias.filter((c) => c.faltan_obligatorias === 0)
            .length,
);

/** Anillo del avance. */
const RADIO = 42;
const CIRCUNFERENCIA = 2 * Math.PI * RADIO;

const avanceDe = (c: { respondidas: number; total: number }) =>
    Math.round((c.respondidas * 100) / Math.max(1, c.total));

const pasos = [
    {
        icono: Send,
        titulo: 'Envías',
        texto: 'Tus respuestas quedan guardadas y ya no se cambian.',
    },
    {
        icono: Sparkles,
        titulo: 'La IA las analiza',
        texto: 'Suele tardar 1 o 2 minutos por todas las categorías.',
    },
    {
        icono: Mail,
        titulo: 'Te avisamos',
        texto: 'Te llega un correo y ves tu resultado en Captter.',
    },
];
const confirmar = ref(false);
const form = useForm({});

function enviar(): void {
    form.post(rutas.responder.enviar(props.medicion.id), {
        onError: () => (confirmar.value = false),
    });
}

const errorEnvio = computed(
    () => (form.errors as Record<string, string | undefined>).enviar,
);

const hora = computed(() =>
    props.guardadoEn
        ? new Date(props.guardadoEn).toLocaleTimeString('es-CO', {
              hour: 'numeric',
              minute: '2-digit',
          })
        : null,
);
</script>

<template>
    <Head title="Revisa antes de enviar" />

    <BarraResponder
        :titulo="`Diagnóstico · ${medicion.sector} · Medición ${medicion.numero}`"
        :detalle="`Revisión final · ${avance.respondidas} de ${avance.total} preguntas respondidas`"
        :porcentaje="avance.porcentaje"
        :guardado="hora"
    />

    <main class="mx-auto flex max-w-5xl flex-col gap-5 px-4 pt-8 pb-32 sm:px-8">
        <!-- Resumen -->
        <section
            class="grid gap-6 rounded-2xl border border-linea bg-linear-to-br from-marca-suave/70 via-white to-white p-6 sm:grid-cols-[auto_minmax(0,1fr)] sm:p-8"
        >
            <div class="relative mx-auto size-28 shrink-0">
                <svg
                    viewBox="0 0 100 100"
                    class="size-28 -rotate-90"
                    aria-hidden="true"
                >
                    <circle
                        cx="50"
                        cy="50"
                        :r="RADIO"
                        fill="none"
                        stroke-width="10"
                        class="stroke-lienzo-oscuro"
                    />
                    <circle
                        cx="50"
                        cy="50"
                        :r="RADIO"
                        fill="none"
                        stroke-width="10"
                        stroke-linecap="round"
                        :class="
                            avance.completo ? 'stroke-exito' : 'stroke-marca'
                        "
                        :stroke-dasharray="CIRCUNFERENCIA"
                        :stroke-dashoffset="
                            CIRCUNFERENCIA * (1 - avance.porcentaje / 100)
                        "
                    />
                </svg>
                <span
                    class="absolute inset-0 flex flex-col items-center justify-center"
                >
                    <Check
                        v-if="avance.completo"
                        class="size-10 text-exito"
                        aria-hidden="true"
                    />
                    <span
                        v-else
                        class="font-mono text-2xl leading-none font-semibold"
                    >
                        {{ avance.porcentaje }}%
                    </span>
                </span>
            </div>
            <div class="min-w-0">
                <h1 class="text-3xl leading-tight font-semibold">
                    Revisa antes de enviar
                </h1>
                <p class="mt-1 text-base text-tinta-suave">
                    Puedes volver a cualquier categoría para cambiar tus
                    respuestas. Una vez enviado, el diagnóstico no se puede
                    modificar.
                </p>
                <dl class="mt-4 grid grid-cols-3 gap-2 sm:gap-3">
                    <div
                        class="flex flex-col-reverse rounded-xl bg-white/80 px-3 py-2.5"
                    >
                        <dt class="text-xs text-tinta-suave sm:text-sm">
                            categorías completas
                        </dt>
                        <dd class="font-mono text-xl font-semibold">
                            {{ completas }}/{{ avance.categorias.length }}
                        </dd>
                    </div>
                    <div
                        class="flex flex-col-reverse rounded-xl bg-white/80 px-3 py-2.5"
                    >
                        <dt class="text-xs text-tinta-suave sm:text-sm">
                            preguntas respondidas
                        </dt>
                        <dd class="font-mono text-xl font-semibold">
                            {{ avance.respondidas }}/{{ avance.total }}
                        </dd>
                    </div>
                    <div
                        :class="
                            cn(
                                'flex flex-col-reverse rounded-xl px-3 py-2.5',
                                faltan > 0
                                    ? 'bg-aviso-suave'
                                    : 'bg-exito-suave',
                            )
                        "
                    >
                        <dt class="text-xs text-tinta-suave sm:text-sm">
                            obligatorias pendientes
                        </dt>
                        <dd
                            :class="
                                cn(
                                    'font-mono text-xl font-semibold',
                                    faltan > 0 ? 'text-aviso' : 'text-exito',
                                )
                            "
                        >
                            {{ faltan }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Aviso -->
        <div
            v-if="primera"
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-aviso/30 bg-aviso-suave px-5 py-4 text-base"
            role="alert"
        >
            <span class="flex items-start gap-3">
                <CircleAlert
                    class="mt-0.5 size-6 shrink-0 text-aviso"
                    aria-hidden="true"
                />
                <span>
                    <strong class="text-aviso">
                        Falta{{ faltan === 1 ? '' : 'n' }}
                        {{
                            plural(
                                faltan,
                                'pregunta obligatoria',
                                'preguntas obligatorias',
                            )
                        }}<template v-if="incompletas.length === 1">
                            en {{ primera.nombre }}</template
                        >.
                    </strong>
                    Respóndela{{ faltan === 1 ? '' : 's' }} para poder enviar.
                </span>
            </span>
            <Link
                :href="rutas.responder.mostrar(medicion.id, primera.indice)"
                class="inline-flex h-11 items-center gap-1.5 rounded-lg bg-aviso px-4 font-medium text-white hover:brightness-95"
            >
                Ir a la pregunta
                <ArrowRight class="size-4" aria-hidden="true" />
            </Link>
        </div>
        <div
            v-else
            class="flex items-start gap-3 rounded-2xl border border-exito/30 bg-exito-suave px-5 py-4 text-base"
            role="status"
        >
            <PartyPopper
                class="mt-0.5 size-6 shrink-0 text-exito"
                aria-hidden="true"
            />
            <span>
                <strong class="text-exito">Todo listo.</strong>
                Respondiste las {{ avance.categorias.length }} categorías. Ya
                puedes enviar el diagnóstico.
            </span>
        </div>

        <!-- Categorías -->
        <section>
            <h2 class="text-lg font-semibold">Tus categorías</h2>
            <ul class="mt-3 grid gap-3 md:grid-cols-2">
                <li
                    v-for="(c, i) in avance.categorias"
                    :key="c.ref"
                    :class="
                        cn(
                            'flex flex-col gap-3 rounded-2xl border bg-white px-5 py-4',
                            c.faltan_obligatorias > 0
                                ? 'border-aviso/40'
                                : 'border-linea',
                        )
                    "
                >
                    <div class="flex items-start gap-3">
                        <span
                            :class="
                                cn(
                                    'flex size-9 shrink-0 items-center justify-center rounded-full',
                                    c.faltan_obligatorias > 0
                                        ? 'bg-aviso-suave text-aviso'
                                        : 'bg-exito text-white',
                                )
                            "
                            aria-hidden="true"
                        >
                            <CircleAlert
                                v-if="c.faltan_obligatorias > 0"
                                class="size-5"
                            />
                            <Check v-else class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg leading-snug font-semibold">
                                {{ c.nombre }}
                            </p>
                            <p
                                :class="
                                    cn(
                                        'text-base',
                                        c.faltan_obligatorias > 0
                                            ? 'font-medium text-aviso'
                                            : 'text-tinta-suave',
                                    )
                                "
                            >
                                {{ c.respondidas }} de {{ c.total }}
                                respondidas
                                <template v-if="c.faltan_obligatorias">
                                    · falta{{
                                        c.faltan_obligatorias === 1 ? '' : 'n'
                                    }}
                                    {{
                                        plural(
                                            c.faltan_obligatorias,
                                            'obligatoria',
                                            'obligatorias',
                                        )
                                    }}
                                </template>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div
                            class="h-2 flex-1 rounded-full bg-lienzo-oscuro"
                            aria-hidden="true"
                        >
                            <div
                                :class="
                                    cn(
                                        'h-2 rounded-full',
                                        c.faltan_obligatorias > 0
                                            ? 'bg-aviso'
                                            : 'bg-exito',
                                    )
                                "
                                :style="{ width: `${avanceDe(c)}%` }"
                            />
                        </div>
                        <Link
                            :href="rutas.responder.mostrar(medicion.id, i)"
                            :class="
                                cn(
                                    'inline-flex h-11 min-w-32 items-center justify-center gap-1.5 rounded-lg border px-4 text-base font-medium',
                                    c.faltan_obligatorias > 0
                                        ? 'border-aviso bg-white text-aviso hover:bg-aviso-suave'
                                        : 'border-linea-fuerte text-marca hover:bg-lienzo',
                                )
                            "
                        >
                            <ArrowRight
                                v-if="c.faltan_obligatorias > 0"
                                class="size-4"
                                aria-hidden="true"
                            />
                            <Pencil v-else class="size-4" aria-hidden="true" />
                            {{
                                c.faltan_obligatorias > 0
                                    ? 'Completar'
                                    : 'Revisar'
                            }}
                        </Link>
                    </div>
                </li>
            </ul>
        </section>

        <!-- Qué pasa al enviar -->
        <section class="rounded-2xl border border-linea bg-white p-5 sm:p-6">
            <h2 class="text-lg font-semibold">Qué pasa al enviar</h2>
            <ol class="mt-4 grid gap-4 sm:grid-cols-3">
                <li
                    v-for="(paso, i) in pasos"
                    :key="paso.titulo"
                    class="flex items-start gap-3"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                        aria-hidden="true"
                    >
                        <component :is="paso.icono" class="size-5" />
                    </span>
                    <span>
                        <span class="block text-base font-semibold">
                            {{ i + 1 }}. {{ paso.titulo }}
                        </span>
                        <span class="block text-sm text-tinta-suave">
                            {{ paso.texto }}
                        </span>
                    </span>
                </li>
            </ol>
        </section>

        <p v-if="errorEnvio" class="text-base text-aviso" role="alert">
            {{ errorEnvio }}
        </p>
    </main>

    <footer
        class="fixed inset-x-0 bottom-0 flex items-center justify-between gap-3 border-t border-linea bg-white/95 px-4 py-3 backdrop-blur sm:px-6"
    >
        <Boton
            variante="secundario"
            class="min-w-0 gap-1"
            :href="
                rutas.responder.mostrar(
                    medicion.id,
                    avance.categorias.length - 1,
                )
            "
        >
            <ChevronLeft class="size-4 shrink-0" aria-hidden="true" />
            <span class="truncate"
                >Volver<span class="hidden sm:inline">
                    a {{ ultima?.nombre }}</span
                ></span
            >
        </Boton>
        <span class="flex items-center gap-4">
            <span
                v-if="!avance.completo"
                class="hidden text-sm text-tinta-suave md:inline"
            >
                Responde las preguntas obligatorias pendientes para enviar
            </span>
            <span
                v-else
                class="hidden items-center gap-1.5 text-sm text-exito md:inline-flex"
            >
                <CircleCheck class="size-4" aria-hidden="true" />
                Todo listo para enviar
            </span>
            <Boton
                class="gap-1.5"
                :disabled="!avance.completo"
                @click="confirmar = true"
            >
                <Send class="size-4" aria-hidden="true" />
                Enviar diagnóstico
            </Boton>
        </span>
    </footer>

    <Modal
        v-model:abierto="confirmar"
        ancho="sm"
        titulo="¿Enviar tu diagnóstico?"
        :descripcion="`${medicion.sector} · Medición ${medicion.numero} · ${avance.respondidas} preguntas respondidas`"
        :icono="Send"
    >
        <div class="flex flex-col gap-3 text-base">
            <Recuadro tono="alerta">
                Después de enviarlo
                <strong>no podrás cambiar tus respuestas</strong>.
            </Recuadro>
            <p>
                Empezaremos a analizarlas: suele tardar 1 o 2 minutos y, cuando
                esté listo, te avisamos por correo.
            </p>
        </div>
        <template #pie>
            <Boton variante="secundario" @click="confirmar = false">
                Todavía no
            </Boton>
            <Boton class="gap-1.5" :cargando="form.processing" @click="enviar">
                <Send class="size-4" aria-hidden="true" />
                Sí, enviar diagnóstico
            </Boton>
        </template>
    </Modal>
</template>
