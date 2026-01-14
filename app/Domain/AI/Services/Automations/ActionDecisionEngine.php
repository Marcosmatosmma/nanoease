<?php

declare(strict_types=1);

namespace App\Domain\AI\Services\Automations;

use App\Domain\AI\Prompts\Automations\Email\EmailActionExecutionPrompt;
use App\Domain\AI\Services\PrismClient;

final class ActionDecisionEngine
{
    public function __construct(
        private readonly PrismClient $client,
        private readonly EmailActionExecutionPrompt $prompt,
    ) {}

    /**
     * Usa o Prism (LLM) para analisar a regra e o evento, retornando uma decisão estruturada.
     *
     * @param string $planRuleText Regra planejada otimizada
     * @param array $event Evento normalizado (from, subject, body/snippet)
     * @param array $availableActions Ações disponíveis na automação
     * @return array Decisão estruturada com should_execute, confidence, action_to_execute, etc
     */
    public function decide(string $planRuleText, array $event, array $availableActions): array
    {
        $cleanedEvent = $this->prepareEventForAI($event);
        
        $promptText = $this->prompt->render([
            'plan_rule' => $planRuleText,
            'event' => $cleanedEvent,
            'available_actions' => $availableActions,
        ]);

        try {
            $raw = $this->client->ask($promptText);
            $decoded = $this->parseResponse($raw);
        } catch (\Throwable $e) {
            return $this->fallbackDecision('LLM indisponível: ' . $e->getMessage());
        }

        return [
            'should_execute' => (bool) ($decoded['should_execute'] ?? false),
            'confidence' => $this->normalizeConfidence($decoded['confidence'] ?? 0.0),
            'matched_conditions' => (array) ($decoded['matched_conditions'] ?? []),
            'action_to_execute' => $decoded['action_to_execute'] ?? null,
            'action_parameters' => (array) ($decoded['action_parameters'] ?? []),
            'reasoning' => trim((string) ($decoded['reasoning'] ?? '')),
            'raw_response' => $raw,
        ];
    }
    
    private function prepareEventForAI(array $event): array
    {
        $body = $event['body'] ?? $event['snippet'] ?? '';
        
        $cleanBody = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $body);
        $cleanBody = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $cleanBody);
        $cleanBody = preg_replace('/<head\b[^>]*>(.*?)<\/head>/is', '', $cleanBody);
        
        $cleanBody = strip_tags($cleanBody);
        $cleanBody = html_entity_decode($cleanBody, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleanBody = preg_replace('/\s+/', ' ', $cleanBody);
        $cleanBody = trim($cleanBody);
        
        $maxChars = 8000;
        if (strlen($cleanBody) > $maxChars) {
            $cleanBody = substr($cleanBody, 0, $maxChars) . '... [conteúdo truncado]';
        }
        
        return [
            'from' => $event['from'] ?? '',
            'subject' => $event['subject'] ?? '',
            'body' => $cleanBody,
            'snippet' => $event['snippet'] ?? '',
        ];
    }

    private function parseResponse(string $raw): array
    {
        $cleaned = trim($raw);
        
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $cleaned, $matches)) {
            $cleaned = $matches[1];
        } elseif (preg_match('/(\{.*\})/s', $cleaned, $matches)) {
            $cleaned = $matches[1];
        }

        return json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);
    }

    private function normalizeConfidence(mixed $value): float
    {
        if (is_numeric($value)) {
            $float = (float) $value;
            if ($float > 1.0 && $float <= 100.0) {
                $float = $float / 100;
            }
            return max(0.0, min(1.0, $float));
        }

        if (is_string($value)) {
            $clean = str_replace(['%', ' '], '', $value);
            $float = (float) $clean;
            if ($float > 1) {
                $float = $float / 100;
            }
            return max(0.0, min(1.0, $float));
        }

        return 0.0;
    }

    private function fallbackDecision(string $errorMessage): array
    {
        return [
            'should_execute' => false,
            'confidence' => 0.0,
            'matched_conditions' => [],
            'action_to_execute' => null,
            'action_parameters' => [],
            'reasoning' => $errorMessage,
            'raw_response' => null,
        ];
    }
}
