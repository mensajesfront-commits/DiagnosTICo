<script setup lang="ts">
/**
 * Menú lateral oscuro del Administrador y de la empresa (A1, E1–E11):
 * marca arriba, secciones con submenú plegable y, abajo, la cuenta con
 * "Mi perfil" y "Cerrar sesión" (HU-005 CA-003).
 *
 * En pantallas grandes se pliega a una barra angosta con los iconos (el
 * nombre de cada sección sale al pasar el ratón) con el botón de arriba, y
 * se recuerda la elección (`useMenuPlegado`). En el menú del celular
 * (`plegable = false`) siempre se ve completo.
 */
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronRight,
    LogOut,
    PanelLeftClose,
    PanelLeftOpen,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useMenuPlegado } from '@/composables/useMenuPlegado';
import Logo from '@/components/marca/Logo.vue';
import {
    filtrarMenu,
    menuAdministrador,
    menuEmpresa,
    rolesDeEmpresa,
} from '@/lib/menu';
import type { ItemMenu } from '@/lib/menu';
import { cn, toUrl } from '@/lib/utils';
import { logout } from '@/routes';
import { edit as perfil } from '@/routes/profile';

const props = withDefaults(defineProps<{ plegable?: boolean }>(), {
    plegable: true,
});

const page = usePage();
const menu = useMenuPlegado();
const plegado = computed(() => props.plegable && menu.plegado.value);
const { isCurrentOrParentUrl } = useCurrentUrl();

const usuario = computed(() => page.props.auth.user);
const rol = computed(() => page.props.auth.rol);

// Empresa y Colaborador usan el menú de empresa; el Administrador y los
// roles creados en A5.1 usan el del Administrador, filtrado por permisos.
const items = computed(() =>
    filtrarMenu(
        rolesDeEmpresa.includes(rol.value ?? '')
            ? menuEmpresa
            : menuAdministrador,
        page.props.auth.permisos,
        rol.value ?? null,
    ),
);

function activo(item: ItemMenu): boolean {
    if (item.hijos) {
        return item.hijos.some(activo);
    }

    return item.href !== undefined && isCurrentOrParentUrl(item.href);
}

// Submenús abiertos; uno arranca abierto si contiene la página actual.
const abiertos = ref<string[]>(
    items.value
        .filter((item) => item.hijos && activo(item))
        .map((i) => i.titulo),
);

function alternar(titulo: string): void {
    // Con el menú plegado, un grupo con submenú abre el menú para verlo.
    if (plegado.value) {
        menu.alternar();
    }

    abiertos.value = abiertos.value.includes(titulo)
        ? abiertos.value.filter((t) => t !== titulo)
        : [...abiertos.value, titulo];
}

const claseItem = (esActivo: boolean) =>
    cn(
        'flex w-full items-center gap-3 rounded-md py-2 text-sm transition-colors',
        plegado.value ? 'justify-center px-0' : 'px-3',
        esActivo
            ? 'bg-menu-activo font-medium text-white'
            : 'text-menu-texto hover:bg-menu-activo/60 hover:text-white',
    );

function cerrarSesion(): void {
    router.flushAll();
}
</script>

