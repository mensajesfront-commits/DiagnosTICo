<script setup lang="ts">
/**
 * Lista con búsqueda: se escribe para filtrar (sin importar tildes ni
 * mayúsculas) y se elige con el ratón o con las flechas y Enter.
 *
 * - Sin `permitirOtro`, solo vale una opción de la lista: si lo escrito no
 *   coincide, al salir vuelve a la opción anterior.
 * - Con `permitirOtro` (ciudad), lo escrito se acepta tal cual y la lista
 *   ofrece "Usar «…»".
 *
 * Muestra hasta 50 opciones; con más, pide seguir escribiendo.
 */
import { ChevronDown } from '@lucide/vue';
import { computed, nextTick, ref, useId, watch } from 'vue';
import { filtrarOpciones, opcionExacta } from '@/lib/texto';
import { cn } from '@/lib/utils';

const MAXIMO = 50;

const props = withDefaults(
    defineProps<{
        id: string;
        opciones: string[];
        placeholder?: string;
        disabled?: boolean;
        required?: boolean;
        invalida?: boolean;
        permitirOtro?: boolean;
        /** Texto cuando nada coincide y no se permite otro valor. */
        sinResultados?: string;
    }>(),
    {
        placeholder: undefined,
        disabled: false,
        required: false,
        invalida: false,
        permitirOtro: false,
        sinResultados: 'No hay coincidencias.',
    },
);

const modelo = defineModel<string>({ default: '' });

const texto = ref(modelo.value);
const abierto = ref(false);
const activo = ref(0);
const escribiendo = ref(false);
const lista = ref<HTMLUListElement | null>(null);
const entrada = ref<HTMLInputElement | null>(null);
/** La lista se abre hacia arriba si abajo no cabe (final de la página). */
const haciaArriba = ref(false);
const idLista = `${useId()}-lista`;

watch(modelo, (valor) => {
    texto.value = valor;
});

const filtradas = computed(() =>
    escribiendo.value
        ? filtrarOpciones(props.opciones, texto.value)
        : props.opciones,
);

const visibles = computed(() => filtradas.value.slice(0, MAXIMO));

/** "Usar «…»" cuando se puede escribir y lo escrito no está en la lista. */
const otro = computed(() => {
    const valor = texto.value.trim();

    return props.permitirOtro &&
        escribiendo.value &&
        valor !== '' &&
        !opcionExacta(props.opciones, valor)
        ? valor
        : null;
});

// "Usar «…»" va al final: con Enter se elige primero la coincidencia.
const items = computed(() => [
    ...visibles.value.map((valor) => ({ valor, otro: false })),
    ...(otro.value ? [{ valor: otro.value, otro: true }] : []),
]);

function abrir(): void {
    if (props.disabled) {
        return;
    }

    const caja = entrada.value?.getBoundingClientRect();

    if (caja) {
        const abajo = window.innerHeight - caja.bottom;
        haciaArriba.value = abajo < 272 && caja.top > abajo;
    }

    abierto.value = true;
    activo.value = Math.max(0, visibles.value.indexOf(modelo.value));
    void nextTick(desplazarAlActivo);
}

function elegir(valor: string): void {
    modelo.value = valor;
    texto.value = valor;
    escribiendo.value = false;
    abierto.value = false;
}

function confirmar(): void {
    const valor = texto.value.trim();
    const exacta = opcionExacta(props.opciones, valor);

    if (exacta) {
        elegir(exacta);
    } else if (props.permitirOtro) {
        elegir(valor);
    } else {
        // Lo escrito no es una opción: vuelve a lo que estaba elegido.
        texto.value = modelo.value;
        escribiendo.value = false;
        abierto.value = false;
    }
}

function alEscribir(evento: Event): void {
    texto.value = (evento.target as HTMLInputElement).value;
    escribiendo.value = true;
    abierto.value = true;
    activo.value = 0;
}

function desplazarAlActivo(): void {
    lista.value
        ?.querySelector<HTMLElement>(`[data-indice="${activo.value}"]`)
        ?.scrollIntoView({ block: 'nearest' });
}

