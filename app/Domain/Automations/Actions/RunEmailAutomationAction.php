<?php

declare(strict_types=1);

namespace App\Domain\Automations\Actions;

use App\Domain\Automations\Models\Automation;
use App\Domain\Integrations\Services\EmailSenderManager;
use Illuminate\Support\Arr;

final class RunEmailAutomationAction
{
    public function __construct(private readonly EmailSenderManager $emailSenderManager)
    {
    }

    public function handle(Automation $automation, array $email): array
    {
        $automation->loadMissing('actions');

        $action = $automation->actions->sortBy('position')->first();
        if (! $action) {
            return $this->result('ignored', 'Nenhuma ação configurada.');
        }

        if ($automation->status !== Automation::STATUS_ACTIVE) {
            return $this->result('ignored', 'Automação não está ativa.');
        }

        if ($automation->event !== 'email_received') {
            return $this->result('ignored', 'Evento não suportado.');
        }

        if ($action->type !== 'encaminhar') {
            return $this->result('ignored', 'Ação não implementada neste teste.');
        }

        $recipients = Arr::wrap(Arr::get($action->config, 'forward_to', []));
        $recipients = array_values(array_filter(array_map('trim', $recipients)));

        if (empty($recipients)) {
            return $this->result('ignored', 'Nenhum destinatário configurado.');
        }

        $from = $email['from'] ?? 'teste@example.com';
        $subject = $email['subject'] ?? 'Email de teste da automação';
        $body = $email['body'] ?? $email['snippet'] ?? 'Corpo não fornecido.';

        $sendResult = $this->emailSenderManager->send($automation, $from, $recipients, $subject, $body);

        return $this->result(
            $sendResult['status'] ?? 'error',
            $sendResult['message'] ?? 'Falha ao enviar.',
            [
                'recipients' => $recipients,
                'from' => $from,
                'subject' => $subject,
                'provider' => $sendResult['provider'] ?? null,
                'context' => $sendResult['context'] ?? [],
            ],
        );
    }

    private function result(string $status, string $message, array $context = []): array
    {
        return [
            'status' => $status,
            'message' => $message,
            'context' => $context,
        ];
    }
}
