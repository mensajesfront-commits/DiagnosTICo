<script setup lang="ts">
/**
 * Encabezado de A5 y A5.1: título, pestañas "Usuarios · Roles" y el botón de
 * la derecha ("+ Invitar usuario" o "+ Crear rol", por el slot).
 */
import { Link } from '@inertiajs/vue3';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';

defineProps<{ pestana: 'usuarios' | 'roles' }>();

const pestanas = [
    { clave: 'usuarios', titulo: 'Usuarios', href: rutas.usuarios.lista() },
    { clave: 'roles', titulo: 'Roles', href: rutas.usuarios.roles() },
] as const;
</script>

<template>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Usuarios y roles</h1>
            <p class="mt-1 text-sm text-tinta-suave">
                Quién puede entrar al sistema y qué puede hacer cada rol
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <nav
                aria-label="Secciones de Usuarios y roles"
                class="inline-flex rounded-lg bg-lienzo-oscuro p-1"
            >
                <Link
                    v-for="item in pestanas"
                    :key="item.clave"
                    :href="item.href"
                    :aria-current="item.clave === pestana ? 'page' : undefined"
                    :class="
                        cn(
                            'rounded-md px-4 py-1.5 text-sm',
                            item.clave === pestana
                                ? 'bg-white font-medium text-tinta shadow-sm'
                                : 'text-tinta-suave hover:text-tinta',
                        )
                    "
                >
                    {{ item.titulo }}
                </Link>
            </nav>
            <slot />
        </div>
    </header>
</template>
