<?php

namespace Database\Seeders;

use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Medicion;
use App\Models\Respuesta;
use App\Models\Resultado;
use App\Models\User;
use App\Models\VersionDiagnostico;
use App\Services\Resultados\CalculadoraPuntajes;
use App\Services\Resultados\PublicarResultado;
use App\Services\Resultados\ResultadoEjemplo;
use App\Support\AnalisisSinIa;
use App\Support\MetodosAnalisis;
use App\Support\Niveles;
use App\Support\PaginasColaborador;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Datos de demostración para ver las pantallas con volumen. Solo para
 * desarrollo local: no lo llama DatabaseSeeder.
 *
 *     ./vendor/bin/sail artisan db:seed --class=DatosDemoSeeder
 *
 * Crea:
 * - 20 empresas en los sectores de los diagnósticos publicados "Comidas" y
 *   "Programación y consultoría informática", cada una con su cuenta
 *   principal y de 1 a 4 mediciones terminadas con respuestas y resultados
 *   (calculados con PublicarResultado, igual que uno real; los textos de la
 *   IA son de ejemplo, sin llamarla). Algunas tienen además una medición en
 *   curso, sin empezar o vencida.
 * - Una empresa (Panadería La Espiga) con 6 colaboradores (uno desactivado).
 * - Una consultora (Valeria Ríos) vinculada a 10 empresas (RN-030).
 *
 * Todas las cuentas usan el dominio `captter-demo.test` y la contraseña
 * CLAVE. Volver a correrlo borra lo anterior de la demostración y lo crea
 * de nuevo; `DatosDemoSeeder::limpiar()` solo lo borra.
 */
class DatosDemoSeeder extends Seeder
{
    public const string DOMINIO = 'captter-demo.test';

    public const string CLAVE = 'Demo-Captter-2026';

    /** Nombre, sector (clave de SECTORES), ciudad, departamento, mediciones terminadas, talento inicial, pendiente. */
    private const array EMPRESAS = [
        ['Panadería La Espiga', 'comidas', 'Neiva', 'Huila', 4, 0.30, 'en_curso'],
        ['Restaurante Sazón Criollo', 'comidas', 'Cali', 'Valle del Cauca', 3, 0.45, null],
        ['Café Montaña Alta', 'comidas', 'Manizales', 'Caldas', 2, 0.60, 'no_iniciada'],
        ['Heladería Polo Norte', 'comidas', 'Barranquilla', 'Atlántico', 1, 0.20, null],
        ['Pizzería Don Vito', 'comidas', 'Bogotá', 'Bogotá, D.C.', 3, 0.35, 'vencida'],
        ['Frutería El Huerto', 'comidas', 'Pasto', 'Nariño', 2, 0.15, null],
        ['Asadero La Brasa', 'comidas', 'Villavicencio', 'Meta', 4, 0.50, null],
        ['Pastelería Dulce Hogar', 'comidas', 'Medellín', 'Antioquia', 2, 0.70, 'en_curso'],
        ['Comidas Rápidas El Paisa', 'comidas', 'Pereira', 'Risaralda', 1, 0.40, null],
        ['Cevichería Mar Azul', 'comidas', 'Cartagena de Indias', 'Bolívar', 3, 0.25, null],
        ['Tienda Gourmet Sabores', 'comidas', 'Bucaramanga', 'Santander', 2, 0.80, null],
        ['Arepas La Tradición', 'comidas', 'Tunja', 'Boyacá', 3, 0.10, 'no_iniciada'],
        ['Código Andino', 'software', 'Bogotá', 'Bogotá, D.C.', 4, 0.55, null],
        ['Nube Pacífica', 'software', 'Cali', 'Valle del Cauca', 2, 0.30, 'en_curso'],
        ['AppLab Medellín', 'software', 'Medellín', 'Antioquia', 3, 0.65, null],
        ['Datos Claros', 'software', 'Bucaramanga', 'Santander', 1, 0.50, null],
        ['Pixel Sur', 'software', 'Popayán', 'Cauca', 2, 0.20, 'vencida'],
        ['Soft Llanos', 'software', 'Villavicencio', 'Meta', 3, 0.40, null],
        ['Red Ágil', 'software', 'Barranquilla', 'Atlántico', 2, 0.75, null],
        ['Innova Apps', 'software', 'Armenia', 'Quindío', 4, 0.25, 'en_curso'],
    ];

    /** Nombre del sector de cada diagnóstico publicado que se usa. */
    private const array SECTORES = [
        'comidas' => 'Comidas',
        'software' => 'Programación y consultoría informática',
    ];

