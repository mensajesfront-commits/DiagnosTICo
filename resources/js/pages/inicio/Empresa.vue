<script setup lang="ts">
/**
 * E1 · Inicio de la empresa y del colaborador (HU-057, HU-058, HU-059,
 * HU-082).
 *
 * Arriba, la medición pendiente según su estado:
 * - Primera vez: "Descubre en qué punto está tu empresa" y "Cómo funciona".
 * - No iniciada: mensaje del equipo de NuevasTIC, fecha límite y "Iniciar
 *   diagnóstico".
 * - En curso: avance y "Continuar diagnóstico" (sigue donde iba).
 * - Vencida: no se responde (RN-016); "Pedir una nueva fecha" avisa al
 *   equipo, una solicitud a la vez.
 * - Enviada: la IA analiza las respuestas.
 * - Sin pendiente: "No tienes diagnósticos pendientes".
 *
 * Abajo, el último resultado con su variación y evolución, y las 3 áreas a
 * trabajar o la lista de mediciones.
 *
 * [FUNCIONALIDAD POR DEFINIR] "Ver resultado" (E6) y "Ver informe PDF" (E7)
 * llegan con Resultados (semana 5): por ahora se ven sin enlace.
 */
import { Head, router } from '@inertiajs/vue3';
import { CalendarDays, Mail } from '@lucide/vue';
import { computed, ref } from 'vue';
import Boton from '@/components/base/Boton.vue';
import EtiquetaEstado from '@/components/base/EtiquetaEstado.vue';
import EtiquetaNivel from '@/components/base/EtiquetaNivel.vue';
import Variacion from '@/components/empresas/Variacion.vue';
import AnilloPuntaje from '@/components/graficas/AnilloPuntaje.vue';
import MiniEvolucion from '@/components/graficas/MiniEvolucion.vue';
import { diaYMes, fechaLocal } from '@/lib/fechas';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';
import type { InicioEmpresa } from '@/types/responder';

const props = defineProps<Partial<InicioEmpresa> & { sinEmpresa?: boolean }>();

const plural = (n: number, uno: string, varios: string) =>
    `${n} ${n === 1 ? uno : varios}`;

const subtitulo = computed(() => {
    const e = props.empresa;

    if (!e) {
        return '';
    }

    const hechos = props.realizadas
        ? plural(
              props.realizadas,
              'diagnóstico realizado',
              'diagnósticos realizados',
          )
        : 'Aún no has realizado ningún diagnóstico';

    return `${e.nombre} · Sector ${e.sector} · ${hechos}`;
});

const p = computed(() => props.pendiente ?? null);

const fechaLarga = (texto: string) =>
    fechaLocal(texto).toLocaleDateString('es-CO', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });

const quedan = computed(() => {
    const d = p.value?.dias_restantes;

    if (d === null || d === undefined) {
        return null;
    }

    return d === 0 ? 'Vence hoy' : `Quedan ${plural(d, 'día', 'días')}`;
});

const evolucion = computed(() =>
    [...(props.mediciones ?? [])].reverse().map((m) => m.puntaje),
);

// --- HU-082: pedir una nueva fecha -------------------------------------------
const pidiendo = ref(false);

function pedirNueva(): void {
    if (!p.value) {
        return;
    }

    router.post(
        rutas.responder.solicitar(p.value.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (pidiendo.value = true),
            onFinish: () => (pidiendo.value = false),
        },
    );
}

