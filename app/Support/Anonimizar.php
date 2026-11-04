<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\Medicion;
use Illuminate\Support\Facades\DB;

/**
 * Antes de borrar para siempre una empresa (DEC-017): sus mediciones
 * terminadas se conservan sin nombre para las estadísticas (decisión de
 * NuevasTIC, 23 de octubre de 2026).
 *
 * - Se conservan: sector y versión del diagnóstico, fechas, puntajes,
 *   niveles, las opciones elegidas y el análisis de cada categoría.
 * - Se borra lo que puede identificar a la empresa: el texto libre de las
 *   respuestas (abiertas y "Otro: …", con su observación de la IA), el prompt guardado, el
 *   mensaje y el correo de la asignación, y el informe PDF (lleva el nombre).
 * - Las mediciones sin terminar no sirven para estadísticas: se borran.
 *
 * La medición queda con `empresa_id` en null al borrar la empresa.
 */
class Anonimizar
{
    /** @return list<string> rutas de los PDF que hay que borrar del disco */
    public static function medicionesDe(Empresa $empresa): array
    {
        $archivos = [];

        Medicion::where('empresa_id', $empresa->id)->where('estado', '!=', 'terminada')->delete();

        foreach (Medicion::with('resultado')->where('empresa_id', $empresa->id)->get() as $medicion) {
            $medicion->forceFill(['mensaje' => null, 'correo_asunto' => null, 'correo_cuerpo' => null])->save();

            // Preguntas con texto libre: las abiertas y las opciones "Otro: …".
            $conTexto = DB::table('respuestas')->where('medicion_id', $medicion->id)->whereNotNull('texto')
                ->pluck('pregunta_ref')->map(fn ($r) => (string) $r)->all();
            DB::table('respuestas')->where('medicion_id', $medicion->id)->whereNotNull('texto')
                ->update(['texto' => null, 'observacion_ia' => null]);
            DB::table('analisis_categoria')->where('medicion_id', $medicion->id)->update(['prompt' => null]);

            $resultado = $medicion->resultado;

            if ($resultado === null) {
                continue;
            }

            $archivos[] = $resultado->pdf_ruta;
            /** @var list<array<string, mixed>> $categorias */
            $categorias = $resultado->por_categoria;

            foreach ($categorias as $i => $categoria) {
                /** @var list<array<string, mixed>> $preguntas */
                $preguntas = $categoria['preguntas'] ?? [];

                foreach ($preguntas as $j => $pregunta) {
                    if (($pregunta['tipo'] ?? null) === 'abierta' || in_array((string) ($pregunta['ref'] ?? ''), $conTexto, true)) {
                        $categorias[$i]['preguntas'][$j]['respuesta'] = null;
                        $categorias[$i]['preguntas'][$j]['observacion'] = null;
                    }
                }
            }

            $resultado->forceFill(['por_categoria' => $categorias, 'pdf_ruta' => null])->save();
        }

        return array_values(array_filter($archivos));
    }
}