    private const array PERSONAS = [
        'Andrea Gómez', 'Carlos Ruiz', 'Diana Patiño', 'Esteban Mora', 'Felipe Castro',
        'Gloria Herrera', 'Hernán López', 'Isabel Cárdenas', 'Jorge Salazar', 'Karen Ospina',
        'Luis Fernández', 'Marcela Pineda', 'Natalia Vargas', 'Óscar Rincón', 'Paula Mejía',
        'Ricardo Silva', 'Sandra Quintero', 'Tomás Arango', 'Vanessa Duarte', 'William Torres',
    ];

    /** Texto de las abiertas según cómo le fue a la empresa en esa pregunta. */
    private const array ABIERTAS = [
        'mejor' => [90, 'Tenemos un proceso definido, lo medimos cada mes y lo ajustamos con los datos.'],
        'medio' => [50, 'Lo hacemos, pero sin un plan fijo ni medición.'],
        'peor' => [10, 'Por ahora no lo hacemos.'],
    ];

    public function run(): void
    {
        mt_srand(2026);
        self::limpiar();

        /** @var array<string, VersionDiagnostico> $versiones */
        $versiones = [];

        foreach (self::SECTORES as $clave => $sector) {
            $diagnostico = Diagnostico::query()
                ->where('estado', 'publicado')
                ->whereHas('sector', fn ($q) => $q->where('nombre', $sector))
                ->first();
            $version = $diagnostico?->versiones()->whereNotNull('publicada_en')->orderByDesc('numero')->first();

            if ($version === null) {
                throw new RuntimeException("Falta un diagnóstico publicado del sector «{$sector}». Corre primero DiagnosticoEjemploSeeder y DiagnosticoProgramacionSeeder.");
            }

            $versiones[$clave] = $version;
        }

        DB::transaction(function () use ($versiones): void {
            $empresas = [];

            foreach (self::EMPRESAS as $i => [$nombre, $sector, $ciudad, $departamento, $terminadas, $talento, $pendiente]) {
                $empresas[] = $this->empresa($i, $nombre, $versiones[$sector], $ciudad, $departamento, $terminadas, $talento, $pendiente);
            }

            $this->colaboradores($empresas[0]);
            $this->consultora($empresas);
        });
    }

    /** Borra todo lo de la demostración (cuentas del dominio y sus empresas). */
    public static function limpiar(): void
    {
        $cuentas = User::withTrashed()->where('email', 'like', '%'.self::DOMINIO)->get();
        $empresas = $cuentas->pluck('empresa_id')->filter()->unique()->values();

        DB::transaction(function () use ($cuentas, $empresas): void {
            $mediciones = Medicion::whereIn('empresa_id', $empresas)->pluck('id');
            Respuesta::whereIn('medicion_id', $mediciones)->delete();
            Resultado::whereIn('medicion_id', $mediciones)->delete();
            DB::table('analisis_categoria')->whereIn('medicion_id', $mediciones)->delete();
            Medicion::whereIn('id', $mediciones)->delete();

            foreach ($cuentas as $cuenta) {
                $cuenta->empresasAsesoradas()->detach();
                $cuenta->syncRoles([]);
                $cuenta->syncPermissions([]);
                $cuenta->forceDelete();
            }

            Empresa::withTrashed()->whereIn('id', $empresas)->get()->each(fn (Empresa $e) => $e->forceDelete());
        });
    }

    private function empresa(int $i, string $nombre, VersionDiagnostico $version, string $ciudad, string $departamento, int $terminadas, float $talento, ?string $pendiente): Empresa
    {
        $slug = Str::slug($nombre);
        // La primera medición, de 70 a 90 días por cada medición hacia atrás.
        $inicio = now()->subDays($terminadas * 80 + 20 + $i);

        $empresa = Empresa::create([
            'nombre' => $nombre,
            'descripcion' => 'Empresa de demostración para ver Captter con datos.',
            'sector_id' => $version->diagnostico->sector_id,
            'ciudad' => $ciudad,
            'departamento' => $departamento,
            'pais' => 'Colombia',
            'numero_empleados' => ['1 a 10', '11 a 50', '51 a 200'][$i % 3],
            'activa' => true,
        ]);
        $empresa->forceFill(['created_at' => $inicio->copy()->subDays(10)])->save();

        $principal = $this->cuenta(self::PERSONAS[$i], "gerencia@{$slug}.".self::DOMINIO, $empresa->id, 'Gerente', $inicio->copy()->subDays(10));
        $principal->assignRole('Empresa');

        $anterior = null;
        $fecha = $inicio->copy();

        for ($n = 1; $n <= $terminadas; $n++) {
            // Mejora con el tiempo, con algún tropiezo para que haya variaciones negativas.
            $nivel = min(0.95, max(0.05, $talento + ($n - 1) * 0.12 + ($n === 3 ? -0.15 : 0)));
            $anterior = $this->terminada($empresa, $version, $n, $fecha, $nivel, $principal, $anterior);
            $fecha = $fecha->copy()->addDays(75 + mt_rand(0, 15));
        }

        if ($pendiente !== null) {
            $this->pendiente($empresa, $version, $terminadas + 1, $pendiente, $principal, $talento);
        }

        return $empresa;
    }

