<script setup lang="ts">
/**
 * Menú lateral oscuro del Administrador y de la empresa (A1, E1–E11):
 * marca arriba, secciones con submenú plegable y, abajo, la cuenta con
 * "Mi perfil" y "Cerrar sesión" (HU-005 CA-003).
 */
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
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

const page = usePage();
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
    abiertos.value = abiertos.value.includes(titulo)
        ? abiertos.value.filter((t) => t !== titulo)
        : [...abiertos.value, titulo];
}

const claseItem = (esActivo: boolean) =>
    cn(
        'flex w-full items-center justify-between rounded-md px-3 py-2 text-sm transition-colors',
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
        class="flex h-full w-60 shrink-0 flex-col bg-menu px-3 py-6"
        aria-label="Menú principal"
    >
        <Link
            :href="items[0]?.href ?? '/'"
            class="mb-8 flex items-center gap-3 px-3 leading-tight"
        >
            <Logo class="size-10" />
            <span class="text-xl font-semibold text-white">Captter</span>
        </Link>

        <ul class="flex flex-1 flex-col gap-1">
            <li v-for="item in items" :key="item.titulo">
                <template v-if="item.hijos">
                    <button
                        type="button"
                        :class="claseItem(activo(item))"
                        :aria-expanded="abiertos.includes(item.titulo)"
                        @click="alternar(item.titulo)"
                    >
                        {{ item.titulo }}
                        <ChevronRight
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
                        v-show="abiertos.includes(item.titulo)"
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
                    :aria-current="activo(item) ? 'page' : undefined"
                >
                    {{ item.titulo }}
                </Link>
            </li>
        </ul>

        <div class="border-t border-menu-activo px-3 pt-4 text-xs">
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
