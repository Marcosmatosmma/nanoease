<?php

declare(strict_types=1);

namespace App\Domain\Automations\Services\Executors;

use App\Domain\AI\Services\Automations\ActionDecisionEngine;
use App\Domain\Automations\Models\Automation;
use App\Domain\Integrations\Services\EmailSenderManager;

final class EmailAutomationExecutor
{
    public function __construct(
        private readonly ActionDecisionEngine $decisionEngine,
        private readonly EmailSenderManager $emailSender,
    ) {}

    /**
     * Executa a automação usando o MCP para decisão inteligente.
     *
     * @param Automation $automation
     * @param array $event Evento normalizado (from, subject, body/snippet)
     * @return array Resultado da execução
     */
    public function execute(Automation $automation, array $event): array
    {
        if ($automation->status !== Automation::STATUS_ACTIVE) {
            return $this->result('ignored', 'Automação não está ativa.', []);
        }

        $automation->loadMissing('actions');
        
        if ($automation->actions->isEmpty()) {
            return $this->result('ignored', 'Nenhuma ação configurada.', []);
        }

        $planRule = $automation->plan_rule_text ?: $automation->rule_text;
        
        $availableActions = $automation->actions->map(fn ($action) => [
            'type' => $action->type,
            'position' => $action->position,
            'config' => $action->config,
        ])->toArray();

        $decision = $this->decisionEngine->decide($planRule, $event, $availableActions);

        if (!$decision['should_execute']) {
            return $this->result('ignored', $decision['reasoning'], [
                'confidence' => $decision['confidence'],
                'matched_conditions' => $decision['matched_conditions'],
            ]);
        }

        $actionType = $decision['action_to_execute'];
        $actionParams = $decision['action_parameters'];

        $executionResult = match ($actionType) {
            'encaminhar' => $this->executeForward($automation, $event, $actionParams),
            'responder' => $this->executeReply($automation, $event, $actionParams),
            'classificar' => $this->executeClassify($automation, $event, $actionParams),
            'criar_tarefa' => $this->executeCreateTask($automation, $event, $actionParams),
            default => $this->result('error', "Ação '{$actionType}' não implementada.", []),
        };

        $executionResult['context']['decision'] = [
            'confidence' => $decision['confidence'],
            'matched_conditions' => $decision['matched_conditions'],
            'reasoning' => $decision['reasoning'],
        ];

        return $executionResult;
    }

    private function executeForward(Automation $automation, array $event, array $params): array
    {
        $recipients = $params['forward_to'] ?? [];
        
        if (empty($recipients)) {
            return $this->result('error', 'Nenhum destinatário especificado para encaminhar.', []);
        }

        $from = $event['from'] ?? 'unknown@example.com';
        $subject = $event['subject'] ?? 'Sem assunto';
        $body = $event['body'] ?? $event['snippet'] ?? 'Sem conteúdo';

        $sendResult = $this->emailSender->send(
            $automation,
            $from,
            $recipients,
            $subject,
            $body
        );

        return $this->result(
            $sendResult['status'] ?? 'error',
            $sendResult['message'] ?? 'Falha ao encaminhar.',
            [
                'recipients' => $recipients,
                'original_from' => $from,
                'subject' => $subject,
                'provider' => $sendResult['provider'] ?? null,
            ]
        );
    }

    private function executeReply(Automation $automation, array $event, array $params): array
    {
        $replyText = $params['reply_text'] ?? '';
        $replyTo = $event['from'] ?? null;

        if (!$replyTo) {
            return $this->result('error', 'Não foi possível identificar o remetente para responder.', []);
        }

        if (empty($replyText)) {
            return $this->result('error', 'Texto de resposta não especificado.', []);
        }

        $subject = $event['subject'] ?? 'Sem assunto';
        
        $sendResult = $this->emailSender->send(
            $automation,
            $automation->integration?->metadata['email'] ?? config('mail.from.address'),
            [$replyTo],
            "Re: {$subject}",
            $replyText
        );

        return $this->result(
            $sendResult['status'] ?? 'error',
            $sendResult['message'] ?? 'Falha ao responder.',
            [
                'reply_to' => $replyTo,
                'subject' => $subject,
                'provider' => $sendResult['provider'] ?? null,
            ]
        );
    }

    private function executeClassify(Automation $automation, array $event, array $params): array
    {
        return $this->result('executed', 'Ação de classificação não implementada ainda.', [
            'category' => $params['category'] ?? null,
            'tags' => $params['tags'] ?? [],
            'event' => $event,
        ]);
    }

    private function executeCreateTask(Automation $automation, array $event, array $params): array
    {
        return $this->result('executed', 'Ação de criar tarefa não implementada ainda.', [
            'task_title' => $params['title'] ?? 'Nova tarefa',
            'task_description' => $params['description'] ?? '',
            'event' => $event,
        ]);
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
