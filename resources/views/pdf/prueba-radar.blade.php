{{--
    Prueba técnica T-025: PDF con el radar de Chart.js.
    Chart.js y las fuentes van incrustados (App\Support\RecursosPdf) porque
    Chromium genera el PDF sin conexión a internet.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Prueba de PDF · {{ $radar['empresa'] }}</title>
    <style>
        {!! \App\Support\RecursosPdf::fuentes() !!}

        body {
            margin: 0;
            font-family: 'IBM Plex Sans', sans-serif;
            color: #1e2533;
        }
        .marca { font-size: 12px; font-weight: 600; color: #1e293b; }
        .marca span { color: #2d4a7a; }
        h1 { font-size: 22px; font-weight: 600; margin: 18px 0 4px; }
        .sub { font-size: 12px; color: #5f6470; margin: 0 0 18px; }
        .tarjeta { border: 1px solid #e3e2dd; border-radius: 10px; padding: 16px 20px; }
        .tarjeta h2 { font-size: 14px; font-weight: 600; margin: 0 0 8px; }
        .leyenda { font-size: 11px; color: #5f6470; text-align: right; margin-bottom: 4px; }
        .grafica { width: 560px; height: 420px; margin: 0 auto; }
        .nota { font-family: 'IBM Plex Mono', monospace; font-size: 10px; color: #5f6470; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="marca">Diagnóstico <span>Empresarial</span></div>
    <h1>Mapa de las 10 categorías</h1>
    <p class="sub">{{ $radar['empresa'] }} · {{ $radar['actual']['nombre'] }} frente a {{ $radar['anterior']['nombre'] }}</p>

    <div class="tarjeta">
        <div class="leyenda">— {{ $radar['actual']['nombre'] }} &nbsp; - - {{ $radar['anterior']['nombre'] }}</div>
        <div class="grafica"><canvas id="radar"></canvas></div>
    </div>

    <p class="nota">Prueba técnica · generado con spatie/laravel-pdf y Chromium · datos de ejemplo del wireframe</p>

    <script>{!! \App\Support\RecursosPdf::chartJs() !!}</script>
    <script>
        const radar = @json($radar);

        // El canvas no espera a las fuentes: se dibuja cuando IBM Plex ya cargó.
        Promise.all([
            document.fonts.load("10px 'IBM Plex Mono'"),
            document.fonts.load("12px 'IBM Plex Sans'"),
        ]).then(dibujarRadar);

        function dibujarRadar() {
            new Chart(document.getElementById('radar'), {
                type: 'radar',
                data: {
                    labels: radar.etiquetas.map((etiqueta, i) => `${etiqueta} · ${radar.actual.puntajes[i]}`),
                    datasets: [
                        {
                            label: radar.actual.nombre,
                            data: radar.actual.puntajes,
                            borderColor: '#2d4a7a',
                            backgroundColor: 'rgba(45, 74, 122, 0.12)',
                            borderWidth: 2,
                            pointRadius: 2,
                        },
                        {
                            label: radar.anterior.nombre,
                            data: radar.anterior.puntajes,
                            borderColor: '#8a909c',
                            backgroundColor: 'transparent',
                            borderDash: [4, 4],
                            borderWidth: 1.5,
                            pointRadius: 0,
                        },
                    ],
                },
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
                            pointLabels: { font: { family: 'IBM Plex Mono', size: 10 }, color: '#1e2533' },
                        },
                    },
                },
            });

            window.radarListo = true;
        }
    </script>
</body>
</html>
