<script setup lang="ts">
/**
 * A2.1d · Agregar categoría a este diagnóstico (HU-026).
 *
 * - Busca en el catálogo; las que ya están se ven marcadas y bloqueadas, y
 *   las archivadas no aparecen para elegir.
 * - Reparto de importancia: mantener los pesos actuales (las nuevas en 0%,
 *   luego se ajustan) o repartir en partes iguales.
 * - "¿No está? Crear en el catálogo" lleva a A2.3.
 *
 * Envía: POST /diagnosticos/{id}/categorias con categorias[] y reparto.
 */
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Entrada from '@/components/base/Entrada.vue';
import Modal from '@/components/base/Modal.vue';
import { rutas } from '@/lib/rutas';
import { normalizar } from '@/lib/texto';
import type { CategoriaCatalogo, ParteEditor } from '@/types/editor';

const props = defineProps<{
    diagnosticoId: number;
    catalogo: CategoriaCatalogo[];
    partes: ParteEditor[];
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const busqueda = ref('');
const form = useForm({
    categorias: [] as number[],
    reparto: 'mantener' as 'mantener' | 'iguales',
});

watch(
    abierto,
    (valor) => {
        if (valor) {
            busqueda.value = '';
            form.reset();
            form.clearErrors();
        }
    },
    { immediate: true },
);

const coincide = (c: CategoriaCatalogo) =>
    normalizar(c.nombre).includes(normalizar(busqueda.value));

const disponibles = computed(() =>
    props.catalogo.filter(
        (c) => !c.en_diagnostico && !c.archivada && coincide(c),
    ),
);
const yaEstan = computed(() =>
    props.catalogo.filter((c) => c.en_diagnostico && coincide(c)),
);

const total = computed(() => props.partes.length + form.categorias.length);
const igual = computed(() =>
    total.value ? Math.floor(10000 / total.value) / 100 : 0,
);

const nombresNuevas = computed(() =>
    props.catalogo
        .filter((c) => form.categorias.includes(c.id))
        .map((c) => c.nombre)
        .join(', '),
);

function agregar(): void {
    form.post(rutas.diagnosticos.agregarCategorias(props.diagnosticoId), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (abierto.value = false),
    });
}

const errores = computed(
    () => form.errors as Record<string, string | undefined>,
);
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        ancho="lg"
        titulo="Agregar categoría a este diagnóstico"
        descripcion="Elige categorías del catálogo. Si falta una, primero se crea en el catálogo de categorías."
    >
        <div class="flex flex-col gap-4 text-sm">
            <Entrada
                v-model="busqueda"
                type="search"
                placeholder="Buscar en el catálogo…"
                aria-label="Buscar en el catálogo"
            />

            <section>
                <h3 class="mb-2 text-xs font-medium">
                    Disponibles para agregar ({{ disponibles.length }})
                </h3>
                <p
                    v-if="disponibles.length === 0"
                    class="rounded-md bg-lienzo px-3 py-2 text-xs text-tinta-suave"
                >
                    No hay más categorías en el catálogo{{
                        busqueda ? ' con ese nombre' : ''
                    }}.
                </p>
                <div v-else class="flex flex-col gap-2">
                    <label
                        v-for="c in disponibles"
                        :key="c.id"
                        :class="[
                            'flex cursor-pointer items-center justify-between gap-3 rounded-lg border px-3 py-2',
                            form.categorias.includes(c.id)
                                ? 'border-marca bg-marca-suave/30'
                                : 'border-linea-fuerte hover:bg-lienzo',
                        ]"
                    >
                        <span class="flex items-center gap-2 font-medium">
                            <input
                                v-model="form.categorias"
                                type="checkbox"
                                :value="c.id"
                                class="size-4 accent-marca"
                            />
                            {{ c.nombre }}
                        </span>
                        <span class="text-xs text-tinta-suave">
                            Del catálogo · empieza sin preguntas
                        </span>
                    </label>
                </div>
            </section>

            <section v-if="yaEstan.length">
                <h3 class="mb-2 text-xs font-medium">
                    Ya en este diagnóstico ({{ yaEstan.length }})
                </h3>
                <div class="grid gap-x-4 gap-y-2 sm:grid-cols-2">
                    <span
                        v-for="c in yaEstan"
                        :key="c.id"
                        class="flex items-start gap-2 text-tinta-suave"
                    >
                        <input
                            type="checkbox"
                            checked
                            disabled
                            class="mt-0.5 size-4"
                            :aria-label="`${c.nombre}: ya en este diagnóstico`"
                        />
                        <span>
                            {{ c.nombre }}
                            <span class="block text-xs">
                                Ya en este diagnóstico
                            </span>
                        </span>
                    </span>
                </div>
            </section>

            <fieldset
                v-if="form.categorias.length"
                class="rounded-lg border border-linea px-4 py-3"
            >
                <legend class="px-1 text-xs">Reparto de importancia</legend>
                <label class="flex items-start gap-2">
                    <input
                        v-model="form.reparto"
                        type="radio"
                        value="mantener"
                        class="mt-1 size-4 accent-marca"
                    />
                    <span>
                        <span class="font-medium">
                            Mantener los pesos actuales y dar 0% a las nuevas
                            (luego las ajustas)
                        </span>
                        <span class="block text-xs text-tinta-suave">
                            Las {{ partes.length }} actuales siguen igual ·
                            {{ nombresNuevas }}: 0%
                        </span>
                    </span>
                </label>
                <label class="mt-2 flex items-start gap-2">
                    <input
                        v-model="form.reparto"
                        type="radio"
                        value="iguales"
                        class="mt-1 size-4 accent-marca"
                    />
                    <span>
                        <span class="font-medium"
                            >Repartir en partes iguales</span
                        >
                        <span class="block text-xs text-tinta-suave">
                            {{ total }} categorías con {{ igual }}% cada una ·
                            se pierden los ajustes actuales
                        </span>
                    </span>
                </label>
            </fieldset>

            <p v-if="form.categorias.length" class="text-xs text-tinta-suave">
                El diagnóstico queda <strong>incompleto</strong> hasta que cada
                categoría nueva tenga al menos 1 pregunta.
            </p>
            <p
                v-if="errores.categorias || errores['categorias.0']"
                class="text-xs text-aviso"
                role="alert"
            >
                {{ errores.categorias ?? errores['categorias.0'] }}
            </p>
        </div>

        <template #pie>
            <Link
                :href="rutas.categorias.catalogo()"
                class="mr-auto text-sm text-marca underline underline-offset-2"
            >
                ¿No está? Crear en el catálogo
            </Link>
            <Boton variante="secundario" @click="abierto = false">
                Cancelar
            </Boton>
            <Boton
                :disabled="form.categorias.length === 0"
                :cargando="form.processing"
                @click="agregar"
            >
                Agregar
                {{
                    form.categorias.length === 1
                        ? '1 categoría'
                        : `${form.categorias.length || ''} categorías`
                }}
            </Boton>
        </template>
    </Modal>
</template>
