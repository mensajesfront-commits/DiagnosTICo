<?php

namespace App\Notifications;

use App\Models\Empresa;
use App\Models\Medicion;
use App\Support\CorreoAviso;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * HU-082 / HU-083 · Una empresa pide una medición nueva porque la suya
 * venció. Le llega a cada Administrador activo, que la atiende asignando
 * otra medición (A3.1b).
 */
class NuevaMedicionSolicitada extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Empresa $empresa, public Medicion $vencida, public string $quien) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fecha = $this->vencida->fecha_limite ? CorreoAviso::fecha($this->vencida->fecha_limite) : null;

        return (new MailMessage)
            ->subject("{$this->empresa->nombre} pide una nueva medición")
            ->line("{$this->quien}, de {$this->empresa->nombre}, pide una nueva fecha: la medición {$this->vencida->numero} venció".($fecha ? " el {$fecha}" : '').'.')
            ->action('Asignar medición', route('mediciones.crear', ['empresa' => $this->empresa->id]))
            ->line('Al asignarle una medición, la solicitud queda atendida.');
    }
}
