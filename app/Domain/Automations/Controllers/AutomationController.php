<?php

declare(strict_types=1);

namespace App\Domain\Automations\Controllers;

use App\Domain\Integrations\Actions\ListUserIntegrationsAction;
use App\Domain\Automations\Actions\StoreEmailAutomationAction;
use App\Domain\Automations\Actions\StoreEmailMassSendAction;
use App\Domain\Automations\Requests\StoreEmailAutomationRequest;
use App\Domain\Automations\Requests\StoreEmailMassSendRequest;
use App\Domain\Automations\Actions\SimulateEmailAutomationAction;
use App\Domain\Automations\Requests\SimulateEmailAutomationRequest;
use App\Domain\Automations\Actions\ListUserAutomationsAction;
use App\Domain\Automations\Models\Automation;
use App\Domain\Automations\Models\MassEmailSend;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Domain\Automations\Http\Actions\ExecuteHttpRequestAction;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $automations = $listUserAutomationsAction->handle($user);

        return Inertia::render('Automations/Index', [
            'connectedEmail' => data_get($gmail?->metadata, 'email'),
            'hasGmail' => $gmail?->status === 'connected',
            'automations' => $automations,
        ]);
    }

    public function selectEvent(ListUserIntegrationsAction $listUserIntegrationsAction): Response
    {
        $user = Auth::user();
        $integrations = $listUserIntegrationsAction->handle($user);
        $gmail = $integrations->get('gmail');

        $events = \App\Domain\Automations\Models\AutomationEvent::query()
            ->where('active', true)
            ->orderBy('position')
            ->orderBy('title')
            ->get(['id', 'key', 'title', 'description', 'icon', 'category']);

        // Injetando evento de HTTP Request manualmente para testes
        $events->push([
            'id' => 999,
            'key' => 'http_request',
            'title' => 'Requisição HTTP',
            'description' => 'Faça chamadas GET/POST para APIs externas.',
            'icon' => 'lucide:globe',
            'category' => 'Integrações',
        ]);

        return Inertia::render('Automations/SelectEvent', [
            'connectedEmail' => data_get($gmail?->metadata, 'email'),
            'hasGmail' => $gmail?->status === 'connected',
            'events' => $events,
        ]);
    }

    public function httpRequest(): Response
    {
        return Inertia::render('Automations/WorkflowEditor');
    }

    public function testHttpRequest(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
            'method' => 'required|in:GET,POST,PUT,DELETE',
            'headers' => 'nullable|array',
            'body' => 'nullable|string',
        ]);

        try {
            $method = $request->input('method');
            $url = $request->input('url');
            $headers = $request->input('headers', []);
            $body = $request->input('body');

            // Prepara a requisição
            $http = \Illuminate\Support\Facades\Http::withHeaders($headers);

            // Se tiver body e for JSON, decodifica para array
            $data = [];
            if ($body) {
                $decoded = json_decode($body, true);
                $data = $decoded ?: [];
            }

            // Executa
            $response = match ($method) {
                'GET' => $http->get($url),
                'POST' => $http->post($url, $data),
                'PUT' => $http->put($url, $data),
                'DELETE' => $http->delete($url, $data),
                default => throw new \Exception('Método não suportado'),
            };

            return response()->json([
                'status' => $response->status(),
                'data' => $response->json(),
                'headers' => $response->headers(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testWorkflow(Request $request): JsonResponse
    {
        $request->validate([
            'steps' => 'required|array',
        ]);

        $steps = $request->input('steps');
        $context = []; // Armazena variáveis
        $logs = [];

        foreach ($steps as $step) {
            $stepId = $step['id'];
            $type = $step['type'];
            $title = $step['title'];
            $config = $step['config'] ?? [];

            try {
                $logEntry = [
                    'step_id' => $stepId,
                    'title' => $title,
                    'type' => $type,
                    'status' => 'pending',
                    'output' => null,
                ];

                switch ($type) {
                    case 'http_request':
                        // Resolver variáveis n URL/Body (ex: {{user_id}})
                        // Por simplicidade, faremos direto inicialmente
                        $url = $config['url'] ?? '';
                        $method = $config['method'] ?? 'GET';
                        $body = $config['body'] ?? '';
                        $headers = collect($config['headers'] ?? [])->mapWithKeys(fn($h) => [$h['key'] => $h['value']])->toArray();

                        $http = \Illuminate\Support\Facades\Http::withHeaders($headers);
                        
                        $dataBody = [];
                        if ($body) {
                            $dataBody = json_decode($body, true) ?: [];
                        }

                        $response = match ($method) {
                            'GET' => $http->get($url),
                            'POST' => $http->post($url, $dataBody),
                            'PUT' => $http->put($url, $dataBody),
                            'DELETE' => $http->delete($url, $dataBody),
                            default => $http->get($url),
                        };

                        $jsonResponse = $response->json();
                        $context['_last_response'] = $jsonResponse;
                        $logEntry['output'] = ['status' => $response->status(), 'data' => $jsonResponse];
                        break;

                    case 'extract_data':
                        $jsonKey = $config['json_key'] ?? '';
                        $varName = $config['variable_name'] ?? 'extracted_value';
                        
                        $lastResponse = $context['_last_response'] ?? [];
                        $value = data_get($lastResponse, $jsonKey);
                        
                        $context[$varName] = $value;
                        $logEntry['output'] = ['variable' => $varName, 'extracted_value' => $value];
                        break;

                    case 'link_contract':
                        // Mock de busca
                        // Em produção buscaria em Contract::where(...)
                        $searchBy = $config['search_by'] ?? 'cpf_cnpj';
                        $varValueVar = $config['value_variable'] ?? '';
                        $searchValue = $context[$varValueVar] ?? 'N/A';

                        // Mock finding a contract
                        $contractId = rand(100, 999);
                        $context['linked_contract_id'] = $contractId;
                        
                        $logEntry['output'] = [
                            'message' => "Buscando contrato por $searchBy = $searchValue",
                            'found_contract_id' => $contractId
                        ];
                        break;

                    case 'send_email':
                        $to = $config['to_email'] ?? '';
                        $subject = $config['subject'] ?? '';
                        // Simulação
                        $logEntry['output'] = ['message' => "Email simulado para $to com assunto '$subject'"];
                        break;
                }

                $logEntry['status'] = 'success';
                $logs[] = $logEntry;

            } catch (\Exception $e) {
                $logs[] = [
                    'step_id' => $stepId,
                    'title' => $title,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
                break; // Stop on error
            }
        }

        return response()->json([
            'logs' => $logs,
            'final_context' => $context
        ]);
    }

    public function emailReceived(
        ListUserIntegrationsAction $listUserIntegrationsAction,
        \App\Domain\Tasks\Actions\ListUserBoardsAction $listUserBoardsAction,
    ): Response
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
            'boards' => $listUserBoardsAction->handle($user),
            'teamUsers' => $user->currentTeam->allUsers(),
        ]);
    }

    public function emailReceivedEdit(
        Automation $automation,
        ListUserIntegrationsAction $listUserIntegrationsAction,
        \App\Domain\Tasks\Actions\ListUserBoardsAction $listUserBoardsAction,
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
                'gmail_label' => $automation->gmail_label,
                'status' => $automation->status,
            ],
            'triggerTypes' => $triggerTypes,
            'executions' => $executions,
            'boards' => $listUserBoardsAction->handle($user),
            'teamUsers' => $user->currentTeam->allUsers(),
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
            gmailLabel: $request->validated('gmail_label'),
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

    /**
     * Exibe o formulário de criação de envio em massa
     * Carrega integrações e renderiza a página Vue
     */
    public function emailMassSend(
        ListUserIntegrationsAction $listUserIntegrationsAction,
    ): Response {
        $user = Auth::user();

        $integrations = $listUserIntegrationsAction->handle($user);
        $gmail = $integrations->get('gmail');

        return Inertia::render('Automations/EmailMassSend', [
            'connectedEmail' => data_get($gmail?->metadata, 'email'),
            'hasGmail' => $gmail?->status === 'connected',
            'automation' => null,
        ]);
    }

    /**
     * Exibe detalhes/relatório de um envio em massa
     * Mostra estatísticas e lista de destinatários
     */
    public function emailMassSendView(
        MassEmailSend $massEmailSend,
        ListUserIntegrationsAction $listUserIntegrationsAction,
    ): Response {
        $user = Auth::user();
        abort_unless($user && $massEmailSend->user_id === $user->id, 403);

        $integrations = $listUserIntegrationsAction->handle($user);
        $gmail = $integrations->get('gmail');

        // Busca destinatários
        $recipients = $massEmailSend->recipients()
            ->orderBy('status')
            ->orderBy('email')
            ->get()
            ->map(function ($recipient) {
                return [
                    'id' => $recipient->id,
                    'email' => $recipient->email,
                    'data' => $recipient->data,
                    'status' => $recipient->status,
                    'error_message' => $recipient->error_message,
                    'ignore_reason' => $recipient->ignore_reason,
                    'sent_at' => $recipient->sent_at,
                ];
            });

        return Inertia::render('Automations/EmailMassSendView', [
            'connectedEmail' => data_get($gmail?->metadata, 'email'),
            'hasGmail' => $gmail?->status === 'connected',
            'massEmailSend' => [
                'id' => $massEmailSend->id,
                'name' => $massEmailSend->name,
                'send_type' => $massEmailSend->send_type,
                'scheduled_at' => $massEmailSend->scheduled_at,
                'subject' => $massEmailSend->subject,
                'body' => $massEmailSend->body,
                'status' => $massEmailSend->status,
                'total_recipients' => $massEmailSend->total_recipients,
                'valid_recipients' => $massEmailSend->valid_recipients,
                'invalid_recipients' => $massEmailSend->invalid_recipients,
                'sent_count' => $massEmailSend->sent_count,
                'failed_count' => $massEmailSend->failed_count,
                'started_at' => $massEmailSend->started_at,
                'completed_at' => $massEmailSend->completed_at,
            ],
            'recipients' => $recipients,
        ]);
    }

    /**
     * Salva ou atualiza um envio em massa
     * Valida, cria registros e dispara o processamento
     */
    public function storeEmailMassSend(
        StoreEmailMassSendRequest $request,
        ListUserIntegrationsAction $listUserIntegrationsAction,
        StoreEmailMassSendAction $storeEmailMassSendAction,
        ?MassEmailSend $massEmailSend = null,
    ): RedirectResponse {
        $user = Auth::user();
        abort_unless($user, 401);

        // Verifica se está editando e se pertence ao usuário
        if ($massEmailSend && $massEmailSend->user_id !== $user->id) {
            abort(403);
        }

        $integrations = $listUserIntegrationsAction->handle($user);
        $gmail = $integrations->get('gmail');

        if (!$gmail || $gmail->status !== 'connected') {
            return Redirect::back()->withErrors([
                'integration' => 'Conecte o Gmail antes de continuar.',
            ]);
        }

        // Executa a action
        $result = $storeEmailMassSendAction->handle(
            user: $user,
            integration: $gmail,
            data: $request->validated(),
            massEmailSend: $massEmailSend,
        );

        $message = $massEmailSend 
            ? 'Envio em massa atualizado com sucesso!'
            : ($request->input('send_type') === 'now' 
                ? 'Envio iniciado! Acompanhe o progresso.' 
                : 'Envio agendado com sucesso!');

        return Redirect::route('automations.index')->with('success', $message);
    }

    /**
     * Apaga um envio em massa
     */
    public function destroyMassEmailSend(MassEmailSend $massEmailSend): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $massEmailSend->user_id === $user->id, 403);

        $massEmailSend->delete();

        return Redirect::route('automations.index')->with('success', 'Envio em massa removido.');
    }

    /**
     * Reenvia emails que falharam
     */
    public function resendMassEmailSend(MassEmailSend $massEmailSend): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $massEmailSend->user_id === $user->id, 403);

        // Reseta status dos emails que falharam para "pending"
        $massEmailSend->recipients()
            ->where('status', 'failed')
            ->update([
                'status' => 'pending',
                'error_message' => null,
                'sent_at' => null,
                'message_id' => null,
            ]);

        // Atualiza contadores
        $failedCount = $massEmailSend->failed_count;
        $massEmailSend->update([
            'status' => 'processing',
            'sent_count' => 0,
            'failed_count' => 0,
        ]);

        // Dispara o job novamente
        \App\Jobs\ProcessMassEmailSendJob::dispatch($massEmailSend->id);

        return Redirect::back()->with('success', "{$failedCount} email(s) serão reenviados.");
    }
}
