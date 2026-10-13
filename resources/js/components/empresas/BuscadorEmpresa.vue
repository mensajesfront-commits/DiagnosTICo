<script setup lang="ts">
/**
 * Buscador de empresa de A3.1b: se escribe el nombre (sin mirar tildes ni
 * mayúsculas) y la lista muestra junto a cada una su sector y si ya tiene
 * una medición pendiente. Se usa con v-model (id de la empresa).
 */
import { Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { normalizar } from '@/lib/texto';
import { cn } from '@/lib/utils';
import type { EmpresaAsignable } from '@/types/empresas';

const props = defineProps<{
    id: string;
    empresas: EmpresaAsignable[];
    invalida?: boolean;
}>();

const modelo = defineModel<number | ''>({ required: true });

const elegida = computed(
    () => props.empresas.find((e) => e.id === modelo.value) ?? null,
);

const texto = ref(elegida.value?.nombre ?? '');
const abierta = ref(false);
const activa = ref(0);

watch(elegida, (e) => (texto.value = e?.nombre ?? texto.value));

const MAXIMO = 8;

const opciones = computed(() => {
    const buscado = normalizar(texto.value);
    const todas =
        buscado === '' || buscado === normalizar(elegida.value?.nombre ?? '')
            ? props.empresas
            : props.empresas.filter((e) =>
                  normalizar(e.nombre).includes(buscado),
              );

    return todas.slice(0, MAXIMO);
});

watch(opciones, () => (activa.value = 0));

const estados: Record<string, string> = {
    no_iniciada: 'No iniciada',
    en_curso: 'En curso',
    enviada: 'Enviada',
    vencida: 'Vencida',
};

function elegir(e: EmpresaAsignable): void {
    modelo.value = e.id;
    texto.value = e.nombre;
    abierta.value = false;
}

function escribir(): void {
    abierta.value = true;

    if (elegida.value && texto.value !== elegida.value.nombre) {
        modelo.value = '';
    }
}

function teclas(evento: KeyboardEvent): void {
    if (evento.key === 'ArrowDown') {
        evento.preventDefault();
        abierta.value = true;
        activa.value = Math.min(activa.value + 1, opciones.value.length - 1);
    } else if (evento.key === 'ArrowUp') {
        evento.preventDefault();
        activa.value = Math.max(activa.value - 1, 0);
    } else if (evento.key === 'Enter' && abierta.value) {
        evento.preventDefault();
        const e = opciones.value[activa.value];

        if (e) {
            elegir(e);
        }
    } else if (evento.key === 'Escape') {
        abierta.value = false;
    }
}

// Al salir del campo sin elegir, vuelve al nombre de la elegida.
function salir(): void {
    setTimeout(() => {
        abierta.value = false;
        texto.value = elegida.value?.nombre ?? texto.value;
    }, 150);
}
</script>

<template>
    <div class="relative">
        <Search
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-tinta-suave"
            aria-hidden="true"
        />
        <input
            :id="id"
            v-model="texto"
            type="text"
            role="combobox"
            autocomplete="off"
            placeholder="Escribe el nombre de la empresa"
            :aria-expanded="abierta"
            :aria-controls="`${id}-lista`"
            :aria-invalid="invalida || undefined"
            :class="
                cn(
                    'h-10 w-full rounded-md border border-linea-fuerte bg-white pr-3 pl-9 text-sm text-tinta placeholder:text-tinta-suave/70 focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none',
                    invalida && 'border-aviso',
                )
            "
            @input="escribir"
            @focus="abierta = true"
            @blur="salir"
            @keydown="teclas"
        />
        <ul
            v-if="abierta"
            :id="`${id}-lista`"
            role="listbox"
            class="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-md border border-linea bg-white py-1 text-sm shadow-lg"
        >
            <li
                v-for="(e, i) in opciones"
                :key="e.id"
                role="option"
                :aria-selected="e.id === modelo"
                :class="
                    cn(
                        'flex cursor-pointer items-center justify-between gap-3 px-3 py-2',
                        i === activa && 'bg-marca-suave/60',
                    )
                "
                @mousedown.prevent="elegir(e)"
                @mouseenter="activa = i"
            >
                <span>
                    <span class="font-medium">{{ e.nombre }}</span>
                    <span class="ml-2 text-tinta-suave">{{ e.sector }}</span>
                </span>
                <span
                    v-if="e.pendiente"
                    class="shrink-0 rounded bg-lienzo-oscuro px-2 py-0.5 text-xs text-tinta-suave"
                >
                    Medición {{ e.pendiente.numero }} pendiente ·
                    {{ estados[e.pendiente.estado] }}
                </span>
            </li>
            <li v-if="opciones.length === 0" class="px-3 py-2 text-tinta-suave">
                Ninguna empresa activa con ese nombre.
            </li>
        </ul>
    </div>
</template>
