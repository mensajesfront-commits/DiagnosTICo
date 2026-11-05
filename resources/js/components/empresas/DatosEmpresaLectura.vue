<script setup lang="ts">
/**
 * A3.1d en solo lectura: los datos de la empresa y de su contacto principal
 * como texto, para quien no puede editarlos (el Consultor, RN-030). Sin
 * campos deshabilitados, asteriscos ni ayudas de edición.
 */
import {
    Building2,
    Globe,
    Mail,
    MapPin,
    Phone,
    User,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import type {
    DatosEmpresa,
    OpcionesEmpresa,
    UsuarioPrincipal,
} from '@/types/empresas';

const props = defineProps<{
    empresa: DatosEmpresa;
    principal: UsuarioPrincipal | null;
    opciones: OpcionesEmpresa;
}>();

const actividad = computed(() => {
    const a = props.opciones.sectores
        .find((s) => s.id === props.empresa.sector_id)
        ?.actividades.find(
            (x) => x.id === props.empresa.actividad_economica_id,
        );

    return a ? `${a.codigo} · ${a.nombre}` : null;
});

const ubicacion = computed(() =>
    [props.empresa.ciudad, props.empresa.departamento, props.empresa.pais]
        .filter(Boolean)
        .join(', '),
);

const web = computed(() => {
    const url = props.empresa.sitio_web;

    if (!url) {
        return null;
    }

    return {
        href: /^https?:\/\//.test(url) ? url : `https://${url}`,
        texto: url.replace(/^https?:\/\//, '').replace(/\/$/, ''),
    };
});

const enlace =
    'font-medium text-marca underline-offset-2 hover:text-marca-hover hover:underline';
</script>

<template>
    <div class="flex flex-col gap-4">
        <section class="rounded-2xl border border-linea bg-white p-5">
            <h2 class="flex items-center gap-2 font-semibold">
                <Building2 class="size-4 text-marca" aria-hidden="true" />
                Empresa
            </h2>
            <p
                v-if="empresa.descripcion"
                class="mt-3 rounded-xl bg-lienzo/70 px-4 py-3 text-sm text-tinta"
            >
                {{ empresa.descripcion }}
            </p>
            <dl class="mt-4 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-tinta-suave">Sector</dt>
                    <dd class="mt-0.5 font-medium">
                        {{ empresa.sector ?? 'Sin sector' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-tinta-suave">
                        Actividad económica
                    </dt>
                    <dd
                        :class="
                            actividad
                                ? 'mt-0.5 font-medium'
                                : 'text-tinta-suave'
                        "
                    >
                        {{ actividad ?? 'Sin definir' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-tinta-suave">Ubicación</dt>
                    <dd class="mt-0.5 flex items-start gap-1.5 font-medium">
                        <MapPin
                            class="mt-0.5 size-3.5 shrink-0 text-tinta-suave"
                            aria-hidden="true"
                        />
                        {{ ubicacion }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-tinta-suave">
                        Número de empleados
                    </dt>
                    <dd
                        :class="
                            empresa.numero_empleados
                                ? 'mt-0.5 flex items-center gap-1.5 font-medium'
                                : 'mt-0.5 text-tinta-suave'
                        "
                    >
                        <Users
                            v-if="empresa.numero_empleados"
                            class="size-3.5 text-tinta-suave"
                            aria-hidden="true"
                        />
                        {{ empresa.numero_empleados ?? 'No lo indicó' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-tinta-suave">Teléfono</dt>
                    <dd class="mt-0.5">
                        <a
                            v-if="empresa.telefono"
                            :href="`tel:${empresa.telefono}`"
                            :class="`${enlace} inline-flex items-center gap-1.5`"
                        >
                            <Phone class="size-3.5" aria-hidden="true" />
                            {{ empresa.telefono }}
                        </a>
                        <span v-else class="text-tinta-suave"
                            >No lo indicó</span
                        >
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-tinta-suave">Sitio web</dt>
                    <dd class="mt-0.5 min-w-0">
                        <a
                            v-if="web"
                            :href="web.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            :class="`${enlace} inline-flex max-w-full items-center gap-1.5`"
                        >
                            <Globe
                                class="size-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            <span class="truncate">{{ web.texto }}</span>
                        </a>
                        <span v-else class="text-tinta-suave"
                            >No lo indicó</span
                        >
                    </dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-linea bg-white p-5">
            <h2 class="flex items-center gap-2 font-semibold">
                <User class="size-4 text-marca" aria-hidden="true" />
                Contacto principal
            </h2>
            <div v-if="principal" class="mt-4 flex items-start gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-marca-suave text-sm font-semibold text-marca"
                    aria-hidden="true"
                    >{{
                        principal.name
                            .split(' ')
                            .slice(0, 2)
                            .map((p) => p[0])
                            .join('')
                            .toUpperCase()
                    }}</span
                >
                <div class="min-w-0 flex-1 text-sm">
                    <p class="font-semibold">{{ principal.name }}</p>
                    <p class="text-tinta-suave">
                        {{ principal.cargo ?? 'Sin cargo' }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1.5">
                        <a
                            :href="`mailto:${principal.email}`"
                            :class="`${enlace} inline-flex min-w-0 items-center gap-1.5`"
                        >
                            <Mail
                                class="size-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            <span class="break-all">{{ principal.email }}</span>
                        </a>
                        <a
                            v-if="principal.telefono"
                            :href="`tel:${principal.telefono}`"
                            :class="`${enlace} inline-flex items-center gap-1.5`"
                        >
                            <Phone class="size-3.5" aria-hidden="true" />
                            {{ principal.telefono }}
                        </a>
                    </div>
                </div>
            </div>
            <p v-else class="mt-3 text-sm text-tinta-suave">
                Esta empresa no tiene cuenta principal por ahora.
            </p>
        </section>

        <p
            class="rounded-xl border border-dashed border-linea-fuerte px-4 py-2.5 text-xs text-tinta-suave"
        >
            Solo lectura. Si un dato está mal, avísale al equipo de NuevasTIC.
        </p>
    </div>
</template>
