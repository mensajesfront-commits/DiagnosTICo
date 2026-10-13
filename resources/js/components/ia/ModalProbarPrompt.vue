<script setup lang="ts">
/**
 * A4.7 · Probar con un ejemplo (HU-045): manda una categoría real a la IA
 * con las instrucciones actuales, incluidos los cambios sin guardar, y
 * muestra lo que respondería. No guarda nada; gasta una llamada a la IA.
 */
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import SelectorEjemplo from '@/components/ia/SelectorEjemplo.vue';
import { enviarJson } from '@/lib/ia';
import { rutas } from '@/lib/rutas';
import type {
    Borrador,
    EjemploIa,
    PreguntaPrompt,
    ResultadoPrueba,
    VistaPrevia,
} from '@/types/configuracionIa';

const props = defineProps<{ ejemplos: EjemploIa[]; borrador: Borrador }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const medicion = ref<number | ''>('');
const categoria = ref('');
const preguntas = ref<PreguntaPrompt[]>([]);
const prueba = ref<ResultadoPrueba | null>(null);
const error = ref('');
const probando = ref(false);

watch(
    abierto,
    (a) => {
        if (a) {
            medicion.value = props.ejemplos[0]?.medicion_id ?? '';
            prueba.value = null;
            error.value = '';
        }
    },
    { immediate: true },
);

// Las respuestas del ejemplo, antes de gastar la llamada.
watch([medicion, categoria], async () => {
    prueba.value = null;
    error.value = '';

    if (!abierto.value || !medicion.value || !categoria.value) {
        return;
    }

    const r = await enviarJson<VistaPrevia>(rutas.ia.vistaPrevia(), {
        medicion_id: medicion.value,
        categoria_ref: categoria.value,
    });
    preguntas.value = r.ok ? r.datos.preguntas : [];
});

async function probar(): Promise<void> {
    probando.value = true;
    error.value = '';
    const r = await enviarJson<ResultadoPrueba>(rutas.ia.probar(), {
        medicion_id: medicion.value,
        categoria_ref: categoria.value,
        borrador: props.borrador,
    });
    probando.value = false;
    prueba.value = r.ok ? r.datos : null;
    error.value = r.ok ? '' : r.mensaje;
}

const tipos = {
    opcion_unica: 'opción única',
    seleccion_multiple: 'selección múltiple',
    abierta: 'abierta · la califica la IA',
};

const resultadoDe = computed(
    () =>
        new Map(
            (prueba.value?.resultado.preguntas ?? []).map((p) => [p.id, p]),
        ),
);
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        ancho="xl"
        titulo="Probar con un ejemplo · Analizar categoría"
        descripcion="Elige una empresa y una categoría de ejemplo y mira qué respondería la IA con tus instrucciones actuales, incluidos los cambios sin guardar."
    >
        <div class="flex max-h-[60vh] flex-col gap-4 overflow-y-auto">
            <SelectorEjemplo
                v-model:medicion="medicion"
                v-model:categoria="categoria"
                :ejemplos="ejemplos"
            />

            <p v-if="ejemplos.length === 0" class="text-sm text-tinta-suave">
                Todavía no hay mediciones con respuestas para probar.
            </p>

            <div
                v-if="preguntas.length"
                class="rounded-lg bg-lienzo px-4 py-3 text-sm"
            >
                <p class="text-xs font-semibold">
                    Respuestas que recibe la IA ({{ preguntas.length }})
                </p>
                <ul class="mt-1 flex flex-col gap-1">
                    <li v-for="q in preguntas" :key="q.id">
                        {{ q.texto }} → {{ q.respuesta }}
                        <span class="text-xs text-tinta-suave">
                            ({{ tipos[q.tipo]
                            }}<template v-if="q.puntaje !== null">
                                · {{ q.puntaje }}</template
                            >)
                        </span>
                    </li>
                </ul>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <Boton
                    :cargando="probando"
                    :disabled="!medicion || !categoria"
                    @click="probar"
                >
                    {{ prueba ? 'Ejecutar de nuevo' : 'Ejecutar prueba' }}
                </Boton>
                <span class="text-xs text-tinta-suave">
                    Gasta una llamada a la IA. Probar no afecta resultados
                    reales: no se guarda nada.
                </span>
            </div>

            <p
                v-if="error"
                class="rounded-md bg-aviso-suave px-3 py-2 text-sm text-aviso"
                role="alert"
            >
                {{ error }}
            </p>

            <section
                v-if="prueba"
                class="flex flex-col gap-3"
                aria-live="polite"
            >
                <p class="text-xs text-exito">
                    ✓ Resultado de la prueba · con tus instrucciones actuales
                    (incluidos los cambios sin guardar)
                </p>
                <div class="rounded-lg bg-marca-suave/40 px-4 py-3 text-sm">
                    <h3 class="text-xs font-semibold">
                        Observación por pregunta
                    </h3>
                    <ol class="mt-1 flex list-decimal flex-col gap-1 pl-5">
                        <li v-for="q in prueba.preguntas" :key="q.id">
                            <span
                                v-if="
                                    resultadoDe.get(q.id)?.puntaje !== null &&
                                    resultadoDe.get(q.id)?.puntaje !== undefined
                                "
                                class="font-mono font-semibold text-marca"
                                >{{ resultadoDe.get(q.id)?.puntaje }} pts ·
                            </span>
                            {{ resultadoDe.get(q.id)?.observacion }}
                        </li>
                    </ol>
                    <h3 class="mt-3 text-xs font-semibold">
                        Observación de la categoría
                    </h3>
                    <p>{{ prueba.resultado.observacion_categoria }}</p>
                    <h3 class="mt-3 text-xs font-semibold">Recomendación</h3>
                    <p>
                        <strong
                            >{{
                                prueba.resultado.recomendacion.titulo
                            }}.</strong
                        >
                        {{ prueba.resultado.recomendacion.detalle }}
                    </p>
                </div>
            </section>
        </div>

        <template #pie>
            <Boton variante="secundario" @click="abierto = false">Cerrar</Boton>
        </template>
    </Modal>
</template>
