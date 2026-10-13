<script setup lang="ts">
/**
 * E3 · Revisa antes de enviar (HU-061, HU-062).
 *
 * - Si falta algo: cada categoría con "N de M respondidas", las incompletas
 *   con "Completar" y el aviso de qué obligatoria falta; "Enviar
 *   diagnóstico" desactivado.
 * - Si está completo: "10/10 categorías completas" (detalle desplegable) y
 *   "Todo listo".
 * - Enviar pide confirmar: después no se cambian las respuestas (RN-017).
 *
 * Envía: POST /mi-diagnostico/{id}/enviar y pasa a E4.
 */
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
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

const verDetalle = ref(false);
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

    <main class="mx-auto flex max-w-3xl flex-col gap-4 px-6 pt-8 pb-28">
        <div>
            <h1 class="text-2xl font-semibold">Revisa antes de enviar</h1>
            <p class="mt-1 text-sm text-tinta-suave">
                Puedes volver a cualquier categoría para cambiar tus respuestas.
                Una vez enviado, el diagnóstico no se puede modificar.
            </p>
        </div>

        <section
            v-if="avance.completo"
            class="rounded-xl border border-linea bg-white"
        >
            <div class="flex items-center justify-between gap-3 px-5 py-4">
                <span class="flex items-center gap-3">
                    <span
                        class="flex size-8 items-center justify-center rounded-full bg-exito-suave text-exito"
                        aria-hidden="true"
                        >✓</span
                    >
                    <span>
                        <span class="block font-semibold">
                            {{ avance.categorias.length }}/{{
                                avance.categorias.length
                            }}
                            categorías completas
                        </span>
                        <span class="text-xs text-tinta-suave">
                            {{ avance.respondidas }} de {{ avance.total }}
                            preguntas respondidas
                        </span>
                    </span>
                </span>
                <button
                    type="button"
                    class="text-sm text-marca underline underline-offset-2"
                    :aria-expanded="verDetalle"
                    @click="verDetalle = !verDetalle"
                >
                    Ver detalle por categoría {{ verDetalle ? '▴' : '▾' }}
                </button>
            </div>
        </section>

        <ul
            v-if="!avance.completo || verDetalle"
            class="divide-y divide-linea rounded-xl border border-linea bg-white"
        >
            <li
                v-for="(c, i) in avance.categorias"
                :key="c.ref"
                :class="
                    cn(
                        'flex items-center gap-3 px-5 py-3 text-sm',
                        c.faltan_obligatorias > 0 && 'bg-aviso-suave/50',
                    )
                "
            >
                <span
                    :class="
                        cn(
                            'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                            c.faltan_obligatorias > 0
                                ? 'bg-aviso text-white'
                                : 'bg-exito-suave text-exito',
                        )
                    "
                    aria-hidden="true"
                >
                    {{ c.faltan_obligatorias > 0 ? '!' : '✓' }}
                </span>
                <span class="flex-1 font-medium">{{ c.nombre }}</span>
                <span
                    :class="
                        cn(
                            'text-xs',
                            c.faltan_obligatorias > 0
                                ? 'font-medium text-aviso'
                                : 'text-tinta-suave',
                        )
                    "
                >
                    {{ c.respondidas }} de {{ c.total }} respondidas
                    <template v-if="c.faltan_obligatorias">
                        · falta{{ c.faltan_obligatorias === 1 ? '' : 'n' }}
                        {{
                            plural(
                                c.faltan_obligatorias,
                                'obligatoria',
                                'obligatorias',
                            )
                        }}
                    </template>
                </span>
                <Link
                    :href="rutas.responder.mostrar(medicion.id, i)"
                    class="w-20 text-right text-xs text-marca underline"
                >
                    {{ c.faltan_obligatorias > 0 ? 'Completar' : 'Revisar' }}
                </Link>
            </li>
        </ul>

        <p
            v-if="primera"
            class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-aviso-suave px-4 py-3 text-sm text-aviso"
            role="alert"
        >
            <span>
                ⚠ Falta{{ faltan === 1 ? '' : 'n' }}
                {{
                    plural(
                        faltan,
                        'pregunta obligatoria',
                        'preguntas obligatorias',
                    )
                }}<template v-if="incompletas.length === 1">
                    en <strong>{{ primera.nombre }}</strong></template
                >. Respóndela{{ faltan === 1 ? '' : 's' }} para poder enviar.
            </span>
            <Link
                :href="rutas.responder.mostrar(medicion.id, primera.indice)"
                class="font-medium underline"
            >
                Ir a la pregunta
            </Link>
        </p>
        <p
            v-else
            class="rounded-lg bg-exito-suave px-4 py-3 text-sm text-exito"
            role="status"
        >
            ✓ Todo listo: respondiste las
            {{ avance.categorias.length }} categorías. Puedes enviar el
            diagnóstico.
        </p>
        <p v-if="errorEnvio" class="text-sm text-aviso" role="alert">
            {{ errorEnvio }}
        </p>
    </main>

    <footer
        class="fixed inset-x-0 bottom-0 flex items-center justify-between gap-4 border-t border-linea bg-white px-6 py-3"
    >
        <Boton
            variante="secundario"
            :href="
                rutas.responder.mostrar(
                    medicion.id,
                    avance.categorias.length - 1,
                )
            "
        >
            ← Volver a {{ ultima?.nombre }}
        </Boton>
        <span class="flex items-center gap-4">
            <span
                v-if="!avance.completo"
                class="hidden text-xs text-tinta-suave sm:inline"
            >
                Responde las preguntas obligatorias pendientes para enviar
            </span>
            <Boton :disabled="!avance.completo" @click="confirmar = true">
                Enviar diagnóstico
            </Boton>
        </span>
    </footer>

    <Modal
        v-model:abierto="confirmar"
        ancho="sm"
        titulo="¿Enviar tu diagnóstico?"
        :descripcion="`${medicion.sector} · Medición ${medicion.numero} · ${avance.respondidas} preguntas respondidas`"
    >
        <p class="text-sm">
            Después de enviarlo
            <strong>no podrás cambiar tus respuestas</strong>. Empezaremos a
            analizarlas: suele tardar menos de un minuto; si tarda más, te
            avisamos por correo.
        </p>
        <template #pie>
            <Boton variante="secundario" @click="confirmar = false">
                Todavía no
            </Boton>
            <Boton :cargando="form.processing" @click="enviar">
                Sí, enviar diagnóstico
            </Boton>
        </template>
    </Modal>
</template>
