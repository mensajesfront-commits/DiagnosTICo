<script setup lang="ts">
/**
 * A2.5 · Crear diagnóstico (HU-020).
 *
 * Nace como borrador v1 (RN-010). En blanco se eligen las categorías del
 * catálogo y la importancia empieza repartida en partes iguales; copiando, se
 * trae todo un diagnóstico publicado de cualquier sector.
 *
 * Envía: nombre (máx. 60), sector_id, descripcion (opcional),
 * punto_partida ("blanco" | "copia"), categorias[] (en blanco) y
 * copiar_de (en copia). Al crearlo, el backend abre el editor (A2.1).
 */
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AreaTexto from '@/components/base/AreaTexto.vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import EncabezadoPagina from '@/components/base/EncabezadoPagina.vue';
import Entrada from '@/components/base/Entrada.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import Seleccion from '@/components/base/Seleccion.vue';
import { rutas } from '@/lib/rutas';
import type { DiagnosticoPublicado, Sector } from '@/types/diagnosticos';

const MAXIMO_NOMBRE = 60;

const props = withDefaults(
    defineProps<{
        /** Sectores activos. */
        sectores: Sector[];
        /** Sector preseleccionado (al venir desde un sector en A2). */
        sectorId?: number | null;
        /** Categorías activas del catálogo. */
        categorias: { id: number; nombre: string }[];
        /** Última versión publicada de cada diagnóstico, de todos los sectores. */
        publicados: DiagnosticoPublicado[];
    }>(),
    { sectorId: null },
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Diagnósticos', href: rutas.diagnosticos.todos() },
            { title: 'Crear diagnóstico', href: rutas.diagnosticos.crear() },
        ],
    },
});

const form = useForm<{
    nombre: string;
    sector_id: number | '';
    descripcion: string;
    punto_partida: 'blanco' | 'copia';
    categorias: number[];
    copiar_de: number | '';
}>({
    nombre: '',
    sector_id: props.sectorId ?? '',
    descripcion: '',
    punto_partida: 'blanco',
    categorias: [],
    copiar_de: '',
});

const sectorElegido = computed(() =>
    props.sectores.find((s) => s.id === form.sector_id),
);

const origen = computed(() =>
    props.publicados.find((d) => d.id === form.copiar_de),
);

const publicadosPorSector = computed(() => {
    const grupos = new Map<string, DiagnosticoPublicado[]>();

    for (const diagnostico of props.publicados) {
        const lista = grupos.get(diagnostico.sector_nombre) ?? [];
        lista.push(diagnostico);
        grupos.set(diagnostico.sector_nombre, lista);
    }

    return [...grupos.entries()];
});

const importanciaInicial = computed(() =>
    form.categorias.length > 0
        ? Math.round((100 / form.categorias.length) * 10) / 10
        : 0,
);

const listo = computed(
    () =>
        form.nombre.trim() !== '' &&
        form.sector_id !== '' &&
        (form.punto_partida === 'blanco'
            ? form.categorias.length > 0
            : form.copiar_de !== ''),
);

function crear(): void {
    form.transform((datos) => ({
        ...datos,
        categorias: datos.punto_partida === 'blanco' ? datos.categorias : [],
        copiar_de: datos.punto_partida === 'copia' ? datos.copiar_de : null,
    })).post(rutas.diagnosticos.guardar());
}
</script>

