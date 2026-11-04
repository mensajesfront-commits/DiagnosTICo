<script setup lang="ts">
/**
 * Captcha del registro (L2) con Cloudflare Turnstile. Carga el script solo
 * en el navegador (con SSR no hay `window` al armar la página) y deja el
 * token en v-model. El token sirve una vez: `reiniciar()` pide otro después
 * de un error del formulario.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ llave: string }>();

const token = defineModel<string>({ default: '' });

const contenedor = ref<HTMLElement | null>(null);
let widget: string | null = null;

type Turnstile = {
    render: (el: HTMLElement, opciones: Record<string, unknown>) => string;
    reset: (id: string) => void;
    remove: (id: string) => void;
};

const SCRIPT =
    'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

function cargarScript(): Promise<Turnstile> {
    const w = window as unknown as { turnstile?: Turnstile };

    if (w.turnstile) {
        return Promise.resolve(w.turnstile);
    }

    return new Promise((resolver, rechazar) => {
        let script = document.querySelector<HTMLScriptElement>(
            `script[src="${SCRIPT}"]`,
        );

        if (!script) {
            script = document.createElement('script');
            script.src = SCRIPT;
            script.async = true;
            document.head.appendChild(script);
        }

        script.addEventListener('load', () =>
            w.turnstile ? resolver(w.turnstile) : rechazar(),
        );
        script.addEventListener('error', () => rechazar());
    });
}

const error = ref(false);

onMounted(async () => {
    try {
        const turnstile = await cargarScript();

        if (!contenedor.value) {
            return;
        }

        widget = turnstile.render(contenedor.value, {
            sitekey: props.llave,
            language: 'es',
            theme: 'light',
            callback: (valor: string) => (token.value = valor),
            'expired-callback': () => (token.value = ''),
            'error-callback': () => (token.value = ''),
        });
    } catch {
        error.value = true;
    }
});

onBeforeUnmount(() => {
    const w = window as unknown as { turnstile?: Turnstile };

    if (widget && w.turnstile) {
        w.turnstile.remove(widget);
    }
});

function reiniciar(): void {
    const w = window as unknown as { turnstile?: Turnstile };
    token.value = '';

    if (widget && w.turnstile) {
        w.turnstile.reset(widget);
    }
}

defineExpose({ reiniciar });
</script>

<template>
    <div>
        <div ref="contenedor" class="min-h-[65px]" />
        <p v-if="error" class="text-xs text-aviso" role="alert">
            No se pudo cargar la verificación de seguridad. Revisa tu conexión y
            recarga la página.
        </p>
    </div>
</template>
