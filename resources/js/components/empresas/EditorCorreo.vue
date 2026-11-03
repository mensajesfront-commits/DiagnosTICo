<script setup lang="ts">
/**
 * A3.1e · Editor del correo de aviso (HU-037): para quién, asunto y mensaje
 * con variables (se insertan donde está el cursor), "Guardar como plantilla
 * para las próximas mediciones" y a la derecha "Así le llega a la empresa",
 * con los datos reales (CA-001, CA-002).
 *
 * Lo usan A3.1b (antes de asignar) y `ModalCorreoAviso` (Recordatorio y
 * Reenviar aviso). Los botones van en quien lo usa.
 *
 * Diseño: "Para" en una tarjeta con el usuario principal, variables en
 * chips, "Guardar como plantilla" con interruptor y, a la derecha, el correo
 * con la misma cara que la plantilla real (banda azul "CAPTTER.", tarjeta
 * blanca y pie gris; `resources/views/vendor/mail`).
 */
import { AtSign, Braces, Eye } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Interruptor from '@/components/base/Interruptor.vue';
import { parrafos, rellenar, VARIABLES } from '@/lib/correo';
import type { ValoresCorreo } from '@/lib/correo';

const props = defineProps<{
    /** "laura@laesquina.co (Laura Gómez · usuario principal)". */
    para: string;
    valores: ValoresCorreo;
    errores?: Partial<Record<string, string>>;
}>();

const asunto = defineModel<string>('asunto', { required: true });
const cuerpo = defineModel<string>('cuerpo', { required: true });
const guardarPlantilla = defineModel<boolean>('guardarPlantilla', {
    default: false,
});

const area = ref<HTMLElement | null>(null);

/** "{empresa}" (en la plantilla de Vue, las llaves dentro de {{ }} confunden). */
const marca = (variable: string) => `{${variable}}`;

/** Inserta `{variable}` donde está el cursor del mensaje. */
async function insertar(variable: string): Promise<void> {
    const campo = area.value?.querySelector('textarea');
    const texto = marca(variable);

    if (!campo) {
        cuerpo.value += texto;

        return;
    }

    const inicio = campo.selectionStart ?? cuerpo.value.length;
    const fin = campo.selectionEnd ?? inicio;
    cuerpo.value =
        cuerpo.value.slice(0, inicio) + texto + cuerpo.value.slice(fin);
    await nextTick();
    campo.focus();
    campo.setSelectionRange(inicio + texto.length, inicio + texto.length);
}

const asuntoFinal = computed(() => rellenar(asunto.value, props.valores));
const cuerpoFinal = computed(() =>
    parrafos(rellenar(cuerpo.value, props.valores)),
);

/** El párrafo que es el mensaje del Administrador va resaltado, como en el wireframe. */
const esMensaje = (p: string) =>
    !!props.valores.mensaje_admin && p === props.valores.mensaje_admin.trim();
</script>

<template>
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <div class="flex min-w-0 flex-col gap-3">
            <div
                class="flex items-center gap-3 rounded-xl border border-linea bg-lienzo/50 px-3 py-2.5"
            >
                <span
                    class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-marca shadow-sm"
                    aria-hidden="true"
                >
                    <AtSign class="size-4" />
                </span>
                <div class="min-w-0 text-sm">
                    <p class="text-[11px] text-tinta-suave">Para</p>
                    <p id="correo-para" class="font-medium break-words">
                        {{ para }}
                    </p>
                </div>
            </div>
            <Campo
                obligatorio
                etiqueta="Asunto"
                para="correo-asunto"
                :error="errores?.asunto"
            >
                <Entrada
                    id="correo-asunto"
                    v-model="asunto"
                    maxlength="255"
                    :invalida="!!errores?.asunto"
                />
            </Campo>
            <Campo
                obligatorio
                etiqueta="Mensaje"
                para="correo-cuerpo"
                :error="errores?.cuerpo"
            >
                <div ref="area">
                    <AreaTexto
                        id="correo-cuerpo"
                        v-model="cuerpo"
                        rows="9"
                        maxlength="5000"
                        :invalida="!!errores?.cuerpo"
                    />
                </div>
            </Campo>
            <div class="rounded-xl bg-lienzo/60 px-3 py-2.5">
                <p
                    class="flex items-center gap-1.5 text-xs font-medium text-tinta-suave"
                >
                    <Braces class="size-3.5" aria-hidden="true" />
                    Variables · se insertan donde está el cursor
                </p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <button
                        v-for="v in VARIABLES"
                        :key="v"
                        type="button"
                        class="rounded-full border border-marca/30 bg-white px-2.5 py-0.5 font-mono text-xs text-marca transition hover:bg-marca hover:text-white"
                        :title="`Insertar ${marca(v)} en el mensaje`"
                        @click="insertar(v)"
                    >
                        {{ marca(v) }}
                    </button>
                </div>
            </div>
            <div class="rounded-xl border border-linea px-3.5 py-1">
                <Interruptor id="correo-plantilla" v-model="guardarPlantilla">
                    Guardar como plantilla para las próximas mediciones
                </Interruptor>
            </div>
        </div>

        <section
            aria-label="Así le llega a la empresa"
            class="flex min-w-0 flex-col gap-2"
        >
            <p
                class="flex items-center gap-1.5 text-xs font-medium text-tinta-suave"
            >
                <Eye class="size-3.5" aria-hidden="true" />
                Así le llega a la empresa
            </p>
            <div
                class="overflow-hidden rounded-2xl border border-linea bg-lienzo text-sm"
            >
                <div class="border-b border-linea bg-white px-4 py-3">
                    <p class="font-semibold">
                        {{ asuntoFinal || '(sin asunto)' }}
                    </p>
                    <p class="mt-0.5 text-xs break-words text-tinta-suave">
                        Para: {{ para }}
                    </p>
                </div>
                <div class="p-3 sm:p-4">
                    <div
                        class="rounded-t-xl bg-[#14154d] px-4 py-3 text-center"
                    >
                        <p class="text-lg font-bold tracking-wide text-white">
                            CAPTTER<span class="text-[#6ae45b]">.</span>
                        </p>
                        <p class="text-[11px] text-[#c9cdea]">
                            Diagnóstico de marketing digital · NuevasTIC
                        </p>
                    </div>
                    <div
                        class="flex flex-col gap-3 rounded-b-xl border border-t-0 border-linea bg-white px-4 py-4"
                    >
                        <template v-for="(p, i) in cuerpoFinal" :key="i">
                            <p
                                v-if="esMensaje(p)"
                                class="rounded-lg border-l-4 border-marca bg-marca-suave/60 px-3 py-2 whitespace-pre-line text-tinta"
                            >
                                “{{ p }}”
                            </p>
                            <p v-else class="whitespace-pre-line">{{ p }}</p>
                        </template>
                        <span
                            class="inline-flex h-10 w-fit items-center self-center rounded-lg bg-marca px-5 text-xs font-semibold text-white"
                            aria-hidden="true"
                        >
                            Entrar e iniciar el diagnóstico
                        </span>
                    </div>
                    <p
                        class="px-2 pt-3 text-center text-[11px] text-tinta-suave"
                    >
                        Recibes este correo porque tu empresa está registrada en
                        Captter. Si tienes dudas, responde a tu administrador.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
