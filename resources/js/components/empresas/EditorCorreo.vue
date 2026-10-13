<script setup lang="ts">
/**
 * A3.1e · Editor del correo de aviso (HU-037): para quién, asunto y mensaje
 * con variables (se insertan donde está el cursor), "Guardar como plantilla
 * para las próximas mediciones" y a la derecha "Así le llega a la empresa",
 * con los datos reales (CA-001, CA-002).
 *
 * Lo usan A3.1b (antes de asignar) y `ModalCorreoAviso` (Recordatorio y
 * Reenviar aviso). Los botones van en quien lo usa.
 */
import { computed, nextTick, ref } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
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
    <div class="grid gap-5 lg:grid-cols-[1fr_1fr]">
        <div class="flex flex-col gap-3">
            <p class="text-xs text-tinta-suave">
                Se envía al usuario principal de la empresa. Es una plantilla:
                las variables se llenan solas.
            </p>
            <Campo etiqueta="Para" para="correo-para">
                <Entrada id="correo-para" :model-value="para" disabled />
            </Campo>
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
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                <span class="text-tinta-suave">Variables:</span>
                <button
                    v-for="v in VARIABLES"
                    :key="v"
                    type="button"
                    class="rounded border border-marca/30 bg-marca-suave/50 px-1.5 py-0.5 font-mono text-marca hover:bg-marca-suave"
                    :title="`Insertar ${marca(v)} en el mensaje`"
                    @click="insertar(v)"
                >
                    {{ marca(v) }}
                </button>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input
                    v-model="guardarPlantilla"
                    type="checkbox"
                    class="size-4 accent-marca"
                />
                Guardar como plantilla para las próximas mediciones
            </label>
        </div>

        <section
            aria-label="Así le llega a la empresa"
            class="flex flex-col gap-2"
        >
            <p class="text-xs font-medium">Así le llega a la empresa</p>
            <div class="rounded-lg border border-linea bg-white text-sm">
                <div class="border-b border-linea px-4 py-3">
                    <p class="font-semibold">
                        {{ asuntoFinal || '(sin asunto)' }}
                    </p>
                    <p class="mt-0.5 text-xs text-tinta-suave">
                        Para: {{ para }}
                    </p>
                </div>
                <div class="flex flex-col gap-3 px-4 py-4">
                    <p class="text-base font-semibold">CAPTTER</p>
                    <template v-for="(p, i) in cuerpoFinal" :key="i">
                        <p
                            v-if="esMensaje(p)"
                            class="rounded-md bg-lienzo px-3 py-2 whitespace-pre-line text-tinta"
                        >
                            “{{ p }}”
                        </p>
                        <p v-else class="whitespace-pre-line">{{ p }}</p>
                    </template>
                    <span
                        class="inline-flex h-9 w-fit items-center rounded-md bg-marca px-4 text-xs font-medium text-white"
                        aria-hidden="true"
                    >
                        Entrar e iniciar el diagnóstico
                    </span>
                    <p
                        class="border-t border-linea pt-3 text-xs text-tinta-suave"
                    >
                        Recibes este correo porque tu empresa está registrada en
                        Captter. Si tienes dudas, responde a tu administrador.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
