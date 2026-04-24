<?php

namespace App\Notifications;

use App\Models\Evento;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgendamentoAlteradoMailNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Evento $evento,
        private readonly string $acao,
        private readonly ?string $atorNome = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $evento = $this->evento->refresh()->loadMissing('user');
        $ator = trim((string) ($this->atorNome ?? ''));
        $ator = $ator !== '' ? $ator : 'Sistema';

        $dataHora = $evento->starts_at
            ? Carbon::parse($evento->starts_at, 'America/Sao_Paulo')->format('d/m/Y H:i')
            : '-';

        $responsavelAgenda = trim((string) ($evento->user?->name ?? ''));
        $responsavelAgenda = $responsavelAgenda !== '' ? $responsavelAgenda : 'Nao informado';

        $tipoAcao = match ($this->acao) {
            'criado' => 'criado',
            'atualizado' => 'editado',
            default => $this->acao,
        };

        return (new MailMessage)
            ->subject('Agendamento ' . $tipoAcao . ' - ' . config('app.name'))
            ->greeting('Ola, ' . trim((string) ($notifiable->name ?? '')))
            ->line('Um agendamento da sua agenda foi ' . $tipoAcao . '.')
            ->line('Responsavel pela agenda: ' . $responsavelAgenda)
            ->line('Acao realizada por: ' . $ator)
            ->line('Data e hora: ' . $dataHora . ' (GMT-3)')
            ->line('Intimado: ' . ($evento->intimado ?: '-'))
            ->line('Procedimento: ' . ($evento->numero_procedimento ?: '-'))
            ->line('WhatsApp: ' . ($evento->whatsapp ?: '-'))
            ->line('Modalidade: ' . ($evento->oitiva_online ? 'Online' : 'Presencial'))
            ->line('Se necessario, acesse o sistema para acompanhar os detalhes atualizados.');
    }
}
