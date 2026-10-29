<script setup lang="ts">
/**
 * Barra de arriba de E2–E4: marca, "Diagnóstico · Comidas · Medición 4" con
 * la barra de avance, "✓ Guardado 10:42" y "Guardar y salir" (vuelve al
 * inicio; las respuestas ya están guardadas, HU-060).
 */
import { Link } from '@inertiajs/vue3';
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
        class="sticky top-0 z-20 flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-linea bg-white px-4 py-3 sm:flex-nowrap sm:px-6"
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
            <div class="flex items-baseline justify-between gap-3 text-xs">
                <span class="truncate font-medium">{{ titulo }}</span>
                <span v-if="detalle" class="shrink-0 text-tinta-suave">
                    {{ detalle }}
                </span>
            </div>
            <div
                v-if="porcentaje !== null"
                class="mt-1.5 h-1.5 rounded-full bg-lienzo-oscuro"
                role="progressbar"
                :aria-valuenow="porcentaje"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="Avance del diagnóstico"
            >
                <div
                    class="h-1.5 rounded-full bg-marca transition-all"
                    :style="{ width: `${porcentaje}%` }"
                />
            </div>
        </div>
        <span class="flex-1 sm:hidden" />
        <span v-if="!titulo" class="hidden flex-1 sm:block" />
        <!--
            Ancho fijo: "Guardando…" y "✓ Guardado 10:42 p. m." ocupan lo
            mismo, así la barra de avance y los textos no se mueven al guardar.
        -->
        <span
            v-if="titulo"
            :class="[
                'w-40 shrink-0 text-right text-xs',
                guardando ? 'text-tinta-suave' : 'text-exito',
            ]"
            aria-live="polite"
        >
            {{
                guardando
                    ? 'Guardando…'
                    : guardado
                      ? `✓ Guardado ${guardado}`
                      : ''
            }}
        </span>
        <Link
            v-if="conSalir"
            :href="inicioEmpresa()"
            class="shrink-0 text-sm text-marca underline underline-offset-2 hover:text-marca-hover"
        >
            Guardar y salir
        </Link>
    </header>
</template>
