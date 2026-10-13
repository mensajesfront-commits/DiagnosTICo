<script setup lang="ts">
/**
 * E8 · Mi historial (HU-067) y A3.4 · Historial de una empresa (HU-039): la
 * misma pantalla para la empresa y para el equipo de NuevasTIC.
 *
 * - La medición pendiente, si hay. La empresa la continúa desde aquí si está
 *   en curso; no se inicia aquí (CA-005). El Administrador puede cambiar la
 *   fecha o cancelarla (A3.1c) y asignar otra (HU-039 CA-004).
 * - La evolución del puntaje total con las franjas de nivel, desde la 2.ª
 *   medición (CA-001, CA-002).
 * - Las mediciones terminadas con fecha, puntaje, nivel y variación (RN-020)
 *   y "Ver resultado", "Ver mis respuestas" y "Ver informe PDF".
 */
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import Paginacion from '@/components/base/Paginacion.vue';
import ModalCambiarFecha from '@/components/empresas/ModalCambiarFecha.vue';
import ModalCancelarMedicion from '@/components/empresas/ModalCancelarMedicion.vue';
import Variacion from '@/components/empresas/Variacion.vue';
import EvolucionPuntaje from '@/components/graficas/EvolucionPuntaje.vue';
import { diaYMes, fechaLocal } from '@/lib/fechas';
import { useFilasQueCaben, usePaginacion } from '@/lib/paginacion';
import { rutas } from '@/lib/rutas';
import { empresa as inicioEmpresa } from '@/routes/inicio';
import type { Historial } from '@/types/historial';

const props = defineProps<Historial>();

const page = usePage();
const puedeAsignar = computed(() =>
    page.props.auth.permisos.includes('mediciones.asignar'),
);

const fecha = (texto: string | null) =>
    texto ? diaYMes(fechaLocal(texto)) : '—';

// --- Tabla paginada con las filas que caben ------------------------------------
const contenedor = ref<HTMLElement | null>(null);
const { porPagina, ajustar } = useFilasQueCaben(contenedor, {
    movil: 8,
    minimo: 3,
});
const { pagina, paginas, total, desde, visibles } = usePaginacion(
    () => props.mediciones,
    porPagina,
);
watch(visibles, ajustar);

// --- Pendiente -----------------------------------------------------------------------
const accionEmpresa = computed(() => {
    const p = props.pendiente;

    if (!p || props.interno) {
        return null;
    }

    switch (p.estado) {
        case 'en_curso':
            return {
                texto: 'Continuar diagnóstico',
                href: rutas.responder.mostrar(p.id),
            };
        case 'enviada':
            return {
                texto: 'Ver el avance del análisis',
                href: rutas.responder.enviado(p.id),
            };
        default:
            return {
                texto: 'Ir a mi inicio',
                href: inicioEmpresa().url,
                secundario: true,
            };
    }
});

const notaPendiente = computed(() => {
    switch (props.pendiente?.estado) {
        case 'enviada':
            return 'La estamos analizando: cuando termine, aparecerá en la tabla.';
        case 'vencida':
            return props.interno
                ? 'Venció sin enviarse. Cámbiale la fecha o asígnale otra.'
                : 'Venció. Desde tu inicio puedes pedir una nueva fecha.';
        case 'no_iniciada':
            return props.interno
                ? 'La empresa aún no la empieza.'
                : 'Empiézala desde tu inicio. Cuando la envíes, aparecerá en la tabla.';
        default:
            return 'Cuando la envíes, aparecerá en la tabla de mediciones terminadas.';
    }
});

const modalFecha = ref(false);
const modalCancelar = ref(false);

const enlace = 'text-marca underline underline-offset-2 hover:text-marca-hover';
</script>