    private function cuenta(string $nombre, string $correo, ?int $empresaId, ?string $cargo, CarbonInterface $creada): User
    {
        $cuenta = User::create([
            'name' => $nombre,
            'email' => $correo,
            'cargo' => $cargo,
            'password' => self::CLAVE,
            'empresa_id' => $empresaId,
            'activo' => true,
        ]);
        $cuenta->forceFill([
            'email_verified_at' => $creada,
            'contrasena_actualizada_en' => $creada,
            'created_at' => $creada,
        ])->save();

        return $cuenta;
    }

    /** Una medición terminada con sus respuestas y su resultado publicado. */
    private function terminada(Empresa $empresa, VersionDiagnostico $version, int $numero, CarbonInterface $fecha, float $talento, User $quien, ?Resultado $anterior): Resultado
    {
        $medicion = Medicion::create([
            'empresa_id' => $empresa->id,
            'version_diagnostico_id' => $version->id,
            'numero' => $numero,
            'estado' => 'terminada',
            'fecha_limite' => $fecha->copy()->addDays(15)->toDateString(),
        ]);
        $medicion->forceFill([
            'created_at' => $fecha->copy()->subDays(7),
            'enviada_en' => $fecha,
            'terminada_en' => $fecha->copy()->addMinutes(3),
        ])->save();

        /** @var list<array<string, mixed>> $categorias */
        $categorias = $version->contenido['categorias'] ?? [];
        [$respuestas, $analisis, $puntajes] = $this->responder($categorias, $talento);

        $this->guardarRespuestas($medicion, $categorias, $respuestas, $puntajes, $quien, $fecha);

        $datos = PublicarResultado::calcular($categorias, $respuestas, $analisis, $anterior);

        return Resultado::create([
            'medicion_id' => $medicion->id,
            'puntaje_total' => $datos['puntaje_total'],
            'nivel' => $datos['nivel'],
            'variacion' => $datos['variacion'],
            'por_categoria' => $datos['por_categoria'],
            'publicado_en' => $fecha->copy()->addMinutes(3),
        ]);
    }

    /** Medición pendiente: sin empezar, en curso (con parte respondida) o vencida. */
    private function pendiente(Empresa $empresa, VersionDiagnostico $version, int $numero, string $estado, User $quien, float $talento): void
    {
        $vencida = $estado === 'vencida';

        $medicion = Medicion::create([
            'empresa_id' => $empresa->id,
            'version_diagnostico_id' => $version->id,
            'numero' => $numero,
            'estado' => $vencida ? 'en_curso' : $estado,
            // La vencida pasó su fecha sin enviarse (Plazos::vencio).
            'fecha_limite' => $vencida ? now()->subDays(5)->toDateString() : now()->addDays(12)->toDateString(),
        ]);
        $medicion->forceFill(['created_at' => now()->subDays($vencida ? 25 : 6)])->save();

        if ($estado === 'no_iniciada') {
            return;
        }

        /** @var list<array<string, mixed>> $categorias */
        $categorias = $version->contenido['categorias'] ?? [];
        [$respuestas, , $puntajes] = $this->responder($categorias, $talento + 0.2);
        // Solo la primera categoría y la mitad de la segunda.
        $refs = array_merge(
            array_column($categorias[0]['preguntas'] ?? [], 'ref'),
            array_slice(array_column($categorias[1]['preguntas'] ?? [], 'ref'), 0, 2),
        );

        $this->guardarRespuestas($medicion, $categorias, array_intersect_key($respuestas, array_flip($refs)), $puntajes, $quien, now()->subDays(2));
    }

