<?php

declare(strict_types=1);

namespace App\Domain\Automations\Actions;

use App\Domain\Automations\Models\Automation;
use App\Domain\Automations\Models\AutomationAction;
use App\Domain\Integrations\Services\EmailSenderManager;
use App\Domain\Tasks\Actions\CreateTaskFromEmailAction;
use Illuminate\Support\Arr;

final class RunEmailAutomationAction
{
    public function __construct(
        private readonly EmailSenderManager $emailSenderManager,
        private readonly CreateTaskFromEmailAction $createTaskAction,
    ) {
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

        // Despachar para handler específico por tipo de ação
        return match ($action->type) {
            'encaminhar' => $this->handleForwardEmail($automation, $action, $email),
            'tarefa' => $this->handleCreateTask($automation, $action, $email),
            default => $this->result('ignored', 'Ação não implementada: ' . $action->type),
        };
    }

    /**
     * Processa ação de encaminhar email
     */
    private function handleForwardEmail(Automation $automation, AutomationAction $action, array $email): array
    {
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

    /**
     * Processa ação de criar tarefa
     */
    private function handleCreateTask(Automation $automation, AutomationAction $action, array $email): array
    {
        $config = $action->config ?? [];
        
        if (empty($config['board_list_id'])) {
            return $this->result('error', 'Lista não configurada na automação.');
        }
        
        try {
            $task = $this->createTaskAction->handle($email, $config, $automation->team_id, $automation->user_id);
            
            return $this->result('success', 'Tarefa criada com sucesso.', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'board_list' => $task->boardList->name,
                'assigned_to' => $task->assignedUser?->name,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->result('error', 'Lista não encontrada ou sem permissão.');
        } catch (\Exception $e) {
            return $this->result('error', 'Falha ao criar tarefa: ' . $e->getMessage());
        }
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
