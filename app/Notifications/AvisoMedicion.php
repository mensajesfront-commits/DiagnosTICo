<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de aviso de una medición (A3.1e, HU-036 CA-006, HU-008): el asunto
 * y el cuerpo llegan ya rellenados (`CorreoAviso::rellenar`). Va a la cola
 * para no bloquear la pantalla (HU-037 CA-004) y se reintenta hasta 3 veces
 * si falla (CA-005). `prueba` es el que el Administrador se envía a sí mismo.
 */
class AvisoMedicion extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $asunto,
        public string $cuerpo,
        public bool $prueba = false,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $correo = (new MailMessage)
            ->subject(($this->prueba ? '[Prueba] ' : '').$this->asunto)
            ->greeting(' ');

        // Cada párrafo del cuerpo es una línea del correo.
        foreach (preg_split("/\n\s*\n/", $this->cuerpo) ?: [] as $parrafo) {
            $correo->line(trim($parrafo));
        }

        return $correo
            ->action('Entrar e iniciar el diagnóstico', route('inicio.empresa'))
            ->line('Recibes este correo porque tu empresa está registrada en '.config('app.name').'. Si tienes dudas, responde a tu administrador.')
            ->salutation(' ');
    }
}