    /**
     * Respuestas de toda la versión según el "talento" de la empresa (0 a 1),
     * y el análisis de muestra de cada categoría.
     *
     * @param  list<array<string, mixed>>  $categorias
     * @return array{0: array<string, array{opciones: list<string>, texto: string}>, 1: array<string, array<string, mixed>>, 2: array<string, int|null>}
     */
    private function responder(array $categorias, float $talento): array
    {
        $respuestas = [];
        $analisis = [];
        $puntajes = [];

        foreach ($categorias as $c) {
            /** @var list<array<string, mixed>> $preguntas */
            $preguntas = $c['preguntas'] ?? [];
            $iaPreguntas = [];
            $suma = [];

            foreach ($preguntas as $p) {
                $azar = mt_rand(0, 1000) / 1000;
                $caso = $azar < $talento ? 'mejor' : ($azar < $talento + (1 - $talento) * 0.55 ? 'medio' : 'peor');

                if ($p['tipo'] === 'abierta') {
                    [$puntaje, $texto] = self::ABIERTAS[$caso];
                    $respuestas[$p['ref']] = ['opciones' => [], 'texto' => $texto];
                    $iaPreguntas[] = ['id' => $p['ref'], 'puntaje' => $puntaje, 'observacion' => "Ejemplo: la IA la calificaría con {$puntaje} según su criterio."];
                } else {
                    $elegidas = ResultadoEjemplo::elegir($p, $caso);
                    $respuestas[$p['ref']] = ['opciones' => $elegidas, 'texto' => ''];
                    $puntaje = CalculadoraPuntajes::cerrada($p, $elegidas) ?? 0;
                    $iaPreguntas[] = ['id' => $p['ref'], 'puntaje' => null, 'observacion' => "Ejemplo: con esta respuesta la pregunta suma {$puntaje} puntos."];
                }

                $puntajes[$p['ref']] = $puntaje;
                $suma[] = $puntaje;
            }

            $promedio = CalculadoraPuntajes::categoria($suma) ?? 0;
            $nombre = (string) $c['nombre'];
            $metodo = MetodosAnalisis::porCategoria($nombre, $promedio);
            $analisis[$c['ref']] = [
                'preguntas' => $iaPreguntas,
                'metodo' => $metodo,
                'metodo_motivo' => 'Ejemplo: el método que mejor encaja con lo que respondió la empresa.',
                'observacion_categoria' => "Ejemplo: con estas respuestas, {$nombre} queda en nivel ".Niveles::nombre(Niveles::de($promedio)).'. Aquí la IA explica qué está pasando, por qué pasa y qué le cuesta a la empresa.',
                ...AnalisisSinIa::partes($nombre, $metodo, 'de ejemplo'),
            ];
        }

        return [$respuestas, $analisis, $puntajes];
    }

    /**
     * @param  list<array<string, mixed>>  $categorias
     * @param  array<string, array{opciones: list<string>, texto: string}>  $respuestas
     * @param  array<string, int|null>  $puntajes
     */
    private function guardarRespuestas(Medicion $medicion, array $categorias, array $respuestas, array $puntajes, User $quien, CarbonInterface $fecha): void
    {
        $filas = [];

        foreach ($categorias as $c) {
            foreach ($c['preguntas'] ?? [] as $p) {
                $r = $respuestas[$p['ref']] ?? null;

                if ($r === null) {
                    continue;
                }

                $filas[] = [
                    'medicion_id' => $medicion->id,
                    'pregunta_ref' => $p['ref'],
                    'opciones_elegidas' => json_encode($r['opciones']),
                    'texto' => $r['texto'] !== '' ? $r['texto'] : null,
                    'puntaje' => $puntajes[$p['ref']] ?? null,
                    'respondida_por' => $quien->id,
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                ];
            }
        }

        Respuesta::insert($filas);
    }

    /** E12 · Seis colaboradores en la primera empresa, uno desactivado. */
    private function colaboradores(Empresa $empresa): void
    {
        $todas = array_column(PaginasColaborador::opciones(), 'clave');
        $personas = [
            ['Camila Restrepo', 'Marketing', $todas, true],
            ['Juan Pablo Ortiz', 'Ventas', ['resultados.ver', 'resultados.pdf'], true],
            ['Laura Benítez', 'Producción', ['diagnostico.responder'], true],
            ['Miguel Ángel Rojas', 'Administración', ['diagnostico.responder', 'resultados.ver'], true],
            ['Sofía Valencia', 'Atención al cliente', ['resultados.ver'], true],
            ['Daniel Cifuentes', 'Logística', ['diagnostico.responder', 'resultados.ver'], false],
        ];

        foreach ($personas as $k => [$nombre, $cargo, $paginas, $activo]) {
            $correo = Str::slug($nombre, '.').'@la-espiga.'.self::DOMINIO;
            $cuenta = $this->cuenta($nombre, $correo, $empresa->id, $cargo, now()->subDays(200 - $k * 20));
            $cuenta->forceFill(['activo' => $activo])->save();
            $cuenta->assignRole('Colaborador');
            PaginasColaborador::asignar($cuenta, $paginas);
        }
    }

    /**
     * RN-030 · Consultora externa vinculada a 10 empresas de los dos sectores.
     *
     * @param  list<Empresa>  $empresas
     */
    private function consultora(array $empresas): void
    {
        $cuenta = $this->cuenta('Valeria Ríos', 'valeria.rios@'.self::DOMINIO, null, 'Consultora de marketing', now()->subDays(120));
        $cuenta->assignRole('Consultor');

        $elegidas = [0, 1, 2, 4, 6, 7, 12, 13, 14, 16];
        $cuenta->empresasAsesoradas()->attach(array_map(fn (int $i) => $empresas[$i]->id, $elegidas));
    }
}
