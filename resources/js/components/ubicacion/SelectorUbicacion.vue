<script setup lang="ts">
/**
 * País → departamento (estado, provincia o región) → ciudad, en ese orden
 * (L2, A6, E11; DEC-016).
 *
 * - País: los 18 de Hispanoamérica, con búsqueda.
 * - Departamento: se habilita al elegir el país; la etiqueta cambia según el
 *   país ("Estado" en México, "Provincia" en Argentina…).
 * - Ciudad: se habilita al elegir el departamento. Si no aparece, se puede
 *   escribir (las listas públicas no traen todos los municipios).
 *
 * Los departamentos y ciudades se piden a GET /ubicaciones/{pais} y se
 * guardan en memoria mientras la página esté abierta.
 */
import { computed, ref, watch } from 'vue';
import Campo from '@/components/base/Campo.vue';
import Combobox from '@/components/base/Combobox.vue';
import { pais as rutaPais } from '@/routes/ubicaciones';
import type { Departamento, Pais } from '@/types/ubicaciones';

const props = withDefaults(
    defineProps<{
        paises: Pais[];
        /** Prefijo de los id ("registro", "perfil", "empresa"). */
        prefijo: string;
        errores?: { pais?: string; departamento?: string; ciudad?: string };
        requerido?: boolean;
        /** Solo lectura (el colaborador ve los datos de la empresa en E11). */
        bloqueado?: boolean;
    }>(),
    { errores: () => ({}), requerido: true, bloqueado: false },
);

const pais = defineModel<string>('pais', { default: '' });
const departamento = defineModel<string>('departamento', { default: '' });
const ciudad = defineModel<string>('ciudad', { default: '' });

const cache = new Map<string, Promise<Departamento[]>>();
const departamentos = ref<Departamento[]>([]);
const cargando = ref(false);
const fallo = ref(false);

const paisElegido = computed(() =>
    props.paises.find((p) => p.nombre === pais.value),
);

const division = computed(() => paisElegido.value?.division ?? 'Departamento');

const nombresDepartamentos = computed(() =>
    departamentos.value.map((d) => d.nombre),
);

const ciudades = computed(
    () =>
        departamentos.value.find((d) => d.nombre === departamento.value)
            ?.ciudades ?? [],
);

function pedir(codigo: string): Promise<Departamento[]> {
    if (!cache.has(codigo)) {
        cache.set(
            codigo,
            fetch(rutaPais(codigo.toLowerCase()).url, {
                headers: { Accept: 'application/json' },
            }).then((r) => {
                if (!r.ok) {
                    throw new Error(String(r.status));
                }

                return r.json() as Promise<Departamento[]>;
            }),
        );
    }

    return cache.get(codigo)!;
}

async function cargar(): Promise<void> {
    const codigo = paisElegido.value?.codigo;
    departamentos.value = [];
    fallo.value = false;

    if (!codigo) {
        return;
    }

    cargando.value = true;

    try {
        departamentos.value = await pedir(codigo);
    } catch {
        cache.delete(codigo);
        fallo.value = true;
    } finally {
        cargando.value = false;
    }
}

// Solo cuando la persona cambia el país o el departamento se borra lo que
// depende de él; si cambia por "Cancelar" o al cargar datos, no.
function elegirPais(valor: string): void {
    if (valor !== pais.value) {
        pais.value = valor;
        departamento.value = '';
        ciudad.value = '';
    }
}

function elegirDepartamento(valor: string): void {
    if (valor !== departamento.value) {
        departamento.value = valor;
        ciudad.value = '';
    }
}

watch(pais, () => void cargar());

void cargar();

const nombresPaises = computed(() => props.paises.map((p) => p.nombre));

const ayudaDepartamento = computed(() => {
    if (!paisElegido.value) {
        return 'Primero elige el país.';
    }

    if (fallo.value) {
        return 'No pudimos cargar la lista. Revisa tu conexión y vuelve a elegir el país.';
    }

    return cargando.value ? 'Cargando…' : undefined;
});

const ayudaCiudad = computed(() => {
    if (departamento.value !== '') {
        return 'Escribe para buscarla. Si no aparece, escríbela completa y elige «Usar…».';
    }

    const articulo = ['Provincia', 'Región'].includes(division.value)
        ? 'la'
        : 'el';

    return `Primero elige ${articulo} ${division.value.toLowerCase()}.`;
});
</script>

<template>
    <div class="grid gap-4 sm:col-span-2 sm:grid-cols-3">
        <Campo
            :obligatorio="requerido"
            etiqueta="País"
            :para="`${prefijo}-pais`"
            :opcional="!requerido"
            :error="errores.pais"
        >
            <Combobox
                :id="`${prefijo}-pais`"
                :model-value="pais"
                @update:model-value="elegirPais"
                :opciones="nombresPaises"
                :disabled="bloqueado"
                :required="requerido"
                :invalida="!!errores.pais"
                placeholder="Escribe o elige"
            />
        </Campo>

        <Campo
            :obligatorio="requerido"
            :etiqueta="division"
            :para="`${prefijo}-departamento`"
            :opcional="!requerido"
            :ayuda="ayudaDepartamento"
            :error="errores.departamento"
        >
            <Combobox
                :id="`${prefijo}-departamento`"
                :model-value="departamento"
                @update:model-value="elegirDepartamento"
                :opciones="nombresDepartamentos"
                :disabled="bloqueado || !paisElegido || cargando || fallo"
                :required="requerido"
                :invalida="!!errores.departamento"
                placeholder="Escribe o elige"
            />
        </Campo>

        <Campo
            :obligatorio="requerido"
            etiqueta="Ciudad"
            :para="`${prefijo}-ciudad`"
            :opcional="!requerido"
            :ayuda="ayudaCiudad"
            :error="errores.ciudad"
        >
            <Combobox
                :id="`${prefijo}-ciudad`"
                v-model="ciudad"
                :opciones="ciudades"
                :disabled="bloqueado || departamento === ''"
                :required="requerido"
                :invalida="!!errores.ciudad"
                permitir-otro
                placeholder="Escribe o elige"
            />
        </Campo>
    </div>
</template>
