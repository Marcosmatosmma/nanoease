<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Prompts\Automations\Email\EmailRuleInterpretationPrompt;
use Illuminate\Support\Str;

final class RuleInterpreter
{
    public function __construct(
        private readonly PrismClient $client,
        private readonly EmailRuleInterpretationPrompt $prompt,
    ) {}

    /**
     * Interpreta a regra via Prism (LLM) e retorna apenas interpretação, não execução.
     */
    public function interpret(string $ruleText, array $event): array
    {
        $promptText = $this->prompt->render([
            'rule' => $ruleText,
            'event' => $event,
        ]);

        try {
            $raw = $this->client->ask($promptText);
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return [
                'should_execute' => false,
                'confidence' => 0.0,
                'signals' => ['fallback' => 'LLM indisponível: '.$e->getMessage()],
                'echo_rule' => $ruleText,
                'example_event' => $event,
            ];
        }

        $shouldExecute = (bool) ($decoded['should_execute'] ?? false);
        $confidence = $this->normalizeConfidence($decoded['confidence'] ?? 0.0);
        $reason = is_string($decoded['reason'] ?? null) ? trim((string) $decoded['reason']) : '';
        $matchedSender = (bool) ($decoded['matched_sender'] ?? false);
        $matchedAction = (bool) ($decoded['matched_action'] ?? false);

        return [
            'should_execute' => $shouldExecute,
            'confidence' => $confidence,
            'signals' => array_values(array_filter([
                $reason ?: null,
                $matchedSender ? 'matched_sender' : null,
                $matchedAction ? 'matched_action' : null,
            ])),
            'echo_rule' => $ruleText,
            'example_event' => $event,
        ];
    }

    private function normalizeConfidence(mixed $value): float
    {
        if (is_numeric($value)) {
            $float = (float) $value;
            return max(0.0, min(1.0, $float));
        }

        if (is_string($value)) {
            $float = (float) Str::of($value)->replace('%', '')->toString();
            // Se vier em percentual, normaliza
            if ($float > 1) {
                $float = $float / 100;
            }
            return max(0.0, min(1.0, $float));
        }

        return 0.0;
    }
}
