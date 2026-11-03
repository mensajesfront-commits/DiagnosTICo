<script setup lang="ts">
/**
 * Los 15 permisos de A5.2 en sus 6 bloques, con el contador "6 de 15
 * marcados". Se usa con v-model (lista de nombres técnicos).
 *
 * Bloqueada (roles del sistema): las casillas se ven marcadas en gris y no
 * cambian (RN-027).
 *
 * Diseño: cada bloque en una tarjeta con su ícono y "2/3" marcados.
 */
import {
    Building2,
    ClipboardList,
    FileText,
    Shield,
    Sparkles,
    UserRound,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import type { BloquePermisos } from '@/types/usuarios';

const props = withDefaults(
    defineProps<{
        bloques: BloquePermisos[];
        bloqueada?: boolean;
        /** Aclaración junto a un permiso ("solo su empresa"). */
        notas?: Record<string, string>;
        /** Prefijo de los id, por si hay dos grillas en la misma pantalla. */
        prefijo?: string;
    }>(),
    { bloqueada: false, notas: () => ({}), prefijo: 'permiso' },
);

const marcados = defineModel<string[]>({ required: true });

const total = computed(() =>
    props.bloques.reduce((suma, b) => suma + b.permisos.length, 0),
);

const iconos: Record<string, Component> = {
    Diagnósticos: FileText,
    Empresas: Building2,
    'Mediciones y resultados': ClipboardList,
    'Configuración IA': Sparkles,
    'Usuarios y roles': Users,
    'Su cuenta': UserRound,
};

const marcadosDe = (b: BloquePermisos) =>
    b.permisos.filter((p) => marcados.value.includes(p.nombre)).length;

function alternar(nombre: string, valor: boolean): void {
    marcados.value = valor
        ? [...marcados.value, nombre]
        : marcados.value.filter((p) => p !== nombre);
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex items-baseline justify-between gap-3">
            <slot name="titulo">
                <p class="text-sm font-semibold">Permisos</p>
            </slot>
            <p
                class="rounded-full bg-marca-suave px-2.5 py-0.5 text-xs font-medium text-marca"
                aria-live="polite"
            >
                {{ marcados.length }} de {{ total }} marcados
            </p>
        </div>
        <slot name="ayuda" />

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <fieldset
                v-for="bloque in bloques"
                :key="bloque.bloque"
                class="rounded-xl border border-linea bg-white px-4 pt-3 pb-3"
            >
                <legend class="sr-only">{{ bloque.bloque }}</legend>
                <p
                    class="flex items-center justify-between gap-2 text-sm font-semibold"
                    aria-hidden="true"
                >
                    <span class="flex items-center gap-2">
                        <span
                            class="flex size-7 items-center justify-center rounded-lg bg-marca-suave text-marca"
                        >
                            <component
                                :is="iconos[bloque.bloque] ?? Shield"
                                class="size-3.5"
                            />
                        </span>
                        {{ bloque.bloque }}
                    </span>
                    <span
                        class="font-mono text-[11px] font-normal text-tinta-suave"
                    >
                        {{ marcadosDe(bloque) }}/{{ bloque.permisos.length }}
                    </span>
                </p>
                <label
                    v-for="permiso in bloque.permisos"
                    :key="permiso.nombre"
                    :for="`${prefijo}-${permiso.nombre}`"
                    :class="[
                        'mt-2 flex items-start gap-2.5 text-sm',
                        bloqueada ? 'text-tinta-suave' : 'cursor-pointer',
                    ]"
                >
                    <input
                        :id="`${prefijo}-${permiso.nombre}`"
                        type="checkbox"
                        class="mt-0.5 size-4 shrink-0 accent-marca disabled:opacity-60"
                        :checked="marcados.includes(permiso.nombre)"
                        :disabled="bloqueada"
                        @change="
                            alternar(
                                permiso.nombre,
                                ($event.target as HTMLInputElement).checked,
                            )
                        "
                    />
                    <span>
                        {{ permiso.texto }}
                        <span
                            v-if="notas[permiso.nombre]"
                            class="text-xs text-tinta-suave"
                        >
                            · {{ notas[permiso.nombre] }}
                        </span>
                    </span>
                </label>
            </fieldset>
        </div>
    </div>
</template>
