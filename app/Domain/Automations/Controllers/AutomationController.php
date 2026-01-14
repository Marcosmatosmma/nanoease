<?php

declare(strict_types=1);

namespace App\Domain\Automations\Controllers;

use App\Domain\Integrations\Actions\ListUserIntegrationsAction;
use App\Domain\Automations\Actions\StoreEmailAutomationAction;
use App\Domain\Automations\Requests\StoreEmailAutomationRequest;
use App\Domain\Automations\Actions\SimulateEmailAutomationAction;
use App\Domain\Automations\Requests\SimulateEmailAutomationRequest;
use App\Domain\Automations\Actions\ListUserAutomationsAction;
use App\Domain\Automations\Models\Automation;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

final class AutomationController extends Controller
{
    public function index(
        ListUserIntegrationsAction $listUserIntegrationsAction,
        ListUserAutomationsAction $listUserAutomationsAction,
    ): Response
    {
        $user = Auth::user();
        $integrations = $listUserIntegrationsAction->handle($user);
        $gmail = $integrations->get('gmail');

        $automations = $listUserAutomationsAction->handle($user)->map(function ($automation) {
            $action = $automation->actions->first();

            return [
                'id' => $automation->id,
                'event' => $automation->event,
                'rule' => $automation->rule_text,
                'status' => $automation->status,
                'action_type' => $action?->type,
                'updated_at' => $automation->updated_at,
            ];
        });

        return Inertia::render('Automations/Index', [
            'connectedEmail' => data_get($gmail?->metadata, 'email'),
            'hasGmail' => $gmail?->status === 'connected',
            'automations' => $automations,
        ]);
    }

    public function emailReceived(ListUserIntegrationsAction $listUserIntegrationsAction): Response
    {
        $user = Auth::user();
        $integrations = $listUserIntegrationsAction->handle($user);
        $gmail = $integrations->get('gmail');

        $emailReceivedEvent = \App\Domain\Automations\Models\AutomationEvent::where('key', 'email_received')->first();
        $triggerTypes = $emailReceivedEvent 
            ? $emailReceivedEvent->triggerTypes()->get(['id', 'key', 'title', 'description', 'icon', 'placeholder', 'uses_ai'])
            : [];

        return Inertia::render('Automations/EmailReceived', [
            'connectedEmail' => data_get($gmail?->metadata, 'email'),
            'hasGmail' => $gmail?->status === 'connected',
            'automation' => null,
            'triggerTypes' => $triggerTypes,
        ]);
    }

    public function emailReceivedEdit(
        Automation $automation,
        ListUserIntegrationsAction $listUserIntegrationsAction,
    ): Response {
        $user = Auth::user();
        abort_unless($user && $automation->user_id === $user->id, 403);

        $integrations = $listUserIntegrationsAction->handle($user);
        $gmail = $integrations->get('gmail');

        $action = $automation->actions()->orderBy('position')->first();

        $emailReceivedEvent = \App\Domain\Automations\Models\AutomationEvent::where('key', 'email_received')->first();
        $triggerTypes = $emailReceivedEvent 
            ? $emailReceivedEvent->triggerTypes()->get(['id', 'key', 'title', 'description', 'icon', 'placeholder', 'uses_ai'])
            : [];

        $executions = \Illuminate\Support\Facades\DB::table('automation_executions')
            ->where('automation_id', $automation->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($execution) {
                return [
                    'id' => $execution->id,
                    'email_subject' => $execution->email_subject,
                    'email_from' => $execution->email_from,
                    'status' => $execution->status,
                    'reasoning' => $execution->reasoning,
                    'confidence' => $execution->confidence,
                    'action_result' => $execution->action_result ? json_decode($execution->action_result, true) : null,
                    'created_at' => $execution->created_at,
                ];
            });

        return Inertia::render('Automations/EmailReceived', [
            'connectedEmail' => data_get($gmail?->metadata, 'email'),
            'hasGmail' => $gmail?->status === 'connected',
            'automation' => [
                'id' => $automation->id,
                'trigger_type_id' => $automation->trigger_type_id,
                'rule' => $automation->rule_text,
                'action_type' => $action?->type,
                'action_config' => $action?->config ?? [],
                'status' => $automation->status,
            ],
            'triggerTypes' => $triggerTypes,
            'executions' => $executions,
        ]);
    }

    public function simulateEmailReceived(
        SimulateEmailAutomationRequest $request,
        ListUserIntegrationsAction $listUserIntegrationsAction,
        SimulateEmailAutomationAction $simulateEmailAutomationAction,
    ): JsonResponse {
        $user = Auth::user();
        abort_unless($user, 401);

        $integrations = $listUserIntegrationsAction->handle($user);
        $gmail = $integrations->get('gmail');

        if (! $gmail || $gmail->status !== 'connected') {
            return response()->json([
                'message' => 'Conecte o Gmail antes de simular.',
            ], 422);
        }

        $result = $simulateEmailAutomationAction->handle(
            user: $user,
            ruleText: $request->validated('rule'),
            actionType: $request->validated('action_type'),
            actionConfig: $request->validated('action_config') ?? [],
            integrationMetadata: $gmail->metadata ?? [],
        );

        return response()->json($result);
    }

    public function storeEmailReceived(
        StoreEmailAutomationRequest $request,
        ListUserIntegrationsAction $listUserIntegrationsAction,
        StoreEmailAutomationAction $storeEmailAutomationAction,
    ): RedirectResponse {
        $user = Auth::user();
        abort_unless($user, 401);

        $integrations = $listUserIntegrationsAction->handle($user);
        $gmail = $integrations->get('gmail');

        if (! $gmail || $gmail->status !== 'connected') {
            return Redirect::route('integrations.index')->with(
                'error',
                'Conecte o Gmail antes de criar a automação.',
            );
        }

        $automationId = $request->route('automation');
        $automationId = is_numeric($automationId) ? (int) $automationId : null;

        $storeEmailAutomationAction->handle(
            user: $user,
            triggerTypeId: $request->validated('trigger_type_id'),
            ruleText: $request->validated('rule'),
            actionType: $request->validated('action_type'),
            actionConfig: $request->validated('action_config') ?? [],
            integrationId: $gmail->id,
            automationId: $automationId,
        );

        return Redirect::route('automations.index')->with(
            'success',
            'Automação salva como rascunho.',
        );
    }

    public function updateStatus(Automation $automation): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $automation->user_id === $user->id, 403);

        $status = request()->string('status')->toString();
        if (! in_array($status, [Automation::STATUS_DRAFT, Automation::STATUS_ACTIVE, Automation::STATUS_PAUSED], true)) {
            return Redirect::back()->with('error', 'Status inválido.');
        }

        $automation->update(['status' => $status]);

        return Redirect::back()->with('success', 'Status atualizado.');
    }

    public function destroy(Automation $automation): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $automation->user_id === $user->id, 403);

        $automation->delete();

        return Redirect::route('automations.index')->with('success', 'Automação removida.');
    }
}
