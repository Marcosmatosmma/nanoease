<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Prompts\TextImprovementPrompt;
use Echolabsdev\Prism\Facades\Prism;

/**
 * Serviço de assistente de IA para melhorar textos
 * 
 * Fornece funcionalidades para:
 * - Melhorar texto (tom profissional)
 * - Corrigir gramática e ortografia
 * - Expandir conteúdo
 * - Resumir texto
 */
final class TextAssistantService
{
    /**
     * Processa texto com IA baseado na ação
     *
     * @param string $text Texto a processar
     * @param string $action Ação: improve, correct, expand, summarize
     * @return string Texto processado
     * @throws \Exception Se ação for inválida ou LLM falhar
     */
    public function process(string $text, string $action): string
    {
        // Valida ação
        $validActions = ['improve', 'correct', 'expand', 'summarize'];
        if (!in_array($action, $validActions, true)) {
            throw new \InvalidArgumentException("Ação inválida: {$action}. Use: " . implode(', ', $validActions));
        }

        // Valida texto
        if (empty(trim($text))) {
            throw new \InvalidArgumentException('Texto não pode estar vazio');
        }

        // Gera prompt
        $prompt = new TextImprovementPrompt($text, $action);

        // Chama LLM
        try {
            $response = Prism::text()
                ->using('anthropic', 'claude-3-5-sonnet-20241022')
                ->withSystemPrompt($prompt->getSystemPrompt())
                ->withPrompt($prompt->getUserPrompt())
                ->withMaxTokens(2048)
                ->generate();

            return trim($response->text);
        } catch (\Exception $e) {
            throw new \Exception('Erro ao processar texto com IA: ' . $e->getMessage());
        }
    }
}
