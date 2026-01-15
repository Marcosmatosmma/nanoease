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
        return DB::transaction(function () use ($user, $triggerTypeId, $ruleText, $actionType, $actionConfig, $integrationId, $automationId, $gmailLabel) {
            $plan = $this->buildPlanSafely($ruleText, $actionType, $actionConfig);

            // Buscar o automation_event_id do trigger_type
            $triggerType = \App\Domain\Automations\Models\AutomationTriggerType::findOrFail($triggerTypeId);
            $automationEventId = $triggerType->automation_event_id;

            if ($automationId) {
                $automation = Automation::query()
                    ->where('id', $automationId)
                    ->where('user_id', $user->id)
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
