@props(['url'])
{{-- Encabezado de los correos: banda azul con el nombre (sin imágenes: Gmail las bloquea a veces). --}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: block;">
<table class="marca" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<div class="marca-nombre">{{ mb_strtoupper(trim($slot)) }}<span class="marca-punto">.</span></div>
<div class="marca-lema">Diagnóstico de marketing digital · NuevasTIC</div>
</td>
</tr>
</table>
</a>
</td>
</tr>
