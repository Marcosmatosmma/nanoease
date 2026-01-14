<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Prompts\Automations\Email\EmailRulePlanPrompt;
use Illuminate\Support\Str;

final class EmailRulePlanner
{
    public function __construct(
        private readonly PrismClient $client,
        private readonly EmailRulePlanPrompt $prompt,
    ) {}

    public function plan(string $ruleText, ?string $event = null, ?string $action = null, ?array $actionConfig = null): string
    {
        $prompt = $this->prompt->render([
            'rule' => $ruleText,
            'event' => $event,
            'action' => $action,
            'action_config' => $actionConfig,
        ]);

        try {
            $plan = trim($this->client->ask($prompt));
        } catch (\Throwable $e) {
            return $this->fallbackPlan($ruleText, $event, $action, $actionConfig);
        }

        if ($plan === '' || $this->isSameMeaning($plan, $ruleText)) {
            return $this->fallbackPlan($ruleText, $event, $action, $actionConfig);
        }

        return $plan;
    }

    private function isSameMeaning(string $plan, string $ruleText): bool
    {
        $normalizedPlan = Str::of($plan)->lower()->trim()->replace('.', '')->replace(',', '')->toString();
        $normalizedRule = Str::of($ruleText)->lower()->trim()->replace('.', '')->replace(',', '')->toString();

        return $normalizedPlan === $normalizedRule;
    }

    public function fallbackPlan(string $ruleText, ?string $event, ?string $action, ?array $actionConfig): string
    {
        $condition = trim($ruleText) !== '' ? trim($ruleText) : 'Quando o evento ocorrer';
        $actionText = $this->describeAction($action, $actionConfig);

        return rtrim($condition, '.').'. '.$actionText;
    }

    private function formatRecipients(array $recipients): string
    {
        $clean = array_values(array_filter(array_map('trim', $recipients))); // rare comment: keep order
        if (empty($clean)) {
            return '';
        }

        if (count($clean) === 1) {
            return $clean[0];
        }

        $last = array_pop($clean);
        return implode(', ', $clean).' e '.$last;
    }

    private function describeAction(?string $action, ?array $actionConfig): string
    {
        $type = $action ?: 'executar a ação configurada';

        if ($type === 'encaminhar') {
            $recipients = $this->formatRecipients($actionConfig['forward_to'] ?? []);
            if ($recipients !== '') {
                return 'Encaminhar para '.$recipients.'.';
            }
            return 'Encaminhar para os destinatários configurados.';
        }

        if ($type === 'organizar') {
            $folder = $actionConfig['folder'] ?? null;
            return $folder ? 'Organizar e mover para '.$folder.'.' : 'Organizar conforme configuração.';
        }

        if ($type === 'responder') {
            return 'Responder automaticamente usando o modelo configurado.';
        }

        if ($type === 'criar_tarefa') {
            return 'Criar tarefa com os dados da mensagem.';
        }

        return 'Ação: '.$type.'.';
    }
}
