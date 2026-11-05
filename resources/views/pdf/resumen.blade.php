{{--
    E10 · Resumen del diagnóstico para la empresa (HU-066, RN-024).

    Decisión del 4 de noviembre de 2026: la empresa recibe este resumen corto
    y el informe completo (pdf/informe.blade.php) queda para el Administrador
    y el Consultor. Un empresario no lee 36 páginas.

    Es lo que la IA ya escribió de cada categoría, copiado tal cual y en orden;
    no se vuelve a llamar a la IA ni se agrega texto inventado. Sin
    metodología, DOFA, fichas, cronograma ni KPI: eso está en el completo.

    1 · Tu resultado (calificación, nivel, cómo leerlo y las categorías en
    barras) · 2 · Por dónde empezar (la acción principal de las 3 categorías
    más débiles) · 3 · Tu diagnóstico por categoría (de la más urgente a la
    más fuerte: qué encontramos, por qué importa, lo que haces bien, lo que
    debes mejorar y qué hacer) · 4 · Próximos pasos.

    Usa los nombres de nivel de la pantalla (E6) para que la empresa lea lo
    mismo en los dos lados. Mismos colores y fuentes que el completo; las
    barras son HTML, sin Chart.js.
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
    $nombreNivel = fn (string $n) => \App\Support\Niveles::nombre($n);
    $etiqueta = fn (string $n) => '<span class="etiqueta" style="background:'.($colores[$n][1] ?? '#eee').';color:'.($colores[$n][2] ?? '#333').'">'.e($nombreNivel($n)).'</span>';
    $variacion = fn (?int $v) => $v === null ? '' : ($v > 0 ? "+{$v}" : ($v < 0 ? '−'.abs($v) : '0'));
    $claseVar = fn (?int $v) => ($v ?? 0) > 0 ? 'sube' : (($v ?? 0) < 0 ? 'baja' : 'igual');
    $nCat = count($i['categorias']);
    $prioritarias = array_slice(array_values(array_filter($i['fichas'], fn ($f) => $f['tipo'] === 'prioritaria')), 0, 3);
    $lista = fn (array $l) => array_values(array_filter($l, fn ($t) => is_string($t) && trim($t) !== ''));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resumen del diagnóstico · {{ $i['empresa']['nombre'] }} · Medición {{ $i['numero'] }}</title>
    <style>
        {!! \App\Support\RecursosPdf::fuentesInforme() !!}

        :root { --azul: #172B46; --naranja: #F28C28; --gris: #F3F5F8; --linea: #DDE2EA; --suave: #5B6576; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', sans-serif; color: #1F2733; font-size: 10pt; line-height: 1.5; font-variant-numeric: tabular-nums; }
        p { margin: 0 0 5pt; }
        ul, ol { margin: 0 0 5pt; padding-left: 14pt; }
        li { margin-bottom: 2pt; }
        b, strong { font-weight: 600; }

        h1, h2, h3, h4 { font-family: 'Montserrat', sans-serif; color: var(--azul); }
        h2 { font-size: 16pt; font-weight: 700; line-height: 1.2; margin: 0 0 3pt; }
        h2 .n { color: var(--naranja); margin-right: 6pt; }
        .bajada { color: var(--suave); font-size: 9.5pt; margin: 0 0 9pt; padding-bottom: 7pt; border-bottom: 2px solid var(--naranja); }
        h3 { font-size: 13pt; font-weight: 700; margin: 0; }
        h4 { font-family: 'Montserrat', sans-serif; font-size: 8.5pt; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--azul); margin: 8pt 0 2pt; }
        h2, h3, h4 { break-after: avoid; }

        .seccion { break-before: page; }
        .bloque { break-inside: avoid; }
        .suave { color: var(--suave); }
        .nota { font-size: 8.5pt; color: var(--suave); }
        .etiqueta { display: inline-block; border-radius: 3pt; padding: 0 6pt; font-size: 8pt; font-weight: 600; white-space: nowrap; line-height: 1.7; }
        .sube { color: #2A6E4F; } .baja { color: #9A3333; } .igual { color: var(--suave); }

        /* 1 · Tu resultado */
        .cabecera { background: var(--azul); color: #fff; border-radius: 6pt; padding: 18pt 20pt 16pt; }
        .cabecera .marca { display: flex; align-items: center; gap: 7pt; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 9.5pt; letter-spacing: .04em; }
        .cabecera .marca .punto { color: var(--naranja); }
        .cabecera h1 { color: #fff; font-size: 21pt; font-weight: 700; line-height: 1.15; margin: 12pt 0 4pt; }
        .cabecera .empresa { font-family: 'Montserrat', sans-serif; font-size: 13pt; font-weight: 600; }
        .cabecera .datos { color: #C9D3E2; font-size: 8.5pt; margin-top: 4pt; }
        .aviso-ejemplo { background: var(--naranja); color: #fff; font-weight: 600; font-size: 9pt; padding: 6pt 10pt; border-radius: 4pt; margin-top: 10pt; }

        .calif { display: flex; align-items: center; gap: 16pt; margin: 14pt 0 10pt; }
        .anillo { position: relative; width: 88pt; height: 88pt; flex: none; }
        .anillo .num { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .anillo .num b { font-family: 'Montserrat', sans-serif; font-size: 24pt; color: var(--azul); line-height: 1; }
        .anillo .num small { font-size: 7pt; color: var(--suave); }
        .frase { font-size: 10.5pt; margin: 4pt 0; }

        .niveles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5pt; margin: 4pt 0 12pt; }
        .niveles div { border-radius: 4pt; padding: 5pt 7pt; font-size: 8pt; line-height: 1.35; }
        .niveles b { display: block; font-size: 8.5pt; }

        .barras { border: 1px solid var(--linea); border-radius: 5pt; padding: 6pt 10pt; }
        .barras .fila { display: grid; grid-template-columns: 36% 1fr 30pt 120pt; align-items: center; gap: 8pt; padding: 4pt 0; border-bottom: 1px solid #EEF1F5; break-inside: avoid; }
        .barras .fila:last-child { border-bottom: 0; }
        .barras .nom { font-size: 9pt; line-height: 1.3; }
        .barras .pista { height: 8pt; background: #E8EBF0; border-radius: 4pt; overflow: hidden; }
        .barras .pista i { display: block; height: 100%; border-radius: 4pt; }
        .barras .pts { font-family: 'Montserrat', sans-serif; font-weight: 700; color: var(--azul); text-align: right; }

        /* 2 · Por dónde empezar */
        .accion { border: 1px solid var(--linea); border-left: 3pt solid var(--naranja); border-radius: 4pt; padding: 8pt 11pt; margin-bottom: 7pt; break-inside: avoid; }
        .accion .t { display: flex; justify-content: space-between; gap: 10pt; align-items: baseline; }
        .accion .t b { font-family: 'Montserrat', sans-serif; color: var(--azul); font-size: 10.5pt; }

        /* 3 · Por categoría */
        .categoria { border: 1px solid var(--linea); border-radius: 6pt; margin-bottom: 12pt; }
        .categoria .cab { border-radius: 6pt 6pt 0 0; display: flex; justify-content: space-between; align-items: center; gap: 10pt; padding: 8pt 12pt; border-bottom: 1px solid var(--linea); break-after: avoid; }
        .categoria .cab .orden { font-size: 7.5pt; color: var(--suave); text-transform: uppercase; letter-spacing: .08em; }
        .categoria .cab .pts { font-family: 'Montserrat', sans-serif; font-size: 18pt; font-weight: 700; color: var(--azul); line-height: 1; text-align: right; }
        .categoria .cab .pts small { font-size: 8pt; color: var(--suave); font-weight: 500; }
        .categoria .cuerpo { padding: 0 12pt 6pt; }
        .categoria .bloque > .cuerpo { padding-top: 2pt; padding-bottom: 0; }
        .dos { display: grid; grid-template-columns: 1fr 1fr; gap: 10pt; }
        .reco { margin: 5pt 0 7pt; break-inside: avoid; }
        .reco .t { font-weight: 600; color: var(--azul); }
        .reco ol { margin: 2pt 0 0; font-size: 9.5pt; }

        .destacado { background: var(--gris); border-left: 3pt solid var(--azul); padding: 8pt 11pt; margin: 8pt 0; }
    </style>
</head>
<body>
    {{-- 1 · Tu resultado --}}
    <section>
        <div class="cabecera">
            {{-- Símbolo de NuevasTIC (confirmado el 4 de noviembre: el del Login). --}}
            <div class="marca">@include('pdf.logo', ['tam' => 22]) <span>NuevasTIC <span class="punto">·</span> Captter</span></div>
            <h1>Resumen de tu diagnóstico de marketing digital</h1>
            <div class="empresa">{{ $i['empresa']['nombre'] }}</div>
            <div class="datos">
                {{ $i['sector'] }} · Medición {{ $i['numero'] }}{{ $i['fecha_respuesta'] ? ' · respondida el '.$i['fecha_respuesta'] : '' }} · generado el {{ $i['fecha_generado'] }}
            </div>
            @if ($i['ejemplo'])
                <div class="aviso-ejemplo">Resumen de ejemplo: los datos y textos son ilustrativos y no corresponden a una empresa real.</div>
            @endif
        </div>

        <div class="calif bloque">
            <div class="anillo">
                <svg viewBox="0 0 100 100" width="88" height="88" style="width: 88pt; height: 88pt">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="#E8EBF0" stroke-width="9" />
                    <circle cx="50" cy="50" r="40" fill="none" stroke="{{ $color[0] }}" stroke-width="9" stroke-linecap="round"
                        stroke-dasharray="{{ round($circ * $i['puntaje'] / 100, 2) }} {{ round($circ, 2) }}" transform="rotate(-90 50 50)" />
                </svg>
                <div class="num"><b>{{ $i['puntaje'] }}</b><small>de 100</small></div>
            </div>
            <div>
                <div class="nota" style="text-transform: uppercase; letter-spacing: .08em">Tu calificación general</div>
                <div style="margin: 2pt 0">Nivel: {!! $etiqueta($i['nivel']) !!}</div>
                <p class="frase">{{ \App\Support\Niveles::frase($i['nivel']) }}</p>
                @if ($i['variacion'] !== null && $i['anterior'])
                    <div class="{{ $claseVar($i['variacion']) }}">{{ $variacion($i['variacion']) }} puntos frente a tu medición {{ $i['anterior']['numero'] }} ({{ $i['anterior']['puntaje'] }} puntos).</div>
                @else
                    <div class="suave">Es tu primera medición: la próxima se compara con esta.</div>
                @endif
            </div>
        </div>

        <h4>Cómo leer tus resultados</h4>
        <div class="niveles bloque">
            @foreach ($i['niveles'] as $n)
                <div style="background: {{ $colores[$n['clave']][1] }}; color: {{ $colores[$n['clave']][2] }}">
                    <b>{{ $n['nombre_pantalla'] }} · {{ $n['rango'] }}</b>
                    {{ $n['interpretacion'] }}
                </div>
            @endforeach
        </div>

        <h4>Tus resultados por categoría</h4>
        <div class="barras">
            @foreach ($i['categorias'] as $c)
                <div class="fila">
                    <div class="nom">{{ $c['nombre'] }}</div>
                    <div class="pista"><i style="width: {{ max(2, (int) $c['puntaje']) }}%; background: {{ $colores[$c['nivel']][0] }}"></i></div>
                    <div class="pts">{{ $c['puntaje'] }}</div>
                    <div>
                        {!! $etiqueta($c['nivel']) !!}
                        @if (($c['variacion'] ?? null) !== null)
                            <span class="nota {{ $claseVar($c['variacion']) }}">{{ $variacion($c['variacion']) }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <p class="nota" style="margin-top: 3pt">Puntaje de 0 a 100 en cada categoría{{ $i['variacion'] !== null ? '; al lado, la diferencia frente a tu medición anterior' : '' }}.</p>
    </section>

    {{-- 2 · Por dónde empezar --}}
    @if ($prioritarias !== [])
        <section class="bloque" style="margin-top: 14pt">
            <h2><span class="n">1</span>Por dónde empezar</h2>
            <p class="bajada">Las acciones más importantes de tus {{ count($prioritarias) === 1 ? 'categoría más débil' : count($prioritarias).' categorías más débiles' }}, en el orden en que conviene empezar.</p>
            @foreach ($prioritarias as $n => $f)
                <div class="accion">
                    <div class="t"><b>{{ $n + 1 }}. {{ $f['titulo'] }}</b>@if ($f['plazo'] !== '')<span class="nota">Plazo: {{ $f['plazo'] }}</span>@endif</div>
                    <div class="nota">{{ $f['categoria'] }} · {{ $nombreNivel($f['nivel']) }}</div>
                    @if ($f['detalle'] !== '')<p style="margin: 3pt 0 0">{{ $f['detalle'] }}</p>@endif
                </div>
            @endforeach
            <p class="nota">Cada acción tiene sus pasos en la página de su categoría.</p>
        </section>
    @endif

    {{-- 3 · Tu diagnóstico por categoría: sigue en la misma hoja; cada categoría no se parte. --}}
    <section style="margin-top: 16pt">
        <h2><span class="n">{{ $prioritarias !== [] ? 2 : 1 }}</span>Tu diagnóstico por categoría</h2>
        <p class="bajada">Lo que encontró el análisis en cada tema, de lo más urgente a lo más fuerte.</p>

        @foreach ($i['por_prioridad'] as $n => $c)
            @php
                $fortalezas = $lista($c['fortalezas']);
                $debilidades = $lista($c['debilidades']);
                $recos = array_values(array_filter($c['recomendaciones'], fn ($r) => $r['titulo'] !== ''));
            @endphp
            <div class="categoria">
                {{-- El encabezado nunca queda solo al pie de una hoja: va pegado a "Qué encontramos". --}}
                <div class="bloque">
                <div class="cab" style="background: {{ $colores[$c['nivel']][1] }}">
                    <div>
                        <div class="orden">Categoría {{ $n + 1 }} de {{ $nCat }}</div>
                        <h3>{{ $c['nombre'] }}</h3>
                        <div style="margin-top: 2pt">{!! $etiqueta($c['nivel']) !!}</div>
                    </div>
                    <div class="pts">{{ $c['puntaje'] }}<small> / 100</small>
                        @if (($c['variacion'] ?? null) !== null)
                            <div class="nota {{ $claseVar($c['variacion']) }}" style="font-family: 'Inter', sans-serif; font-weight: 500; margin-top: 3pt">{{ $variacion($c['variacion']) }} puntos</div>
                        @endif
                    </div>
                </div>
                @if (($c['observacion'] ?? '') !== '')
                    <div class="cuerpo">
                        <h4>Qué encontramos</h4>
                        <p>{{ $c['observacion'] }}</p>
                    </div>
                @endif
                </div>
                <div class="cuerpo">
                    @if ($c['impacto'] !== '')
                        <div class="bloque">
                            <h4>Por qué importa</h4>
                            <p>{{ $c['impacto'] }}</p>
                        </div>
                    @endif
                    @if ($fortalezas !== [] || $debilidades !== [])
                        <div class="dos bloque">
                            <div>
                                <h4 style="color: #2A6E4F">Lo que ya haces bien</h4>
                                @if ($fortalezas !== [])
                                    <ul>@foreach ($fortalezas as $t)<li>{{ $t }}</li>@endforeach</ul>
                                @else
                                    <p class="nota">Todavía nada que destacar en este tema.</p>
                                @endif
                            </div>
                            <div>
                                <h4 style="color: #9A3333">Lo que debes mejorar</h4>
                                @if ($debilidades !== [])
                                    <ul>@foreach ($debilidades as $t)<li>{{ $t }}</li>@endforeach</ul>
                                @else
                                    <p class="nota">Nada urgente en este tema.</p>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if ($recos !== [])
                        <h4>Qué hacer</h4>
                        @foreach ($recos as $r => $reco)
                            <div class="reco">
                                <div class="t">{{ $r + 1 }}. {{ $reco['titulo'] }}@if ($reco['plazo'] !== '')<span class="nota" style="font-weight: 400"> · Plazo: {{ $reco['plazo'] }}</span>@endif</div>
                                @if ($reco['detalle'] !== '')<p class="suave" style="margin: 1pt 0 0">{{ $reco['detalle'] }}</p>@endif
                                @if ($reco['pasos'] !== [])
                                    <ol>@foreach ($reco['pasos'] as $paso)<li>{{ $paso }}</li>@endforeach</ol>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endforeach
    </section>

    {{-- 4 · Próximos pasos --}}
    <section class="bloque" style="margin-top: 6pt">
        <h2><span class="n">{{ $prioritarias !== [] ? 3 : 2 }}</span>Próximos pasos</h2>
        <p class="bajada">Cómo seguir después de leer este resumen.</p>
        <ol>
            <li><b>Empieza por «Por dónde empezar».</b> Haz una acción a la vez y sigue sus pasos.</li>
            <li><b>Guarda evidencia</b> de lo que hagas (enlaces, capturas, registros) para ver tu avance.</li>
            <li><b>Pide una nueva medición</b> desde Captter cuando termines; verás cuánto subiste en cada categoría en «Mi historial».</li>
        </ol>
        <div class="destacado">
            Este resumen recoge lo esencial del análisis. El equipo de NuevasTIC tiene el informe completo, con la metodología, el plan detallado, el cronograma y los indicadores.
            @if ($i['soporte'] !== '')
                Dudas: <b>{{ $i['soporte'] }}</b>
            @endif
        </div>
    </section>

    <script>
        // Sin gráficas: solo espera las fuentes. Pase lo que pase, el PDF no se
        // queda esperando (ERR-14).
        Promise.all([document.fonts.load("9px 'Inter'"), document.fonts.load("600 9px 'Montserrat'")])
            .catch((error) => console.error(error))
            .finally(() => { window.radarListo = true; });
    </script>
</body>
</html>
