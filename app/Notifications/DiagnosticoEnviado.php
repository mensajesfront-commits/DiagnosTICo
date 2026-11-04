<?php

namespace App\Notifications;

use App\Models\Medicion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Una empresa envió su diagnóstico (HU-062). Le llega a cada Administrador
 * activo que tenga encendido "Cuando una empresa envía su diagnóstico" en Mi
 * perfil (A6). El resultado llega solo cuando la IA termina.
 */
class DiagnosticoEnviado extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Medicion $medicion) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User && ! $notifiable->quiereAviso('empresa_envia') ? [] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $empresa = $this->medicion->empresa->nombre ?? '';

        return (new MailMessage)
            ->subject("{$empresa} envió su diagnóstico")
            ->line("{$empresa} envió la medición {$this->medicion->numero}. La IA ya la está analizando.")
            ->line('Cuando termine, el resultado queda publicado y la empresa recibe su aviso.')
            ->action('Ver la empresa', route('empresas.ver', $this->medicion->empresa_id))
            ->line('Puedes apagar este aviso en Mi perfil.');
    }
}
