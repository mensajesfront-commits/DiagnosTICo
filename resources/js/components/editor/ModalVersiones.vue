<script setup lang="ts">
/**
 * A2.1d · Versiones y publicar (HU-029).
 *
 * - Historial: el borrador, la publicada (con fecha y cuántas empresas la
 *   usan) y las reemplazadas, con "Ver (solo lectura)".
 * - Publicar vN: los 5 requisitos (RN-012), la nota de cambios (obligatoria)
 *   y qué hacer con las mediciones pendientes: dejarlas con su versión o
 *   pasarlas a la nueva si aún no empezaron (CA-004).
 *
 * Envía: POST /diagnosticos/{id}/publicar con nota y pendientes.
 */
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import Modal from '@/components/base/Modal.vue';
import { mesYAnio } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';
import type {
    CabeceraEditor,
    EstadoEditor,
    VersionHistorial,
} from '@/types/editor';

const props = defineProps<{
    diagnostico: CabeceraEditor;
    estado: EstadoEditor;
    versiones: VersionHistorial[];
    pendientes: number;
}>();

const abierto = defineModel<boolean>('abierto', { default: false });

const form = useForm({ nota: '', pendientes: 'dejar' as 'dejar' | 'pasar' });

watch(
    abierto,
    (valor) => {
        if (valor) {
            form.clearErrors();
        }
    },
    { immediate: true },
);

const cumplidos = computed(
    () => props.estado.requisitos.filter((r) => r.ok).length,
);

/** Hay algo que publicar: un borrador nuevo o la v1 sin publicar. */
const hayCambios = computed(
    () =>
        props.diagnostico.hay_borrador ||
        props.diagnostico.ultima_publicada === null,
);

const fecha = (iso: string | null) => {
    if (!iso) {
        return '';
    }

    const d = new Date(iso);

    return `${d.getDate()} ${mesYAnio(iso)}`;
};