<template>
    <nav
        :class="
            cn(
                'flex h-full shrink-0 flex-col bg-menu py-6 transition-[width] duration-200',
                plegado ? 'w-16 px-2' : 'w-60 px-3',
            )
        "
        aria-label="Menú principal"
    >
        <div
            :class="
                cn(
                    'mb-8 flex items-center',
                    plegado
                        ? 'flex-col gap-3'
                        : 'justify-between gap-2 pr-1 pl-3',
                )
            "
        >
            <Link
                :href="items[0]?.href ?? '/'"
                class="flex items-center gap-3 leading-tight"
                :aria-label="plegado ? 'CAPTTER · Inicio' : undefined"
            >
                <Logo :class="plegado ? 'size-9' : 'size-10'" />
                <span v-if="!plegado" class="text-xl font-semibold text-white"
                    >CAPTTER</span
                >
            </Link>
            <button
                v-if="plegable"
                type="button"
                class="rounded-md p-1.5 text-menu-texto hover:bg-menu-activo/60 hover:text-white"
                :title="plegado ? 'Abrir menú' : 'Cerrar menú'"
                :aria-label="plegado ? 'Abrir menú' : 'Cerrar menú'"
                :aria-expanded="!plegado"
                data-test="plegar-menu"
                @click="menu.alternar()"
            >
                <PanelLeftOpen v-if="plegado" class="size-5" />
                <PanelLeftClose v-else class="size-5" />
            </button>
        </div>

        <ul class="flex flex-1 flex-col gap-1">
            <li v-for="item in items" :key="item.titulo">
                <template v-if="item.hijos">
                    <button
                        type="button"
                        :class="claseItem(activo(item))"
                        :title="plegado ? item.titulo : undefined"
                        :aria-expanded="abiertos.includes(item.titulo)"
                        @click="alternar(item.titulo)"
                    >
                        <component
                            :is="item.icono"
                            v-if="item.icono"
                            class="size-5 shrink-0"
                            aria-hidden="true"
                        />
                        <span :class="plegado ? 'sr-only' : 'flex-1 text-left'">
                            {{ item.titulo }}
                        </span>
                        <ChevronRight
                            v-if="!plegado"
                            :class="
                                cn(
                                    'size-3.5 transition-transform',
                                    abiertos.includes(item.titulo) &&
                                        'rotate-90',
                                )
                            "
                        />
                    </button>
                    <ul
                        v-show="!plegado && abiertos.includes(item.titulo)"
                        class="mt-1 ml-3 flex flex-col gap-1 border-l border-menu-activo pl-2"
                    >
                        <li v-for="hijo in item.hijos" :key="hijo.titulo">
                            <Link
                                v-if="hijo.href"
                                :href="hijo.href"
                                :class="claseItem(activo(hijo))"
                                :aria-current="
                                    activo(hijo) ? 'page' : undefined
                                "
                            >
                                {{ hijo.titulo }}
                            </Link>
                        </li>
                    </ul>
                </template>
                <Link
                    v-else-if="item.href"
                    :href="item.href"
                    :class="claseItem(activo(item))"
                    :title="plegado ? item.titulo : undefined"
                    :aria-current="activo(item) ? 'page' : undefined"
                >
                    <component
                        :is="item.icono"
                        v-if="item.icono"
                        class="size-5 shrink-0"
                        aria-hidden="true"
                    />
                    <span :class="plegado && 'sr-only'">{{ item.titulo }}</span>
                </Link>
            </li>
        </ul>

        <div
            v-if="plegado"
            class="flex flex-col items-center gap-3 border-t border-menu-activo pt-4"
        >
            <Link
                :href="perfil()"
                :title="`${usuario.name} · Mi perfil`"
                :class="
                    cn(
                        'flex size-9 items-center justify-center rounded-full bg-menu-activo text-sm font-semibold text-white hover:ring-2 hover:ring-white/40',
                        isCurrentOrParentUrl(toUrl(perfil())) &&
                            'ring-2 ring-white/60',
                    )
                "
            >
                {{ usuario.name.charAt(0).toUpperCase() }}
                <span class="sr-only">Mi perfil</span>
            </Link>
            <Link
                :href="logout()"
                as="button"
                title="Cerrar sesión"
                class="rounded-md p-1.5 text-menu-texto hover:bg-menu-activo/60 hover:text-white"
                data-test="cerrar-sesion"
                @click="cerrarSesion"
            >
                <LogOut class="size-5" />
                <span class="sr-only">Cerrar sesión</span>
            </Link>
        </div>
        <div v-else class="border-t border-menu-activo px-3 pt-4 text-xs">
            <p class="text-sm font-semibold text-white">{{ usuario.name }}</p>
            <p class="mt-0.5 text-menu-texto">
                {{ rol ?? 'Sin rol' }} ·
                <Link
                    :href="perfil()"
                    :class="
                        cn(
                            'hover:text-white',
                            isCurrentOrParentUrl(toUrl(perfil())) &&
                                'text-white underline',
                        )
                    "
                >
                    Mi perfil
                </Link>
            </p>
            <Link
                :href="logout()"
                as="button"
                class="mt-3 text-menu-texto hover:text-white"
                data-test="cerrar-sesion"
                @click="cerrarSesion"
            >
                Cerrar sesión
            </Link>
        </div>
    </nav>
</template>
