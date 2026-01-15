<?php

declare(strict_types=1);

namespace App\Domain\Automations\Services\Executors;

use App\Domain\AI\Services\Automations\ActionDecisionEngine;
use App\Domain\Automations\Models\Automation;
use App\Domain\Automations\Models\ClassifiedEmail;
use App\Domain\Automations\Services\EmailResponseComposer;
use App\Domain\Integrations\Services\EmailSenderManager;
use App\Domain\Integrations\Services\GmailLabelManager;
use Carbon\Carbon;

final class EmailAutomationExecutor
{
    public function __construct(
        private readonly ActionDecisionEngine $decisionEngine,
        private readonly EmailSenderManager $emailSender,
        private readonly EmailResponseComposer $responseComposer,
        private readonly GmailLabelManager $gmailLabelManager,
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
            'organizar' => $this->executeClassify($automation, $event, $actionParams),
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

    /**
     * Executa ação de resposta automática
     * Substitui variáveis dinâmicas no template e envia resposta
     */
    private function executeReply(Automation $automation, array $event, array $params): array
    {
        // Busca config da ação ou usa params da decisão da IA
        $action = $automation->actions()->orderBy('position')->first();
        $config = $action?->config ?? [];
        
        $replySubjectTemplate = $config['reply_subject'] ?? $params['reply_subject'] ?? 'Re: {subject}';
        $replyBodyTemplate = $config['reply_body'] ?? $params['reply_body'] ?? '';
        
        $replyTo = $this->extractEmail($event['from'] ?? '');

        if (!$replyTo) {
            return $this->result('error', 'Não foi possível identificar o remetente para responder.', []);
        }

        if (empty(trim(strip_tags($replyBodyTemplate)))) {
            return $this->result('error', 'Texto de resposta não especificado.', []);
        }

        // Substitui variáveis dinâmicas
        $replySubject = $this->responseComposer->compose($replySubjectTemplate, $event);
        $replyBody = $this->responseComposer->compose($replyBodyTemplate, $event);
        
        $sendResult = $this->emailSender->send(
            $automation,
            $automation->integration?->metadata['email'] ?? config('mail.from.address'),
            [$replyTo],
            $replySubject,
            $replyBody
        );

        return $this->result(
            $sendResult['status'] ?? 'error',
            $sendResult['message'] ?? 'Falha ao responder.',
            [
                'reply_to' => $replyTo,
                'subject' => $replySubject,
                'provider' => $sendResult['provider'] ?? null,
            ]
        );
    }

    /**
     * Executa ação de organizar/classificar e-mail
     * Aplica label no Gmail
     */
    private function executeClassify(Automation $automation, array $event, array $params): array
    {
        $automation->loadMissing('integration');
        
        if (!$automation->integration) {
            return $this->result('error', 'Integração não encontrada.', []);
        }

        $gmailLabel = $automation->gmail_label;
        
        if (empty($gmailLabel)) {
            return $this->result('error', 'Gmail label não configurado nesta automação.', []);
        }

        $gmailMessageId = $event['gmail_id'] ?? $event['id'] ?? null;
        
        if (!$gmailMessageId) {
            return $this->result('error', 'ID do e-mail não encontrado.', []);
        }

        // Aplicar label no Gmail
        $result = $this->gmailLabelManager->applyLabel(
            $automation->integration,
            $gmailMessageId,
            $gmailLabel
        );

        if (!$result['success']) {
            return $this->result('error', $result['message'], [
                'gmail_label' => $gmailLabel,
                'gmail_message_id' => $gmailMessageId,
            ]);
        }

        // Salvar metadados no banco de dados
        try {
            ClassifiedEmail::create([
                'user_id' => $automation->user_id,
                'automation_id' => $automation->id,
                'integration_id' => $automation->integration_id,
                'gmail_id' => $gmailMessageId,
                'email_message_id' => $event['message_id'] ?? null,
                'email_from' => $event['from'] ?? null,
                'email_subject' => $event['subject'] ?? null,
                'email_date' => isset($event['date']) ? Carbon::parse($event['date']) : now(),
                'gmail_label' => $gmailLabel,
                'metadata' => [
                    'snippet' => $event['snippet'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            // Log erro mas não falha a execução (label já foi aplicado)
            \Log::error('Erro ao salvar classified_email', [
                'error' => $e->getMessage(),
                'automation_id' => $automation->id,
                'gmail_id' => $gmailMessageId,
            ]);
        }

        return $this->result(
            'executed',
            $result['message'],
            [
                'gmail_label' => $gmailLabel,
                'gmail_message_id' => $gmailMessageId,
                'email_subject' => $event['subject'] ?? '',
            ]
        );
    }

    private function executeCreateTask(Automation $automation, array $event, array $params): array
    {
        return $this->result('executed', 'Ação de criar tarefa não implementada ainda.', [
            'task_title' => $params['title'] ?? 'Nova tarefa',
            'task_description' => $params['description'] ?? '',
            'event' => $event,
        ]);
    }

    /**
     * Extrai e-mail do campo From
     */
    private function extractEmail(string $from): string
    {
        if (preg_match('/<(.+?)>/', $from, $matches)) {
            return $matches[1];
        }
        
        return trim($from);
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