function publicar(): void {
    form.post(rutas.diagnosticos.publicar(props.diagnostico.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Modal
        v-model:abierto="abierto"
        ancho="lg"
        :titulo="`Versiones de “${diagnostico.nombre}” · ${diagnostico.sector.nombre}`"
        descripcion="El número de versión lo pone el sistema: v1, v2, v3… Solo se escribe una nota de qué cambió."
    >
        <div class="flex flex-col gap-4 text-sm">
            <section>
                <h3 class="mb-1 text-xs font-semibold">Historial</h3>
                <ul class="divide-y divide-linea">
                    <li
                        v-for="v in versiones"
                        :key="v.numero"
                        class="grid grid-cols-[2.5rem_7rem_1fr_auto] items-center gap-3 py-2"
                    >
                        <span class="font-mono font-semibold"
                            >v{{ v.numero }}</span
                        >
                        <Etiqueta
                            :tono="
                                v.estado === 'publicada'
                                    ? 'exito'
                                    : v.estado === 'borrador'
                                      ? 'alerta'
                                      : 'neutro'
                            "
                        >
                            {{
                                {
                                    borrador: '✎ Borrador',
                                    publicada: '✓ Publicada',
                                    reemplazada: 'Reemplazada',
                                }[v.estado]
                            }}
                        </Etiqueta>
                        <span>
                            <template v-if="v.estado === 'borrador'">
                                Editando ahora<template
                                    v-if="estado.por_corregir"
                                >
                                    · {{ estado.por_corregir }}
                                    {{
                                        estado.por_corregir === 1
                                            ? 'categoría por corregir'
                                            : 'categorías por corregir'
                                    }}</template
                                >
                                <span class="block text-xs text-tinta-suave">
                                    Sin publicar
                                </span>
                            </template>
                            <template v-else>
                                Publicada el {{ fecha(v.publicada_en) }}
                                <template v-if="v.estado === 'publicada'">
                                    · en uso por {{ v.empresas }}
                                    {{
                                        v.empresas === 1
                                            ? 'empresa'
                                            : 'empresas'
                                    }}
                                </template>
                                <template v-else>
                                    · {{ v.mediciones }}
                                    {{
                                        v.mediciones === 1
                                            ? 'medición hecha'
                                            : 'mediciones hechas'
                                    }}
                                </template>
                                <span
                                    v-if="v.nota"
                                    class="block text-xs text-tinta-suave"
                                >
                                    {{ v.nota }}
                                </span>
                            </template>
                        </span>
                        <Boton
                            variante="secundario"
                            tamano="sm"
                            :href="
                                v.estado === 'borrador'
                                    ? rutas.diagnosticos.vistaPrevia(
                                          diagnostico.id,
                                      )
                                    : rutas.diagnosticos.vistaPrevia(
                                          diagnostico.id,
                                          v.numero,
                                      )
                            "
                        >
                            {{
                                v.estado === 'borrador'
                                    ? 'Abrir vista previa'
                                    : 'Ver (solo lectura)'
                            }}
                        </Boton>
                    </li>
                    <li
                        v-if="versiones.length === 0"
                        class="py-2 text-tinta-suave"
                    >
                        Todavía no hay versiones publicadas.
                    </li>
                </ul>
            </section>

            <section
                v-if="hayCambios"
                class="grid gap-4 rounded-lg border border-linea bg-lienzo px-4 py-3 sm:grid-cols-2"
            >
                <div>
                    <h3 class="font-semibold">
                        Publicar v{{ diagnostico.version }}
                    </h3>
                    <p class="mt-2 text-xs font-medium">
                        Requisitos · {{ cumplidos }} de
                        {{ estado.requisitos.length }} cumplidos
                    </p>
                    <ul class="mt-1 flex flex-col gap-1 text-xs">
                        <li
                            v-for="r in estado.requisitos"
                            :key="r.clave"
                            :class="r.ok ? '' : 'text-alerta'"
                        >
                            {{ r.ok ? '✓' : '⚠' }} {{ r.texto
                            }}<template v-if="r.detalle">
                                · {{ r.detalle }}</template
                            >
                        </li>
                    </ul>
                    <Link
                        v-if="!estado.publicable"
                        :href="rutas.diagnosticos.vistaPrevia(diagnostico.id)"
                        class="mt-2 inline-block text-xs text-marca underline"
                    >
                        Ver qué corregir en la vista previa
                    </Link>
                </div>
                <div class="flex flex-col gap-3">
                    <Campo
                        obligatorio
                        etiqueta="Nota de cambios"
                        para="version-nota"
                        :ayuda="`Las empresas ven “${diagnostico.nombre} · v${diagnostico.version}”. Los resultados anteriores guardan su versión.`"
                        :error="form.errors.nota"
                    >
                        <Entrada
                            id="version-nota"
                            v-model="form.nota"
                            maxlength="500"
                            placeholder="Ej. Se agregó la categoría Medición de KPIs"
                            :invalida="!!form.errors.nota"
                        />
                    </Campo>
                    <fieldset v-if="pendientes > 0" class="text-xs">
                        <legend class="mb-1 font-medium">
                            Mediciones pendientes con versiones anteriores ({{
                                pendientes
                            }}
                            {{ pendientes === 1 ? 'empresa' : 'empresas' }})
                        </legend>
                        <label class="flex items-center gap-2">
                            <input
                                v-model="form.pendientes"
                                type="radio"
                                value="dejar"
                                class="size-4 accent-marca"
                            />
                            Dejarlas con su versión hasta que las respondan
                        </label>
                        <label class="mt-1 flex items-center gap-2">
                            <input
                                v-model="form.pendientes"
                                type="radio"
                                value="pasar"
                                class="size-4 accent-marca"
                            />
                            Pasarlas a la v{{ diagnostico.version }} si aún no
                            han empezado
                        </label>
                    </fieldset>
                </div>
            </section>
        </div>

        <template #nota>
            <template v-if="hayCambios && !estado.publicable">
                Corrige {{ estado.por_corregir || 'lo que falta' }}
                {{
                    estado.por_corregir === 1
                        ? 'categoría'
                        : estado.por_corregir
                          ? 'categorías'
                          : ''
                }}
                para publicar
            </template>
        </template>
        <template #pie>
            <Boton variante="secundario" @click="abierto = false">
                Cerrar
            </Boton>
            <Boton
                v-if="hayCambios"
                :disabled="!estado.publicable || !form.nota.trim()"
                :cargando="form.processing"
                @click="publicar"
            >
                Publicar v{{ diagnostico.version }}
            </Boton>
        </template>
    </Modal>
</template>