const enlaceInactivo =
    'cursor-not-allowed text-tinta-suave underline underline-offset-2';
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-col gap-5 p-6">
        <header>
            <h1 class="text-2xl font-semibold">Hola, {{ nombre }}</h1>
            <p class="mt-1 text-sm text-tinta-suave">{{ subtitulo }}</p>
        </header>

        <p
            v-if="sinEmpresa"
            class="rounded-xl border border-dashed border-linea-fuerte bg-white p-6 text-sm text-tinta-suave"
        >
            Tu cuenta todavía no está vinculada a una empresa. Escríbele al
            equipo de NuevasTIC.
        </p>

        <!-- Medición pendiente -->
        <section
            v-if="p"
            :class="
                cn(
                    'grid gap-6 rounded-xl border-2 bg-white p-6 md:grid-cols-[1fr_auto]',
                    p.estado === 'vencida' ? 'border-aviso' : 'border-marca',
                )
            "
        >
            <div class="flex flex-col gap-3">
                <p class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-semibold">Medición {{ p.numero }}</span>
                    <EtiquetaEstado :estado="p.estado" />
                    <span
                        v-if="p.estado === 'en_curso'"
                        class="text-tinta-suave"
                    >
                        {{ p.avance.porcentaje }}% respondido · Categoría
                        {{ p.avance.categoria_numero }} de {{ p.categorias }}
                    </span>
                    <span
                        v-else-if="p.estado === 'no_iniciada'"
                        class="text-tinta-suave"
                    >
                        {{
                            p.primera
                                ? 'Tu primer diagnóstico'
                                : 'Nueva medición disponible'
                        }}
                    </span>
                    <span
                        v-if="p.estado === 'vencida' && p.solicitud_abierta"
                        class="rounded bg-alerta-suave px-2 py-0.5 font-medium text-alerta"
                    >
                        Nueva medición solicitada
                    </span>
                </p>

                <template v-if="p.estado === 'no_iniciada'">
                    <h2 class="text-xl font-semibold">
                        {{
                            p.primera
                                ? 'Descubre en qué punto está tu empresa'
                                : 'Es momento de medir cómo vas'
                        }}
                    </h2>
                    <p v-if="p.primera" class="text-sm text-tinta-suave">
                        Este primer resultado será tu punto de partida. En los
                        próximos diagnósticos podrás ver cómo mejoras.
                    </p>
                </template>
                <h2
                    v-else-if="p.estado === 'en_curso'"
                    class="text-xl font-semibold"
                >
                    Continúa donde lo dejaste
                </h2>
                <h2
                    v-else-if="p.estado === 'vencida'"
                    class="text-xl font-semibold"
                >
                    La fecha límite de esta medición ya pasó
                </h2>
                <h2 v-else class="text-xl font-semibold">
                    Estamos analizando tus respuestas
                </h2>

                <p
                    v-if="p.mensaje && p.estado !== 'enviada'"
                    class="rounded-md bg-lienzo px-4 py-3 text-sm"
                >
                    <strong>Mensaje del equipo de NuevasTIC:</strong>
                    {{ p.mensaje }}
                </p>

                <template
                    v-if="p.estado === 'en_curso' || p.estado === 'vencida'"
                >
                    <div>
                        <p
                            class="flex justify-between text-xs text-tinta-suave"
                        >
                            <span
                                v-if="
                                    p.estado === 'en_curso' &&
                                    p.avance.categoria
                                "
                            >
                                Vas en
                                <strong class="text-tinta">{{
                                    p.avance.categoria
                                }}</strong>
                                · {{ p.avance.respondidas }} de
                                {{ p.avance.total }} preguntas
                            </span>
                            <span v-else>
                                {{ p.avance.respondidas }} de
                                {{ p.avance.total }} preguntas respondidas
                            </span>
                            <span class="font-mono"
                                >{{ p.avance.porcentaje }}%</span
                            >
                        </p>
                        <div class="mt-1.5 h-2 rounded-full bg-lienzo-oscuro">
                            <div
                                :class="
                                    cn(
                                        'h-2 rounded-full',
                                        p.estado === 'vencida'
                                            ? 'bg-tinta-suave'
                                            : 'bg-marca',
                                    )
                                "
                                :style="{ width: `${p.avance.porcentaje}%` }"
                            />
                        </div>
                    </div>
                </template>

                <p
                    v-if="p.estado === 'vencida'"
                    class="text-sm text-tinta-suave"
                >
                    <template v-if="p.solicitud_abierta">
                        Ya le pediste al equipo de NuevasTIC una nueva fecha: te
                        escribirán cuando la asignen.
                    </template>
                    <template v-else>
                        Respondiste el {{ p.avance.porcentaje }}%. Pide una
                        nueva fecha al equipo de NuevasTIC.
                    </template>
                </p>
                <p
                    v-else-if="p.estado === 'enviada'"
                    class="text-sm text-tinta-suave"
                >
                    Suele tardar menos de un minuto; si tarda más, te avisamos
                    por correo.
                </p>
                <p
                    v-else
                    class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-tinta-suave"
                >
                    <span>
                        {{ plural(p.categorias, 'categoría', 'categorías') }} ·
                        {{ plural(p.preguntas, 'pregunta', 'preguntas') }}
                    </span>
                    <span v-if="p.estado === 'en_curso'">
                        ✓ Tu avance se guarda automáticamente
                    </span>
                    <span>
                        {{
                            p.estado === 'en_curso'
                                ? 'Puedes moverte entre categorías cuando quieras'
                                : 'Puedes pausar y continuar después'
                        }}
                    </span>
                </p>
            </div>

            <div
                class="flex min-w-60 flex-col justify-center gap-3 md:border-l md:border-linea md:pl-6"
            >
                <div v-if="p.fecha_limite" class="flex items-start gap-2">
                    <CalendarDays
                        :class="
                            cn(
                                'mt-0.5 size-5',
                                p.estado === 'vencida'
                                    ? 'text-aviso'
                                    : 'text-tinta-suave',
                            )
                        "
                        aria-hidden="true"
                    />
                    <div>
                        <p class="text-xs text-tinta-suave">Fecha límite</p>
                        <p
                            :class="
                                cn(
                                    'text-lg font-semibold',
                                    p.estado === 'vencida' && 'text-aviso',
                                )
                            "
                        >
                            {{ fechaLarga(p.fecha_limite) }}
                        </p>
                        <p
                            v-if="p.estado === 'vencida'"
                            class="text-xs font-medium text-aviso"
                        >
                            ⚠ Vencida
                        </p>
                        <p
                            v-else-if="quedan"
                            class="text-xs font-medium text-alerta"
                        >
                            {{ quedan }}
                        </p>
                    </div>
                </div>

                <Boton
                    v-if="p.estado === 'no_iniciada' || p.estado === 'en_curso'"
                    :href="rutas.responder.mostrar(p.id)"
                >
                    {{
                        p.estado === 'en_curso'
                            ? 'Continuar diagnóstico'
                            : 'Iniciar diagnóstico'
                    }}
                </Boton>
                <Boton
                    v-else-if="p.estado === 'enviada'"
                    variante="secundario"
                    :href="rutas.responder.enviado(p.id)"
                >
                    Ver el avance del análisis
                </Boton>
                <template v-else>
                    <Boton
                        v-if="!p.solicitud_abierta"
                        variante="secundario"
                        :cargando="pidiendo"
                        @click="pedirNueva"
                    >
                        Pedir una nueva fecha
                    </Boton>
                    <a
                        :href="`mailto:${soporte}`"
                        class="flex items-center justify-center gap-1.5 text-xs text-tinta-suave hover:text-marca"
                    >
                        <Mail class="size-3.5" aria-hidden="true" />
                        {{ soporte }}
                    </a>
                </template>
            </div>
        </section>

        <section
            v-else-if="!sinEmpresa"
            class="flex flex-wrap items-center gap-5 rounded-xl border border-dashed border-linea-fuerte bg-white p-6"
        >
            <span
                class="flex size-12 shrink-0 items-center justify-center rounded-full bg-exito-suave text-xl text-exito"
                aria-hidden="true"
                >✓</span
            >
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-semibold">
                    No tienes diagnósticos pendientes
                </h2>
                <p class="text-sm text-tinta-suave">
                    Cuando el equipo de NuevasTIC te asigne una nueva medición,
                    aparecerá aquí.<template v-if="ultima">
                        Mientras tanto, trabaja en las recomendaciones de tu
                        medición {{ ultima.numero }}, la más reciente.</template
                    >
                </p>
            </div>
            <span
                v-if="ultima"
                class="rounded-md border border-linea-fuerte px-4 py-2 text-sm text-tinta-suave"
                title="El resultado completo llega con Resultados (E6)"
            >
                Ver resultado de la medición {{ ultima.numero }}
            </span>
        </section>

        <!-- Primera vez: cómo funciona -->
        <template v-if="!ultima && !sinEmpresa">
            <section>
                <h2 class="mb-3 text-sm font-semibold">Cómo funciona</h2>
                <ol class="grid gap-4 md:grid-cols-3">
                    <li
                        v-for="(paso, i) in [
                            {
                                t: 'Respondes',
                                d: 'Preguntas sobre las áreas del marketing digital de tu negocio.',
                            },
                            {
                                t: 'Analizamos',
                                d: 'El sistema calcula tu puntaje y la IA analiza todas tus respuestas.',
                            },
                            {
                                t: 'Recibes tu resultado',
                                d: 'Puntaje de 0 a 100, tu nivel y recomendaciones priorizadas, descargables en PDF.',
                            },
                        ]"
                        :key="paso.t"
                        class="rounded-xl border border-linea bg-white p-5 text-sm"
                    >
                        <span
                            class="flex size-8 items-center justify-center rounded-full bg-marca-suave font-semibold text-marca"
                            >{{ i + 1 }}</span
                        >
                        <p class="mt-3 font-semibold">{{ paso.t }}</p>
                        <p class="mt-1 text-tinta-suave">{{ paso.d }}</p>
                    </li>
                </ol>
            </section>
            <p
                class="rounded-xl border border-dashed border-linea-fuerte bg-white p-6 text-center text-sm"
            >
                <strong>Aquí verás tus resultados</strong>
                <span class="block text-tinta-suave">
                    Tu puntaje, tu nivel y tu evolución aparecerán cuando
                    completes el primer diagnóstico.
                </span>
            </p>
        </template>

        <!-- Último resultado -->
        <div
            v-else-if="ultima"
            class="grid items-start gap-5 lg:grid-cols-[1fr_1.4fr]"
        >
            <section class="rounded-xl border border-linea bg-white p-5">
                <h2 class="text-sm font-semibold">
                    {{
                        realizadas === 1
                            ? 'Resultado de tu primera medición'
                            : 'Resultado de tu medición más reciente'
                    }}
                </h2>
                <p class="text-xs text-tinta-suave">
                    Medición {{ ultima.numero }}
                    <template v-if="ultima.fecha">
                        · respondida el {{ diaYMes(fechaLocal(ultima.fecha)) }}
                    </template>
                </p>
                <div class="mt-4 flex items-center gap-5">
                    <AnilloPuntaje
                        :puntaje="ultima.puntaje"
                        :nivel="ultima.nivel"
                    />
                    <div class="flex flex-col items-start gap-2 text-sm">
                        <EtiquetaNivel :nivel="ultima.nivel" />
                        <span v-if="ultima.variacion !== null">
                            <Variacion :valor="ultima.variacion" />
                            <span class="text-xs text-exito">
                                vs. medición anterior</span
                            >
                        </span>
                        <span v-else class="text-xs text-tinta-suave">
                            Primera medición
                        </span>
                        <span
                            :class="enlaceInactivo"
                            title="El resultado completo llega con Resultados (E6)"
                        >
                            Ver resultado completo
                        </span>
                    </div>
                </div>
                <div class="mt-4 border-t border-linea pt-3">
                    <p class="text-xs text-tinta-suave">Evolución</p>
                    <MiniEvolucion
                        v-if="evolucion.length > 1"
                        :puntajes="evolucion"
                        class="mt-2"
                    />
                    <p
                        v-else
                        class="mt-2 rounded-md border border-dashed border-linea-fuerte px-3 py-2 text-xs text-tinta-suave"
                    >
                        La variación y la gráfica de evolución aparecen desde tu
                        segunda medición.
                    </p>
                </div>
            </section>

            <section
                v-if="p && areas && areas.length"
                class="rounded-xl border border-linea bg-white p-5"
            >
                <div class="flex items-baseline justify-between">
                    <h2 class="text-sm font-semibold">
                        Tus {{ areas.length }} áreas a trabajar
                    </h2>
                    <span class="text-xs text-tinta-suave"
                        >Del último diagnóstico</span
                    >
                </div>
                <ul class="mt-2 divide-y divide-linea">
                    <li
                        v-for="a in areas"
                        :key="a.nombre"
                        class="flex items-center justify-between gap-3 py-3"
                    >
                        <span>
                            <span class="block text-sm font-medium">{{
                                a.nombre
                            }}</span>
                            <span
                                v-if="a.recomendacion"
                                class="text-xs text-tinta-suave"
                            >
                                {{ a.recomendacion }}
                            </span>
                        </span>
                        <span class="flex items-center gap-2">
                            <strong class="font-mono">{{ a.puntaje }}</strong>
                            <EtiquetaNivel :nivel="a.nivel" />
                        </span>
                    </li>
                </ul>
                <p class="mt-2 text-xs text-tinta-suave">
                    Tu próximo diagnóstico mostrará si estas áreas mejoraron.
                </p>
            </section>

            <section v-else class="rounded-xl border border-linea bg-white">
                <div class="flex items-baseline justify-between px-5 py-4">
                    <h2 class="text-sm font-semibold">Mis mediciones</h2>
                    <span
                        :class="cn(enlaceInactivo, 'text-xs')"
                        title="Mi historial llega con Resultados (E8)"
                    >
                        Ver historial completo
                    </span>
                </div>
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr
                            class="border-y border-linea bg-[#f9f8f4] text-left"
                        >
                            <th
                                v-for="c in [
                                    'Medición',
                                    'Puntaje y nivel',
                                    'vs. anterior',
                                    '',
                                ]"
                                :key="c"
                                scope="col"
                                class="px-5 py-2 text-xs font-normal text-tinta-suave"
                            >
                                {{ c }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="m in mediciones"
                            :key="m.id"
                            class="border-b border-linea last:border-b-0"
                        >
                            <td class="px-5 py-2">
                                <p class="font-medium">
                                    Medición {{ m.numero }}
                                </p>
                                <p
                                    v-if="m.fecha"
                                    class="text-xs text-tinta-suave"
                                >
                                    {{ diaYMes(fechaLocal(m.fecha)) }}
                                </p>
                            </td>
                            <td class="px-5 py-2">
                                <span class="flex items-center gap-2">
                                    <strong class="font-mono">{{
                                        m.puntaje
                                    }}</strong>
                                    <EtiquetaNivel :nivel="m.nivel" />
                                </span>
                            </td>
                            <td class="px-5 py-2">
                                <Variacion
                                    v-if="m.variacion !== null"
                                    :valor="m.variacion"
                                />
                                <span v-else class="text-xs text-tinta-suave">
                                    Primera medición
                                </span>
                            </td>
                            <td
                                class="px-5 py-2 text-right text-xs whitespace-nowrap"
                            >
                                <span
                                    :class="enlaceInactivo"
                                    title="Llega con Resultados (E6)"
                                >
                                    Ver resultado
                                </span>
                                <span
                                    :class="cn(enlaceInactivo, 'ml-3')"
                                    title="Llega con Resultados (E7)"
                                >
                                    Ver informe PDF
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</template>
