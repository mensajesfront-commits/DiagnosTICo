<?php

namespace App\Notifications;

use App\Models\Medicion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Revisión antes de publicar: la IA terminó y el resultado espera que un
 * administrador lo apruebe. Le llega a cada Administrador activo; se puede
 * apagar en Mi perfil (A6).
 */
class ResultadoPorRevisar extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Medicion $medicion) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User && ! $notifiable->quiereAviso('resultado_por_revisar') ? [] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $empresa = $this->medicion->empresa->nombre ?? 'Una empresa';

        return (new MailMessage)
            ->subject("Resultado por revisar: {$empresa}")
            ->line("La IA terminó de analizar la medición {$this->medicion->numero} de {$empresa}.")
            ->line('El resultado no se publica hasta que lo apruebes. Revisa las observaciones y recomendaciones y apruébalo, o pide rehacer el análisis de una categoría.')
            ->action('Revisar el resultado', route('revision.ver', $this->medicion));
    }
}
