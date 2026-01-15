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

        $query = ClassifiedEmail::query()
            ->where('user_id', $user->id)
            ->with(['automation'])
            ->orderBy('email_date', 'desc');

        if ($automationId) {
            $query->where('automation_id', $automationId);
        }

        $emails = $query->paginate(20);

        $automations = \App\Domain\Automations\Models\Automation::query()
            ->where('user_id', $user->id)
            ->whereNotNull('gmail_label')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'rule_text', 'gmail_label', 'status']);

        return Inertia::render('OrganizedEmails/Index', [
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
            ->where('service', 'gmail')
            ->where('status', 'connected')
            ->first();

        if (!$integration) {
            return response()->json([
                'success' => false,
                'message' => 'Gmail não conectado',
                'labels' => [],
            ]);
        }

        try {
            $labels = $labelManager->listLabels($integration);
            
            // Filtrar apenas labels de usuário (não system labels)
            $userLabels = array_filter($labels, function ($label) {
                return $label['type'] === 'user';
            });

            // Formatar para select
            $formatted = array_map(function ($label) {
                return [
                    'id' => $label['id'],
                    'name' => $label['name'],
                    'color' => $label['color'] ?? null,
                ];
            }, array_values($userLabels));

            return response()->json([
                'success' => true,
                'labels' => $formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar labels: ' . $e->getMessage(),
                'labels' => [],
            ]);
        }
    }
}
