{{--
    Símbolo de NuevasTIC (aro azul con la "C" y el punto verde), el mismo del
    Login (components/marca/Logo.vue) y de public/favicon.svg. Va en línea
    para que Chromium no tenga que buscar archivos. $tam: alto en pt.
--}}
<svg viewBox="0 0 110 110" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="NuevasTIC" style="width: {{ $tam ?? 22 }}pt; height: {{ $tam ?? 22 }}pt; flex: none">
    <circle cx="55" cy="55" r="55" fill="#14154d" />
    <circle cx="55" cy="55" r="44" fill="#e6e8ea" />
    <circle cx="55" cy="55" r="28" fill="#14154d" />
    <path d="M55 35H70L98 24V87L70 76H55Z" fill="#14154d" />
    <circle cx="57" cy="55" r="14" fill="#6ae45b" />
    <path d="M85 51L100 43Q104 55 100 67L85 59Z" fill="#6ae45b" />
</svg>