function teclas(evento: KeyboardEvent): void {
    if (evento.key === 'ArrowDown' || evento.key === 'ArrowUp') {
        evento.preventDefault();

        if (!abierto.value) {
            abrir();

            return;
        }

        const total = items.value.length;

        if (total > 0) {
            activo.value =
                (activo.value + (evento.key === 'ArrowDown' ? 1 : -1) + total) %
                total;
            void nextTick(desplazarAlActivo);
        }
    } else if (evento.key === 'Enter') {
        if (abierto.value) {
            evento.preventDefault();
            const item = items.value[activo.value];

            if (item) {
                elegir(item.valor);
            } else {
                confirmar();
            }
        }
    } else if (evento.key === 'Escape' && abierto.value) {
        evento.preventDefault();
        texto.value = modelo.value;
        escribiendo.value = false;
        abierto.value = false;
    }
}

function alSalir(): void {
    if (abierto.value || escribiendo.value) {
        confirmar();
    }
}

const restantes = computed(
    () => filtradas.value.length - visibles.value.length,
);

const idActivo = computed(() =>
    abierto.value && items.value[activo.value]
        ? `${idLista}-${activo.value}`
        : undefined,
);
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            ref="entrada"
            :value="texto"
            type="text"
            role="combobox"
            autocomplete="off"
            aria-autocomplete="list"
            :aria-expanded="abierto"
            :aria-controls="idLista"
            :aria-activedescendant="idActivo"
            :aria-invalid="invalida || undefined"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :class="
                cn(
                    'h-10 w-full rounded-md border border-linea-fuerte bg-white pr-9 pl-3 text-sm text-tinta placeholder:text-tinta-suave/70 focus-visible:border-marca focus-visible:ring-2 focus-visible:ring-marca/25 focus-visible:outline-none disabled:bg-lienzo disabled:text-tinta-suave',
                    invalida && 'border-aviso',
                )
            "
            @focus="
                ($event.target as HTMLInputElement).select();
                abrir();
            "
            @click="abrir"
            @input="alEscribir"
            @keydown="teclas"
            @blur="alSalir"
        />
        <ChevronDown
            class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-tinta-suave"
            aria-hidden="true"
        />

        <ul
            v-show="abierto && !disabled"
            :id="idLista"
            ref="lista"
            role="listbox"
            :class="
                cn(
                    'absolute z-30 max-h-64 w-full overflow-y-auto rounded-md border border-linea bg-white py-1 text-sm shadow-lg',
                    haciaArriba ? 'bottom-full mb-1' : 'top-full mt-1',
                )
            "
        >
            <li
                v-for="(item, indice) in items"
                :id="`${idLista}-${indice}`"
                :key="`${item.otro ? 'otro' : 'opcion'}-${item.valor}`"
                :data-indice="indice"
                role="option"
                :aria-selected="item.valor === modelo"
                :class="
                    cn(
                        'cursor-pointer px-3 py-2',
                        indice === activo && 'bg-marca-suave',
                        item.valor === modelo && 'font-medium text-marca',
                    )
                "
                @mousedown.prevent="elegir(item.valor)"
                @mousemove="activo = indice"
            >
                <template v-if="item.otro">
                    Usar «{{ item.valor }}»
                    <span class="block text-xs text-tinta-suave">
                        No está en la lista; se guarda como lo escribiste.
                    </span>
                </template>
                <template v-else>{{ item.valor }}</template>
            </li>
            <li
                v-if="items.length === 0"
                class="px-3 py-2 text-tinta-suave"
                aria-disabled="true"
            >
                {{ sinResultados }}
            </li>
            <li
                v-if="restantes > 0"
                class="border-t border-linea px-3 py-2 text-xs text-tinta-suave"
                aria-disabled="true"
            >
                Y {{ restantes }} más: sigue escribiendo para encontrarla.
            </li>
        </ul>
    </div>
</template>
