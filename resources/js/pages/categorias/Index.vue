<script setup lang="ts">
/**
 * A2.3 · Catálogo de categorías (HU-017, HU-018, HU-019).
 *
 * Lista única de categorías para todos los diagnósticos, con el número de
 * diagnósticos donde se usa cada una. Las archivadas se muestran aparte.
 *
 * [INFORMACIÓN PENDIENTE] El wireframe A2.3 no está en los PDF recibidos;
 * la pantalla sigue las historias y el estilo de A2. Editar abre un modal.
 */
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import ModalCategoria from '@/components/categorias/ModalCategoria.vue';
import ModalRetirarCategoria from '@/components/categorias/ModalRetirarCategoria.vue';
import { rutas } from '@/lib/rutas';
import type { Categoria, DiagnosticoBorrador } from '@/types/diagnosticos';

const props = withDefaults(
    defineProps<{
        categorias: Categoria[];
        /** Diagnósticos en borrador, para agregar una categoría nueva. */
        borradores?: DiagnosticoBorrador[];
    }>(),
    { borradores: () => [] },
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Diagnósticos', href: rutas.diagnosticos.todos() },
            {
                title: 'Catálogo de categorías',
                href: rutas.categorias.catalogo(),
            },
        ],
    },
});

const activas = computed(() => props.categorias.filter((c) => !c.archivada));
const archivadas = computed(() => props.categorias.filter((c) => c.archivada));

const modalCategoria = ref(false);
const modalRetirar = ref(false);
const elegida = ref<Categoria | null>(null);

function crear(): void {
    elegida.value = null;
    modalCategoria.value = true;
}

function editar(categoria: Categoria): void {
    elegida.value = categoria;
    modalCategoria.value = true;
}

function retirar(categoria: Categoria): void {
    elegida.value = categoria;
    modalRetirar.value = true;
}

const usos = (n: number) => `${n} ${n === 1 ? 'diagnóstico' : 'diagnósticos'}`;
</script>

<template>
    <Head title="Catálogo de categorías" />

    <div class="flex flex-col gap-5 p-6">
        <EncabezadoPagina
            titulo="Catálogo de categorías"
            descripcion="Lista única de categorías para que todos los diagnósticos usen los mismos nombres. La descripción es el texto que la empresa lee al iniciar cada categoría."
        >
            <Boton @click="crear">+ Crear categoría</Boton>
        </EncabezadoPagina>

        <section class="rounded-xl border border-linea bg-white">
            <header class="flex items-baseline gap-2 px-5 pt-4 pb-3">
                <h2 class="text-base font-semibold">Categorías activas</h2>
                <span class="text-xs text-tinta-suave">
                    {{ activas.length }} categorías
                </span>
            </header>

            <p
                v-if="activas.length === 0"
                class="border-t border-linea px-5 py-8 text-center text-sm text-tinta-suave"
            >
                Todavía no hay categorías. Crea la primera con «+ Crear
                categoría».
            </p>

            <div v-else class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr
                            class="border-y border-linea bg-[#f9f8f4] text-left"
                        >
                            <th
                                v-for="columna in [
                                    'Categoría',
                                    'Se usa en',
                                    'Acciones',
                                ]"
                                :key="columna"
                                scope="col"
                                class="px-5 py-2.5 text-xs font-normal text-tinta-suave"
                            >
                                {{ columna }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="categoria in activas"
                            :key="categoria.id"
                            class="border-b border-linea last:border-b-0"
                        >
                            <td class="px-5 py-3 align-top">
                                <p class="font-medium">
                                    {{ categoria.nombre }}
                                </p>
                                <p
                                    class="mt-0.5 max-w-xl text-xs text-tinta-suave"
                                >
                                    {{
                                        categoria.descripcion ||
                                        'Sin descripción para la empresa.'
                                    }}
                                </p>
                            </td>
                            <td class="px-5 py-3 align-top whitespace-nowrap">
                                {{ usos(categoria.diagnosticos) }}
                            </td>
                            <td class="px-5 py-3 align-top whitespace-nowrap">
                                <Boton
                                    tamano="sm"
                                    variante="secundario"
                                    class="border-marca text-marca"
                                    @click="editar(categoria)"
                                >
                                    Editar
                                </Boton>
                                <button
                                    type="button"
                                    class="ml-3 text-xs text-tinta underline hover:text-aviso"
                                    @click="retirar(categoria)"
                                >
                                    {{
                                        categoria.tiene_respuestas
                                            ? 'Archivar'
                                            : 'Eliminar'
                                    }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section
            v-if="archivadas.length > 0"
            class="rounded-xl border border-linea bg-white"
        >
            <header class="px-5 pt-4 pb-3">
                <h2 class="text-base font-semibold">Archivadas</h2>
                <p class="text-xs text-tinta-suave">
                    Ya no se pueden elegir en diagnósticos nuevos. Los
                    resultados anteriores las siguen mostrando.
                </p>
            </header>
            <ul class="border-t border-linea">
                <li
                    v-for="categoria in archivadas"
                    :key="categoria.id"
                    class="flex items-center justify-between gap-3 border-b border-linea px-5 py-3 text-sm last:border-b-0"
                >
                    <span class="text-tinta-suave">{{ categoria.nombre }}</span>
                    <Etiqueta>Archivada</Etiqueta>
                </li>
            </ul>
        </section>
    </div>

    <ModalCategoria
        v-model:abierto="modalCategoria"
        :categoria="elegida"
        :borradores="borradores"
    />
    <ModalRetirarCategoria
        v-if="elegida"
        v-model:abierto="modalRetirar"
        :categoria="elegida"
    />
</template>
