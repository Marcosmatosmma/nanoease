<?php

declare(strict_types=1);

namespace App\Domain\Automations\Actions;

use App\Domain\Integrations\Models\Integration;
use App\Domain\Integrations\Services\GmailMessageFetcher;
use App\Domain\AI\Services\RuleInterpreter;
use App\Models\User;

final class TestAutomationWithRealEmailsAction
{
    public function __construct(
        private readonly GmailMessageFetcher $gmailFetcher,
        private readonly RuleInterpreter $ruleInterpreter
    ) {
    }

    public function handle(User $user, Integration $integration, int $triggerTypeId, string $ruleText): array
    {
        // Buscar apenas da aba Principal (category:primary) - equivalente à aba "Principal" do Gmail
        // Isso exclui automaticamente: Promoções, Social e Atualizações
        $result = $this->gmailFetcher->fetch($integration, 'category:primary', 10);

        \Log::info('GmailFetcher result:', $result);

        if ($result['status'] !== 'ok' || empty($result['messages'])) {
            return [
                'status' => 'warning',
                'message' => 'Não conseguimos buscar e-mails da sua caixa. Detalhes: ' . ($result['message'] ?? 'Erro desconhecido'),
                'examples' => [],
                'debug' => $result,
            ];
        }

        $examples = [];
        $totalAnalyzed = 0;
        
        foreach ($result['messages'] as $message) {
            $totalAnalyzed++;
            
            $event = [
                'from' => $message['from'] ?? '',
                'subject' => $message['subject'] ?? '',
                'snippet' => $message['snippet'] ?? '',
            ];

            $interpretation = $this->ruleInterpreter->interpret($ruleText, $event);

            if (!$interpretation['should_execute']) {
                continue;
            }

            $confidence = $interpretation['confidence'] ?? null;
            
            if ($confidence !== null && $confidence >= 0.8) {
                $decision = 'execute';
                $variant = 'success';
                $icon = 'lucide:check-circle';
            } elseif ($confidence !== null && $confidence >= 0.5) {
                $decision = 'uncertain';
                $variant = 'warning';
                $icon = 'lucide:alert-circle';
            } else {
                $decision = 'execute';
                $variant = 'success';
                $icon = 'lucide:check-circle';
            }

            $examples[] = [
                'from' => $message['from'] ?? 'Desconhecido',
                'subject' => $message['subject'] ?? '(sem assunto)',
                'snippet' => mb_substr($message['snippet'] ?? '', 0, 100),
                'decision' => $decision,
                'variant' => $variant,
                'icon' => $icon,
                'confidence' => $confidence,
                'reasoning' => $interpretation['reasoning'] ?? '',
            ];
        }

        $matchCount = count($examples);
        $message = $matchCount > 0
            ? sprintf('Encontrados %d e-mail(s) correspondente(s) entre os %d mais recentes da sua caixa.', $matchCount, $totalAnalyzed)
            : sprintf('Nenhum e-mail correspondente encontrado entre os %d mais recentes da sua caixa.', $totalAnalyzed);

        return [
            'status' => 'ok',
            'message' => $message,
            'examples' => $examples,
            'total_analyzed' => $totalAnalyzed,
            'total_matched' => $matchCount,
        ];
    }
}
