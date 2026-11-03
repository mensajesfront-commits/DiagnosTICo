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
import {
    CircleCheck,
    Eye,
    History,
    PenLine,
    Rocket,
    TriangleAlert,
} from '@lucide/vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Campo from '@/components/base/Campo.vue';
import Entrada from '@/components/base/Entrada.vue';
import Etiqueta from '@/components/base/Etiqueta.vue';
import { cn } from '@/lib/utils';
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
        :icono="History"
    >
        <div class="flex flex-col gap-4 text-sm">
            <section aria-labelledby="titulo-historial">
                <h3
                    id="titulo-historial"
                    class="mb-3 text-xs font-semibold tracking-wide text-tinta-suave uppercase"
                >
                    Historial
                </h3>
                <ol class="relative flex flex-col gap-3">
                    <li
                        v-for="(v, i) in versiones"
                        :key="v.numero"
                        class="relative flex gap-3"
                    >
                        <!-- Línea que une las versiones -->
                        <span
                            v-if="i < versiones.length - 1"
                            class="absolute top-10 bottom-[-0.75rem] left-[1.1875rem] w-px bg-linea-fuerte"
                            aria-hidden="true"
                        />
                        <span
                            :class="
                                cn(
                                    'relative z-10 flex size-10 shrink-0 items-center justify-center rounded-full font-mono text-xs font-semibold ring-4 ring-white',
                                    v.estado === 'publicada'
                                        ? 'bg-exito text-white'
                                        : v.estado === 'borrador'
                                          ? 'bg-alerta-suave text-alerta'
                                          : 'bg-lienzo-oscuro text-tinta-suave',
                                )
                            "
                            aria-hidden="true"
                        >
                            v{{ v.numero }}
                        </span>
                        <div
                            :class="
                                cn(
                                    'flex min-w-0 flex-1 flex-col gap-2 rounded-xl border px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between',
                                    v.estado === 'publicada'
                                        ? 'border-exito/30 bg-exito-suave/40'
                                        : v.estado === 'borrador'
                                          ? 'border-alerta/30 bg-alerta-suave/30'
                                          : 'border-linea',
                                )
                            "
                        >
                            <div class="min-w-0">
                                <p
                                    class="flex flex-wrap items-center gap-2 font-medium"
                                >
                                    <span class="sr-only"
                                        >Versión {{ v.numero }}:</span
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
                                                borrador: 'Borrador',
                                                publicada: 'Publicada',
                                                reemplazada: 'Reemplazada',
                                            }[v.estado]
                                        }}
                                    </Etiqueta>
                                    <template v-if="v.estado === 'borrador'">
                                        Editando ahora
                                    </template>
                                    <template v-else>
                                        {{ fecha(v.publicada_en) }}
                                    </template>
                                </p>
                                <p class="mt-1 text-xs text-tinta-suave">
                                    <template v-if="v.estado === 'borrador'">
                                        Sin publicar<template
                                            v-if="estado.por_corregir"
                                        >
                                            · {{ estado.por_corregir }}
                                            {{
                                                estado.por_corregir === 1
                                                    ? 'categoría por corregir'
                                                    : 'categorías por corregir'
                                            }}</template
                                        >
                                    </template>
                                    <template
                                        v-else-if="v.estado === 'publicada'"
                                    >
                                        En uso por {{ v.empresas }}
                                        {{
                                            v.empresas === 1
                                                ? 'empresa'
                                                : 'empresas'
                                        }}
                                    </template>
                                    <template v-else>
                                        {{ v.mediciones }}
                                        {{
                                            v.mediciones === 1
                                                ? 'medición hecha'
                                                : 'mediciones hechas'
                                        }}
                                    </template>
                                </p>
                                <p
                                    v-if="v.nota && v.estado !== 'borrador'"
                                    class="mt-1.5 flex items-start gap-1.5 text-xs text-tinta"
                                >
                                    <PenLine
                                        class="mt-px size-3.5 shrink-0 text-tinta-suave"
                                        aria-hidden="true"
                                    />
                                    {{ v.nota }}
                                </p>
                            </div>
                            <Boton
                                variante="secundario"
                                tamano="sm"
                                class="shrink-0 self-start sm:self-center"
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
                                <Eye class="size-3.5" aria-hidden="true" />
                                {{
                                    v.estado === 'borrador'
                                        ? 'Abrir vista previa'
                                        : 'Ver (solo lectura)'
                                }}
                            </Boton>
                        </div>
                    </li>
                    <li
                        v-if="versiones.length === 0"
                        class="rounded-xl border border-dashed border-linea-fuerte px-4 py-3 text-tinta-suave"
                    >
                        Todavía no hay versiones publicadas.
                    </li>
                </ol>
            </section>

            <section
                v-if="hayCambios"
                class="grid gap-4 rounded-xl border border-linea bg-lienzo/60 p-4 sm:grid-cols-2"
            >
                <div>
                    <h3 class="flex items-center gap-2 font-semibold">
                        <span
                            class="flex size-8 items-center justify-center rounded-lg bg-marca-suave text-marca"
                            aria-hidden="true"
                        >
                            <Rocket class="size-4" />
                        </span>
                        Publicar v{{ diagnostico.version }}
                    </h3>
                    <p class="mt-2 text-xs font-medium">
                        Requisitos · {{ cumplidos }} de
                        {{ estado.requisitos.length }} cumplidos
                    </p>
                    <ul class="mt-1.5 flex flex-col gap-1.5 text-xs">
                        <li
                            v-for="r in estado.requisitos"
                            :key="r.clave"
                            :class="
                                cn(
                                    'flex items-start gap-1.5',
                                    !r.ok && 'text-alerta',
                                )
                            "
                        >
                            <CircleCheck
                                v-if="r.ok"
                                class="mt-px size-3.5 shrink-0 text-exito"
                                aria-hidden="true"
                            />
                            <TriangleAlert
                                v-else
                                class="mt-px size-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            <span>
                                <span class="sr-only">{{
                                    r.ok ? 'Cumplido:' : 'Falta:'
                                }}</span>
                                {{ r.texto
                                }}<template v-if="r.detalle">
                                    · {{ r.detalle }}</template
                                >
                            </span>
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
                <Rocket class="size-4" aria-hidden="true" />
                Publicar v{{ diagnostico.version }}
            </Boton>
        </template>
    </Modal>
</template>
