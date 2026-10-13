<script setup lang="ts">
/**
 * Una pregunta del resultado (E6 detalle, E9): el texto, la respuesta, su
 * puntaje ("· IA" si lo dio la IA, en las abiertas) y la observación de la
 * IA. Solo lectura (RN-017).
 */
import type { PreguntaResultado } from '@/types/resultados';

withDefaults(
    defineProps<{
        pregunta: PreguntaResultado;
        numero?: number;
        /** Lo ve el equipo de NuevasTIC: "Respuesta" en vez de "Tu respuesta". */
        interno?: boolean;
    }>(),
    { numero: undefined, interno: false },
);
</script>

<template>
    <article class="border-b border-lienzo-oscuro pb-4 last:border-0">
        <div class="flex items-start justify-between gap-4">
            <p class="text-sm font-medium">
                <span v-if="numero" class="mr-1 text-tinta-suave">{{
                    numero
                }}</span>
                {{ pregunta.texto }}
            </p>
            <span
                class="shrink-0 font-mono text-xs whitespace-nowrap text-tinta-suave"
            >
                <template v-if="pregunta.puntaje !== null">
                    {{ pregunta.puntaje }} pts<template
                        v-if="pregunta.tipo === 'abierta'"
                    >
                        · IA</template
                    >
                </template>
                <template v-else>—</template>
            </span>
        </div>
        <p
            v-if="pregunta.respuesta !== null"
            class="mt-1 text-sm text-tinta-suave"
        >
            {{ interno ? 'Respuesta' : 'Tu respuesta' }}:
            <span class="text-tinta">{{
                pregunta.tipo === 'abierta'
                    ? `“${pregunta.respuesta}”`
                    : pregunta.respuesta
            }}</span>
        </p>
        <p v-else class="mt-1 text-sm text-tinta-suave italic">
            Sin responder (opcional). No cuenta en el puntaje.
        </p>
        <p
            v-if="pregunta.observacion"
            class="mt-2 flex items-start gap-2 rounded-md bg-lienzo px-3 py-2 text-xs"
        >
            <span
                class="rounded border border-linea bg-white px-1 font-mono text-[10px]"
                >IA</span
            >
            <span>
                <strong v-if="pregunta.tipo === 'abierta'"
                    >Calificación de la IA:
                </strong>
                {{ pregunta.observacion }}
            </span>
        </p>
    </article>
</template>
