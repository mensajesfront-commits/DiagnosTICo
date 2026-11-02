{{--
    E10 · Pie de cada página del informe PDF, con el número de página.
    Chromium lo dibuja aparte del cuerpo: no ve las fuentes ni los estilos
    del informe, por eso todo va en línea.
--}}
<div style="width: 100%; box-sizing: border-box; padding: 0 18mm; font-family: Arial, sans-serif; font-size: 7.5px; color: #5f6470;">
    <div style="padding-top: 4px; border-top: 1px solid #e3e2dd; display: flex; justify-content: space-between;">
    <span>NuevasTIC · Captter · Documento confidencial</span>
    <span>Página @pageNumber de @totalPages</span>
    </div>
</div>
