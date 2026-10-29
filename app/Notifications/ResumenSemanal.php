<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Resumen semanal de las mediciones pendientes (A6, "Resumen semanal de
 * mediciones pendientes"). Lo manda `mediciones:resumen` a los
 * Administradores que lo encendieron en Mi perfil.
 */
class ResumenSemanal extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, int>  $cuenta  por estado: no_iniciada, en_curso, enviada, vencida
     * @param  list<string>  $vencidas  "Empresa · medición N"
     */
    public function __construct(public array $cuenta, public array $vencidas, public int $solicitudes) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User && ! $notifiable->quiereAviso('resumen_semanal') ? [] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $c = $this->cuenta;
        $correo = (new MailMessage)
            ->subject('Resumen semanal de mediciones pendientes')
            ->line('No iniciadas: '.($c['no_iniciada'] ?? 0).' · En curso: '.($c['en_curso'] ?? 0)
                .' · En análisis: '.($c['enviada'] ?? 0).' · Vencidas: '.($c['vencida'] ?? 0).'.');

        if ($this->solicitudes > 0) {
            $correo->line($this->solicitudes === 1
                ? '1 empresa pidió una medición nueva.'
                : "{$this->solicitudes} empresas pidieron una medición nueva.");
        }

        if ($this->vencidas !== []) {
            $correo->line('Vencidas: '.implode('; ', $this->vencidas).'.');
        }

        return $correo
            ->action('Ir al inicio', route('inicio.administrador'))
            ->line('Puedes apagar este resumen en Mi perfil.');
    }
}
