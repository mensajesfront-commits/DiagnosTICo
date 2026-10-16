<script setup lang="ts">
/**
 * L2b · Verifica tu correo (después de "Crear cuenta" en L2).
 *
 * La cuenta queda creada pero no entra hasta confirmar el correo: así no se
 * crean cuentas con correos que no existen. Llega un correo con "Verificar
 * mi correo"; el enlace lleva a Iniciar sesión con el correo ya escrito.
 *
 * "Reenviar el correo" vuelve a mandarlo (3 por minuto). Esta pantalla no
 * cambia sola: la persona sigue desde el enlace del correo.
 */
import { Head, Link, useForm } from '@inertiajs/vue3';
import { MailCheck } from '@lucide/vue';
import Aviso from '@/components/base/Aviso.vue';
import Boton from '@/components/base/Boton.vue';
import { rutas } from '@/lib/rutas';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Verifica tu correo',
        description: 'Falta un paso para activar la cuenta de tu empresa.',
    },
});

defineProps<{
    correo: string;
    status?: string;
}>();

const form = useForm({});

function reenviar(): void {
    form.post(rutas.verificacion.reenviar, { preserveScroll: true });
}
</script>

<template>
    <Head title="Verifica tu correo" />

    <div class="flex flex-col gap-5">
        <div class="flex items-start gap-3 rounded-lg bg-lienzo px-4 py-4">
            <MailCheck
                class="mt-0.5 size-6 shrink-0 text-marca"
                aria-hidden="true"
            />
            <div class="text-sm">
                <p class="font-medium">Esperando la verificación</p>
                <p class="mt-1 text-tinta-suave">
                    Te enviamos un correo a
                    <strong class="text-tinta">{{ correo }}</strong
                    >. Ábrelo y pulsa
                    <strong class="text-tinta">Verificar mi correo</strong>.
                    Después podrás iniciar sesión con los datos que acabas de
                    registrar.
                </p>
            </div>
        </div>

        <ol class="flex flex-col gap-1.5 text-sm text-tinta-suave">
            <li>1. Revisa tu bandeja de entrada (y la carpeta de spam).</li>
            <li>2. Pulsa el botón del correo. El enlace vence en 24 horas.</li>
            <li>3. Entra con tu correo y tu contraseña.</li>
        </ol>

        <Aviso v-if="status" tono="exito">{{ status }}</Aviso>

        <Boton
            variante="secundario"
            class="w-full"
            :cargando="form.processing"
            @click="reenviar"
        >
            Reenviar el correo
        </Boton>

        <Link
            :href="login()"
            class="text-center text-sm text-marca underline underline-offset-2 hover:text-marca-hover"
        >
            ← Volver a iniciar sesión
        </Link>
    </div>
</template>