<template>
    <Head title="Crear diagnóstico" />

    <div class="flex flex-col gap-5 p-6">
        <EncabezadoPagina titulo="Crear diagnóstico" />

        <div class="grid items-start gap-5 xl:grid-cols-[1fr_300px]">
            <form
                class="flex flex-col gap-5 rounded-xl border border-linea bg-white p-5"
                @submit.prevent="crear"
            >
                <header
                    class="flex flex-wrap items-baseline justify-between gap-2"
                >
                    <h2 class="text-base font-semibold">
                        Datos del diagnóstico
                    </h2>
                    <p class="text-xs text-tinta-suave">
                        Todos los campos son obligatorios salvo los marcados
                        “(opcional)”.
                    </p>
                </header>

                <div class="grid gap-4 sm:grid-cols-2">
                    <Campo
                        obligatorio
                        etiqueta="Nombre del diagnóstico"
                        para="nombre"
                        ayuda="Máximo 60 caracteres. Puedes cambiarlo después."
                        :contador="`${form.nombre.length}/${MAXIMO_NOMBRE}`"
                        :error="form.errors.nombre"
                    >
                        <Entrada
                            id="nombre"
                            v-model="form.nombre"
                            required
                            :maxlength="MAXIMO_NOMBRE"
                            :invalida="!!form.errors.nombre"
                        />
                    </Campo>

                    <Campo
                        obligatorio
                        etiqueta="Sector"
                        para="sector"
                        :error="form.errors.sector_id"
                        :ayuda="
                            sectorElegido
                                ? `Las empresas de ${sectorElegido.nombre} lo recibirán cuando lo publiques.`
                                : 'Las empresas del sector lo recibirán cuando lo publiques.'
                        "
                    >
                        <Seleccion
                            id="sector"
                            v-model="form.sector_id"
                            required
                            :invalida="!!form.errors.sector_id"
                        >
                            <option value="" disabled>
                                Selecciona el sector
                            </option>
                            <option
                                v-for="sector in sectores"
                                :key="sector.id"
                                :value="sector.id"
                            >
                                {{ sector.nombre }}
                            </option>
                        </Seleccion>
                    </Campo>
                </div>

                <Campo
                    etiqueta="Descripción"
                    para="descripcion"
                    opcional
                    ayuda="Solo la ve el administrador."
                    :error="form.errors.descripcion"
                >
                    <AreaTexto
                        id="descripcion"
                        v-model="form.descripcion"
                        rows="2"
                    />
                </Campo>

                <fieldset class="flex flex-col gap-3">
                    <legend class="mb-2 text-xs font-medium">
                        Punto de partida
                    </legend>

                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="form.punto_partida"
                            type="radio"
                            value="blanco"
                            class="size-4 accent-marca"
                        />
                        En blanco: elijo las categorías y escribo las preguntas
                    </label>

                    <div
                        v-if="form.punto_partida === 'blanco'"
                        class="ml-6 flex flex-col gap-2"
                    >
                        <p class="text-xs font-medium">
                            Categorías del catálogo
                        </p>
                        <div class="grid gap-1.5 sm:grid-cols-2">
                            <label
                                v-for="categoria in categorias"
                                :key="categoria.id"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    v-model="form.categorias"
                                    type="checkbox"
                                    :value="categoria.id"
                                    class="size-4 accent-marca"
                                />
                                {{ categoria.nombre }}
                            </label>
                        </div>
                        <p class="text-xs text-tinta-suave">
                            <template v-if="form.categorias.length > 0">
                                {{ form.categorias.length }} categorías · cada
                                una empieza valiendo {{ importanciaInicial }}%
                                (suma 100%). La ajustas en el editor.
                            </template>
                            <template v-else>
                                Elige al menos una categoría.
                            </template>
                        </p>
                        <p
                            v-if="form.errors.categorias"
                            class="text-xs text-aviso"
                        >
                            {{ form.errors.categorias }}
                        </p>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="form.punto_partida"
                            type="radio"
                            value="copia"
                            class="size-4 accent-marca"
                        />
                        Copiar de un diagnóstico publicado de cualquier sector
                    </label>

                    <div
                        v-if="form.punto_partida === 'copia'"
                        class="ml-6 flex flex-col gap-3"
                    >
                        <Campo
                            obligatorio
                            etiqueta="Copiar de"
                            para="copiar-de"
                            ayuda="Agrupados por sector. Solo aparecen diagnósticos publicados."
                            :error="form.errors.copiar_de"
                        >
                            <Seleccion
                                id="copiar-de"
                                v-model="form.copiar_de"
                                required
                            >
                                <option value="" disabled>
                                    Selecciona un diagnóstico
                                </option>
                                <optgroup
                                    v-for="[
                                        sector,
                                        lista,
                                    ] in publicadosPorSector"
                                    :key="sector"
                                    :label="sector"
                                >
                                    <option
                                        v-for="diagnostico in lista"
                                        :key="diagnostico.id"
                                        :value="diagnostico.id"
                                    >
                                        {{ diagnostico.nombre }} · v{{
                                            diagnostico.version
                                        }}
                                    </option>
                                </optgroup>
                            </Seleccion>
                        </Campo>

                        <div
                            v-if="origen"
                            class="rounded-md bg-lienzo px-4 py-3 text-sm"
                        >
                            <p class="font-semibold">
                                Se copia todo de {{ origen.sector_nombre }} ·
                                v{{ origen.version }}:
                                <span class="font-mono font-normal">
                                    {{ origen.categorias.length }} categorías ·
                                    {{ origen.preguntas }} preguntas
                                </span>
                            </p>
                            <p class="mt-1 text-xs text-tinta-suave">
                                Con sus opciones, puntajes, indicaciones e
                                importancia. Luego puedes editarlo: agregar o
                                quitar preguntas y ajustar la importancia.
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <Etiqueta
                                    v-for="categoria in origen.categorias"
                                    :key="categoria"
                                    class="border border-linea bg-white font-normal"
                                >
                                    {{ categoria }}
                                </Etiqueta>
                            </div>
                            <p
                                v-if="
                                    sectorElegido &&
                                    sectorElegido.id !== origen.sector_id
                                "
                                class="mt-2 text-xs text-alerta"
                            >
                                ⚠ Las preguntas están escritas para
                                {{ origen.sector_nombre }}: revisa el lenguaje
                                para {{ sectorElegido.nombre }} antes de
                                publicar.
                            </p>
                        </div>
                    </div>
                </fieldset>

                <div class="flex justify-end gap-3 border-t border-linea pt-4">
                    <Boton
                        variante="secundario"
                        :href="
                            sectorElegido
                                ? rutas.diagnosticos.sector(sectorElegido.id)
                                : rutas.diagnosticos.todos()
                        "
                    >
                        Cancelar
                    </Boton>
                    <Boton
                        type="submit"
                        :disabled="!listo"
                        :cargando="form.processing"
                    >
                        Crear y abrir el editor
                    </Boton>
                </div>
            </form>

            <aside class="rounded-xl border border-linea bg-white p-5 text-sm">
                <h2 class="font-semibold">Qué pasa al crearlo</h2>
                <ol class="mt-3 flex flex-col gap-2.5">
                    <li>
                        <strong>1.</strong> Se crea como
                        <strong>borrador</strong>: ninguna empresa lo ve.
                    </li>
                    <li>
                        <strong>2.</strong> Si copias, se copian también la
                        importancia y las preguntas. En blanco, la importancia
                        empieza repartida en partes iguales (suma 100%).
                    </li>
                    <li>
                        <strong>3.</strong> Se abre el editor para escribir
                        preguntas, puntajes e indicaciones.
                    </li>
                    <li>
                        <strong>4.</strong> Cuando cumple los 5 requisitos, se
                        publica y ya se puede asignar en una medición.
                    </li>
                </ol>
                <p
                    class="mt-4 border-t border-linea pt-3 text-xs text-tinta-suave"
                >
                    ¿Quieres copiar uno concreto con otro nombre? También puedes
                    usar <strong>Duplicar</strong> en su fila de la lista de
                    diagnósticos.
                </p>
            </aside>
        </div>
    </div>
</template>
