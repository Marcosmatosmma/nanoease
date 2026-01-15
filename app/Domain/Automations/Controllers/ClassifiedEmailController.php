<?php

declare(strict_types=1);

namespace App\Domain\Automations\Controllers;

use App\Domain\Automations\Actions\ExportClassifiedEmailsAction;
use App\Domain\Automations\Models\ClassifiedEmail;
use App\Domain\Integrations\Services\GmailLabelManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ClassifiedEmailController
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $automationId = $request->integer('automation_id');
        $viewMode = $request->get('view', 'kanban'); // 'kanban' ou 'list'

        $query = ClassifiedEmail::query()
            ->where('user_id', $user->id)
            ->with(['automation'])
            ->orderBy('email_date', 'desc');

        if ($automationId) {
            $query->where('automation_id', $automationId);
        }

        // Para kanban, pegar todos (limit 200 para performance)
        // Para lista, manter paginação
        if ($viewMode === 'kanban') {
            $emails = [
                'data' => $query->limit(200)->get(),
                'total' => $query->count(),
            ];
        } else {
            $emails = $query->paginate(20);
        }

        $automations = \App\Domain\Automations\Models\Automation::query()
            ->where('user_id', $user->id)
            ->whereNotNull('gmail_label')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'rule_text', 'gmail_label', 'status']);

        return Inertia::render('OrganizedEmails/Kanban', [
            'emails' => $emails,
            'automations' => $automations,
            'selectedAutomationId' => $automationId ?: null,
        ]);
    }

    public function export(
        Request $request,
        ExportClassifiedEmailsAction $exportAction
    ): StreamedResponse {
        $user = Auth::user();
        abort_unless($user, 401);

        $automationId = $request->integer('automation_id') ?: null;

        return $exportAction->handle($user->id, $automationId);
    }

    public function listGmailLabels(Request $request, GmailLabelManager $labelManager)
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $integration = \App\Domain\Integrations\Models\Integration::query()
            ->where('user_id', $user->id)
            ->where('provider', 'gmail')
            ->where('status', 'connected')
            ->first();

        $allLabels = [];

        // 1. Labels do banco de dados (automations)
        $dbLabels = \App\Domain\Automations\Models\Automation::query()
            ->where('user_id', $user->id)
            ->whereNotNull('gmail_label')
            ->distinct()
            ->pluck('gmail_label')
            ->map(function ($label) {
                return [
                    'id' => 'db-' . md5($label),
                    'name' => $label,
                    'color' => null,
                    'source' => 'database',
                ];
            })
            ->toArray();

        $allLabels = array_merge($allLabels, $dbLabels);

        // 2. Labels sugeridas (predefinidas)
        $suggestedLabels = [
            'Financeiro/Boletos',
            'Financeiro/Notas Fiscais',
            'Financeiro/Cobranças',
            'Vendas/Pedidos',
            'Vendas/Propostas',
            'Vendas/Contratos',
            'Suporte/Tickets',
            'Suporte/Bugs',
            'Suporte/Melhorias',
            'RH/Candidatos',
            'RH/Funcionários',
            'Marketing/Campanhas',
            'Jurídico/Contratos',
            'Jurídico/Processos',
            'TI/Infraestrutura',
            'TI/Desenvolvimento',
        ];

        foreach ($suggestedLabels as $label) {
            // Não adicionar se já existe no banco
            if (!in_array($label, array_column($dbLabels, 'name'))) {
                $allLabels[] = [
                    'id' => 'suggested-' . md5($label),
                    'name' => $label,
                    'color' => null,
                    'source' => 'suggested',
                ];
            }
        }

        // 3. Labels do Gmail (se conectado)
        if ($integration) {
            try {
                $gmailLabels = $labelManager->listLabels($integration);
                
                $userLabels = array_filter($gmailLabels, function ($label) {
                    return $label['type'] === 'user';
                });

                foreach ($userLabels as $label) {
                    // Não adicionar se já existe no banco
                    if (!in_array($label['name'], array_column($dbLabels, 'name'))) {
                        $allLabels[] = [
                            'id' => $label['id'],
                            'name' => $label['name'],
                            'color' => $label['color'] ?? null,
                            'source' => 'gmail',
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Silenciosamente ignora erros do Gmail
            }
        }

        // Ordenar por nome
        usort($allLabels, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return response()->json([
            'success' => true,
            'labels' => $allLabels,
        ]);
    }
}
