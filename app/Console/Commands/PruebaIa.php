<?php

namespace App\Console\Commands;

use App\Services\Ia\AnalizadorDeCategoria;
use App\Services\Ia\RespuestaIaInvalida;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Prueba técnica T-019: una llamada real a OpenAI que califica una respuesta
 * abierta y devuelve el JSON con el formato fijo. Necesita OPENAI_API_KEY y
 * OPENAI_MODEL en el .env; cada ejecución gasta una llamada.
 */
#[Signature('prueba:ia')]
#[Description('Prueba técnica: analiza una categoría de ejemplo con OpenAI y muestra el JSON')]
class PruebaIa extends Command
{
    public function handle(AnalizadorDeCategoria $analizador): int
    {
        $categoria = self::categoriaDeEjemplo();

        $this->info('Enviando la categoría "'.$categoria['nombre'].'" a OpenAI…');

        try {
            $resultado = $analizador->analizar($categoria);
        } catch (RespuestaIaInvalida $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line((string) json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    /**
     * Categoría de ejemplo tomada del wireframe E6 (Restaurante La Esquina).
     *
     * @return array{nombre: string, sector: string, preguntas: list<array{id: int, tipo: string, texto: string, respuesta: string, puntaje: int|null, criterio?: string}>}
     */
    public static function categoriaDeEjemplo(): array
    {
        return [
            'nombre' => 'Herramientas de automatización',
            'sector' => 'Comidas',
            'preguntas' => [
                [
                    'id' => 1,
                    'tipo' => 'opcion_unica',
                    'texto' => '¿Qué herramientas usas para recibir pedidos o reservas?',
                    'respuesta' => 'WhatsApp personal y llamadas',
                    'puntaje' => 20,
                ],
                [
                    'id' => 2,
                    'tipo' => 'opcion_unica',
                    'texto' => '¿Automatizas algún mensaje a tus clientes?',
                    'respuesta' => 'No',
                    'puntaje' => 0,
                ],
                [
                    'id' => 3,
                    'tipo' => 'abierta',
                    'texto' => 'Describe cómo respondes los mensajes que llegan por redes sociales.',
                    'respuesta' => 'Los contesto yo cuando tengo tiempo, a veces al día siguiente.',
                    'puntaje' => null,
                    'criterio' => 'Más puntaje si responde rápido, con un responsable claro y con mensajes automáticos o plantillas.',
                ],
            ],
        ];
    }
}
