<script setup lang="ts">
/**
 * Estructura de todas las pantallas con sesión iniciada: menú lateral oscuro
 * a la izquierda y el contenido sobre el fondo gris cálido. En pantallas
 * pequeñas el menú se abre con un botón.
 *
 * `migas` dibuja la ruta de navegación pequeña de arriba
 * ("Diagnósticos › Abogados › Diagnóstico general").
 */
import { Link, router } from '@inertiajs/vue3';
import { Menu, X } from '@lucide/vue';
import { ref } from 'vue';
import MenuLateral from '@/components/base/MenuLateral.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

withDefaults(defineProps<{ migas?: BreadcrumbItem[] }>(), {
    migas: () => [],
});

const menuMovilAbierto = ref(false);

router.on('navigate', () => {
    menuMovilAbierto.value = false;
});
</script>

<template>
    <div class="flex min-h-screen bg-lienzo text-tinta">
        <div class="hidden shrink-0 bg-menu lg:block">
            <div class="sticky top-0 h-screen">
                <MenuLateral />
            </div>
        </div>

        <div
            v-if="menuMovilAbierto"
            class="fixed inset-0 z-40 flex lg:hidden"
            role="dialog"
            aria-modal="true"
        >
            <MenuLateral />
            <button
                type="button"
                class="flex-1 bg-menu/45"
                aria-label="Cerrar menú"
                @click="menuMovilAbierto = false"
            />
        </div>

        <main class="flex min-w-0 flex-1 flex-col">
            <div
                class="flex items-center gap-3 border-b border-linea bg-white px-4 py-3 lg:hidden"
            >
                <button
                    type="button"
                    class="rounded p-1 text-tinta hover:bg-lienzo"
                    :aria-label="
                        menuMovilAbierto ? 'Cerrar menú' : 'Abrir menú'
                    "
                    @click="menuMovilAbierto = !menuMovilAbierto"
                >
                    <X v-if="menuMovilAbierto" class="size-5" />
                    <Menu v-else class="size-5" />
                </button>
                <span class="text-sm font-semibold">
                    Diagnóstico
                    <span class="text-marca">Empresarial</span>
                </span>
            </div>

            <nav
                v-if="migas.length > 0"
                aria-label="Ruta de navegación"
                class="px-6 pt-4 text-xs text-tinta-suave"
            >
                <template v-for="(miga, i) in migas" :key="i">
                    <Link
                        v-if="i < migas.length - 1"
                        :href="miga.href"
                        class="underline hover:text-tinta"
                    >
                        {{ miga.title }}
                    </Link>
                    <span v-else>{{ miga.title }}</span>
                    <span v-if="i < migas.length - 1" class="mx-1">›</span>
                </template>
            </nav>

            <slot />
        </main>

        <Toaster />
    </div>
</template>
