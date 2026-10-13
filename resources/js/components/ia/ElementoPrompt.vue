<script setup lang="ts">
/**
 * Un elemento del prompt en A4 (contexto, tarea, detalles o ejemplos).
 *
 * - Instrucciones generales: el texto se edita directo; abajo dice qué datos
 *   automáticos usa y "Restaurar texto original".
 * - Ajuste de sector o empresa (A4.5, A4.6): se elige "Usar general" (o
 *   "Usar sector"), "Agregar" o "Reemplazar" y se ve cómo lo leerá la IA.
 */
import { computed } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import { variablesUsadas } from '@/lib/ia';
import { cn } from '@/lib/utils';
import type {
    Alcance,
    ElementoPrompt,
    Modo,
    VariableIa,
} from '@/types/configuracionIa';

const props = defineProps<{
    numero: number;
    clave: ElementoPrompt;
    titulo: string;
    ayuda: string;
    alcance: Alcance;
    /** Texto que se hereda ("usar"). */
    base: string;
    /** "general" o "sector": de dónde viene la base. */
    origenBase: 'general' | 'sector';
    /** Nombre de la capa que se edita: "Abogados", "Rojas & Asociados". */
    nombreCapa?: string;
    original?: string;
    variables: VariableIa[];
    soloLectura: boolean;
    error?: string;
}>();

const texto = defineModel<string>('texto', { required: true });
const modo = defineModel<Modo>('modo', { default: 'usar' });

const usados = computed(() => new Set(variablesUsadas(texto.value)));
const esperadas = computed(() =>
    props.variables.filter((v) => v.elemento === props.clave),
);
const faltan = computed(() =>
    esperadas.value.filter((v) => !usados.value.has(v.codigo)),
);

const nombreBase = computed(() =>
    props.origenBase === 'sector' ? 'sector' : 'general',
);

const opciones = computed(() => [
    {
        valor: 'usar' as const,
        titulo: `Usar ${nombreBase.value}`,
        detalle:
            props.origenBase === 'sector'
                ? 'Se usan las instrucciones del sector tal cual.'
                : 'Se usan las instrucciones generales tal cual.',
    },
    {
        valor: 'agregar' as const,
        titulo: 'Agregar',
        detalle: `Tu texto se suma al final de las instrucciones ${props.origenBase === 'sector' ? 'del sector' : 'generales'}.`,
    },
    {
        valor: 'reemplazar' as const,
        titulo: 'Reemplazar',
        detalle: `Tu texto sustituye por completo las instrucciones ${props.origenBase === 'sector' ? 'del sector' : 'generales'} de esta parte.`,
    },
]);

/** {sector}, {categoria}: en el template no se puede escribir la llave. */
const lista = (vs: VariableIa[]) =>
    vs.map((v) => '{' + v.codigo + '}').join(', ');

const idTexto = computed(() => `prompt-${props.clave}`);
</script>

