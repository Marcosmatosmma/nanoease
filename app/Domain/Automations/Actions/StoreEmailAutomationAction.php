<?php

declare(strict_types=1);

namespace App\Domain\Automations\Actions;

use App\Domain\Automations\Models\Automation;
use App\Domain\Automations\Models\AutomationAction;
use App\Domain\AI\Services\EmailRulePlanner;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class StoreEmailAutomationAction
{
    public function __construct(
        private readonly EmailRulePlanner $planner,
    ) {}

    public function handle(User $user, int $triggerTypeId, string $ruleText, string $actionType, array $actionConfig = [], ?int $integrationId = null, ?int $automationId = null, ?string $gmailLabel = null): Automation
    {
        // Validar configuração específica por tipo de ação
        $this->validateActionConfig($user, $actionType, $actionConfig);
        
        return DB::transaction(function () use ($user, $triggerTypeId, $ruleText, $actionType, $actionConfig, $integrationId, $automationId, $gmailLabel) {
            $plan = $this->buildPlanSafely($ruleText, $actionType, $actionConfig);

            // Buscar o automation_event_id do trigger_type
            $triggerType = \App\Domain\Automations\Models\AutomationTriggerType::findOrFail($triggerTypeId);
            $automationEventId = $triggerType->automation_event_id;

            if ($automationId) {
                $automation = Automation::query()
                    ->where('id', $automationId)
                    ->where('user_id', $user->id)
                    ->where('team_id', $user->currentTeam->id) // OBRIGATÓRIO - Multi-tenancy
                    ->firstOrFail();

                $automation->update([
                    'integration_id' => $integrationId,
                    'automation_event_id' => $automationEventId,
                    'trigger_type_id' => $triggerTypeId,
                    'rule_text' => $ruleText,
                    'plan_rule_text' => $plan ?? $automation->plan_rule_text,
                    'gmail_label' => $gmailLabel,
                ]);

                $action = $automation->actions()->orderBy('position')->first();
                if ($action) {
                    $action->update([
                        'type' => $actionType,
                        'config' => $actionConfig,
                    ]);
                } else {
                    $automation->actions()->create([
                        'type' => $actionType,
                        'config' => $actionConfig,
                        'position' => 1,
                    ]);
                }

                return $automation->load('actions');
            }

            $automation = Automation::query()->create([
                'user_id' => $user->id,
                'team_id' => $user->currentTeam->id, // OBRIGATÓRIO - Multi-tenancy
                'integration_id' => $integrationId,
                'automation_event_id' => $automationEventId,
                'trigger_type_id' => $triggerTypeId,
                'rule_text' => $ruleText,
                'plan_rule_text' => $plan,
                'gmail_label' => $gmailLabel,
                'status' => Automation::STATUS_DRAFT,
            ]);

            $automation->actions()->create([
                'type' => $actionType,
                'config' => $actionConfig,
                'position' => 1,
            ]);

            return $automation->load('actions');
        });
    }

    private function validateActionConfig(User $user, string $actionType, array $actionConfig): void
    {
        // Validação para ação de criar tarefa
        if ($actionType === 'tarefa') {
            if (empty($actionConfig['board_list_id'])) {
                throw new \InvalidArgumentException('Selecione uma lista para criar as tarefas.');
            }
            
            // Validar se a lista pertence ao team do usuário (multi-tenancy)
            $listExists = \App\Domain\Tasks\Models\BoardList::query()
                ->where('id', $actionConfig['board_list_id'])
                ->whereHas('board', function ($query) use ($user) {
                    $query->where('team_id', $user->currentTeam->id);
                })
                ->exists();
                
            if (!$listExists) {
                throw new \InvalidArgumentException('Lista inválida ou sem permissão.');
            }
            
            // Validar assigned_to se fornecido
            if (!empty($actionConfig['assigned_to'])) {
                $userExists = $user->currentTeam
                    ->allUsers()
                    ->contains('id', $actionConfig['assigned_to']);
                    
                if (!$userExists) {
                    throw new \InvalidArgumentException('Usuário atribuído inválido.');
                }
            }
        }
        
        // Validação para ação de encaminhar email
        if ($actionType === 'encaminhar') {
            if (empty($actionConfig['forward_to']) || !is_array($actionConfig['forward_to'])) {
                throw new \InvalidArgumentException('Informe pelo menos um e-mail para encaminhar.');
            }
        }
        
        // Validação para ação de responder
        if ($actionType === 'responder') {
            if (empty($actionConfig['reply_body'])) {
                throw new \InvalidArgumentException('Digite o corpo da resposta.');
            }
        }
    }

    private function buildPlanSafely(string $ruleText, string $actionType, array $actionConfig): ?string
    {
        try {
            $planned = $this->planner->plan($ruleText, 'email_received', $actionType, $actionConfig);
            return $planned !== '' ? $planned : $this->planner->fallbackPlan($ruleText, 'email_received', $actionType, $actionConfig);
        } catch (\Throwable $e) {
            // Evita quebrar o fluxo de criação; gera fallback determinístico
            return $this->planner->fallbackPlan($ruleText, 'email_received', $actionType, $actionConfig);
        }
    }
}
