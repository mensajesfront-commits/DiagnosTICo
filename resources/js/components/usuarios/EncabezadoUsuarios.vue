<script setup lang="ts">
/**
 * Encabezado de A5 y A5.1: título, pestañas "Usuarios · Roles" y el botón de
 * la derecha ("+ Invitar usuario" o "+ Crear rol", por el slot).
 */
import { Link } from '@inertiajs/vue3';
import { Shield, Users } from '@lucide/vue';
import { rutas } from '@/lib/rutas';
import { cn } from '@/lib/utils';

defineProps<{ pestana: 'usuarios' | 'roles' }>();

const pestanas = [
    {
        clave: 'usuarios',
        titulo: 'Usuarios',
        href: rutas.usuarios.lista(),
        icono: Users,
    },
    {
        clave: 'roles',
        titulo: 'Roles',
        href: rutas.usuarios.roles(),
        icono: Shield,
    },
] as const;
</script>

<template>
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3">
            <span
                class="flex size-11 items-center justify-center rounded-xl bg-marca-suave text-marca"
                aria-hidden="true"
            >
                <Users class="size-5" />
            </span>
            <div>
                <h1 class="text-2xl leading-tight font-semibold">
                    Usuarios y roles
                </h1>
                <p class="mt-0.5 text-sm text-tinta-suave">
                    Quién puede entrar al sistema y qué puede hacer cada rol
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <nav
                aria-label="Secciones de Usuarios y roles"
                class="inline-flex rounded-xl border border-linea bg-white p-1"
            >
                <Link
                    v-for="item in pestanas"
                    :key="item.clave"
                    :href="item.href"
                    :aria-current="item.clave === pestana ? 'page' : undefined"
                    :class="
                        cn(
                            'inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-sm transition',
                            item.clave === pestana
                                ? 'bg-marca font-medium text-white'
                                : 'text-tinta-suave hover:bg-lienzo hover:text-tinta',
                        )
                    "
                >
                    <component
                        :is="item.icono"
                        class="size-4"
                        aria-hidden="true"
                    />
                    {{ item.titulo }}
                </Link>
            </nav>
            <slot />
        </div>
    </header>
</template>
