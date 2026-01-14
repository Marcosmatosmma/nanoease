<?php

declare(strict_types=1);

namespace App\Domain\Automations\Actions;

use App\Models\User;
use App\Domain\AI\Services\RuleInterpreter;

final class SimulateEmailAutomationAction
{
    public function __construct(private readonly RuleInterpreter $ruleInterpreter)
    {
    }

    public function handle(User $user, string $ruleText, string $actionType, array $actionConfig = [], array $integrationMetadata = []): array
    {
        $targetEmail = $this->extractEmail($ruleText);
        $exampleEvent = [
            'from' => $targetEmail ?? 'cliente@exemplo.com',
            'subject' => 'Fatura 1234 enviada pelo cliente',
            'snippet' => 'Olá, segue em anexo a fatura referente ao mês atual.',
        ];

        $interpretation = $this->ruleInterpreter->interpret($ruleText, $exampleEvent);

        $decision = $interpretation['should_execute'] ? 'executar' : 'ignorar';
        $summary = $interpretation['should_execute']
            ? 'Email recebido atende à regra e a ação seria aplicada.'
            : 'Email não atende claramente à regra; ação seria ignorada.';

        return [
            'decision' => $decision,
            'summary' => $summary,
            'rule' => $ruleText,
            'action' => $actionType,
            'action_config' => $actionConfig,
            'integration_email' => $integrationMetadata['email'] ?? null,
            'interpretation' => $interpretation,
        ];
    }

    private function extractEmail(string $text): ?string
    {
        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $matches)) {
            return $matches[0];
        }

        return null;
    }
}
