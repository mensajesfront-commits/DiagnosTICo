<script setup lang="ts">
/**
 * C1 · Inicio del Consultor (HU-086, RN-030).
 *
 * Solo sus empresas vinculadas: cuántas son, cuántas tienen resultado y
 * cuántas tienen una medición en curso; y una tarjeta por empresa con el
 * último puntaje, el nivel, la variación (RN-020) y la medición pendiente.
 * "Ver empresa" abre la ficha (A3.1) e "Historial", A3.4. Solo mira: no
 * responde ni asigna (los botones de la ficha dependen de los permisos).
 *
 * Los datos llegan calculados del servidor (DatosEmpresas::lista).
 */
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    ChartLine,
    ClipboardList,
    Eye,
    History,
    Minus,
    TrendingDown,
    TrendingUp,
} from '@lucide/vue';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import { rutas } from '@/lib/rutas';
import { plural } from '@/lib/texto';
import { cn } from '@/lib/utils';
import type { FilaEmpresa } from '@/types/empresas';

const props = defineProps<{ empresas: FilaEmpresa[] }>();

const page = usePage();
const nombre = computed(
    () => page.props.auth.user?.name?.split(' ')[0] ?? 'consultor',
);

const conResultado = computed(
    () => props.empresas.filter((e) => e.puntaje !== null).length,
);
const enCurso = computed(
    () => props.empresas.filter((e) => e.pendiente !== null).length,
);

const resumen = computed(() => [
    {
        icono: Building2,
        valor: props.empresas.length,
        texto: props.empresas.length === 1 ? 'empresa' : 'empresas',
    },
    {
        icono: ChartLine,
        valor: conResultado.value,
        texto: 'con resultado',
    },
    {
        icono: ClipboardList,
        valor: enCurso.value,
        texto:
            enCurso.value === 1
                ? 'medición pendiente'
                : 'mediciones pendientes',
    },
]);
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-col gap-5 p-4 sm:p-6">
        <header class="flex items-start gap-3">
            <span
                class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-marca-suave text-marca"
                aria-hidden="true"
            >
                <Eye class="size-5" />
            </span>
            <div>
                <h1 class="text-2xl font-semibold">Hola, {{ nombre }}</h1>
                <p class="mt-0.5 text-sm text-tinta-suave">
                    Estas son las empresas que acompañas. Puedes ver sus
                    resultados, su evolución y el informe en PDF.
                </p>
            </div>
        </header>

        <dl class="grid grid-cols-3 gap-3">
            <div
                v-for="r in resumen"
                :key="r.texto"
                class="flex flex-col-reverse gap-1 rounded-2xl border border-linea bg-white p-4 sm:flex-row-reverse sm:items-center sm:justify-end sm:gap-3"
            >
                <dt class="text-xs text-tinta-suave sm:text-sm">
                    {{ r.texto }}
                </dt>
                <dd class="flex items-center gap-2">
                    <span
                        class="hidden size-9 items-center justify-center rounded-xl bg-marca-suave text-marca sm:flex"
                        aria-hidden="true"
                    >
                        <component :is="r.icono" class="size-4.5" />
                    </span>
                    <span class="font-mono text-2xl font-semibold">{{
                        r.valor
                    }}</span>
                </dd>
            </div>
        </dl>

        <section aria-labelledby="titulo-mis-empresas">
            <h2 id="titulo-mis-empresas" class="mb-3 font-semibold">
                Mis empresas
            </h2>

            <ul
                v-if="empresas.length > 0"
                class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3"
            >
                <li
                    v-for="e in empresas"
                    :key="e.id"
                    class="flex flex-col rounded-2xl border border-linea bg-white"
                >
                    <div class="flex flex-1 items-start gap-4 p-4 sm:p-5">
                        <AnilloPuntaje
                            v-if="e.puntaje !== null"
                            :puntaje="e.puntaje"
                            :nivel="e.nivel"
                            :tamano="72"
                        />
                        <span
                            v-else
                            class="flex size-[72px] shrink-0 items-center justify-center rounded-full border-4 border-dashed border-linea text-xs text-tinta-suave"
                            aria-hidden="true"
                            >—</span
                        >
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate font-semibold">
                                {{ e.nombre }}
                            </h3>
                            <p class="truncate text-sm text-tinta-suave">
                                {{ e.sector ?? 'Sin sector' }}
                            </p>
                            <div
                                class="mt-2 flex flex-wrap items-center gap-1.5 text-xs"
                            >
                                <EtiquetaNivel :nivel="e.nivel" />
                                <span
                                    v-if="e.variacion !== null"
                                    :class="
                                        cn(
                                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-medium',
                                            e.variacion > 0
                                                ? 'bg-exito-suave text-exito'
                                                : e.variacion < 0
                                                  ? 'bg-aviso-suave text-aviso'
                                                  : 'bg-lienzo text-tinta-suave',
                                        )
                                    "
                                >
                                    <TrendingUp
                                        v-if="e.variacion > 0"
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    <TrendingDown
                                        v-else-if="e.variacion < 0"
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    <Minus
                                        v-else
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    {{ e.variacion > 0 ? '+' : ''
                                    }}{{ e.variacion }}
                                </span>
                                <span
                                    class="rounded-full bg-lienzo px-2 py-0.5 text-tinta-suave"
                                >
                                    {{
                                        plural(
                                            e.mediciones,
                                            'medición terminada',
                                            'mediciones terminadas',
                                        )
                                    }}
                                </span>
                            </div>
                            <p
                                v-if="e.pendiente"
                                class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-tinta-suave"
                            >
                                Medición {{ e.pendiente.numero }}:
                                <EtiquetaEstado
                                    :estado="e.pendiente.estado"
                                    :avance="e.pendiente.avance ?? undefined"
                                />
                            </p>
                            <p
                                v-if="!e.activa"
                                class="mt-2 text-xs text-tinta-suave"
                            >
                                Cuenta desactivada: sus resultados se conservan.
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex flex-wrap gap-2 rounded-b-2xl border-t border-linea bg-lienzo/40 px-4 py-3 sm:px-5"
                    >
                        <Boton tamano="sm" :href="rutas.empresas.ver(e.id)">
                            <Eye class="size-3.5" aria-hidden="true" />
                            Ver empresa
                        </Boton>
                        <Boton
                            v-if="e.mediciones > 0"
                            variante="secundario"
                            tamano="sm"
                            :href="rutas.historial.empresa(e.id)"
                        >
                            <History class="size-3.5" aria-hidden="true" />
                            Historial
                        </Boton>
                    </div>
                </li>
            </ul>

            <div
                v-else
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-linea-fuerte bg-white px-5 py-12 text-center"
            >
                <span
                    class="flex size-11 items-center justify-center rounded-xl bg-marca-suave text-marca"
                    aria-hidden="true"
                >
                    <Building2 class="size-5" />
                </span>
                <p class="font-semibold">Todavía no tienes empresas</p>
                <p class="max-w-sm text-sm text-tinta-suave">
                    El equipo de NuevasTIC te las vincula. Cuando lo haga, las
                    verás aquí.
                </p>
                <Link
                    href="/mi-perfil"
                    class="mt-1 text-sm font-medium text-marca hover:text-marca-hover"
                >
                    Mientras tanto, completa tu perfil
                </Link>
            </div>
        </section>
    </div>
</template>
