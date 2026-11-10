<script setup lang="ts">
/**
 * Barra de arriba de E2–E4: marca, "Diagnóstico · Comidas · Medición 4" con
 * la barra de avance, "✓ Guardado 10:42" y "Guardar y salir" (vuelve al
 * inicio; las respuestas ya están guardadas, HU-060).
 *
 * Diseño: avance con una barra más gruesa y el porcentaje en una píldora; el
 * guardado en una píldora verde y "Guardar y salir" como botón con ícono.
 */
import { Link } from '@inertiajs/vue3';
import { CloudCheck, LoaderCircle, LogOut } from '@lucide/vue';
import Logo from '@/components/marca/Logo.vue';
import { empresa as inicioEmpresa } from '@/routes/inicio';

withDefaults(
    defineProps<{
        titulo?: string;
        /** "Categoría 3 de 10 · 24% respondido". */
        detalle?: string;
        porcentaje?: number | null;
        guardado?: string | null;
        guardando?: boolean;
        conSalir?: boolean;
    }>(),
    {
        titulo: undefined,
        detalle: undefined,
        porcentaje: null,
        guardado: null,
        guardando: false,
        conSalir: true,
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-20 flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-linea bg-white/95 px-4 py-3 backdrop-blur sm:h-[61px] sm:flex-nowrap sm:px-6"
    >
        <span class="flex shrink-0 items-center gap-2 font-semibold">
            <Logo class="size-8" />
            <span class="hidden sm:inline">CAPTTER</span>
        </span>
        <!-- En celular el avance pasa a un segundo renglón. -->
        <div
            v-if="titulo"
            class="order-last min-w-0 basis-full sm:order-none sm:flex-1 sm:basis-auto"
        >
            <div class="flex items-center justify-between gap-3 text-xs">
                <span class="truncate font-semibold">{{ titulo }}</span>
                <span
                    v-if="detalle"
                    class="hidden shrink-0 text-tinta-suave md:inline"
                >
                    {{ detalle }}
                </span>
            </div>
            <div
                v-if="porcentaje !== null"
                class="mt-1.5 flex items-center gap-2"
            >
                <div
                    class="h-2 flex-1 rounded-full bg-lienzo-oscuro"
                    role="progressbar"
                    :aria-valuenow="porcentaje"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-label="Avance del diagnóstico"
                >
                    <div
                        class="h-2 rounded-full bg-marca transition-all duration-500"
                        :style="{ width: `${porcentaje}%` }"
                    />
                </div>
                <span
                    class="shrink-0 rounded-full bg-marca-suave px-2 py-0.5 font-mono text-[11px] font-semibold text-marca"
                    aria-hidden="true"
                >
                    {{ porcentaje }}%
                </span>
            </div>
        </div>
        <span class="flex-1 sm:hidden" />
        <span v-if="!titulo" class="hidden flex-1 sm:block" />
        <!--
            Ancho fijo y sin parpadeo: se queda "✓ Guardado 10:42 p. m." y
            solo cambia la hora cuando cambia el minuto. "Guardando…" sale
            únicamente si todavía no se ha guardado nada.
        -->
        <span v-if="titulo" class="flex shrink-0 justify-end sm:w-44">
            <span
                v-if="guardado"
                class="inline-flex items-center gap-1.5 rounded-full bg-exito-suave px-2.5 py-1 text-xs font-medium text-exito"
            >
                <CloudCheck class="size-3.5" aria-hidden="true" />
                <span class="sr-only sm:not-sr-only">Guardado</span>
                {{ guardado }}
            </span>
            <span
                v-else-if="guardando"
                class="inline-flex items-center gap-1.5 text-xs text-tinta-suave"
            >
                <LoaderCircle
                    class="size-3.5 animate-spin motion-reduce:[animation-duration:2s]"
                    aria-hidden="true"
                />
                Guardando…
            </span>
        </span>
        <Link
            v-if="conSalir"
            :href="inicioEmpresa()"
            class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border border-linea px-3 text-sm font-medium text-tinta transition hover:border-marca hover:text-marca"
        >
            <LogOut class="size-4" aria-hidden="true" />
            Guardar y salir
        </Link>
    </header>
</template>
