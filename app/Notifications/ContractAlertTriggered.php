<?php

namespace App\Notifications;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractAlertTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Contract $contract,
        public ContractAlert $alert
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $alertLabel = $this->getAlertLabel();
        $daysUntilExpiration = $this->getDaysUntilExpiration();
        
        $message = (new MailMessage)
            ->subject("⚠️ Alerta de Vencimento: {$this->contract->name}")
            ->greeting("Olá!")
            ->line("Este é um alerta sobre o contrato **{$this->contract->name}**.");

        // Informação específica do tipo de alerta
        if ($this->alert->alert_type === 'before_expiration') {
            $message->line("🗓️ O contrato vence em **{$daysUntilExpiration} dias** ({$this->contract->end_date->format('d/m/Y')})");
        } elseif ($this->alert->alert_type === 'on_expiration') {
            $message->line("🗓️ O contrato vence **hoje** ({$this->contract->end_date->format('d/m/Y')})");
        } else {
            $message->line("⚠️ O contrato **já venceu** em {$this->contract->end_date->format('d/m/Y')}");
        }

        // Informações do contrato
        $message->line("**Informações do Contrato:**")
            ->line("• **Tipo:** {$this->contract->contract_type}")
            ->line("• **Status:** {$this->contract->status}");

        if ($this->contract->contractor) {
            $message->line("• **Contratante:** {$this->contract->contractor}");
        }

        if ($this->contract->contracted) {
            $message->line("• **Contratado:** {$this->contract->contracted}");
        }

        if ($this->contract->amount) {
            $message->line("• **Valor:** R$ " . number_format($this->contract->amount, 2, ',', '.'));
        }

        // Link para o contrato
        $message->action('Ver Contrato', route('contracts.show', $this->contract->id))
            ->line('Para gerenciar este alerta ou revisar o contrato, acesse o sistema.');

        // Renovação automática
        if ($this->contract->auto_renewal) {
            $message->line("⚠️ **Atenção:** Este contrato possui renovação automática.");
        }

        // Cópias (CC)
        if ($this->alert->email_cc && count($this->alert->email_cc) > 0) {
            $message->cc($this->alert->email_cc);
        }

        return $message;
    }

    /**
     * Retorna label do alerta
     */
    private function getAlertLabel(): string
    {
        return match($this->alert->alert_type) {
            'before_expiration' => "{$this->alert->days_before} dias antes do vencimento",
            'on_expiration' => 'No dia do vencimento',
            'after_expiration' => 'Após o vencimento',
            default => $this->alert->alert_type
        };
    }

    /**
     * Calcula dias até o vencimento
     */
    private function getDaysUntilExpiration(): int
    {
        if (!$this->contract->end_date) {
            return 0;
        }

        return now()->startOfDay()->diffInDays($this->contract->end_date->startOfDay(), false);
    }
}
