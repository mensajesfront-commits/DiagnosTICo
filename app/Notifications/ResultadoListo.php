<?php

namespace App\Notifications;

use App\Models\Medicion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HU-084 CA-004 · El resultado de la medición está publicado. Le llega a las
 * cuentas activas de la empresa (principal y colaboradores).
 */
class ResultadoListo extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Medicion $medicion) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        // Se puede apagar en Mi perfil (E11).
        return $notifiable instanceof User && ! $notifiable->quiereAviso('resultado_listo') ? [] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tu resultado de la medición {$this->medicion->numero} está listo")
            ->greeting('Hola, '.($notifiable->name ?? '').':')
            ->line("Ya terminamos de analizar el diagnóstico de {$this->medicion->empresa->nombre}.")
            ->line('Puedes ver tu puntaje, el nivel por categoría y las recomendaciones para empezar.')
            ->action('Ver mi resultado', route('resultados.ver', $this->medicion));
    }
}
