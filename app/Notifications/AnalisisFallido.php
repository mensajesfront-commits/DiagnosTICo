<?php

namespace App\Notifications;

use App\Models\AnalisisCategoria;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * RN-021 · Una categoría no se pudo analizar tras 3 intentos. Le llega a
 * cada Administrador activo. La empresa ve el aviso en E4 y puede reintentar.
 */
class AnalisisFallido extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AnalisisCategoria $analisis, public ?string $error = null) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        // Se puede apagar en Mi perfil (A6).
        return $notifiable instanceof User && ! $notifiable->quiereAviso('ia_falla') ? [] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $medicion = $this->analisis->medicion;
        /** @var list<array<string, mixed>> $categorias */
        $categorias = $medicion->version->contenido['categorias'] ?? [];
        $nombre = collect($categorias)->firstWhere('ref', $this->analisis->categoria_ref)['nombre'] ?? $this->analisis->categoria_ref;

        $correo = (new MailMessage)
            ->subject("No se pudo analizar una categoría de {$medicion->empresa->nombre}")
            ->line("La IA no pudo analizar «{$nombre}» de la medición {$medicion->numero} de {$medicion->empresa->nombre} después de {$this->analisis->intentos} intentos.")
            ->line('Las demás categorías conservan su análisis. La empresa ve el aviso y puede reintentar; revisa la clave y el modelo de OpenAI en el .env si el error se repite.');

        if ($this->error) {
            $correo->line('Error: '.Str::limit($this->error, 300));
        }

        return $correo->action('Ver la empresa', route('empresas.ver', $medicion->empresa_id));
    }
}
