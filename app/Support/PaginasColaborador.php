<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * E12.1 y E12.3 · Páginas que la cuenta principal deja ver a cada
 * colaborador. Cada página es un permiso directo de la cuenta del
 * colaborador; el rol Colaborador no trae ninguno.
 *
 * Para dar acceso a una página nueva, se agrega aquí su permiso: aparece
 * sola en los modales de Colaboradores. Inicio y Mi perfil (solo lectura)
 * siempre se ven.
 */
final class PaginasColaborador
{
    /**
     * Permiso => [título, qué deja hacer].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const array PAGINAS = [
        'diagnostico.responder' => ['Responder el diagnóstico', 'Inicia, continúa y envía la medición pendiente.'],
        'resultados.ver' => ['Resultados y Mi historial', 'Ve los resultados, las respuestas y la evolución.'],
        'resultados.pdf' => ['Descargar el informe PDF', 'Necesita ver los resultados.'],
    ];

    /** Páginas que necesitan otra para tener sentido. */
    private const array NECESITA = [
        'resultados.pdf' => 'resultados.ver',
    ];

    /**
     * Para la pantalla: clave, título y descripción.
     *
     * @return list<array{clave: string, titulo: string, descripcion: string, necesita: string|null}>
     */
    public static function opciones(): array
    {
        $opciones = [];

        foreach (self::PAGINAS as $clave => [$titulo, $descripcion]) {
            $opciones[] = ['clave' => $clave, 'titulo' => $titulo, 'descripcion' => $descripcion, 'necesita' => self::NECESITA[$clave] ?? null];
        }

        return $opciones;
    }

    /**
     * Páginas que hoy tiene el colaborador.
     *
     * @return list<string>
     */
    public static function de(User $colaborador): array
    {
        return array_values(array_intersect(
            array_keys(self::PAGINAS),
            $colaborador->getDirectPermissions()->pluck('name')->all(),
        ));
    }

    /**
     * Reglas del campo `paginas`.
     *
     * @return array<string, list<mixed>>
     */
    public static function reglas(): array
    {
        return [
            'paginas' => ['required', 'array', 'min:1'],
            'paginas.*' => ['string', 'distinct', Rule::in(array_keys(self::PAGINAS))],
        ];
    }

    /** "Descargar el PDF" sin "Resultados" no sirve: el botón está en el resultado. */
    public static function revisar(Validator $validator): void
    {
        /** @var list<string> $paginas */
        $paginas = (array) ($validator->getData()['paginas'] ?? []);

        foreach (self::NECESITA as $pagina => $necesita) {
            if (in_array($pagina, $paginas, true) && ! in_array($necesita, $paginas, true)) {
                $validator->errors()->add('paginas', 'Para «'.self::PAGINAS[$pagina][0].'» marca también «'.self::PAGINAS[$necesita][0].'».');
            }
        }
    }

    /** @param  list<string>  $paginas */
    public static function asignar(User $colaborador, array $paginas): void
    {
        $colaborador->syncPermissions(array_values(array_intersect(array_keys(self::PAGINAS), $paginas)));
    }
}
