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
        class="sticky top-0 z-20 flex items-center gap-6 border-b border-linea bg-white px-6 py-3"
    >
        <span class="flex shrink-0 items-center gap-2 font-semibold">
            <Logo class="size-8" />
            CAPTTER
        </span>
        <div v-if="titulo" class="min-w-0 flex-1">
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
        <span v-else class="flex-1" />
        <span
            v-if="guardando"
            class="shrink-0 text-xs text-tinta-suave"
            aria-live="polite"
        >
            Guardando…
        </span>
        <span
            v-else-if="guardado"
            class="shrink-0 text-xs text-exito"
            aria-live="polite"
        >
            ✓ Guardado {{ guardado }}
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
