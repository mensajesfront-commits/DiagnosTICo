<?php

namespace App\Notifications;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de Captter: escapa el Markdown de cada línea y del saludo (auditoría
 * QA, ERR-03). Las líneas llevan textos que escriben las personas (nombre de
 * la empresa, motivo, mensaje); sin esto, un nombre como
 * "[Haz clic](https://…)" se volvía un enlace real en el correo. El HTML ya lo
 * escapa la plantilla; aquí solo se neutraliza el Markdown. Lo que va como
 * HtmlString (la caja del código) no se toca.
 */
class Correo extends MailMessage
{
    /**
     * Caracteres con los que el Markdown arma enlaces, imágenes o énfasis
     * dentro de una línea. Los paréntesis solos no hacen enlace sin corchetes.
     */
    private const string ESPECIALES = '/([\\\\`*_\[\]|~])/u';

    public static function escapar(string $texto): string
    {
        $texto = (string) preg_replace(self::ESPECIALES, '\\\\$1', $texto);

        // "# Algo", "1. Algo", "- Algo" o "+ Algo" al inicio serían un título o una lista.
        $texto = (string) preg_replace('/^(\s*)([#+-])(\s)/u', '$1\\\\$2$3', $texto);

        return (string) preg_replace('/^(\s*\d+)\.(\s)/u', '$1\\\\.$2', $texto);
    }

    /**
     * @param  mixed  $line
     * @return Htmlable|string
     */
    protected function formatLine($line)
    {
        $linea = parent::formatLine($line);

        return $linea instanceof Htmlable ? $linea : self::escapar($linea);
    }

    /**
     * @param  string  $greeting
     * @return $this
     */
    public function greeting($greeting)
    {
        return parent::greeting(self::escapar($greeting));
    }
}
