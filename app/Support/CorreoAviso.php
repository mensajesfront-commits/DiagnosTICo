<?php

namespace App\Support;

use App\Models\Medicion;
use App\Models\PlantillaCorreo;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * El correo de aviso de una medición (A3.1e, HU-037): una plantilla general
 * con variables que se puede ajustar para cada medición.
 *
 * - La plantilla general vive en `plantillas_correo` (clave `aviso_medicion`);
 *   mientras nadie la guarde, se usa la de abajo, que es la del wireframe.
 * - Cada medición puede tener su propio asunto y cuerpo
 *   (`mediciones.correo_asunto` y `correo_cuerpo`); null = la general.
 * - Las variables van entre llaves: {nombre_usuario}, {empresa},
 *   {diagnostico}, {numero_medicion}, {mensaje_admin} y {fecha_limite}. La
 *   pantalla rellena igual la vista previa (`resources/js/lib/correo.ts`).
 */
class CorreoAviso
{
    public const string CLAVE = 'aviso_medicion';

    public const string ASUNTO = 'Tienes una nueva medición: {diagnostico}';

    public const string CUERPO = "Hola {nombre_usuario},\n\n"
        ."Te asignamos la medición {numero_medicion} de tu diagnóstico de marketing digital: {diagnostico}.\n\n"
        ."{mensaje_admin}\n\n"
        .'Fecha límite: {fecha_limite}.';

    /** @var list<string> */
    public const array VARIABLES = ['nombre_usuario', 'empresa', 'diagnostico', 'numero_medicion', 'mensaje_admin', 'fecha_limite'];

    /**
     * La plantilla general.
     *
     * @return array{asunto: string, cuerpo: string}
     */
    public static function plantilla(): array
    {
        $guardada = PlantillaCorreo::where('clave', self::CLAVE)->first();

        return [
            'asunto' => $guardada->asunto ?? self::ASUNTO,
            'cuerpo' => $guardada->cuerpo ?? self::CUERPO,
        ];
    }

    /** "Guardar como plantilla para las próximas mediciones". */
    public static function guardarPlantilla(string $asunto, string $cuerpo, ?User $por): void
    {
        PlantillaCorreo::updateOrCreate(
            ['clave' => self::CLAVE],
            ['asunto' => $asunto, 'cuerpo' => $cuerpo, 'actualizado_por' => $por?->id],
        );
    }

    /**
     * El asunto y el cuerpo de una medición: los suyos o los de la plantilla.
     *
     * @return array{asunto: string, cuerpo: string}
     */
    public static function deMedicion(Medicion $medicion): array
    {
        $plantilla = self::plantilla();

        return [
            'asunto' => $medicion->correo_asunto ?? $plantilla['asunto'],
            'cuerpo' => $medicion->correo_cuerpo ?? $plantilla['cuerpo'],
        ];
    }

    /**
     * Los valores reales de las variables para una medición.
     *
     * @return array<string, string>
     */
    public static function variables(Medicion $medicion, ?User $destinatario = null): array
    {
        $medicion->loadMissing(['empresa', 'version.diagnostico']);

        return [
            'nombre_usuario' => $destinatario->name ?? '',
            'empresa' => $medicion->empresa->nombre ?? '',
            'diagnostico' => $medicion->version->diagnostico->nombre ?? '',
            'numero_medicion' => (string) $medicion->numero,
            'mensaje_admin' => (string) $medicion->mensaje,
            'fecha_limite' => self::fecha($medicion->fecha_limite),
        ];
    }

    /** "20 de octubre de 2026", o "sin fecha límite". */
    public static function fecha(?CarbonInterface $fecha): string
    {
        return $fecha ? $fecha->locale('es')->translatedFormat('j \d\e F \d\e Y') : 'sin fecha límite';
    }

    /**
     * Cambia cada {variable} por su valor. Las que no existen quedan como
     * están; si una variable queda vacía (sin mensaje), no deja líneas en
     * blanco de más.
     *
     * @param  array<string, string>  $valores
     */
    public static function rellenar(string $texto, array $valores): string
    {
        $texto = preg_replace_callback(
            '/\{([a-z_]+)\}/',
            fn (array $m) => array_key_exists($m[1], $valores) ? $valores[$m[1]] : $m[0],
            str_replace("\r\n", "\n", $texto),
        ) ?? $texto;

        return trim((string) preg_replace("/\n[ \t]*\n(?:[ \t]*\n)+/", "\n\n", $texto));
    }
}