<template>
    <Head
        :title="interno ? `Historial de ${empresa.nombre}` : 'Mi historial'"
    />

    <div class="flex flex-col gap-4 p-6 lg:h-dvh lg:overflow-hidden">
        <header class="flex shrink-0 flex-wrap items-end justify-between gap-4">
            <div>
                <Link
                    v-if="interno"
                    :href="rutas.empresas.ver(empresa.id)"
                    class="mb-2 inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-tinta-suave hover:text-marca"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    {{ empresa.nombre }}
                </Link>
                <h1
                    class="flex flex-wrap items-center gap-3 text-2xl font-semibold"
                >
                    {{
                        interno
                            ? `Historial de ${empresa.nombre}`
                            : 'Mi historial'
                    }}
                    <span
                        v-if="interno"
                        class="rounded bg-lienzo-oscuro px-2 py-0.5 text-xs font-normal text-tinta-suave"
                        >Vista del Administrador</span
                    >
                </h1>
                <p class="mt-0.5 text-sm text-tinta-suave">
                    Todas las mediciones de {{ empresa.nombre }}.
                    {{
                        interno
                            ? 'La empresa ve este mismo historial en “Mi historial”.'
                            : 'El equipo de NuevasTIC ve este mismo historial.'
                    }}
                </p>
            </div>
            <Boton
                v-if="interno && puedeAsignar"
                variante="secundario"
                :href="rutas.mediciones.asignar(empresa.id)"
            >
                Asignar medición
            </Boton>
        </header>

        <!-- Pendiente -->
        <section
            v-if="pendiente"
            class="flex shrink-0 flex-wrap items-center justify-between gap-3 rounded-xl border-2 border-marca/70 bg-white px-5 py-3"
        >
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                <strong>Medición {{ pendiente.numero }}</strong>
                <EtiquetaEstado
                    :estado="pendiente.estado"
                    :avance="pendiente.avance ?? undefined"
                />
                <span class="text-tinta-suave">
                    {{
                        pendiente.vence
                            ? `Vence el ${fecha(pendiente.vence)}`
                            : 'Sin fecha límite'
                    }}
                    <template v-if="interno && pendiente.diagnostico">
                        · {{ pendiente.diagnostico }} v{{ pendiente.version }}
                    </template>
                </span>
                <span class="w-full text-xs text-tinta-suave lg:w-auto">{{
                    notaPendiente
                }}</span>
            </div>
            <Boton
                v-if="accionEmpresa"
                :variante="accionEmpresa.secundario ? 'secundario' : 'primario'"
                :href="accionEmpresa.href"
            >
                {{ accionEmpresa.texto }}
            </Boton>
            <div
                v-else-if="
                    interno && puedeAsignar && pendiente.estado !== 'enviada'
                "
                class="flex gap-2"
            >
                <Boton variante="secundario" @click="modalFecha = true">
                    Cambiar fecha
                </Boton>
                <Boton variante="secundario" @click="modalCancelar = true">
                    Cancelar medición
                </Boton>
            </div>
        </section>

        <!-- Evolución -->
        <section
            class="shrink-0 rounded-xl border border-linea bg-white px-5 py-4"
        >
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-sm font-semibold">
                    Evolución del puntaje total
                </h2>
                <span class="text-xs text-tinta-suave"
                    >Aparece desde la segunda medición</span
                >
            </div>
            <!-- La gráfica toma la altura de su contenedor. -->
            <div v-if="evolucion.length >= 2" class="mt-2 h-44">
                <EvolucionPuntaje :puntos="evolucion" />
            </div>
            <p
                v-else
                class="flex h-20 items-center justify-center text-sm text-tinta-suave"
            >
                {{
                    evolucion.length === 1
                        ? 'Con la segunda medición verás aquí cómo evoluciona el puntaje.'
                        : 'La gráfica aparece desde la segunda medición terminada.'
                }}
            </p>
        </section>

        <!-- Mediciones terminadas -->
        <section
            class="flex flex-col rounded-xl border border-linea bg-white lg:min-h-0 lg:flex-1"
        >
            <div
                class="flex flex-wrap items-baseline justify-between gap-2 px-5 py-3"
            >
                <h2 class="text-sm font-semibold">Mediciones terminadas</h2>
                <span class="text-xs text-tinta-suave">
                    Solo se listan las mediciones enviadas y analizadas
                </span>
            </div>
            <div ref="contenedor" class="overflow-x-auto lg:min-h-0 lg:flex-1">
                <table v-if="mediciones.length" class="w-full text-sm">
                    <thead class="bg-lienzo text-left text-xs text-tinta-suave">
                        <tr>
                            <th class="px-5 py-2 font-normal">Medición</th>
                            <th class="px-4 py-2 font-normal">Fecha</th>
                            <th class="px-4 py-2 font-normal">
                                Puntaje y nivel
                            </th>
                            <th class="px-4 py-2 font-normal">Variación</th>
                            <th class="px-5 py-2 font-normal">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="m in visibles"
                            :key="m.id"
                            class="border-b border-linea last:border-b-0"
                        >
                            <td class="px-5 py-2.5 font-medium">
                                {{ m.numero }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                {{ fecha(m.terminada) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="flex items-center gap-2">
                                    <strong class="w-6 font-mono text-base">{{
                                        m.puntaje
                                    }}</strong>
                                    <EtiquetaNivel
                                        v-if="m.nivel"
                                        :nivel="m.nivel"
                                    />
                                </span>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <template v-if="m.variacion !== null">
                                    <Variacion :valor="m.variacion" />
                                    <span class="text-xs text-tinta-suave">
                                        vs. medición anterior</span
                                    >
                                </template>
                                <span v-else class="text-xs text-tinta-suave"
                                    >Primera medición</span
                                >
                            </td>
                            <td class="px-5 py-2.5 text-xs whitespace-nowrap">
                                <span class="flex flex-wrap gap-x-3 gap-y-1">
                                    <Link
                                        :href="rutas.resultados.ver(m.id)"
                                        :class="enlace"
                                        >Ver resultado</Link
                                    >
                                    <Link
                                        :href="
                                            rutas.resultados.respuestas(m.id)
                                        "
                                        :class="enlace"
                                    >
                                        {{
                                            interno
                                                ? 'Respuestas'
                                                : 'Ver mis respuestas'
                                        }}
                                    </Link>
                                    <a
                                        :href="rutas.resultados.pdf(m.id)"
                                        :class="enlace"
                                        >Ver informe PDF</a
                                    >
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p
                    v-else
                    class="px-5 py-10 text-center text-sm text-tinta-suave"
                >
                    {{
                        interno
                            ? 'Esta empresa aún no tiene mediciones terminadas.'
                            : 'Aún no tienes mediciones terminadas. Cuando envíes tu diagnóstico y se analice, aparecerá aquí.'
                    }}
                </p>
            </div>
            <div
                class="flex flex-wrap items-center justify-between gap-2 border-t border-linea px-5 py-2 text-xs text-tinta-suave"
            >
                <span
                    >La variación se calcula frente a la medición anterior. Con
                    una sola medición no se muestra.</span
                >
                <Paginacion
                    v-if="paginas > 1"
                    v-model:pagina="pagina"
                    :paginas="paginas"
                    :total="total"
                    :desde="desde"
                    :cantidad="visibles.length"
                />
            </div>
        </section>
    </div>

    <template v-if="interno && pendiente">
        <ModalCambiarFecha
            v-model:abierto="modalFecha"
            :medicion="pendiente"
            :empresa="empresa.nombre"
        />
        <ModalCancelarMedicion
            v-model:abierto="modalCancelar"
            :medicion="pendiente"
            :empresa="empresa.nombre"
        />
    </template>
</template>
