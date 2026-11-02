{{--
    E10 · Informe PDF del resultado (HU-066, RN-024). Es un informe completo,
    más extenso que la pantalla E6, y explica cómo leer cada parte:

    Portada · 1. Cómo leer este informe (qué mide, cómo se calcula, niveles y
    métodos de análisis) · 2. Resumen de resultados (radar y tabla) ·
    3. Análisis por categoría (una por página) · 4. Plan de acción ·
    5. Próximos pasos y glosario.

    Sale solo de lo guardado en el resultado (App\Support\DatosResultado::informe).
    El largo depende de las categorías; el número de página lo pone el pie
    (resources/views/pdf/pie.blade.php; el encabezado, en encabezado.blade.php). Chart.js y las fuentes van
    incrustados porque Chromium no tiene internet.
--}}
@php
    $colores = [
        'critico' => ['#d85249', '#fbe7e5', '#a8362e'],
        'mejorar' => ['#e39938', '#fcefdf', '#9a5d12'],
        'camino' => ['#4a85c7', '#e5eff9', '#2a5f99'],
        'sigue' => ['#4e9954', '#e5f2e6', '#2f6e35'],
    ];
    $i = $informe;
    $color = $colores[$i['nivel']] ?? $colores['critico'];
    $circ = 2 * M_PI * 40;
    $titulo = "{$i['empresa']['nombre']} · Medición {$i['numero']}";
    $etiqueta = fn (string $nivel) => '<span class="etiqueta" style="background:'.$colores[$nivel][1].';color:'.$colores[$nivel][2].'">'.e(\App\Support\Niveles::nombre($nivel)).'</span>';
    $pct = fn (float|int $n) => rtrim(rtrim(number_format((float) $n, 1, ',', ''), '0'), ',');
    $variacion = fn (?int $v) => $v === null ? '—' : ($v > 0 ? "+{$v}" : ($v < 0 ? (string) $v : '0'));
    $claseVar = fn (?int $v) => ($v ?? 0) > 0 ? 'sube' : (($v ?? 0) < 0 ? 'baja' : 'igual');
    $nCat = count($i['categorias']);
    $usados = collect($i['metodos'])->filter(fn ($m) => $m['usado_en'] !== [])->count();
    $partes = [
        'primero' => ['Primero', 'La acción más importante de las 3 categorías más débiles. Empieza por aquí.'],
        'despues' => ['Después', 'La acción más importante de las demás categorías, cuando las primeras ya estén en marcha.'],
        'luego' => ['Más adelante', 'Las otras recomendaciones de cada categoría, para seguir mejorando.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe · {{ $titulo }}</title>
    <style>
        {!! \App\Support\RecursosPdf::fuentes() !!}

        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'IBM Plex Sans', sans-serif; color: #1e2533; font-size: 10.5px; line-height: 1.55; }
        .mono { font-family: 'IBM Plex Mono', monospace; }
        p { margin: 0 0 7px; }
        ul, ol { margin: 0 0 7px; padding-left: 18px; }
        li { margin-bottom: 3px; }

        /* Orden de las páginas: cada sección grande empieza en una hoja nueva. */
        .seccion { break-before: page; }
        .bloque { break-inside: avoid; }
        h2, h3, h4 { break-after: avoid; }

        .antetitulo { font-size: 9px; letter-spacing: .12em; text-transform: uppercase; color: #5f6470; }
        h1 { font-size: 27px; font-weight: 600; line-height: 1.2; margin: 8px 0 12px; }
        h2 { font-size: 18px; font-weight: 600; line-height: 1.25; margin: 0 0 4px; }
        h2 .num { color: #2d4a7a; margin-right: 6px; }
        h3 { font-size: 12.5px; font-weight: 600; margin: 18px 0 6px; }
        h4 { font-size: 11px; font-weight: 600; margin: 10px 0 4px; }
        .intro { color: #5f6470; font-size: 10.5px; margin-bottom: 14px; }
        .suave { color: #5f6470; }
        .peq { font-size: 9.5px; }

        .datos p { margin: 2px 0; font-size: 11px; }
        .tarjeta { border: 1px solid #e3e2dd; border-radius: 8px; padding: 16px 18px; }
        .caja { background: #f5f4f0; border-radius: 6px; padding: 10px 13px; }
        .caja-borde { border-left: 3px solid #2d4a7a; background: #f5f4f0; border-radius: 0 6px 6px 0; padding: 10px 13px; }
        .puntaje { display: flex; align-items: center; gap: 22px; margin-top: 24px; }
        .anillo { position: relative; width: 110px; height: 110px; flex: none; }
        .anillo .num { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .anillo .num b { font-size: 30px; font-weight: 600; line-height: 1; }
        .anillo .num small { font-size: 8px; color: #5f6470; }
        .etiqueta { display: inline-block; border-radius: 4px; padding: 1px 7px; font-size: 9.5px; font-weight: 600; white-space: nowrap; line-height: 1.6; }
        .metodo { background: #e5eff9; color: #2d4a7a; }
        .frase { font-size: 11.5px; line-height: 1.5; margin: 8px 0 6px; }
        .sube { color: #2f6e35; } .baja { color: #a8362e; } .igual { color: #5f6470; }

        .indice { margin-top: 26px; }
        .indice ol { list-style: none; padding: 0; margin: 6px 0 0; }
        .indice li { display: flex; gap: 10px; padding: 5px 0; border-bottom: 1px solid #f1f0eb; margin: 0; }
        .indice li b { color: #2d4a7a; width: 16px; }
        .indice li span.suave { margin-left: auto; font-size: 9.5px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-weight: 500; color: #5f6470; font-size: 9px; border-bottom: 1px solid #c9c7c0; padding: 5px 5px; vertical-align: bottom; }
        td { padding: 6px 5px; border-bottom: 1px solid #f1f0eb; font-size: 10px; vertical-align: top; }
        tr { break-inside: avoid; }
        .barra { height: 6px; background: #f1f0eb; border-radius: 3px; overflow: hidden; margin-top: 5px; }
        .barra i { display: block; height: 100%; border-radius: 3px; }

        .niveles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .niveles > div { background: #f9f8f4; border-radius: 4px; padding: 8px 10px; }
        .niveles b { display: block; font-size: 20px; font-weight: 600; line-height: 1.1; }
        .niveles span { font-size: 9.5px; color: #5f6470; }

        .escala td:first-child { width: 22%; }
        .metodos { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .metodos .tarjeta { padding: 11px 13px; }
        .metodos .tarjeta p { font-size: 9.5px; margin-bottom: 4px; }
        .metodos dl { margin: 6px 0 0; font-size: 9px; }
        .metodos dt { font-weight: 600; display: inline; }
        .metodos dd { display: inline; margin: 0; color: #5f6470; }
        .metodos dd::after { content: ''; display: block; margin-bottom: 2px; }

        .radar { width: 100%; height: 360px; }
        .leyenda { font-size: 9px; color: #5f6470; text-align: right; }

        .cabecera-cat { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .cifra { text-align: right; flex: none; }
        .cifra b { display: block; font-size: 34px; font-weight: 600; line-height: 1; }
        .cifras { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin: 12px 0 4px; }
        .cifras div { background: #f9f8f4; border-radius: 4px; padding: 6px 9px; }
        .cifras small { display: block; font-size: 8.5px; color: #5f6470; }
        .cifras b { font-size: 12px; font-weight: 600; }

        .grupos { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .grupo { border: 1px solid #e3e2dd; border-radius: 6px; padding: 8px 11px; break-inside: avoid; }
        .grupo .nombre { font-size: 9px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #2d4a7a; }
        .grupo .que { font-size: 8.5px; color: #5f6470; margin-bottom: 4px; }
        .grupo ul { font-size: 10px; margin: 0; }

        .reco { border: 1px solid #e3e2dd; border-radius: 6px; padding: 11px 14px; margin-top: 8px; break-inside: avoid; }
        .reco .tit { display: flex; justify-content: space-between; gap: 10px; align-items: baseline; }
        .reco .tit b { font-size: 11.5px; font-weight: 600; }
        .reco .tit .n { color: #2d4a7a; margin-right: 4px; }
        .reco .plazo { background: #f5f4f0; border-radius: 4px; padding: 0 7px; font-size: 9px; color: #5f6470; white-space: nowrap; }
        .reco ol { margin: 6px 0 0; }
        .reco .medir { border-top: 1px solid #f1f0eb; margin-top: 6px; padding-top: 5px; font-size: 9.5px; margin-bottom: 0; }

        .pregunta { border-bottom: 1px solid #f1f0eb; padding: 7px 0; break-inside: avoid; }
        .pregunta .q { display: flex; justify-content: space-between; gap: 12px; font-weight: 500; }
        .pregunta .q span { flex: none; font-size: 9px; color: #5f6470; }
        .pregunta .r { margin: 3px 0 2px; }
        .pregunta .ia { color: #5f6470; font-size: 9.5px; margin: 0; }

        .plan td.check { width: 16px; }
        .cuadro { display: inline-block; width: 10px; height: 10px; border: 1px solid #8a909c; border-radius: 2px; }
        .linea { border-bottom: 1px solid #c9c7c0; height: 12px; }
        .glosario dt { font-weight: 600; margin-top: 6px; }
        .glosario dd { margin: 1px 0 0; color: #3a4150; }
        .pasos-siguientes li { margin-bottom: 6px; }
    </style>
</head>
<body>
    {{-- Portada --}}
    <section>
        <div class="antetitulo">Informe de diagnóstico</div>
        <h1>Marketing digital de {{ $i['empresa']['nombre'] }}</h1>
        <div class="datos">
            <p>Sector: {{ $i['sector'] }} · Medición {{ $i['numero'] }}</p>
            @if ($i['fecha_respuesta'])
                <p>Respondida el {{ $i['fecha_respuesta'] }} · informe generado el {{ $i['fecha_generado'] }}</p>
            @else
                <p>Informe generado el {{ $i['fecha_generado'] }}</p>
            @endif
            <p>{{ $i['diagnostico'] }} · versión {{ $i['version'] }} · {{ $nCat }} categorías y {{ $i['preguntas_total'] }} preguntas</p>
        </div>

        <div class="tarjeta puntaje">
            <div class="anillo">
                <svg viewBox="0 0 100 100" width="110" height="110">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="#f1f0eb" stroke-width="9" />
                    <circle cx="50" cy="50" r="40" fill="none" stroke="{{ $color[0] }}" stroke-width="9" stroke-linecap="round"
                        stroke-dasharray="{{ round($circ * $i['puntaje'] / 100, 2) }} {{ round($circ, 2) }}" transform="rotate(-90 50 50)" />
                </svg>
                <div class="num"><b>{{ $i['puntaje'] }}</b><small>de 100</small></div>
            </div>
            <div>
                <div class="antetitulo" style="margin-bottom: 4px">Puntaje total</div>
                {!! $etiqueta($i['nivel']) !!}
                <p class="frase">{{ $i['frase'] }}</p>
                @if ($i['variacion'] !== null && $i['anterior'])
                    <div class="{{ $claseVar($i['variacion']) }}">
                        {{ $i['variacion'] > 0 ? '▲' : ($i['variacion'] < 0 ? '▼' : '=') }} {{ $variacion($i['variacion']) }} puntos frente a la medición {{ $i['anterior']['numero'] }} ({{ $i['anterior']['puntaje'] }} puntos).
                    </div>
                @else
                    <div class="igual">Es la primera medición: sirve como punto de partida para comparar las siguientes.</div>
                @endif
            </div>
        </div>

        <div class="indice">
            <div class="antetitulo">Contenido</div>
            <ol>
                <li><b>1</b> Cómo leer este informe <span class="suave">Qué mide, cómo se calcula, niveles y métodos</span></li>
                <li><b>2</b> Resumen de resultados <span class="suave">El mapa y la tabla de las {{ $nCat }} categorías</span></li>
                <li><b>3</b> Análisis por categoría <span class="suave">Una página por categoría</span></li>
                <li><b>4</b> Plan de acción <span class="suave">Qué hacer primero, después y más adelante</span></li>
                <li><b>5</b> Próximos pasos y glosario <span class="suave">Qué sigue y qué significa cada término</span></li>
            </ol>
        </div>

        <div class="caja" style="margin-top: 26px">
            <b>Para leerlo rápido:</b> mira el puntaje de esta página, el mapa de la sección 2 y el plan de la sección 4.
            Cuando vayas a trabajar en una categoría, lee su página en la sección 3: ahí está el porqué y los pasos.
        </div>
    </section>

    {{-- 1 · Cómo leer este informe --}}
    <section class="seccion">
        <h2><span class="num">1</span>Cómo leer este informe</h2>
        <p class="intro">Antes de ver los resultados, esto es lo que mide el diagnóstico y cómo se llega a cada número.</p>

        <h3>Qué mide el diagnóstico</h3>
        <p>
            El diagnóstico «{{ $i['diagnostico'] }}» revisa el marketing digital de la empresa en {{ $nCat }} categorías, es decir,
            {{ $nCat }} temas como {{ collect($i['categorias'])->take(3)->pluck('nombre')->map(fn ($n) => mb_strtolower($n))->join(', ', ' y ') }}.
            Cada categoría tiene sus preguntas; en total fueron {{ $i['preguntas_total'] }} y se respondieron {{ $i['preguntas_respondidas'] }}.
        </p>

        <h3>Cómo se calcula el puntaje</h3>
        <ol>
            <li><b>Cada pregunta vale de 0 a 100.</b> En las de una sola opción vale lo que vale la opción elegida. En las de varias opciones se suman las marcadas, hasta 100. Las abiertas, donde se escribe la respuesta, las califica la IA de 0 a 100.</li>
            <li><b>Cada categoría</b> es el promedio de sus preguntas respondidas. Las preguntas opcionales no cuentan en el puntaje; si se respondieron, la IA las tiene en cuenta en sus comentarios («dato extra»).</li>
            <li><b>El puntaje total</b> es el promedio de las categorías según su importancia: una categoría con más importancia pesa más. Las importancias suman 100 %.</li>
        </ol>
        @if ($i['ejemplo_calculo'])
            @php $e = $i['ejemplo_calculo']; @endphp
            <div class="caja-borde bloque">
                <b>Ejemplo con tus datos:</b> «{{ $e['nombre'] }}» tiene una importancia de {{ $pct($e['importancia']) }} % y sacó {{ $e['puntaje'] }} de 100.
                Por eso aporta {{ $pct($e['aporte']) }} puntos a los {{ $i['puntaje'] }} del total. Si esta categoría subiera a 100, el total subiría
                unos {{ $pct(round((100 - $e['puntaje']) * $e['importancia'] / 100, 1)) }} puntos.
            </div>
        @endif

        <h3>Los cuatro niveles</h3>
        <p>Cada puntaje, el total y el de cada categoría, cae en uno de estos niveles:</p>
        <table class="escala bloque">
            <thead><tr><th>Nivel</th><th style="width: 12%">Puntaje</th><th>Qué quiere decir en una categoría</th></tr></thead>
            <tbody>
                @foreach ($i['niveles'] as $n)
                    <tr>
                        <td>{!! $etiqueta($n['clave']) !!}</td>
                        <td class="mono">{{ $n['rango'] }}</td>
                        <td>{{ \App\Support\Niveles::enCategoria($n['clave']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h3>Cómo se hizo el análisis</h3>
        <p>
            Una inteligencia artificial (IA) leyó las respuestas de cada categoría y, para ordenar lo que encontró, eligió uno de los
            cinco métodos de análisis que se explican abajo: el que mejor encaja con lo que respondió la empresa. Por eso dos categorías
            pueden tener métodos distintos. Con ese método la IA escribió:
        </p>
        <ul>
            <li><b>Un diagnóstico:</b> qué pasa en la categoría, por qué pasa y qué le cuesta a la empresa.</li>
            <li><b>Lo que encontró</b>, ordenado en los grupos del método.</li>
            <li><b>Lo que ya se hace bien</b>, para no perderlo.</li>
            <li><b>Recomendaciones</b> en orden de importancia, pensadas para hacerse en 1 a 3 meses y con pocos recursos. Cada una trae pasos, un plazo y una forma de saber si funcionó.</li>
        </ul>
        <p class="suave peq">
            La IA se basa solo en las respuestas. Tómala como una guía: quien mejor conoce el negocio es la empresa. Si algo no
            corresponde a tu realidad, coméntalo con el equipo de NuevasTIC.
        </p>

        <h3 style="break-before: page">Los métodos de análisis</h3>
        <p class="intro">
            En esta medición se usaron {{ $usados }} de los 5 métodos. Cada uno mira la empresa desde un ángulo distinto y ordena lo
            encontrado en sus propios grupos.
        </p>
        <div class="metodos">
            @foreach ($i['metodos'] as $m)
                <div class="tarjeta bloque">
                    <div style="display: flex; justify-content: space-between; gap: 8px; align-items: baseline">
                        <b style="font-size: 12px">{{ $m['nombre'] }}</b>
                        <span class="peq {{ $m['usado_en'] === [] ? 'suave' : '' }}">{{ $m['usado_en'] === [] ? 'No se usó esta vez' : 'Se usó en '.count($m['usado_en']).' '.(count($m['usado_en']) === 1 ? 'categoría' : 'categorías') }}</span>
                    </div>
                    <p>{{ $m['que'] }}</p>
                    <p class="suave">Se usa {{ mb_strtolower(mb_substr($m['cuando'], 0, 1)).mb_substr($m['cuando'], 1) }}</p>
                    <dl>
                        @foreach ($m['grupos'] as $g)
                            <dt>{{ $g['nombre'] }}:</dt> <dd>{{ $g['explicacion'] }}</dd>
                        @endforeach
                    </dl>
                    @if ($m['usado_en'] !== [])
                        <p class="peq" style="margin: 6px 0 0"><b>En:</b> {{ implode(', ', $m['usado_en']) }}.</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- 2 · Resumen de resultados --}}
    <section class="seccion">
        <h2><span class="num">2</span>Resumen de resultados</h2>
        <p class="intro">La foto general: cómo le fue a la empresa en total y en cada categoría.</p>

        <div class="caja bloque">
            <p style="margin: 0">
                {{ $i['empresa']['nombre'] }} obtuvo <b>{{ $i['puntaje'] }} de 100</b>, en el nivel <b>{{ $i['nivel_nombre'] }}</b>. {{ $i['frase'] }}
                @if ($i['variacion'] !== null && $i['anterior'])
                    Frente a la medición {{ $i['anterior']['numero'] }}, el total
                    {{ $i['variacion'] > 0 ? 'subió '.$i['variacion'].' puntos' : ($i['variacion'] < 0 ? 'bajó '.abs($i['variacion']).' puntos' : 'se mantuvo igual') }}.
                @endif
            </p>
        </div>

        <h3>Cuántas categorías hay en cada nivel</h3>
        <div class="niveles bloque">
            @foreach ($i['por_nivel'] as $n)
                <div style="border-top: 4px solid {{ $colores[$n['clave']][0] }}">
                    <b class="mono">{{ $n['cantidad'] }}</b>
                    <span>{{ $n['nombre'] }}</span>
                </div>
            @endforeach
        </div>
        @if ($i['fuertes'] !== [])
            <p style="margin-top: 8px">
                <b>Lo más fuerte:</b> {{ collect($i['fuertes'])->map(fn ($c) => "{$c['nombre']} ({$c['puntaje']})")->join(' y ') }}.
                <b>Lo más débil:</b> {{ collect($i['debiles'])->map(fn ($c) => "{$c['nombre']} ({$c['puntaje']})")->join(' y ') }}.
            </p>
        @endif

        <h3>El mapa de las categorías</h3>
        <p class="suave peq">
            Cada punta es una categoría. El centro es 0 y el borde es 100: mientras más grande el área, mejor.
            Las puntas que quedan hacia adentro son las categorías por trabajar.
            @if ($i['radar']['anterior']) La línea punteada es la medición {{ $i['anterior']['numero'] ?? '' }}, para comparar. @endif
        </p>
        <div class="bloque">
            @if ($i['radar']['anterior'])
                <div class="leyenda">— Medición {{ $i['numero'] }} &nbsp; - - Medición {{ $i['anterior']['numero'] ?? '' }}</div>
            @endif
            <div class="radar"><canvas id="radar"></canvas></div>
        </div>

        <h3 style="break-before: page">El puntaje de cada categoría</h3>
        <p class="suave peq">
            <b>Importancia:</b> cuánto pesa la categoría en el total. <b>Aporte:</b> los puntos que suma al total (puntaje × importancia).
            <b>Prioridad:</b> el orden en que conviene trabajarlas, de la más débil a la más fuerte; si dos tienen el mismo puntaje, va primero la que más pesa.
        </p>
        <table>
            <thead>
                <tr>
                    <th style="width: 30%">Categoría</th><th>Importancia</th><th style="width: 18%">Puntaje</th><th>Nivel</th>
                    <th>Aporte</th><th>vs. anterior</th><th>Prioridad</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($i['categorias'] as $c)
                    <tr>
                        <td>{{ $c['nombre'] }}</td>
                        <td class="mono">{{ $pct($c['importancia']) }} %</td>
                        <td><b class="mono">{{ $c['puntaje'] }}</b><div class="barra"><i style="width: {{ $c['puntaje'] }}%; background: {{ $colores[$c['nivel']][0] }}"></i></div></td>
                        <td>{!! $etiqueta($c['nivel']) !!}</td>
                        <td class="mono">{{ $pct($c['aporte']) }}</td>
                        <td class="mono {{ $claseVar($c['variacion']) }}">{{ $variacion($c['variacion']) }}</td>
                        <td class="mono">{{ $c['prioridad'] }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td><b>Total</b></td><td class="mono">100 %</td><td><b class="mono">{{ $i['puntaje'] }}</b></td><td>{!! $etiqueta($i['nivel']) !!}</td>
                    <td class="mono"><b>{{ $i['puntaje'] }}</b></td><td class="mono {{ $claseVar($i['variacion']) }}">{{ $variacion($i['variacion']) }}</td><td></td>
                </tr>
            </tbody>
        </table>

        <h3>Recomendaciones priorizadas</h3>
        <p class="suave peq">Las 3 categorías más débiles y la acción más importante de cada una. El detalle está en la sección 3 y el plan completo en la 4.</p>
        @foreach ($i['prioridades'] as $n => $p)
            <div class="reco">
                <div class="tit">
                    <b><span class="n mono">{{ $n + 1 }}.</span>{{ $p['recomendacion']['titulo'] }}</b>
                    @if (! empty($p['principal']['plazo']))<span class="plazo">{{ $p['principal']['plazo'] }}</span>@endif
                </div>
                <p class="suave peq" style="margin: 2px 0 0">{{ $p['nombre'] }} · {{ $p['puntaje'] }} puntos · {{ \App\Support\Niveles::nombre($p['nivel']) }}</p>
            </div>
        @endforeach
    </section>

    {{-- 3 · Análisis por categoría --}}
    @foreach ($i['categorias'] as $n => $c)
        <section class="seccion">
            @if ($n === 0)
                <h2><span class="num">3</span>Análisis por categoría</h2>
                <p class="intro">
                    Una página por categoría, en el orden del diagnóstico. Cada una dice qué encontró la IA, con qué método, qué ya se
                    hace bien y qué hacer, paso a paso. Al final están las respuestas con lo que dijo la IA de cada una.
                </p>
            @endif

            <div class="cabecera-cat">
                <div>
                    <div class="antetitulo">Categoría 3.{{ $n + 1 }} · prioridad {{ $c['prioridad'] }} de {{ $nCat }}</div>
                    <h2 style="margin-top: 4px">{{ $c['nombre'] }}</h2>
                    {!! $etiqueta($c['nivel']) !!}
                    @if ($c['metodo'])<span class="etiqueta metodo">{{ $c['metodo']['nombre'] }}</span>@endif
                </div>
                <div class="cifra"><b class="mono" style="color: {{ $colores[$c['nivel']][2] }}">{{ $c['puntaje'] }}</b><span class="suave peq">de 100</span></div>
            </div>
            <div class="cifras">
                <div><small>Importancia</small><b class="mono">{{ $pct($c['importancia']) }} %</b></div>
                <div><small>Aporte al total</small><b class="mono">{{ $pct($c['aporte']) }} pts</b></div>
                <div><small>vs. medición anterior</small><b class="mono {{ $claseVar($c['variacion']) }}">{{ $variacion($c['variacion']) }}</b></div>
                <div><small>Preguntas respondidas</small><b class="mono">{{ $c['respondidas'] }} de {{ count($c['preguntas']) }}</b></div>
            </div>
            <p class="suave peq">{{ \App\Support\Niveles::nombre($c['nivel']) }}: {{ $c['significado'] }}</p>

            <h3>Diagnóstico</h3>
            <div class="caja-borde">{{ $c['observacion'] }}</div>

            @if ($c['metodo'])
                <h3>Lo que encontró el análisis {{ $c['metodo']['nombre'] }}</h3>
                <p class="peq">
                    @if ($c['metodo_explicado']){{ $c['metodo_explicado']['que'] }}@endif
                    @if ($c['metodo']['motivo'])<span class="suave"> Se eligió porque: {{ mb_strtolower(mb_substr($c['metodo']['motivo'], 0, 1)).mb_substr($c['metodo']['motivo'], 1) }}</span>@endif
                </p>
                @if ($c['hallazgos'] !== [])
                    <div class="grupos">
                        @foreach ($c['hallazgos'] as $h)
                            <div class="grupo">
                                <div class="nombre">{{ $h['grupo'] }}</div>
                                <div class="que">{{ \App\Support\MetodosAnalisis::EXPLICACION_GRUPOS[$h['grupo']] ?? '' }}</div>
                                <ul>
                                    @foreach ($h['puntos'] as $punto)
                                        <li>{{ $punto }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            @if ($c['fortalezas'] !== [])
                <h3>Lo que ya se hace bien</h3>
                <ul>
                    @foreach ($c['fortalezas'] as $f)
                        <li>{{ $f }}</li>
                    @endforeach
                </ul>
            @endif

            <h3>{{ count($c['recomendaciones']) > 1 ? 'Qué hacer, de lo más a lo menos importante' : 'Qué hacer' }}</h3>
            @foreach ($c['recomendaciones'] as $r => $reco)
                <div class="reco">
                    <div class="tit">
                        <b>@if (count($c['recomendaciones']) > 1)<span class="n mono">{{ $r + 1 }}.</span>@endif{{ $reco['titulo'] }}</b>
                        @if (! empty($reco['plazo']))<span class="plazo">Plazo: {{ $reco['plazo'] }}</span>@endif
                    </div>
                    @if (! empty($reco['detalle']))<p class="suave" style="margin: 3px 0 0">{{ $reco['detalle'] }}</p>@endif
                    @if (! empty($reco['pasos']))
                        <ol>
                            @foreach ($reco['pasos'] as $paso)
                                <li>{{ $paso }}</li>
                            @endforeach
                        </ol>
                    @endif
                    @if (! empty($reco['indicador']))
                        <p class="medir"><b>Cómo saber si funcionó:</b> {{ $reco['indicador'] }}</p>
                    @endif
                </div>
            @endforeach

            <h3>Las respuestas y lo que dijo la IA</h3>
            @foreach ($c['preguntas'] as $q => $p)
                <div class="pregunta">
                    <div class="q">
                        <div>{{ $q + 1 }}. {{ $p['texto'] }}</div>
                        <span class="mono">
                            @if (! empty($p['extra']) && $p['respuesta'] !== null) Dato extra
                            @elseif ($p['puntaje'] !== null) {{ $p['puntaje'] }} pts{{ $p['tipo'] === 'abierta' ? ' · IA' : '' }}
                            @else Sin responder
                            @endif
                        </span>
                    </div>
                    <p class="r">{{ $p['respuesta'] ?? 'No se respondió.' }}</p>
                    @if (! empty($p['observacion']))<p class="ia"><b>IA:</b> {{ $p['observacion'] }}</p>@endif
                </div>
            @endforeach
        </section>
    @endforeach

    {{-- 4 · Plan de acción --}}
    <section class="seccion">
        <h2><span class="num">4</span>Plan de acción</h2>
        <p class="intro">Todas las recomendaciones en un solo lugar y en orden. Puedes imprimir esta parte y marcar lo que vayas terminando.</p>
        <div class="caja bloque">
            <b>Cómo usar el plan:</b>
            <ol style="margin: 4px 0 0">
                <li>Empieza por las acciones de «Primero». Es mejor terminar pocas que empezar todas.</li>
                <li>Escribe quién se encarga de cada una y anota la fecha en que vence el plazo.</li>
                <li>Al cumplirse el plazo, revisa «Cómo saber si funcionó». Si funcionó, vuélvelo costumbre; si no, ajusta y repite.</li>
            </ol>
        </div>

        @foreach ($partes as $clave => [$nombre, $explicacion])
            @if ($i['plan'][$clave] !== [])
                <h3>{{ $nombre }}</h3>
                <p class="suave peq">{{ $explicacion }}</p>
                <table class="plan">
                    <thead><tr><th class="check"></th><th style="width: 30%">Acción</th><th style="width: 18%">Categoría</th><th>Plazo</th><th style="width: 26%">Cómo saber si funcionó</th><th style="width: 13%">Responsable</th></tr></thead>
                    <tbody>
                        @foreach ($i['plan'][$clave] as $f)
                            <tr>
                                <td class="check"><span class="cuadro"></span></td>
                                <td><b style="font-weight: 600">{{ $f['titulo'] }}</b></td>
                                <td>{{ $f['categoria'] }}<br>{!! $etiqueta($f['nivel']) !!}</td>
                                <td>{{ $f['plazo'] !== '' ? $f['plazo'] : '—' }}</td>
                                <td>{{ $f['indicador'] !== '' ? $f['indicador'] : '—' }}</td>
                                <td><div class="linea"></div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    </section>

    {{-- 5 · Próximos pasos y glosario --}}
    <section class="seccion">
        <h2><span class="num">5</span>Próximos pasos</h2>
        <ol class="pasos-siguientes">
            <li><b>Comparte este informe</b> con quienes manejan las redes, la página web y las ventas. Lean juntos el plan de la sección 4.</li>
            <li><b>Trabaja el plan por partes.</b> Empieza por «Primero» y no pases a «Después» hasta tener esas acciones en marcha.</li>
            <li><b>Mide.</b> Al cumplir cada plazo, revisa su indicador. Lo que se mide se puede mejorar.</li>
            <li><b>Pide una nueva medición</b> cuando hayas avanzado: desde Captter puedes solicitarla al equipo de NuevasTIC. Así verás cuánto subió cada categoría.</li>
            <li><b>Compara.</b> Todos los resultados quedan guardados en «Mi historial», con la gráfica de cómo cambió el puntaje.</li>
        </ol>
        @if ($i['soporte'] !== '')
            <div class="caja bloque">¿Dudas sobre este informe? Escribe a <b>{{ $i['soporte'] }}</b>.</div>
        @endif

        <h2 style="margin-top: 22px">Glosario</h2>
        <dl class="glosario">
            <dt>Categoría</dt><dd>Un tema del marketing digital, por ejemplo la presencia en internet o el contenido. Agrupa varias preguntas.</dd>
            <dt>Importancia</dt><dd>Cuánto pesa una categoría en el puntaje total. Entre todas suman 100 %.</dd>
            <dt>Puntaje</dt><dd>Un número de 0 a 100. Lo tienen cada pregunta, cada categoría y el total.</dd>
            <dt>Nivel</dt><dd>El rango en que cae un puntaje: Crítico, Se puede mejorar, Vas en buen camino o Sigue así.</dd>
            <dt>Aporte al total</dt><dd>Los puntos que una categoría suma al puntaje total según su importancia.</dd>
            <dt>Variación</dt><dd>Cuántos puntos subió (+) o bajó (−) un puntaje frente a la medición anterior.</dd>
            <dt>Prioridad</dt><dd>El orden en que conviene trabajar las categorías: primero la de menor puntaje.</dd>
            <dt>Método de análisis</dt><dd>La forma de ordenar lo encontrado: PESTEL, 5 Fuerzas de Porter, VRIO, DOFA o CAME (sección 1).</dd>
            <dt>Hallazgo</dt><dd>Algo que la IA encontró en las respuestas, ubicado en uno de los grupos del método.</dd>
            <dt>Plazo</dt><dd>El tiempo sugerido para hacer una recomendación.</dd>
            <dt>Cómo saber si funcionó (indicador)</dt><dd>Algo que se puede contar o revisar al final del plazo para ver si la acción dio resultado.</dd>
            <dt>Dato extra</dt><dd>Una pregunta opcional respondida: no cuenta en el puntaje, pero la IA la lee.</dd>
            <dt>IA</dt><dd>Inteligencia artificial: el programa que leyó las respuestas, calificó las abiertas y escribió el análisis.</dd>
        </dl>
    </section>

    <script>{!! \App\Support\RecursosPdf::chartJs() !!}</script>
    <script>
        const radar = @json($i['radar']);

        // El canvas no espera a las fuentes: se dibuja cuando IBM Plex ya cargó.
        Promise.all([
            document.fonts.load("9px 'IBM Plex Mono'"),
            document.fonts.load("11px 'IBM Plex Sans'"),
        ]).then(dibujarRadar);

        /** El puntaje va al final de la última línea: "automatización · 0". */
        function conPuntaje(lineas, puntaje) {
            const lista = Array.isArray(lineas) ? [...lineas] : [lineas];
            lista[lista.length - 1] += ` · ${puntaje}`;

            return lista.length === 1 ? lista[0] : lista;
        }

        /** "Herramientas de automatización" → ["Herramientas de", "automatización"]. */
        function partir(texto, max = 20) {
            if (texto.length <= max) {
                return texto;
            }

            const palabras = texto.split(' ');
            const lineas = [''];

            for (const p of palabras) {
                const actual = lineas[lineas.length - 1];

                if (actual && (actual + ' ' + p).length > max && lineas.length < 2) {
                    lineas.push(p);
                } else {
                    lineas[lineas.length - 1] = actual ? actual + ' ' + p : p;
                }
            }

            return lineas;
        }

        function dibujarRadar() {
            const series = [{
                data: radar.actual,
                borderColor: '#2d4a7a',
                backgroundColor: 'rgba(45, 74, 122, 0.12)',
                borderWidth: 2,
                pointRadius: 2,
                fill: true,
            }];

            if (radar.anterior) {
                series.push({
                    data: radar.anterior,
                    borderColor: '#8a909c',
                    backgroundColor: 'transparent',
                    borderDash: [4, 4],
                    borderWidth: 1.5,
                    pointRadius: 0,
                });
            }

            new Chart(document.getElementById('radar'), {
                type: 'radar',
                // Nombres largos en dos líneas para que no se corten en el borde.
                data: { labels: radar.etiquetas.map((e, n) => conPuntaje(partir(e), radar.actual[n])), datasets: series },
                options: {
                    animation: false,
                    layout: { padding: { left: 12, right: 12 } },
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        r: {
                            min: 0,
                            max: 100,
                            ticks: { stepSize: 25, display: false },
                            grid: { color: '#e3e2dd' },
                            angleLines: { color: '#e3e2dd' },
                            pointLabels: { font: { family: 'IBM Plex Mono', size: 9 }, color: '#1e2533' },
                        },
                    },
                },
            });

            window.radarListo = true;
        }
    </script>
</body>
</html>
