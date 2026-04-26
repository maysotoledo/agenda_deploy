<?php

namespace App\Notifications;

use App\Models\Evento;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgendamentoAlteradoMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly Evento $evento,
        private readonly string $acao,
        private readonly ?string $atorNome = null,
        private readonly string $recipientContext = 'agenda_owner',
    ) {
        $this->onConnection('database');
        $this->afterCommit();
    }

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

        $openingLine = $this->recipientContext === 'actor'
            ? 'Voce ' . ($tipoAcao === 'criado' ? 'criou' : 'editou') . ' um agendamento no sistema.'
            : 'Um agendamento da sua agenda foi ' . $tipoAcao . '.';

        return (new MailMessage)
            ->subject('Agendamento ' . $tipoAcao . ' - ' . config('app.name'))
            ->view('emails.agendamento-alterado', [
                'subject' => 'Agendamento ' . $tipoAcao . ' - ' . config('app.name'),
                'title' => 'Olá, ' . trim((string) ($notifiable->name ?? '')),
                'openingLine' => $openingLine,
                'details' => [
                    'Responsável pela agenda' => $responsavelAgenda,
                    'Ação realizada por' => $ator,
                    'Intimado' => $evento->intimado ?: '-',
                    'Data e hora' => $dataHora . ' (GMT-3)',
                    'Procedimento' => $evento->numero_procedimento ?: '-',
                    'WhatsApp' => $evento->whatsapp ?: '-',
                    'Modalidade' => $evento->oitiva_online ? 'Online' : 'Presencial',
                ],
            ]);
    }
}
