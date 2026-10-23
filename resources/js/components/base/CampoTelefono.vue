<script setup lang="ts">
/**
 * Teléfono con el indicativo del país al lado. El indicativo limita cuántos
 * dígitos se escriben (Colombia 10, Argentina 11…) y solo se aceptan
 * números. El valor queda como "+57 3001234567" (vacío si no hay número).
 *
 * `pais` (nombre, como lo guarda la ubicación) elige el indicativo inicial
 * cuando el campo está vacío.
 */
import { computed, ref, watch } from 'vue';
import {
    INDICATIVOS,
    indicativoDe,
    paisPorNombre,
    separar,
    unir,
} from '@/lib/telefonos';
import { cn } from '@/lib/utils';

const props = defineProps<{
    id: string;
    invalida?: boolean;
    pais?: string | null;
}>();

const modelo = defineModel<string>({ default: '' });

const inicial = separar(modelo.value, paisPorNombre(props.pais) ?? undefined);
const pais = ref(inicial.pais);
const numero = ref(inicial.numero);
const indicativo = computed(() => indicativoDe(pais.value));

// Si cambian el valor desde fuera (cargar otra cuenta, limpiar el formulario).
watch(modelo, (valor) => {
    if (valor !== unir(pais.value, numero.value)) {
        const nuevo = separar(valor, pais.value);
        pais.value = nuevo.pais;
        numero.value = nuevo.numero;
    }
});

// Al elegir el país en la ubicación, el indicativo lo sigue mientras no haya número.
watch(
    () => props.pais,
    (nombre) => {
        const codigo = paisPorNombre(nombre);

        if (codigo && numero.value === '') {
            pais.value = codigo;
        }
    },
);

function escribir(evento: Event): void {
    const campo = evento.target as HTMLInputElement;
    numero.value = campo.value
        .replace(/\D/g, '')
        .slice(0, indicativo.value.digitos);
    // El campo muestra solo los dígitos aceptados.
    campo.value = numero.value;
    modelo.value = unir(pais.value, numero.value);
}

function cambiarPais(): void {
    numero.value = numero.value.slice(0, indicativo.value.digitos);
    modelo.value = unir(pais.value, numero.value);
}

const ejemplo = computed(() => '3'.padEnd(indicativo.value.digitos, '0'));
</script>

<template>
    <div
        :class="
            cn(
                'flex h-10 w-full overflow-hidden rounded-md border border-linea-fuerte bg-white focus-within:border-marca focus-within:ring-2 focus-within:ring-marca/25',
                invalida && 'border-aviso',
            )
        "
    >
        <select
            v-model="pais"
            :aria-label="`Indicativo del país (${indicativo.nombre})`"
            class="shrink-0 border-r border-linea bg-lienzo px-2 text-sm text-tinta focus-visible:outline-none"
            @change="cambiarPais"
        >
            <option v-for="i in INDICATIVOS" :key="i.pais" :value="i.pais">
                {{ i.pais }} {{ i.codigo }}
            </option>
        </select>
        <input
            :id="id"
            :value="numero"
            type="tel"
            inputmode="numeric"
            autocomplete="tel-national"
            :maxlength="indicativo.digitos"
            :placeholder="ejemplo"
            :aria-invalid="invalida || undefined"
            :aria-describedby="`${id}-ayuda`"
            class="min-w-0 flex-1 bg-transparent px-3 text-sm text-tinta placeholder:text-tinta-suave/70 focus-visible:outline-none"
            @input="escribir"
        />
    </div>
    <p :id="`${id}-ayuda`" class="mt-1 text-xs text-tinta-suave">
        {{ indicativo.nombre }}: {{ indicativo.digitos }} dígitos sin el
        indicativo ·
        <span class="font-mono"
            >{{ numero.length }}/{{ indicativo.digitos }}</span
        >
    </p>
</template>
