<script setup lang="ts">
/**
 * A4.4 · Instrucciones completas (HU-044): el prompt final de la etapa
 * "Analizar categoría" en un caso real, con las capas ya unidas, los datos
 * reemplazados y los cambios sin guardar.
 *
 * - Lectura: cada parte marcada con su origen (sin marca: general; azul:
 *   sector; morado: empresa); los datos que se insertan solos, subrayados
 *   con puntos; las respuestas como citas, con su puntaje o "la califica la
 *   IA" y el "Ten en cuenta".
 * - Texto exacto: lo que recibe la IA, con "Copiar texto exacto".
 */
import { ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Modal from '@/components/base/Modal.vue';
import SelectorCompacto from '@/components/base/SelectorCompacto.vue';
import SelectorEjemplo from '@/components/ia/SelectorEjemplo.vue';
import { enviarJson, origenes } from '@/lib/ia';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { Borrador, EjemploIa, VistaPrevia } from '@/types/configuracionIa';

const props = defineProps<{ ejemplos: EjemploIa[]; borrador: Borrador }>();

const abierto = defineModel<boolean>('abierto', { default: false });

const medicion = ref<number | ''>('');
const categoria = ref('');
const vista = ref<'lectura' | 'exacto'>('lectura');
const datos = ref<VistaPrevia | null>(null);
const error = ref('');
const cargando = ref(false);
const copiado = ref(false);

watch(
    abierto,
    (a) => {
        if (a) {
            medicion.value = props.ejemplos[0]?.medicion_id ?? '';
            cargar();
        }
    },
    { immediate: true },
);

watch([medicion, categoria], () => abierto.value && cargar());

async function cargar(): Promise<void> {
    if (!medicion.value || !categoria.value) {
        return;
    }

    cargando.value = true;
    const r = await enviarJson<VistaPrevia>(rutas.ia.vistaPrevia(), {
        medicion_id: medicion.value,
        categoria_ref: categoria.value,
        borrador: props.borrador,
    });
    cargando.value = false;
    datos.value = r.ok ? r.datos : null;
    error.value = r.ok ? '' : r.mensaje;
}

async function copiar(): Promise<void> {
    if (!datos.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(datos.value.texto_exacto);
        copiado.value = true;
        setTimeout(() => (copiado.value = false), 2000);
    } catch {
        // Sin permiso para el portapapeles: el texto sigue a la vista.
        vista.value = 'exacto';
    }
}

const tipos = {
    opcion_unica: 'Opción única',
    seleccion_multiple: 'Selección múltiple',
    abierta: 'Abierta',
};
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        ancho="xl"
        titulo="Instrucciones completas · Analizar categoría"
        descripcion="Así lee la IA las instrucciones en un caso real: las partes ya unidas y los datos reemplazados, con los cambios que aún no guardas."
    >
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end gap-3">
                <SelectorEjemplo
                    v-model:medicion="medicion"
                    v-model:categoria="categoria"
                    :ejemplos="ejemplos"
                    class="min-w-0 flex-1"
                />
                <SelectorCompacto
                    v-model="vista"
                    :opciones="[
                        { valor: 'lectura', etiqueta: 'Lectura' },
                        { valor: 'exacto', etiqueta: 'Texto exacto' },
                    ]"
                    etiqueta-accesible="Cómo ver el prompt"
                />
            </div>

            <p v-if="ejemplos.length === 0" class="text-sm text-tinta-suave">
                Todavía no hay mediciones con respuestas para usar de ejemplo.
            </p>
            <p v-else-if="error" class="text-sm text-aviso" role="alert">
                {{ error }}
            </p>

            <div
                v-if="datos"
                :class="
                    cn(
                        'max-h-[55vh] overflow-y-auto rounded-lg border border-linea p-5',
                        cargando && 'opacity-60',
                    )
                "
                aria-live="polite"
            >
                <pre
                    v-if="vista === 'exacto'"
                    class="font-mono text-xs whitespace-pre-wrap"
                    >{{ datos.texto_exacto }}</pre>
                <dl
                    v-else
                    class="grid gap-x-6 gap-y-5 text-sm sm:grid-cols-[110px_1fr]"
                >
                    <template v-for="p in datos.partes" :key="p.elemento">
                        <template v-if="p.piezas.length">
                            <dt
                                class="pt-0.5 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                            >
                                {{ p.titulo }}
                            </dt>
                            <dd class="leading-relaxed whitespace-pre-line">
                                <span
                                    v-for="(pieza, i) in p.piezas"
                                    :key="i"
                                    :class="
                                        cn(
                                            origenes[pieza.origen].clase,
                                            pieza.dato &&
                                                'underline decoration-dotted decoration-2 underline-offset-4',
                                        )
                                    "
                                    >{{ pieza.texto
                                    }}<sup
                                        v-if="
                                            pieza.origen !== 'general' &&
                                            p.piezas[i + 1]?.origen !==
                                                pieza.origen
                                        "
                                        class="ml-0.5 text-[10px] text-marca"
                                        >[{{
                                            origenes[pieza.origen].nombre
                                        }}]</sup
                                    ></span
                                >
                            </dd>
                        </template>
                        <template v-if="p.elemento === 'tarea'">
                            <dt
                                class="pt-0.5 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                            >
                                Respuestas
                            </dt>
                            <dd class="flex flex-col gap-2">
                                <p class="text-xs text-tinta-suave">
                                    Se insertan solas desde lo que respondió la
                                    empresa y las indicaciones de cada pregunta.
                                </p>
                                <blockquote
                                    v-for="(q, n) in datos.preguntas"
                                    :key="q.id"
                                    class="rounded-md bg-lienzo px-3 py-2"
                                >
                                    <p class="text-xs text-tinta-suave">
                                        {{ n + 1 }}. {{ q.texto }} ·
                                        {{ tipos[q.tipo] }}
                                    </p>
                                    <p>
                                        “{{ q.respuesta }}”
                                        <span
                                            class="font-mono text-xs text-tinta-suave"
                                        >
                                            →
                                            {{
                                                q.puntaje !== null
                                                    ? `${q.puntaje} pts (ya calculado)`
                                                    : 'la IA la califica'
                                            }}
                                        </span>
                                    </p>
                                    <p
                                        v-if="q.ten_en_cuenta"
                                        class="mt-1 rounded bg-marca-suave/60 px-2 py-1 text-xs"
                                    >
                                        <strong>Ten en cuenta:</strong>
                                        {{ q.ten_en_cuenta }}
                                    </p>
                                    <p
                                        v-if="q.criterio"
                                        class="mt-1 rounded bg-marca-suave/60 px-2 py-1 text-xs"
                                    >
                                        <strong>Criterio:</strong>
                                        {{ q.criterio }}
                                    </p>
                                </blockquote>
                                <p
                                    v-if="datos.preguntas.length === 0"
                                    class="text-xs text-tinta-suave"
                                >
                                    La empresa no respondió preguntas de esta
                                    categoría.
                                </p>
                            </dd>
                        </template>
                    </template>
                    <dt
                        class="pt-0.5 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                    >
                        Formato
                    </dt>
                    <dd class="leading-relaxed">{{ datos.formato }}</dd>
                </dl>
            </div>

            <p
                v-if="datos && vista === 'lectura'"
                class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-tinta-suave"
            >
                <span>Origen: sin marca, instrucciones generales</span>
                <span :class="origenes.sector.clase"
                    >[Sector] ajuste del sector</span
                >
                <span :class="origenes.empresa.clase"
                    >[Empresa] ajuste de la empresa</span
                >
                <span
                    class="underline decoration-dotted decoration-2 underline-offset-4"
                    >dato que se inserta solo</span
                >
            </p>
        </div>

        <template v-if="datos" #nota>
            ≈ {{ datos.palabras }} palabras · se envía una vez por categoría
        </template>
        <template #pie>
            <Boton variante="secundario" :disabled="!datos" @click="copiar">
                {{ copiado ? '✓ Copiado' : 'Copiar texto exacto' }}
            </Boton>
            <Boton @click="abierto = false">Cerrar</Boton>
        </template>
    </Modal>
</template>
