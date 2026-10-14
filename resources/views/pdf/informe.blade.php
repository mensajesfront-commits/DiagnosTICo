{{--
    E10 · Informe PDF del resultado (HU-066, RN-024). Tres páginas:
    1. portada y puntaje; 2. radar y puntaje por categoría; 3. recomendaciones.
    Sale solo de lo guardado en el resultado (App\Support\DatosResultado::informe).
    Chart.js y las fuentes van incrustados porque Chromium no tiene internet.
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
    $pie = "Generado el {$i['fecha_generado']} · {$i['diagnostico']} v{$i['version']}";
    $etiqueta = fn (string $nivel) => '<span class="etiqueta" style="background:'.$colores[$nivel][1].';color:'.$colores[$nivel][2].'">'.e(\App\Support\Niveles::nombre($nivel)).'</span>';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe · {{ $titulo }}</title>
    <style>
        {!! \App\Support\RecursosPdf::fuentes() !!}

        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'IBM Plex Sans', sans-serif; color: #1e2533; font-size: 11px; }
        .mono { font-family: 'IBM Plex Mono', monospace; }
        .pagina { height: 250mm; display: flex; flex-direction: column; page-break-after: always; overflow: hidden; }
        .pagina:last-child { page-break-after: auto; }
        .encabezado { display: flex; justify-content: space-between; align-items: baseline; border-bottom: 1.5px solid #1e2533; padding-bottom: 6px; font-size: 10px; }
        .encabezado b { font-weight: 600; }
        .encabezado span { color: #5f6470; }
        .cuerpo { flex: 1; padding-top: 22px; }
        .pie { display: flex; justify-content: space-between; border-top: 1px solid #e3e2dd; padding-top: 6px; font-size: 9px; color: #5f6470; }
        .antetitulo { font-size: 9px; letter-spacing: .12em; text-transform: uppercase; color: #5f6470; margin-top: 18px; }
        h1 { font-size: 26px; font-weight: 600; line-height: 1.2; margin: 8px 0 12px; }
        h2 { font-size: 15px; font-weight: 600; margin: 0 0 4px; }
        .sub { color: #5f6470; font-size: 10px; margin: 0 0 14px; }
        .datos p { margin: 3px 0; font-size: 11px; }
        .tarjeta { border: 1px solid #e3e2dd; border-radius: 8px; padding: 18px 20px; }
        .puntaje { display: flex; align-items: center; gap: 22px; margin-top: 26px; }
        .anillo { position: relative; width: 110px; height: 110px; flex: none; }
        .anillo .num { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .anillo .num b { font-size: 30px; font-weight: 600; line-height: 1; }
        .anillo .num small { font-size: 8px; color: #5f6470; }
        .etiqueta { display: inline-block; border-radius: 4px; padding: 2px 7px; font-size: 9.5px; font-weight: 600; white-space: nowrap; }
        .frase { font-size: 11.5px; line-height: 1.5; margin: 8px 0 6px; }
        .variacion { font-size: 10.5px; }
        .sube { color: #2f6e35; } .baja { color: #a8362e; } .igual { color: #5f6470; }
        .escala { display: flex; gap: 3px; margin-top: 18px; }
        .escala div { flex: 1; font-size: 9px; color: #5f6470; }
        .escala i { display: block; height: 5px; border-radius: 2px; margin-bottom: 4px; }
        .nota { margin-top: auto; font-size: 9.5px; color: #5f6470; line-height: 1.5; }
        .radar { width: 100%; height: 300px; }
        .leyenda { font-size: 9px; color: #5f6470; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th { text-align: left; font-weight: 400; color: #5f6470; font-size: 9px; border-bottom: 1px solid #e3e2dd; padding: 4px 4px; }
        td { padding: 5px 4px; border-bottom: 1px solid #f1f0eb; font-size: 10px; vertical-align: middle; }
        .barra { height: 6px; background: #f1f0eb; border-radius: 3px; overflow: hidden; }
        .barra i { display: block; height: 100%; border-radius: 3px; }
        .resumen { background: #f5f4f0; border-radius: 6px; padding: 10px 12px; margin-top: 14px; font-size: 10px; line-height: 1.6; }
        .reco { border: 1px solid #e3e2dd; border-radius: 6px; padding: 12px 14px; margin-top: 10px; }
        .reco .cab { display: flex; gap: 8px; align-items: center; color: #5f6470; font-size: 10px; }
        .reco b { display: block; font-size: 11.5px; font-weight: 600; margin: 6px 0 3px; }
        .reco p { margin: 0; line-height: 1.5; color: #1e2533; }
        .reco .metodo { margin-top: 4px; font-size: 9.5px; color: #5f6470; }
        .siguiente { margin-top: auto; background: #f5f4f0; border-radius: 6px; padding: 10px 12px; font-size: 10px; line-height: 1.5; }
        .cuerpo.columna { display: flex; flex-direction: column; }
    </style>
</head>
<body>
    {{-- Página 1 · Portada y puntaje --}}
    <section class="pagina">
        <div class="encabezado"><b>Diagnóstico Empresarial</b><span>{{ $titulo }}</span></div>
        <div class="cuerpo columna">
            <div class="antetitulo">Informe de diagnóstico</div>
            <h1>Marketing digital de {{ $i['empresa']['nombre'] }}</h1>
            <div class="datos">
                <p>Sector: {{ $i['sector'] }} · Medición {{ $i['numero'] }}</p>
                @if ($i['fecha_respuesta'])
                    <p>Respondida el {{ $i['fecha_respuesta'] }}</p>
                @endif
                <p>{{ $i['diagnostico'] }} · versión {{ $i['version'] }}</p>
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
                    {!! $etiqueta($i['nivel']) !!}
                    <p class="frase">{{ $i['frase'] }}</p>
                    @if ($i['variacion'] !== null)
                        <div class="variacion {{ $i['variacion'] > 0 ? 'sube' : ($i['variacion'] < 0 ? 'baja' : 'igual') }}">
                            {{ $i['variacion'] > 0 ? '▲ +'.$i['variacion'] : ($i['variacion'] < 0 ? '▼ '.$i['variacion'] : '= 0') }} vs. medición anterior
                            @if ($i['anterior'])
                                <span class="igual">(medición {{ $i['anterior']['numero'] }} · {{ $i['anterior']['puntaje'] }} pts)</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="escala">
                @foreach ($i['niveles'] as $n)
                    <div><i style="background: {{ $colores[$n['clave']][0] }}"></i>{{ $n['nombre'] }} {{ $n['rango'] }}</div>
                @endforeach
            </div>

            <p class="nota">
                Este informe resume las respuestas de tu empresa. Las preguntas de opción única y de selección múltiple
                suman según su puntaje; las abiertas las califica una IA de 0 a 100. Las recomendaciones también las
                redacta la IA a partir de tus respuestas.
            </p>
        </div>
        <div class="pie"><span>{{ $pie }}</span><span>Página 1 de 3</span></div>
    </section>

    {{-- Página 2 · Radar y puntaje por categoría --}}
    <section class="pagina">
        <div class="encabezado"><b>Diagnóstico Empresarial</b><span>{{ $titulo }}</span></div>
        <div class="cuerpo">
            <h2>Puntaje por categoría</h2>
            <p class="sub">La importancia indica cuánto aporta cada categoría al puntaje total.</p>

            @if ($i['radar']['anterior'])
                <div class="leyenda">— Medición {{ $i['numero'] }} &nbsp; - - Medición {{ $i['anterior']['numero'] ?? '' }}</div>
            @endif
            <div class="radar"><canvas id="radar"></canvas></div>

            <table>
                <thead>
                    <tr><th style="width: 34%">Categoría</th><th>Import.</th><th style="width: 26%"></th><th>Pts</th><th>Nivel</th><th>vs. ant.</th></tr>
                </thead>
                <tbody>
                    @foreach ($i['categorias'] as $c)
                        <tr>
                            <td>{{ $c['nombre'] }}</td>
                            <td class="mono">{{ rtrim(rtrim(number_format($c['importancia'], 2, ',', ''), '0'), ',') }}%</td>
                            <td><div class="barra"><i style="width: {{ $c['puntaje'] }}%; background: {{ $colores[$c['nivel']][0] }}"></i></div></td>
                            <td class="mono"><b>{{ $c['puntaje'] }}</b></td>
                            <td>{!! $etiqueta($c['nivel']) !!}</td>
                            <td class="mono {{ ($c['variacion'] ?? 0) > 0 ? 'sube' : (($c['variacion'] ?? 0) < 0 ? 'baja' : 'igual') }}">
                                {{ $c['variacion'] === null ? '—' : ($c['variacion'] > 0 ? '+'.$c['variacion'] : $c['variacion']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($i['fuertes'] !== [])
            <div class="resumen">
                <div><b>Lo más fuerte:</b> {{ collect($i['fuertes'])->map(fn ($c) => "{$c['nombre']} ({$c['puntaje']})")->join(' y ') }}.</div>
                <div><b>Lo más débil:</b> {{ collect($i['debiles'])->map(fn ($c) => "{$c['nombre']} ({$c['puntaje']})")->join(' y ') }}.</div>
            </div>
            @endif
        </div>
        <div class="pie"><span>{{ $pie }}</span><span>Página 2 de 3</span></div>
    </section>

    {{-- Página 3 · Recomendaciones priorizadas --}}
    <section class="pagina">
        <div class="encabezado"><b>Diagnóstico Empresarial</b><span>{{ $titulo }}</span></div>
        <div class="cuerpo columna">
            <h2>Recomendaciones priorizadas</h2>
            <p class="sub">De la categoría más débil a la más fuerte. Realizables en 1 a 3 meses y con pocos recursos.</p>

            @foreach ($i['prioridades'] as $n => $p)
                <div class="reco">
                    <div class="cab"><span class="mono">{{ str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="etiqueta" style="background: {{ $colores[$p['nivel']][1] }}; color: {{ $colores[$p['nivel']][2] }}">{{ $p['nombre'] }} · {{ $p['puntaje'] }}</span>
                        @if (! empty($p['metodo']))
                            <span class="etiqueta" style="background: #e5eff9; color: #2d4a7a">{{ $p['metodo']['nombre'] }}</span>
                        @endif
                    </div>
                    <b>{{ $p['recomendacion']['titulo'] }}</b>
                    <p>{{ $p['recomendacion']['detalle'] }}</p>
                    @if (! empty($p['metodo']['motivo']))
                        <p class="metodo">Analizado con {{ $p['metodo']['nombre'] }}: {{ $p['metodo']['motivo'] }}</p>
                    @endif
                </div>
            @endforeach

            <div class="siguiente">
                <b>Siguiente paso:</b> el equipo de NuevasTIC puede asignarte una nueva medición para ver cuánto avanzaste.
                Tus resultados anteriores quedan guardados en “Mi historial”.
            </div>
        </div>
        <div class="pie"><span>{{ $pie }}</span><span>Página 3 de 3</span></div>
    </section>

    <script>{!! \App\Support\RecursosPdf::chartJs() !!}</script>
    <script>
        const radar = @json($i['radar']);

        // El canvas no espera a las fuentes: se dibuja cuando IBM Plex ya cargó.
        Promise.all([
            document.fonts.load("9px 'IBM Plex Mono'"),
            document.fonts.load("11px 'IBM Plex Sans'"),
        ]).then(dibujarRadar);

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
                data: { labels: radar.etiquetas.map((e, n) => `${e} · ${radar.actual[n]}`), datasets: series },
                options: {
                    animation: false,
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
