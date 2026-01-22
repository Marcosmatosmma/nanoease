<?php

declare(strict_types=1);

namespace App\Domain\Automations\Actions;

use App\Domain\Integrations\Models\Integration;
use App\Domain\Integrations\Services\GmailMessageFetcher;
use App\Domain\AI\Services\RuleInterpreter;
use App\Models\User;

final class TestAutomationWithRealEmailsAction
{
    public function __construct(
        private readonly GmailMessageFetcher $gmailFetcher,
        private readonly RuleInterpreter $ruleInterpreter
    ) {
    }

    public function handle(User $user, Integration $integration, int $triggerTypeId, string $ruleText): array
    {
        $triggerType = \App\Domain\Automations\Models\TriggerType::find($triggerTypeId);
        if (! $triggerType) {
            return [
                'status' => 'error',
                'message' => 'Tipo de trigger inválido.',
                'examples' => [],
            ];
        }

        $result = $this->gmailFetcher->fetch($integration, '', 10);

        if ($result['status'] !== 'ok' || empty($result['messages'])) {
            return [
                'status' => 'warning',
                'message' => 'Não conseguimos buscar e-mails da sua caixa. Verifique a conexão com o Gmail.',
                'examples' => [],
            ];
        }

        $examples = [];
        foreach ($result['messages'] as $message) {
            $event = [
                'from' => $message['from'] ?? '',
                'subject' => $message['subject'] ?? '',
                'snippet' => $message['snippet'] ?? '',
            ];

            $interpretation = $this->ruleInterpreter->interpret($ruleText, $event);

            $decision = 'ignore';
            $variant = 'default';
            $icon = 'lucide:minus-circle';
            $confidence = $interpretation['confidence'] ?? null;

            if ($interpretation['should_execute']) {
                if ($confidence !== null && $confidence >= 0.8) {
                    $decision = 'execute';
                    $variant = 'success';
                    $icon = 'lucide:check-circle';
                } elseif ($confidence !== null && $confidence >= 0.5) {
                    $decision = 'uncertain';
                    $variant = 'warning';
                    $icon = 'lucide:alert-circle';
                } else {
                    $decision = 'execute';
                    $variant = 'success';
                    $icon = 'lucide:check-circle';
                }
            }

            $examples[] = [
                'from' => $message['from'] ?? 'Desconhecido',
                'subject' => $message['subject'] ?? '(sem assunto)',
                'snippet' => mb_substr($message['snippet'] ?? '', 0, 100),
                'decision' => $decision,
                'variant' => $variant,
                'icon' => $icon,
                'confidence' => $confidence,
                'reasoning' => $interpretation['reasoning'] ?? '',
            ];
        }

        return [
            'status' => 'ok',
            'message' => sprintf('Testado contra %d e-mails recentes da sua caixa.', count($examples)),
            'examples' => $examples,
        ];
    }
}
