<script setup lang="ts">
/**
 * Páginas de error (403, 404, 500, 503) con la identidad de Captter.
 *
 * Las arma bootstrap/app.php (`withExceptions`) en vez de las de Laravel.
 * Sin menú ni sesión: en un 404 de una ruta que no existe, Laravel no abre
 * la sesión. "Ir al inicio" lleva a "/", que manda al login o al inicio de
 * cada rol. El 500 solo se ve con APP_DEBUG apagado; el 419 no llega aquí
 * (vuelve atrás con un aviso).
 *
 * El correo de soporte sale de SOPORTE_EMAIL (contacto@captter.com).
 */
import { Head } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Construction,
    House,
    LifeBuoy,
    SearchX,
    ServerCrash,
    ShieldX,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import Boton from '@/components/base/Boton.vue';
import Logo from '@/components/marca/Logo.vue';
import { useCorreoSoporte } from '@/lib/soporte';

const soporte = useCorreoSoporte();

const props = defineProps<{ codigo: number }>();

type Info = {
    icono: Component;
    titulo: string;
    texto: string;
    tono: string;
};

const errores: Record<number, Info> = {
    403: {
        icono: ShieldX,
        titulo: 'No tienes acceso a esta página',
        texto: 'Tu cuenta no tiene permiso para ver esto. Si crees que deberías tenerlo, pídeselo a quien administra tu cuenta.',
        tono: 'bg-alerta-suave text-alerta',
    },
    404: {
        icono: SearchX,
        titulo: 'No encontramos esta página',
        texto: 'Puede que el enlace esté mal escrito o que lo que buscas ya no exista.',
        tono: 'bg-marca-suave text-marca',
    },
    500: {
        icono: ServerCrash,
        titulo: 'Algo salió mal de nuestro lado',
        texto: 'No es tu culpa y no perdiste nada de lo que ya estaba guardado. Inténtalo de nuevo en unos minutos.',
        tono: 'bg-aviso-suave text-aviso',
    },
    503: {
        icono: Construction,
        titulo: 'Estamos en mantenimiento',
        texto: 'Estamos haciendo mejoras en Captter. Vuelve en un momento.',
        tono: 'bg-alerta-suave text-alerta',
    },
};

const info = computed(() => errores[props.codigo] ?? errores[500]);

function volver(): void {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/';
    }
}
</script>

<template>
    <Head :title="info.titulo" />

    <div
        class="relative flex min-h-dvh flex-col items-center justify-center overflow-hidden bg-lienzo px-4 py-10 text-tinta"
    >
        <!-- Fondo decorativo -->
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div
                class="absolute -top-40 left-1/2 size-[36rem] -translate-x-1/2 rounded-full bg-marca-suave blur-3xl"
            />
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgb(30_41_59/0.07)_1px,transparent_0)] bg-size-[22px_22px]"
            />
        </div>

        <main class="relative flex w-full max-w-lg flex-col items-center">
            <a
                href="/"
                class="mb-8 flex items-center gap-2.5 rounded-md text-base font-semibold"
            >
                <Logo class="size-8" />
                <span class="text-xl tracking-wide">CAPTTER</span>
            </a>

            <div
                class="w-full rounded-2xl border border-linea bg-white p-6 text-center shadow-[0_12px_40px_-16px_rgb(30_41_59/0.18)] sm:p-10"
            >
                <span
                    :class="[
                        'mx-auto flex size-16 items-center justify-center rounded-2xl',
                        info.tono,
                    ]"
                    aria-hidden="true"
                >
                    <component :is="info.icono" class="size-8" />
                </span>

                <p
                    class="mt-5 font-mono text-sm font-semibold tracking-widest text-tinta-suave"
                >
                    ERROR {{ codigo }}
                </p>
                <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">
                    {{ info.titulo }}
                </h1>
                <p class="mx-auto mt-3 max-w-sm text-sm text-tinta-suave">
                    {{ info.texto }}
                </p>

                <div
                    class="mt-8 flex flex-col-reverse justify-center gap-3 sm:flex-row"
                >
                    <Boton variante="secundario" @click="volver">
                        <ArrowLeft class="size-4" aria-hidden="true" />
                        Volver
                    </Boton>
                    <Boton href="/" archivo>
                        <House class="size-4" aria-hidden="true" />
                        Ir al inicio
                    </Boton>
                </div>
            </div>

            <p
                class="mt-6 flex items-center gap-1.5 text-center text-xs text-tinta-suave"
            >
                <LifeBuoy class="size-3.5 shrink-0" aria-hidden="true" />
                <span>
                    ¿Sigue pasando? Escribe a
                    <a
                        :href="`mailto:${soporte}?subject=${encodeURIComponent(`Error ${codigo} en Captter`)}`"
                        class="underline underline-offset-2"
                        >{{ soporte }}</a
                    >
                </span>
            </p>
        </main>
    </div>
</template>
