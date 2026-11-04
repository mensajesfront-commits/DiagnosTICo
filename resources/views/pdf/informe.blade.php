{{--
    E10 · Informe de Diagnóstico y Estrategia de Marketing Digital (HU-066, RN-024).

    Sigue la "Propuesta de reestructuración del informe" (2 de noviembre de
    2026), fase 1: todo sale de las respuestas de la empresa y del análisis de
    la IA ya guardado (App\Support\DatosInforme). Lo que Captter no recoge
    (capturas de canales, competidores, línea base de los KPI) no se inventa:
    se marca como información no disponible y se dice cómo obtenerla.

    Portada · 1 Ficha técnica y alcance · 2 Resumen ejecutivo · 3 Metodología
    y criterios · 4 Resultados generales · 5 Comparación y priorización ·
    6 Diagnóstico por categoría · 7 Análisis estratégico DOFA · 8 Conclusiones
    · 9 Plan estratégico · 10 Cronograma · 11 Indicadores (KPI) · 12 Próximos
    pasos y anexos.

    Diseño de la propuesta: azul #172B46, naranja #F28C28, Montserrat (títulos)
    e Inter (texto), incrustadas porque Chromium no tiene internet. Los nombres
    de nivel son los formales del informe (Niveles::NOMBRES_INFORME); la
    pantalla conserva los del wireframe. El encabezado y "Página N de M" los
    ponen pdf/encabezado.blade.php y pdf/pie.blade.php.
--}}
@php
    $colores = [
        'critico' => ['#C94C4C', '#F8E5E5', '#9A3333'],
        'mejorar' => ['#E7A13B', '#FCF1DE', '#8A5A12'],
        'camino' => ['#4B91C6', '#E4EFF8', '#2B5F88'],
        'sigue' => ['#3C9A70', '#E1F2EA', '#2A6E4F'],
    ];
    $i = $informe;
    $color = $colores[$i['nivel']] ?? $colores['critico'];
    $circ = 2 * M_PI * 40;
    $nombreNivel = fn (string $n) => \App\Support\Niveles::nombreInforme($n);
    $etiqueta = fn (string $n) => '<span class="etiqueta" style="background:'.$colores[$n][1].';color:'.$colores[$n][2].'">'.e($nombreNivel($n)).'</span>';
    $num = fn (float|int $n) => rtrim(rtrim(number_format((float) $n, 1, ',', '.'), '0'), ',');
    $variacion = fn (?int $v) => $v === null ? '—' : ($v > 0 ? "+{$v}" : ($v < 0 ? '−'.abs($v) : '0'));
    $claseVar = fn (?int $v) => ($v ?? 0) > 0 ? 'sube' : (($v ?? 0) < 0 ? 'baja' : 'igual');
    $vacio = fn (string $t, string $si) => $t !== '' ? e($t) : '<span class="pendiente">'.e($si).'</span>';
    $nCat = count($i['categorias']);
    $usados = collect($i['metodos'])->filter(fn ($m) => $m['usado_en'] !== [])->count();
    $bajas = collect($i['categorias'])->whereIn('nivel', ['critico', 'mejorar'])->count();
    $primera = $i['por_prioridad'][0] ?? null;
    $ultima = $nCat > 1 ? $i['por_prioridad'][$nCat - 1] : null;
    $tipos = [
        'prioritaria' => ['Acciones prioritarias', 'La acción principal de las 3 categorías con menor calificación. Se empiezan primero.'],
        'complementaria' => ['Acciones complementarias', 'La acción principal de las demás categorías, cuando las prioritarias estén en marcha.'],
        'seguimiento' => ['Acciones de seguimiento', 'Las demás recomendaciones de cada categoría, para consolidar lo logrado.'],
    ];
    $sinDato = 'Información no disponible';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe de diagnóstico · {{ $i['empresa']['nombre'] }} · Medición {{ $i['numero'] }}</title>
    <style>
        {!! \App\Support\RecursosPdf::fuentesInforme() !!}

        :root { --azul: #172B46; --naranja: #F28C28; --gris: #F3F5F8; --linea: #DDE2EA; --suave: #5B6576; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', sans-serif; color: #1F2733; font-size: 10pt; line-height: 1.5; font-variant-numeric: tabular-nums; }
        p { margin: 0 0 6pt; }
        ul, ol { margin: 0 0 6pt; padding-left: 15pt; }
        li { margin-bottom: 2pt; }
        b, strong { font-weight: 600; }

        h1, h2, h3, h4, .titulo { font-family: 'Montserrat', sans-serif; color: var(--azul); }
        h2 { font-size: 18pt; font-weight: 700; line-height: 1.2; margin: 0 0 3pt; }
        h2 .n { color: var(--naranja); margin-right: 6pt; }
        .bajada { color: var(--suave); font-size: 10pt; margin: 0 0 8pt; padding-bottom: 8pt; border-bottom: 2px solid var(--naranja); }
        h3 { font-size: 12.5pt; font-weight: 600; margin: 14pt 0 5pt; }
        h4 { font-size: 10.5pt; font-weight: 600; margin: 9pt 0 3pt; }
        h2, h3, h4 { break-after: avoid; }

        .seccion { break-before: page; }
        .bloque { break-inside: avoid; }
        .suave { color: var(--suave); }
        .nota { font-size: 8.5pt; color: var(--suave); }
        .pendiente { color: var(--suave); font-style: italic; }

        .etiqueta { display: inline-block; border-radius: 3pt; padding: 0 6pt; font-size: 8pt; font-weight: 600; white-space: nowrap; line-height: 1.7; }
        .metodo { background: #E4EAF3; color: var(--azul); }
        .sube { color: #2A6E4F; } .baja { color: #9A3333; } .igual { color: var(--suave); }

        .destacado { background: var(--gris); border-left: 3pt solid var(--azul); padding: 8pt 11pt; margin: 6pt 0 8pt; }
        .alerta { background: #FEF3E7; border-left: 3pt solid var(--naranja); padding: 8pt 11pt; margin: 6pt 0 8pt; }
        .tarjeta { border: 1px solid var(--linea); border-radius: 5pt; padding: 9pt 11pt; }

        table { width: 100%; border-collapse: collapse; margin: 4pt 0 8pt; }
        thead { display: table-header-group; }
        th { background: var(--azul); color: #fff; text-align: left; font-weight: 600; font-size: 8.5pt; padding: 5pt 6pt; }
        td { padding: 5pt 6pt; border-bottom: 1px solid var(--linea); font-size: 9pt; vertical-align: top; }
        tbody tr:nth-child(even) td { background: #FAFBFC; }
        tr { break-inside: avoid; }
        td.c, th.c { text-align: center; }
        td.r, th.r { text-align: right; }
        .barra { height: 5pt; background: #E8EBF0; border-radius: 3pt; overflow: hidden; margin-top: 3pt; }
        .barra i { display: block; height: 100%; }

        /* Portada */
        .portada { background: var(--azul); color: #fff; padding: 34pt 34pt 30pt; min-height: 225mm; display: flex; flex-direction: column; }
        .portada .marca { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 11pt; letter-spacing: .04em; }
        .portada .marca span { color: var(--naranja); }
        .portada .ante { font-size: 8.5pt; letter-spacing: .14em; text-transform: uppercase; color: #C9D3E2; margin-top: 54pt; }
        .portada h1 { color: #fff; font-size: 26pt; font-weight: 700; line-height: 1.15; margin: 8pt 0 6pt; }
        .portada .raya { width: 54pt; height: 3pt; background: var(--naranja); margin: 12pt 0 16pt; }
        .portada .empresa { font-family: 'Montserrat', sans-serif; font-size: 17pt; font-weight: 600; }
        .portada dl { margin: 20pt 0 0; display: grid; grid-template-columns: 105pt 1fr; gap: 4pt 10pt; font-size: 9.5pt; }
        .portada dt { color: #C9D3E2; }
        .portada dd { margin: 0; }
        .portada .calificacion { margin-top: auto; display: flex; align-items: center; gap: 16pt; background: rgba(255,255,255,.07); border-radius: 6pt; padding: 12pt 14pt; }
        .portada .calificacion b { font-family: 'Montserrat', sans-serif; font-size: 30pt; line-height: 1; }
        .portada .confidencial { margin-top: 14pt; font-size: 8pt; color: #C9D3E2; }
        .aviso-ejemplo { background: var(--naranja); color: #fff; font-weight: 600; font-size: 9pt; padding: 6pt 10pt; border-radius: 4pt; margin-top: 14pt; }

        /* Componentes */
        .cifras { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6pt; margin: 6pt 0 8pt; }
        .cifras > div { background: var(--gris); border-radius: 4pt; padding: 6pt 9pt; }
        .cifras small { display: block; font-size: 7.5pt; color: var(--suave); text-transform: uppercase; letter-spacing: .05em; }
        .cifras b { font-family: 'Montserrat', sans-serif; font-size: 12pt; color: var(--azul); }
        .calif { display: flex; align-items: center; gap: 16pt; }
        .anillo { position: relative; width: 92pt; height: 92pt; flex: none; }
        .anillo .num { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .anillo .num b { font-family: 'Montserrat', sans-serif; font-size: 24pt; color: var(--azul); line-height: 1; }
        .anillo .num small { font-size: 7pt; color: var(--suave); }
        .dos { display: grid; grid-template-columns: 1fr 1fr; gap: 8pt; }
        .lista-tit { font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 9pt; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 3pt; }
        .cat { font-size: 7.5pt; color: var(--suave); }
        .grupos { display: grid; grid-template-columns: 1fr 1fr; gap: 6pt; }
        .grupo { border: 1px solid var(--linea); border-top: 2.5pt solid var(--azul); border-radius: 4pt; padding: 6pt 9pt; break-inside: avoid; }
        .grupo .nom { font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 8.5pt; color: var(--azul); text-transform: uppercase; letter-spacing: .04em; }
        .grupo .que { font-size: 7.5pt; color: var(--suave); margin-bottom: 3pt; }
        .grupo ul { font-size: 9pt; margin: 0; }
        .dofa { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 1px solid var(--linea); }
        .dofa > div { padding: 8pt 10pt; min-height: 70pt; }
        .dofa > div:nth-child(1), .dofa > div:nth-child(2) { border-bottom: 1px solid var(--linea); }
        .dofa > div:nth-child(odd) { border-right: 1px solid var(--linea); }
        .dofa .cab { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 9.5pt; color: var(--azul); margin-bottom: 1pt; }
        .dofa .def { font-size: 7.5pt; color: var(--suave); margin-bottom: 4pt; }
        .reco { border: 1px solid var(--linea); border-left: 3pt solid var(--naranja); border-radius: 4pt; padding: 7pt 10pt; margin-bottom: 6pt; break-inside: avoid; }
        .reco .t { display: flex; justify-content: space-between; gap: 8pt; }
        .reco .t b { font-family: 'Montserrat', sans-serif; color: var(--azul); }
        .ficha { margin-bottom: 10pt; break-inside: avoid; }
        .ficha .cab { display: flex; justify-content: space-between; align-items: baseline; background: var(--azul); color: #fff; padding: 5pt 9pt; border-radius: 4pt 4pt 0 0; }
        .ficha .cab b { font-family: 'Montserrat', sans-serif; }
        .ficha table { margin: 0; }
        .ficha td:first-child { width: 24%; font-weight: 600; color: var(--azul); background: var(--gris) !important; }
        .linea-escribir { display: inline-block; min-width: 120pt; border-bottom: 1px solid #A9B1BE; height: 10pt; }
        .gantt td { padding: 4pt 3pt; font-size: 8.5pt; }
        .gantt td.s { width: 18pt; padding: 3pt 1pt; }
        .gantt td.s i { display: block; height: 9pt; border-radius: 2pt; }
        .gantt th.s { padding: 4pt 1pt; text-align: center; font-size: 7.5pt; }
        .pregunta { border-bottom: 1px solid var(--linea); padding: 5pt 0; break-inside: avoid; }
        .pregunta .q { display: flex; justify-content: space-between; gap: 10pt; font-weight: 500; }
        .pregunta .q span { flex: none; font-size: 8pt; color: var(--suave); }
        .pregunta .r { margin: 2pt 0 1pt; }
        .glosario { display: grid; grid-template-columns: 1fr 1fr; gap: 3pt 14pt; }
        .glosario div { break-inside: avoid; font-size: 9pt; }
        .grafica { width: 100%; }
    </style>
</head>
<body>
    {{-- Portada --}}
    <section class="portada">
        {{-- [INFORMACIÓN PENDIENTE] Logotipo de NuevasTIC: no está en el repositorio; va el nombre en texto. --}}
        <div class="marca">NuevasTIC <span>·</span> Captter</div>
        <div class="ante">Informe de diagnóstico</div>
        <h1>Informe de Diagnóstico y Estrategia de Marketing Digital</h1>
        <div class="raya"></div>
        <div class="empresa">{{ $i['empresa']['nombre'] }}</div>
        <dl>
            <dt>Sector</dt><dd>{{ $i['sector'] }}</dd>
            <dt>Medición</dt><dd>N.º {{ $i['numero'] }}{{ $i['fecha_respuesta'] ? ' · respondida el '.$i['fecha_respuesta'] : '' }}</dd>
            <dt>Diagnóstico</dt><dd>{{ $i['diagnostico'] }} · versión {{ $i['version'] }}</dd>
            <dt>Fecha del informe</dt><dd>{{ $i['fecha_generado'] }}</dd>
            <dt>Responsable</dt><dd>NuevasTIC, con análisis asistido por IA</dd>
        </dl>
        @if ($i['ejemplo'])
            <div class="aviso-ejemplo">Informe de ejemplo: los datos y textos son ilustrativos y no corresponden a una empresa real.</div>
        @endif
        <div class="calificacion">
            <b>{{ $i['puntaje'] }}</b>
            <div>
                <div style="font-size: 8pt; color: #C9D3E2; text-transform: uppercase; letter-spacing: .1em">Calificación general sobre 100</div>
                <div style="font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 12pt">Nivel de desempeño: {{ $i['nivel_nombre'] }}</div>
            </div>
        </div>
        <div class="confidencial">Documento confidencial · uso exclusivo de {{ $i['empresa']['nombre'] }} y del equipo de NuevasTIC.</div>
    </section>

    {{-- 1 · Ficha técnica y alcance --}}
    <section class="seccion">
        <h2><span class="n">1</span>Ficha técnica y alcance</h2>
        <p class="bajada">Qué empresa se evaluó, con qué información y qué quedó fuera de la evaluación.</p>

        @if ($i['ejemplo'])
            <div class="alerta"><b>Informe de ejemplo.</b> Muestra cómo se verá el informe real. Los textos que empiezan por «Ejemplo:» los escribe la IA a partir de las respuestas de cada empresa.</div>
        @endif

        <h3>Empresa evaluada</h3>
        @php $f = $i['empresa_ficha'] ?? []; @endphp
        <table class="bloque">
            <tbody>
                <tr><td style="width: 30%"><b>Nombre comercial</b></td><td>{{ $i['empresa']['nombre'] }}</td></tr>
                <tr><td><b>Sector</b></td><td>{{ $i['sector'] }}</td></tr>
                <tr><td><b>Actividad económica (CIIU)</b></td><td>{!! $vacio($f['actividad'] ?? '', $sinDato) !!}</td></tr>
                <tr><td><b>Ubicación</b></td><td>{!! $vacio($f['ubicacion'] ?? '', $sinDato) !!}</td></tr>
                <tr><td><b>Sitio web</b></td><td>{!! $vacio($f['sitio_web'] ?? '', 'No registrado') !!}</td></tr>
                <tr><td><b>Número de empleados</b></td><td>{!! $vacio($f['empleados'] ?? '', $sinDato) !!}</td></tr>
                <tr><td><b>Descripción del negocio</b></td><td>{!! $vacio($f['descripcion'] ?? '', 'No registrada. Se puede agregar en los datos de la empresa en Captter.') !!}</td></tr>
            </tbody>
        </table>

        <h3>Medición</h3>
        <table class="bloque">
            <tbody>
                <tr><td style="width: 30%"><b>Diagnóstico aplicado</b></td><td>{{ $i['diagnostico'] }} · versión {{ $i['version'] }}</td></tr>
                <tr><td><b>Medición</b></td><td>N.º {{ $i['numero'] }}{{ $i['anterior'] ? ' · anterior: medición '.$i['anterior']['numero'].' ('.$i['anterior']['puntaje'].' puntos)' : ' · primera medición de la empresa' }}</td></tr>
                <tr><td><b>Fechas</b></td><td>{{ $i['fecha_respuesta'] ? 'Respondida el '.$i['fecha_respuesta'].' · ' : '' }}informe del {{ $i['fecha_generado'] }}</td></tr>
                <tr><td><b>Categorías evaluadas</b></td><td>{{ $nCat }}: {{ collect($i['categorias'])->pluck('nombre')->join(', ', ' y ') }}</td></tr>
                <tr><td><b>Preguntas respondidas</b></td><td>{{ $i['preguntas_respondidas'] }} de {{ $i['preguntas_total'] }}</td></tr>
                <tr><td><b>Responsable</b></td><td>NuevasTIC. El análisis de cada categoría lo hace una IA con instrucciones definidas por NuevasTIC.</td></tr>
            </tbody>
        </table>

        <h3>Fuentes y alcance</h3>
        <div class="dos bloque">
            <div class="tarjeta">
                <div class="lista-tit" style="color: #2A6E4F">Fuentes utilizadas</div>
                <ul style="margin: 0">
                    <li>Respuestas de la empresa al cuestionario en Captter. Es <b>información declarada</b>: no se verificó en los canales.</li>
                    <li>Datos de registro de la empresa en Captter.</li>
                    <li>Análisis de cada categoría hecho por IA a partir de esas respuestas.</li>
                    @if ($i['anterior'])<li>Resultado de la medición {{ $i['anterior']['numero'] }}, para la comparación.</li>@endif
                </ul>
            </div>
            <div class="tarjeta">
                <div class="lista-tit" style="color: #9A3333">Elementos que quedaron fuera</div>
                <ul style="margin: 0">
                    <li>Revisión directa de la web, las redes, Google y WhatsApp (capturas, enlaces y fecha de consulta).</li>
                    <li>Comparación con competidores: no hay datos verificados de otras empresas.</li>
                    <li>Datos de ventas, publicidad o presupuesto, salvo lo que la empresa haya respondido.</li>
                </ul>
            </div>
        </div>
        <p class="nota" style="margin-top: 6pt">
            Este es un diagnóstico especializado en marketing digital; no es una auditoría empresarial ni financiera.
            Lo que no se pudo evaluar se explica en «Información pendiente» (sección 8), con la forma de obtenerlo.
        </p>
    </section>

    {{-- 2 · Resumen ejecutivo --}}
    <section class="seccion">
        <h2><span class="n">2</span>Resumen ejecutivo</h2>
        <p class="bajada">¿Cómo está el marketing digital de la empresa y qué debe atenderse primero?</p>

        <div class="calif bloque">
            <div class="anillo">
                <svg viewBox="0 0 100 100" width="92" height="92" style="width: 92pt; height: 92pt">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="#E8EBF0" stroke-width="9" />
                    <circle cx="50" cy="50" r="40" fill="none" stroke="{{ $color[0] }}" stroke-width="9" stroke-linecap="round"
                        stroke-dasharray="{{ round($circ * $i['puntaje'] / 100, 2) }} {{ round($circ, 2) }}" transform="rotate(-90 50 50)" />
                </svg>
                <div class="num"><b>{{ $i['puntaje'] }}</b><small>de 100</small></div>
            </div>
            <div>
                <div class="nota" style="text-transform: uppercase; letter-spacing: .08em">Calificación general</div>
                <div style="margin: 2pt 0 4pt">Nivel de desempeño: {!! $etiqueta($i['nivel']) !!}</div>
                @if ($i['variacion'] !== null && $i['anterior'])
                    <div class="{{ $claseVar($i['variacion']) }}">Variación frente a la medición anterior: {{ $variacion($i['variacion']) }} puntos (medición {{ $i['anterior']['numero'] }}: {{ $i['anterior']['puntaje'] }}).</div>
                @else
                    <div class="suave">Primera medición: sirve como línea base para las siguientes.</div>
                @endif
            </div>
        </div>

        <div class="destacado bloque">
            <b>Comentario ejecutivo.</b>
            {{ $i['empresa']['nombre'] }} obtuvo una calificación general de {{ $i['puntaje'] }} sobre 100, en el nivel «{{ $i['nivel_nombre'] }}».
            De {{ $nCat }} {{ $nCat === 1 ? 'categoría evaluada' : 'categorías evaluadas' }}, {{ $bajas }} {{ $bajas === 1 ? 'está' : 'están' }} en Crítico o Requiere mejora.
            @if ($ultima) Lo más sólido es {{ $ultima['nombre'] }} ({{ $ultima['puntaje'] }}). @endif
            @if ($primera) Lo que debe atenderse primero es {{ $primera['nombre'] }} ({{ $primera['puntaje'] }}){{ $primera['impacto'] !== '' ? ': '.rtrim($primera['impacto'], '.').'.' : '.' }} @endif
        </div>

        <h3>Resultado por categoría</h3>
        <table class="bloque">
            <thead><tr><th>Categoría</th><th class="r">Calificación</th><th>Nivel de desempeño</th><th class="c">Prioridad</th></tr></thead>
            <tbody>
                @foreach ($i['por_prioridad'] as $c)
                    <tr><td>{{ $c['nombre'] }}</td><td class="r"><b>{{ $c['puntaje'] }}</b></td><td>{!! $etiqueta($c['nivel']) !!}</td><td class="c">{{ $c['prioridad'] }}</td></tr>
                @endforeach
            </tbody>
        </table>

        <div class="dos bloque" style="grid-template-columns: 1fr 1fr 1fr">
            @foreach (['fortalezas' => ['Principales fortalezas', '#2A6E4F'], 'debilidades' => ['Principales debilidades', '#9A3333'], 'oportunidades' => ['Oportunidades', '#2B5F88']] as $clave => [$titulo, $tono])
                <div class="tarjeta">
                    <div class="lista-tit" style="color: {{ $tono }}">{{ $titulo }}</div>
                    @forelse (array_slice($i['dofa'][$clave], 0, 3) as $d)
                        <p style="margin-bottom: 4pt">{{ $d['texto'] }} <span class="cat">· {{ $d['categoria'] }}</span></p>
                    @empty
                        <p class="pendiente">Sin elementos con sustento en las respuestas.</p>
                    @endforelse
                </div>
            @endforeach
        </div>

        <div class="bloque">
            <h3>Tres recomendaciones prioritarias</h3>
            <p class="nota">
                La acción más importante de las 3 categorías con menor calificación, en el orden en que conviene empezar.
                La ficha completa de cada una (pasos, responsable, indicador y meta) está en la sección 9.
            </p>
            @foreach (array_slice(array_values(array_filter($i['fichas'], fn ($f) => $f['tipo'] === 'prioritaria')), 0, 3) as $f)
                <div class="reco">
                    <div class="t"><b>{{ $f['numero'] }}. {{ $f['titulo'] }}</b><span class="nota">{{ $f['plazo'] !== '' ? 'Plazo: '.$f['plazo'] : '' }}</span></div>
                    <div class="nota">{{ $f['categoria'] }} · {{ $nombreNivel($f['nivel']) }}{{ in_array($f['nivel'], ['camino', 'sigue'], true) ? ' (para seguir mejorando)' : '' }}</div>
                    @if ($f['objetivo'] !== '')<p style="margin: 2pt 0 0"><b>Objetivo:</b> {{ $f['objetivo'] }}</p>@endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- 3 · Metodología y criterios --}}
    <section class="seccion">
        <h2><span class="n">3</span>Metodología y criterios</h2>
        <p class="bajada">Cómo se obtuvo cada calificación, qué hizo la IA y cuáles son los límites de la evaluación.</p>

        <h3>Categorías, preguntas y ponderación</h3>
        <p>
            El diagnóstico tiene {{ $nCat }} categorías. Cada una tiene una ponderación (importancia) que indica cuánto pesa en la
            calificación general; entre todas suman 100 %. Cada pregunta se califica de 0 a 100:
        </p>
        <ul>
            <li><b>Opción única:</b> vale el puntaje de la opción elegida.</li>
            <li><b>Selección múltiple:</b> la suma de las opciones marcadas, con tope de 100.</li>
            <li><b>Respuesta abierta:</b> la califica la IA con esta rúbrica: 0–29 la práctica no existe o la respuesta no aplica; 30–59 existe pero es informal; 60–79 está definida y tiene algo de medición; 80–100 está definida, se mide y se mejora con datos.</li>
            <li><b>Preguntas opcionales:</b> no cuentan en la calificación. Si se respondieron, la IA las lee como información complementaria. Las no respondidas no se tienen en cuenta.</li>
        </ul>

        <h3>Fórmula de la calificación</h3>
        <div class="destacado bloque">
            <b>Calificación de la categoría</b> = promedio de sus preguntas respondidas, redondeado al entero más cercano.<br>
            <b>Calificación general</b> = Σ (calificación de la categoría × ponderación) ÷ Σ ponderaciones, redondeado al entero más cercano.<br>
            <span class="nota">
                En esta medición el resultado exacto es {{ $num($i['total_exacto']) }}, que se redondea a {{ $i['puntaje'] }}.
                @if ($i['ejemplo_calculo'])
                    Por ejemplo, {{ $i['ejemplo_calculo']['nombre'] }} pondera {{ $num($i['ejemplo_calculo']['importancia']) }} % y obtuvo {{ $i['ejemplo_calculo']['puntaje'] }}: aporta {{ $num($i['ejemplo_calculo']['aporte']) }} puntos.
                @endif
            </span>
        </div>

        <h3>Niveles de desempeño</h3>
        <table class="bloque">
            <thead><tr><th style="width: 13%">Calificación</th><th style="width: 27%">Nivel</th><th>Interpretación</th></tr></thead>
            <tbody>
                @foreach ($i['niveles'] as $n)
                    <tr><td>{{ $n['rango'] }}</td><td>{!! $etiqueta($n['clave']) !!}</td><td>{{ $n['interpretacion'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <p class="nota">En la plataforma Captter los mismos niveles se llaman {{ collect($i['niveles'])->pluck('nombre_pantalla')->map(fn ($n) => "«{$n}»")->join(', ', ' y ') }}.</p>

        <h3>Análisis con inteligencia artificial y controles de calidad</h3>
        <p>
            Cada categoría se analiza por separado. La IA recibe las preguntas con sus respuestas y unas instrucciones definidas por
            NuevasTIC, elige un método de análisis y entrega el diagnóstico, el impacto, los hallazgos, las fortalezas y debilidades,
            la información que faltó y las recomendaciones con sus indicadores.
        </p>
        <ul>
            <li>La IA responde en un formato fijo; Captter rechaza la respuesta si falta una pregunta, si una calificación está fuera de 0 a 100, si no hay hallazgos del método elegido o si no hay recomendaciones con pasos. En ese caso reintenta la categoría hasta 3 veces.</li>
            <li>Tiene instrucciones de no inventar información, datos de competidores, presupuestos ni metas: la línea base y la meta de un indicador solo aparecen si la empresa dio el dato.</li>
            <li>El resultado publicado no cambia después (queda guardado tal como se entregó).</li>
        </ul>

        <h3>Métodos estratégicos</h3>
        <p>
            No se aplican todos los métodos en cada categoría: la IA usa el que corresponde a la información disponible y explica por qué.
            En esta medición se usaron {{ $usados }} de 5.
        </p>
        <table>
            <thead><tr><th style="width: 18%">Método</th><th>Aplicación en marketing digital</th><th style="width: 30%">Grupos</th><th style="width: 20%">Usado en</th></tr></thead>
            <tbody>
                @foreach ($i['metodos'] as $m)
                    <tr>
                        <td><b>{{ $m['nombre'] }}</b></td>
                        <td>{{ $m['que'] }} <span class="nota">{{ $m['cuando'] }}</span></td>
                        <td class="nota">{{ collect($m['grupos'])->pluck('nombre')->join(', ') }}</td>
                        <td>{!! $m['usado_en'] === [] ? '<span class="pendiente">No se usó</span>' : e(implode(', ', $m['usado_en'])) !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h3>Limitaciones</h3>
        <ul class="bloque">
            <li>Las respuestas son información declarada por la empresa; no se verificaron en los canales.</li>
            <li>La calificación mide prácticas de marketing digital, no resultados comerciales.</li>
            <li>La IA puede equivocarse al interpretar una respuesta: las conclusiones deben revisarse con la empresa.</li>
        </ul>
    </section>
    {{-- 4 · Resultados generales · 5 · Comparación y priorización --}}
    <section class="seccion">
        <h2><span class="n">4</span>Resultados generales</h2>
        <p class="bajada">La calificación de cada categoría, cuánto pesa y cuánto aporta a la calificación general.</p>

        <table>
            <thead><tr><th>Categoría</th><th class="r">Ponderación</th><th style="width: 20%">Calificación</th><th>Nivel</th><th class="r">Aporte</th><th class="r">Variación</th></tr></thead>
            <tbody>
                @foreach ($i['categorias'] as $c)
                    <tr>
                        <td>{{ $c['nombre'] }}</td>
                        <td class="r">{{ $num($c['importancia']) }} %</td>
                        <td><b>{{ $c['puntaje'] }}</b><div class="barra"><i style="width: {{ $c['puntaje'] }}%; background: {{ $colores[$c['nivel']][0] }}"></i></div></td>
                        <td>{!! $etiqueta($c['nivel']) !!}</td>
                        <td class="r">{{ $num($c['aporte']) }}</td>
                        <td class="r {{ $claseVar($c['variacion']) }}">{{ $variacion($c['variacion']) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td><b>Calificación general</b></td><td class="r">100 %</td><td><b>{{ $i['puntaje'] }}</b> <span class="nota">(exacto: {{ $num($i['total_exacto']) }})</span></td>
                    <td>{!! $etiqueta($i['nivel']) !!}</td><td class="r"><b>{{ $num($i['total_exacto']) }}</b></td><td class="r {{ $claseVar($i['variacion']) }}">{{ $variacion($i['variacion']) }}</td>
                </tr>
            </tbody>
        </table>
        <p class="nota"><b>Aporte</b> = calificación × ponderación ÷ 100: los puntos que la categoría suma a la calificación general. <b>Variación</b>: puntos frente a la medición anterior («—» si es la primera).</p>

        <div class="dos bloque" style="margin-top: 8pt">
            <div>
                <h4 style="margin-top: 0">Calificación por categoría (0 a 100)</h4>
                <div style="position: relative; height: {{ min(260, max(120, 22 * $nCat + 40)) }}pt"><canvas id="barras"></canvas></div>
                <p class="nota">Fuente: respuestas de la medición {{ $i['numero'] }}. La línea vertical marca la calificación general.</p>
            </div>
            <div>
                <h4 style="margin-top: 0">Mapa de categorías</h4>
                <div style="position: relative; height: {{ min(260, max(120, 22 * $nCat + 40)) }}pt"><canvas id="radar"></canvas></div>
                <p class="nota">Del centro (0) al borde (100). @if ($i['radar']['anterior']) Línea punteada: medición {{ $i['anterior']['numero'] ?? '' }}. @endif</p>
            </div>
        </div>

        <h4>Distribución por nivel</h4>
        <table class="bloque">
            <thead><tr>@foreach ($i['por_nivel'] as $n)<th class="c">{{ $n['nombre'] }}</th>@endforeach</tr></thead>
            <tbody><tr>@foreach ($i['por_nivel'] as $n)<td class="c" style="font-family: 'Montserrat', sans-serif; font-size: 14pt; font-weight: 700; color: {{ $colores[$n['clave']][2] }}">{{ $n['cantidad'] }}</td>@endforeach</tr></tbody>
        </table>

        <h2 style="margin-top: 16pt"><span class="n">5</span>Comparación y priorización</h2>
        <p class="bajada">En qué orden conviene intervenir las categorías y por qué.</p>
        <p>
            <b>Criterio:</b> primero la categoría con menor calificación, porque es donde hay más por mejorar. Si dos empatan, va primero la
            de mayor ponderación, porque mejorarla sube más la calificación general. Si también empatan en ponderación, se respeta el orden del diagnóstico.
            <span class="suave">El impacto del problema y la viabilidad se explican en cada categoría (sección 6) y en las fichas de acción (sección 9).</span>
        </p>
        <table>
            <thead><tr><th class="c">Orden</th><th>Categoría</th><th class="r">Calificación</th><th class="r">Ponderación</th><th>Justificación</th></tr></thead>
            <tbody>
                @foreach ($i['por_prioridad'] as $c)
                    <tr>
                        <td class="c"><b>{{ $c['prioridad'] }}</b></td>
                        <td>{{ $c['nombre'] }}</td>
                        <td class="r">{{ $c['puntaje'] }}</td>
                        <td class="r">{{ $num($c['importancia']) }} %</td>
                        <td>
                            @if (in_array($c['nivel'], ['critico', 'mejorar'], true))
                                {{ $nombreNivel($c['nivel']) }}: requiere intervención.{{ $c['impacto'] !== '' ? ' '.$c['impacto'] : '' }}
                            @else
                                {{ $nombreNivel($c['nivel']) }}: no requiere intervención inmediata; se trata de mantener lo logrado y seguir midiendo.
                            @endif
                            {!! $c['desempate'] !== '' ? '<span class="nota">'.e($c['desempate']).'</span>' : '' !!}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    {{-- 6 · Diagnóstico por categoría --}}
    @foreach ($i['categorias'] as $n => $c)
        <section class="seccion">
            @if ($n === 0)
                <h2><span class="n">6</span>Diagnóstico por categoría</h2>
                <p class="bajada">Cada categoría con la misma estructura: resultado, diagnóstico, impacto, hallazgos, evidencia, recomendaciones e indicadores.</p>
            @endif

            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12pt">
                <div>
                    <div class="nota" style="text-transform: uppercase; letter-spacing: .1em">6.{{ $n + 1 }} · Prioridad {{ $c['prioridad'] }} de {{ $nCat }}</div>
                    <h2 style="margin-top: 2pt">{{ $c['nombre'] }}</h2>
                    {!! $etiqueta($c['nivel']) !!}
                    @if ($c['metodo'])<span class="etiqueta metodo">{{ $c['metodo']['nombre'] }}</span>@endif
                </div>
                <div style="text-align: right"><span style="font-family: 'Montserrat', sans-serif; font-size: 28pt; font-weight: 700; color: {{ $colores[$c['nivel']][2] }}; line-height: 1">{{ $c['puntaje'] }}</span><div class="nota">de 100</div></div>
            </div>

            <h4>Resultado</h4>
            <div class="cifras">
                <div><small>Ponderación</small><b>{{ $num($c['importancia']) }} %</b></div>
                <div><small>Aporte a la calificación</small><b>{{ $num($c['aporte']) }} pts</b></div>
                <div><small>Preguntas respondidas</small><b>{{ $c['respondidas'] }} de {{ count($c['preguntas']) }}</b></div>
            </div>
            <p class="nota">{{ $c['nivel_nombre'] }}: {{ $c['significado'] }}
                Variación frente a la medición anterior: <span class="{{ $claseVar($c['variacion']) }}">{{ $c['variacion'] === null ? 'no aplica (primera medición)' : $variacion($c['variacion']).' puntos' }}</span>.</p>

            <h4>Diagnóstico</h4>
            <div class="destacado">{{ $c['observacion'] }}</div>

            <h4>Impacto</h4>
            <p>{!! $vacio($c['impacto'], 'No registrado en este resultado.') !!}</p>

            <h4>Hallazgos del diagnóstico @if ($c['metodo'])<span class="nota" style="font-family: 'Inter', sans-serif; font-weight: 400">· método {{ $c['metodo']['nombre'] }}</span>@endif</h4>
            @if ($c['metodo'])
                <p class="nota">
                    {{ $c['metodo_explicado']['que'] ?? '' }}
                    @if ($c['metodo']['motivo'] !== '') <b>Por qué se eligió:</b> {{ $c['metodo']['motivo'] }} @endif
                </p>
            @endif
            @if ($c['grupos'] !== [])
                <div class="grupos">
                    @foreach ($c['grupos'] as $g)
                        <div class="grupo">
                            <div class="nom">{{ $g['nombre'] }}</div>
                            <div class="que">{{ $g['explicacion'] }}</div>
                            @if ($g['puntos'] !== [])
                                <ul>@foreach ($g['puntos'] as $p)<li>{{ $p }}</li>@endforeach</ul>
                            @else
                                <p class="pendiente" style="margin: 0; font-size: 8.5pt">Sin información suficiente en las respuestas.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="dos bloque" style="margin-top: 8pt">
                <div class="tarjeta">
                    <div class="lista-tit" style="color: #2A6E4F">Fortalezas identificadas</div>
                    @forelse ($c['fortalezas'] as $t)<p style="margin-bottom: 3pt">• {{ $t }}</p>@empty<p class="pendiente" style="margin: 0">Sin fortalezas con sustento en las respuestas.</p>@endforelse
                </div>
                <div class="tarjeta">
                    <div class="lista-tit" style="color: #9A3333">Debilidades identificadas</div>
                    @forelse ($c['debilidades'] as $t)<p style="margin-bottom: 3pt">• {{ $t }}</p>@empty<p class="pendiente" style="margin: 0">No registradas en este resultado.</p>@endforelse
                </div>
            </div>

            @if ($c['faltante'] !== [])
                <div class="alerta bloque"><b>Información pendiente:</b>
                    <ul style="margin: 2pt 0 0">@foreach ($c['faltante'] as $t)<li>{{ $t }}</li>@endforeach</ul>
                </div>
            @endif

            <h4>Recomendaciones estratégicas</h4>
            @foreach ($c['recomendaciones'] as $r => $reco)
                <div class="reco">
                    <div class="t"><b>{{ count($c['recomendaciones']) > 1 ? ($r + 1).'. ' : '' }}{{ $reco['titulo'] }}</b><span class="nota">{{ $reco['plazo'] !== '' ? 'Plazo: '.$reco['plazo'] : '' }}</span></div>
                    @if ($reco['detalle'] !== '')<p class="suave" style="margin: 2pt 0">{{ $reco['detalle'] }}</p>@endif
                    @if ($reco['pasos'] !== [])
                        <ol style="margin: 3pt 0">@foreach ($reco['pasos'] as $paso)<li>{{ $paso }}</li>@endforeach</ol>
                    @endif
                    @if ($reco['indicador'] !== '')<p class="nota" style="margin: 0"><b>Indicador de éxito (KPI):</b> {{ $reco['indicador'] }}. Ficha completa en la sección 9.</p>@endif
                </div>
            @endforeach

            <h4>Evidencia: respuestas de la empresa</h4>
            <p class="nota">Información declarada por la empresa en el cuestionario, con la calificación y la observación de cada pregunta.</p>
            @foreach ($c['preguntas'] as $q => $p)
                <div class="pregunta">
                    <div class="q">
                        <div>{{ $q + 1 }}. {{ $p['texto'] }}</div>
                        <span>
                            @if (! empty($p['extra']) && $p['respuesta'] !== null) Información complementaria
                            @elseif ($p['puntaje'] !== null) {{ $p['puntaje'] }} / 100{{ $p['tipo'] === 'abierta' ? ' · calificada por IA' : '' }}
                            @else Sin responder
                            @endif
                        </span>
                    </div>
                    <p class="r">{{ $p['respuesta'] ?? 'No se respondió.' }}</p>
                    @if (! empty($p['observacion']))<p class="nota" style="margin: 0"><b>Observación:</b> {{ $p['observacion'] }}</p>@endif
                </div>
            @endforeach
        </section>
    @endforeach

    {{-- 7 · DOFA · 8 · Conclusiones --}}
    <section class="seccion">
        <h2><span class="n">7</span>Análisis estratégico DOFA</h2>
        <p class="bajada">Los factores internos y externos que encontró el análisis de todas las categorías, en un solo cuadro.</p>
        <div class="dofa bloque">
            @foreach (['fortalezas' => ['Fortalezas', 'Capacidades y prácticas internas que favorecen el marketing digital.'], 'oportunidades' => ['Oportunidades', 'Condiciones externas del mercado que la empresa puede aprovechar.'], 'debilidades' => ['Debilidades', 'Deficiencias internas que limitan el desempeño digital.'], 'amenazas' => ['Amenazas', 'Factores externos que pueden afectar la visibilidad, la captación o la competitividad.']] as $clave => [$titulo, $def])
                <div>
                    <div class="cab">{{ $titulo }}</div>
                    <div class="def">{{ $def }}</div>
                    @forelse ($i['dofa'][$clave] as $d)
                        <p style="margin-bottom: 3pt; font-size: 9pt">• {{ $d['texto'] }} <span class="cat">· {{ $d['categoria'] }}</span></p>
                    @empty
                        <p class="pendiente" style="font-size: 8.5pt">Sin elementos con sustento en las respuestas.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
        <p class="nota">
            Del DOFA a la acción (CAME): las fichas de la sección 9 buscan <b>corregir</b> las debilidades, <b>afrontar</b> las amenazas,
            <b>mantener</b> las fortalezas y <b>explotar</b> las oportunidades.
        </p>

        <h2 style="margin-top: 16pt"><span class="n">8</span>Conclusiones y oportunidades de mejora</h2>
        <p class="bajada">La interpretación de los resultados: qué está pasando, qué conservar y qué falta saber.</p>

        <h3>Situación general</h3>
        <p>
            Con {{ $i['puntaje'] }} sobre 100, el marketing digital de {{ $i['empresa']['nombre'] }} está en el nivel «{{ $i['nivel_nombre'] }}»:
            {{ mb_strtolower(mb_substr(\App\Support\Niveles::enCategoria($i['nivel']), 0, 1)).mb_substr(\App\Support\Niveles::enCategoria($i['nivel']), 1) }}
            {{ $bajas > 0 ? "{$bajas} de {$nCat} categorías necesitan intervención." : 'Ninguna categoría está por debajo de 60.' }}
        </p>

        <h3>Problemas más relevantes y sus causas</h3>
        @forelse ($i['problemas'] as $c)
            <div class="reco" style="border-left-color: {{ $colores[$c['nivel']][0] }}">
                <div class="t"><b>{{ $c['nombre'] }}</b><span>{!! $etiqueta($c['nivel']) !!} {{ $c['puntaje'] }}</span></div>
                <p style="margin: 2pt 0 0">{{ $c['observacion'] }}</p>
                @if ($c['impacto'] !== '')<p class="nota" style="margin: 2pt 0 0"><b>Consecuencia:</b> {{ $c['impacto'] }}</p>@endif
            </div>
        @empty
            <p class="pendiente">Ninguna categoría quedó en Crítico o Requiere mejora.</p>
        @endforelse

        <div class="dos bloque" style="margin-top: 8pt">
            <div class="tarjeta">
                <div class="lista-tit" style="color: #2A6E4F">Fortalezas que deben conservarse</div>
                @forelse ($i['dofa']['fortalezas'] as $d)<p style="margin-bottom: 3pt">• {{ $d['texto'] }} <span class="cat">· {{ $d['categoria'] }}</span></p>@empty<p class="pendiente">Sin elementos con sustento en las respuestas.</p>@endforelse
            </div>
            <div class="tarjeta">
                <div class="lista-tit" style="color: #2B5F88">Oportunidades que pueden aprovecharse</div>
                @forelse ($i['dofa']['oportunidades'] as $d)<p style="margin-bottom: 3pt">• {{ $d['texto'] }} <span class="cat">· {{ $d['categoria'] }}</span></p>@empty<p class="pendiente">Sin elementos con sustento en las respuestas.</p>@endforelse
            </div>
        </div>

        <h3>Información pendiente antes de concluir</h3>
        <ul class="bloque">
            @foreach ($i['faltante'] as $d)
                <li>{{ $d['texto'] }} <span class="cat">· {{ $d['categoria'] }}</span></li>
            @endforeach
            <li>Verificar los canales digitales (web, redes, Google y WhatsApp) con capturas, enlaces y fecha de consulta.</li>
            <li>Identificar 2 o 3 competidores que atiendan al mismo público, para compararse con fuentes y fecha de consulta.</li>
        </ul>
    </section>

    {{-- 9 · Plan estratégico --}}
    <section class="seccion">
        <h2><span class="n">9</span>Plan estratégico de marketing</h2>
        <p class="bajada">Cada recomendación convertida en una ficha de acción que se puede asignar, ejecutar y comprobar.</p>
        <p class="nota">
            El responsable es un rol sugerido: escriban el nombre de quien lo hará. Cuando la línea base o la meta no se conocen, la ficha
            lo dice; se definen al empezar, con el primer dato medido. Las metas no se copian de otras empresas.
        </p>
        @foreach ($tipos as $tipo => [$titulo, $explicacion])
            @php $grupo = array_values(array_filter($i['fichas'], fn ($f) => $f['tipo'] === $tipo)); @endphp
            @if ($grupo !== [])
                <h3>{{ $titulo }}</h3>
                <p class="nota">{{ $explicacion }}</p>
                @foreach ($grupo as $f)
                    <div class="ficha">
                        <div class="cab"><b>{{ $f['numero'] }}. {{ $f['titulo'] }}</b><span style="font-size: 8pt">{{ $f['categoria'] }} · {{ $nombreNivel($f['nivel']) }}</span></div>
                        <table>
                            <tbody>
                                <tr><td>Problema</td><td>{!! $vacio($f['problema'] !== '' ? $f['problema'] : $f['detalle'], 'No registrado en este resultado.') !!}</td></tr>
                                <tr><td>Objetivo</td><td>{!! $vacio($f['objetivo'], 'Por definir con la empresa.') !!}</td></tr>
                                <tr><td>Actividades</td><td>@if ($f['pasos'] !== [])<ol style="margin: 0">@foreach ($f['pasos'] as $paso)<li>{{ $paso }}</li>@endforeach</ol>@else<span class="pendiente">Por definir.</span>@endif</td></tr>
                                <tr><td>Responsable</td><td>{{ $f['responsable'] !== '' ? 'Rol sugerido: '.$f['responsable'].' · ' : '' }}Nombre: <span class="linea-escribir"></span></td></tr>
                                <tr><td>Recursos</td><td>{!! $vacio($f['recursos'], 'Por definir con la empresa.') !!}</td></tr>
                                <tr><td>Plazo</td><td>{!! $vacio($f['plazo'], 'Por definir.') !!} · Inicio: <span class="linea-escribir" style="min-width: 60pt"></span> Fin: <span class="linea-escribir" style="min-width: 60pt"></span></td></tr>
                                <tr><td>Indicador de éxito (KPI)</td><td>{!! $vacio($f['indicador'], 'Por definir.') !!}{!! $f['kpi']['formula'] !== '' ? ' <span class="nota">· '.e($f['kpi']['formula']).'</span>' : '' !!}</td></tr>
                                <tr><td>Meta</td><td>{!! $vacio($f['kpi']['meta'], 'Por definir cuando se mida la línea base.') !!}</td></tr>
                                <tr><td>Evidencia</td><td>{!! $vacio($f['evidencia'], 'Por definir.') !!}</td></tr>
                            </tbody>
                        </table>
                    </div>
                @endforeach
            @endif
        @endforeach
    </section>

    {{-- 10 · Cronograma · 11 · KPI --}}
    <section class="seccion">
        <h2><span class="n">10</span>Cronograma de implementación</h2>
        <p class="bajada">La secuencia sugerida por semanas, a partir del día en que empiece el plan.</p>
        <p class="nota">
            Las acciones prioritarias empiezan en la semana 1; las complementarias, cuando termina la primera prioritaria; cada acción de
            seguimiento, cuando termina la anterior de su categoría. Ajústenlo a los recursos reales de la empresa.
        </p>
        @php $cr = $i['cronograma']; @endphp
        @if ($cr['filas'] !== [])
            <table class="gantt">
                <thead><tr><th>Actividad</th>@for ($s = 1; $s <= $cr['semanas']; $s++)<th class="s">S{{ $s }}</th>@endfor<th class="c">Estado</th></tr></thead>
                <tbody>
                    @foreach ($cr['filas'] as $fila)
                        <tr>
                            <td>{{ $fila['numero'] }}. {{ $fila['titulo'] }} <span class="cat">· {{ $fila['categoria'] }}</span></td>
                            @for ($s = 1; $s <= $cr['semanas']; $s++)
                                <td class="s">@if ($s >= $fila['inicio'] && $s <= $fila['fin'])<i style="background: {{ $fila['tipo'] === 'prioritaria' ? '#F28C28' : ($fila['tipo'] === 'complementaria' ? '#172B46' : '#8A97AB') }}"></i>@endif</td>
                            @endfor
                            <td class="c"><span class="linea-escribir" style="min-width: 40pt"></span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="nota"><span style="color: #F28C28">■</span> Prioritaria &nbsp; <span style="color: #172B46">■</span> Complementaria &nbsp; <span style="color: #8A97AB">■</span> Seguimiento</p>
        @endif
        @if ($cr['sin_semanas'] > 0)
            <p class="pendiente">{{ $cr['sin_semanas'] }} {{ $cr['sin_semanas'] === 1 ? 'acción no tiene' : 'acciones no tienen' }} plazo en semanas y no {{ $cr['sin_semanas'] === 1 ? 'aparece' : 'aparecen' }} en el cronograma.</p>
        @endif

        <h2 style="margin-top: 16pt"><span class="n">11</span>Indicadores de seguimiento (KPI)</h2>
        <p class="bajada">Cómo medir el avance y el resultado de cada acción.</p>
        <div class="alerta bloque">
            <b>No confundir actividad con resultado.</b> Publicar diez veces mide la ejecución; conseguir consultas o ventas que llegan por
            un canal mide el resultado. Cuando no hay línea base, el primer paso es medirla.
        </div>
        <table>
            <thead><tr><th style="width: 22%">Indicador</th><th>Fórmula</th><th>Línea base</th><th>Meta</th><th>Frecuencia</th><th>Fuente</th></tr></thead>
            <tbody>
                @foreach ($i['fichas'] as $f)
                    @if ($f['indicador'] !== '')
                        <tr>
                            <td><b>{{ $f['indicador'] }}</b><div class="cat">Ficha {{ $f['numero'] }} · {{ $f['categoria'] }}</div></td>
                            <td>{!! $vacio($f['kpi']['formula'], 'Por definir') !!}</td>
                            <td>{!! $vacio($f['kpi']['linea_base'], 'Por medir') !!}</td>
                            <td>{!! $vacio($f['kpi']['meta'], 'Por definir') !!}</td>
                            <td>{!! $vacio($f['kpi']['frecuencia'], 'Por definir') !!}</td>
                            <td>{!! $vacio($f['kpi']['fuente'], 'Por definir') !!}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </section>

    {{-- 12 · Próximos pasos y anexos --}}
    <section class="seccion">
        <h2><span class="n">12</span>Próximos pasos y anexos</h2>
        <p class="bajada">Cómo hacer seguimiento al plan y a quién acudir.</p>
        <h3>Seguimiento estratégico</h3>
        <ol>
            <li><b>Asignar responsables</b> a cada ficha de la sección 9 y escribir las fechas de inicio y fin.</li>
            <li><b>Medir la línea base</b> de cada indicador antes de empezar y fijar la meta con ese dato.</li>
            <li><b>Ejecutar primero las acciones prioritarias</b> y revisar el cronograma cada semana.</li>
            <li><b>Guardar la evidencia</b> de cada acción (enlaces, capturas, registros).</li>
            <li><b>Solicitar una nueva medición</b> desde Captter al terminar el plan, para comparar el avance por categoría. Los resultados quedan en «Mi historial».</li>
        </ol>
        @if ($i['soporte'] !== '')
            <div class="destacado bloque">Dudas sobre este informe: <b>{{ $i['soporte'] }}</b></div>
        @endif

        <h3>Glosario</h3>
        <div class="glosario">
            <div><b>Calificación general:</b> resultado de 0 a 100 de la medición, ponderado por categoría.</div>
            <div><b>Categoría:</b> tema del marketing digital que agrupa varias preguntas.</div>
            <div><b>Ponderación:</b> cuánto pesa una categoría en la calificación general.</div>
            <div><b>Aporte:</b> puntos que una categoría suma a la calificación general.</div>
            <div><b>Nivel de desempeño:</b> Crítico, Requiere mejora, Desempeño satisfactorio u óptimo.</div>
            <div><b>Variación:</b> diferencia en puntos frente a la medición anterior.</div>
            <div><b>Hallazgo:</b> algo encontrado en las respuestas, ubicado en un grupo del método.</div>
            <div><b>Información declarada:</b> lo que respondió la empresa, sin verificación externa.</div>
            <div><b>Información complementaria:</b> pregunta opcional respondida; no suma a la calificación.</div>
            <div><b>KPI:</b> indicador de éxito; mide el avance o el resultado de una acción.</div>
            <div><b>Línea base:</b> valor del indicador antes de empezar.</div>
            <div><b>Meta:</b> valor que se quiere alcanzar en un periodo.</div>
            <div><b>DOFA:</b> debilidades, oportunidades, fortalezas y amenazas.</div>
            <div><b>CAME:</b> corregir, afrontar, mantener y explotar.</div>
        </div>

        <h3>Anexo · Fuentes</h3>
        <ul class="nota">
            <li>Cuestionario «{{ $i['diagnostico'] }}» versión {{ $i['version'] }}, respondido en Captter{{ $i['fecha_respuesta'] ? ' el '.$i['fecha_respuesta'] : '' }}.</li>
            <li>Análisis por categoría con IA, guardado al publicar el resultado; este informe no vuelve a consultar la IA.</li>
            <li>Las respuestas completas de cada categoría están en la sección 6, bajo «Evidencia».</li>
        </ul>
    </section>

    <script>{!! \App\Support\RecursosPdf::chartJs() !!}</script>
    <script>
        const radar = @json($i['radar']);
        const colores = @json(collect($i['categorias'])->map(fn ($c) => $colores[$c['nivel']][0])->values());
        const total = {{ (int) $i['puntaje'] }};

        // El canvas no espera a las fuentes: se dibuja cuando Inter ya cargó.
        // Pase lo que pase, el PDF no se queda esperando (ERR-14): si una fuente o
        // una gráfica falla, se imprime igual sin ella en vez de agotar los 30 s.
        Promise.all([document.fonts.load("9px 'Inter'"), document.fonts.load("600 9px 'Montserrat'")])
            .then(dibujar)
            .catch((error) => console.error(error))
            .finally(() => { window.radarListo = true; });

        /** "Herramientas de automatización" → ["Herramientas de", "automatización"]. */
        function partir(texto, max = 22) {
            if (texto.length <= max) {
                return texto;
            }

            const lineas = [''];

            for (const p of texto.split(' ')) {
                const actual = lineas[lineas.length - 1];

                if (actual && (actual + ' ' + p).length > max && lineas.length < 2) {
                    lineas.push(p);
                } else {
                    lineas[lineas.length - 1] = actual ? actual + ' ' + p : p;
                }
            }

            return lineas;
        }

        // Línea vertical de la calificación general sobre las barras.
        const lineaTotal = {
            id: 'lineaTotal',
            afterDatasetsDraw(chart) {
                const x = chart.scales.x.getPixelForValue(total);
                const { top, bottom } = chart.chartArea;
                const ctx = chart.ctx;
                ctx.save();
                ctx.strokeStyle = '#F28C28';
                ctx.setLineDash([4, 3]);
                ctx.beginPath();
                ctx.moveTo(x, top);
                ctx.lineTo(x, bottom);
                ctx.stroke();
                ctx.restore();
            },
        };

        function dibujar() {
            Chart.defaults.font.family = 'Inter';
            Chart.defaults.color = '#1F2733';

            new Chart(document.getElementById('barras'), {
                type: 'bar',
                data: { labels: radar.etiquetas.map((e) => partir(e)), datasets: [{ data: radar.actual, backgroundColor: colores, borderRadius: 3, barThickness: 12 }] },
                options: {
                    indexAxis: 'y',
                    animation: false,
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { min: 0, max: 100, ticks: { stepSize: 20, font: { size: 8 } }, grid: { color: '#E8EBF0' } },
                        y: { ticks: { font: { size: 8 } }, grid: { display: false } },
                    },
                },
                plugins: [lineaTotal],
            });

            const series = [{ data: radar.actual, borderColor: '#172B46', backgroundColor: 'rgba(23, 43, 70, 0.12)', borderWidth: 2, pointRadius: 2, fill: true }];

            if (radar.anterior) {
                series.push({ data: radar.anterior, borderColor: '#8A97AB', backgroundColor: 'transparent', borderDash: [4, 4], borderWidth: 1.5, pointRadius: 0 });
            }

            new Chart(document.getElementById('radar'), {
                type: 'radar',
                data: { labels: radar.etiquetas.map((e, n) => { const l = partir(e, 18); const lista = Array.isArray(l) ? [...l] : [l]; lista[lista.length - 1] += ` · ${radar.actual[n]}`; return lista; }), datasets: series },
                options: {
                    animation: false,
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { r: { min: 0, max: 100, ticks: { stepSize: 25, display: false }, grid: { color: '#DDE2EA' }, angleLines: { color: '#DDE2EA' }, pointLabels: { font: { size: 8 } } } },
                },
            });

            window.radarListo = true;
        }
    </script>
</body>
</html>