<template>
    <section class="relative rounded-xl border border-linea bg-white p-5">
        <header class="flex items-start gap-3">
            <span
                class="flex size-7 shrink-0 items-center justify-center rounded-full bg-lienzo-oscuro text-xs font-semibold"
                aria-hidden="true"
            >
                {{ numero }}
            </span>
            <div>
                <h2 class="font-semibold">
                    <label v-if="alcance === 'general'" :for="idTexto">{{
                        titulo
                    }}</label>
                    <template v-else>{{ titulo }}</template>
                    <span
                        v-if="clave === 'contexto' || clave === 'tarea'"
                        class="text-aviso"
                        aria-hidden="true"
                        >{{ alcance === 'general' ? ' *' : '' }}</span
                    >
                </h2>
                <p class="text-sm text-tinta-suave">{{ ayuda }}</p>
            </div>
        </header>

        <!-- Instrucciones generales -->
        <template v-if="alcance === 'general'">
            <AreaTexto
                :id="idTexto"
                v-model="texto"
                rows="4"
                class="mt-3 min-h-28 font-normal"
                :disabled="soloLectura"
                :invalida="!!error"
                :aria-describedby="`${idTexto}-pie`"
            />
            <p v-if="error" class="mt-1 text-xs text-aviso">{{ error }}</p>
            <div
                :id="`${idTexto}-pie`"
                class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs"
            >
                <span v-if="esperadas.length === 0" class="text-exito">
                    ✓ Esta parte no necesita datos automáticos
                </span>
                <span v-else-if="faltan.length === 0" class="text-exito">
                    ✓ Incluye
                    {{ lista(esperadas) }}
                </span>
                <span v-else class="text-[#9a5d12]">
                    Sin {{ lista(faltan) }}: la IA no recibirá ese dato en esta
                    parte.
                </span>
                <button
                    v-if="!soloLectura && original !== undefined"
                    type="button"
                    class="text-marca underline underline-offset-2 disabled:text-tinta-suave disabled:no-underline"
                    :disabled="texto === original"
                    @click="texto = original ?? ''"
                >
                    Restaurar texto original
                </button>
            </div>
        </template>

        <!-- Ajuste de sector o de empresa -->
        <template v-else>
            <fieldset class="mt-3">
                <legend class="sr-only">Cómo ajustar {{ titulo }}</legend>
                <div class="grid gap-2 sm:grid-cols-3">
                    <label
                        v-for="o in opciones"
                        :key="o.valor"
                        :class="
                            cn(
                                'flex cursor-pointer gap-2 rounded-lg border px-3 py-2.5 text-sm transition-colors',
                                modo === o.valor
                                    ? 'border-marca bg-marca-suave/50'
                                    : 'border-linea hover:bg-lienzo',
                                soloLectura && 'cursor-default',
                            )
                        "
                    >
                        <input
                            v-model="modo"
                            type="radio"
                            :name="`modo-${clave}`"
                            :value="o.valor"
                            :disabled="soloLectura"
                            class="mt-0.5 accent-marca"
                        />
                        <span>
                            <span class="block font-medium">{{
                                o.titulo
                            }}</span>
                            <span class="block text-xs text-tinta-suave">{{
                                o.detalle
                            }}</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <div class="mt-3 overflow-hidden rounded-lg border border-linea">
                <div
                    v-if="modo !== 'reemplazar'"
                    class="bg-lienzo px-4 py-3 text-sm"
                >
                    <p class="text-xs font-semibold text-tinta-suave">
                        {{
                            origenBase === 'sector'
                                ? 'Instrucciones del sector'
                                : 'Instrucciones generales'
                        }}{{ modo === 'usar' ? ' · se usan tal cual' : '' }}
                    </p>
                    <p
                        class="mt-1 whitespace-pre-line"
                        :class="modo === 'usar' ? '' : 'text-tinta-suave'"
                    >
                        {{ base || '(vacío)' }}
                    </p>
                </div>
                <div
                    v-if="modo !== 'usar'"
                    :class="
                        cn(
                            'px-4 py-3',
                            modo === 'agregar' &&
                                'border-t border-dashed border-linea-fuerte bg-marca-suave/30',
                        )
                    "
                >
                    <label
                        :for="idTexto"
                        class="text-xs font-semibold text-marca"
                    >
                        {{
                            modo === 'agregar'
                                ? '+ Se agrega al final'
                                : 'Tu texto reemplaza al anterior'
                        }}
                        · {{ nombreCapa }}
                        <span class="text-aviso" aria-hidden="true">*</span>
                    </label>
                    <AreaTexto
                        :id="idTexto"
                        v-model="texto"
                        rows="3"
                        class="mt-1"
                        :disabled="soloLectura"
                        :invalida="!!error"
                    />
                    <p v-if="error" class="mt-1 text-xs text-aviso">
                        {{ error }}
                    </p>
                </div>
            </div>
        </template>
    </section>
</template>
