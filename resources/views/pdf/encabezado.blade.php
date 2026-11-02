{{--
    E10 · Encabezado de cada página del informe PDF (se repite en las hojas
    de continuación). Chromium lo dibuja aparte: los estilos van en línea.
--}}
<div style="width: 100%; box-sizing: border-box; padding: 0 18mm; font-family: Arial, sans-serif; font-size: 7.5px; color: #5f6470;">
    <div style="padding-bottom: 4px; border-bottom: 1px solid #1e2533; display: flex; justify-content: space-between;">
    <b style="color: #1e2533">Diagnóstico Empresarial</b>
    <span>{{ $titulo }}</span>
    </div>
</div>
