<?php

declare(strict_types=1);

namespace App\Domain\AI\Prompts;

/**
 * Prompt para melhorar/corrigir/expandir/resumir texto com IA
 * 
 * Gera instruções específicas para cada tipo de ação
 */
final class TextImprovementPrompt
{
    public function __construct(
        private readonly string $text,
        private readonly string $action,
    ) {}

    /**
     * Retorna o system prompt baseado na ação
     */
    public function getSystemPrompt(): string
    {
        return match($this->action) {
            'improve' => $this->getImprovePrompt(),
            'correct' => $this->getCorrectPrompt(),
            'expand' => $this->getExpandPrompt(),
            'summarize' => $this->getSummarizePrompt(),
            default => 'Você é um assistente de escrita profissional.',
        };
    }

    /**
     * Retorna o user prompt (texto a processar)
     */
    public function getUserPrompt(): string
    {
        return $this->text;
    }

    /**
     * Prompt para melhorar texto
     */
    private function getImprovePrompt(): string
    {
        return <<<'PROMPT'
Você é um assistente de escrita profissional especializado em melhorar textos.

Sua tarefa é melhorar o texto fornecido seguindo estas diretrizes:
- Mantenha o tom profissional e cordial
- Corrija erros gramaticais e ortográficos
- Melhore a clareza e coesão
- Mantenha o significado original
- Preserve variáveis no formato {nome_variavel}
- Retorne APENAS o texto melhorado, sem explicações adicionais
- Mantenha a formatação HTML se houver (tags <p>, <strong>, etc)

Responda APENAS com o texto melhorado, nada mais.
PROMPT;
    }

    /**
     * Prompt para corrigir gramática
     */
    private function getCorrectPrompt(): string
    {
        return <<<'PROMPT'
Você é um corretor ortográfico e gramatical expert em português brasileiro.

Sua tarefa é corrigir o texto fornecido seguindo estas diretrizes:
- Corrija APENAS erros ortográficos e gramaticais
- NÃO altere o estilo ou tom do texto
- NÃO adicione ou remova conteúdo
- Preserve variáveis no formato {nome_variavel}
- Mantenha a formatação HTML se houver (tags <p>, <strong>, etc)
- Retorne APENAS o texto corrigido, sem explicações

Responda APENAS com o texto corrigido, nada mais.
PROMPT;
    }

    /**
     * Prompt para expandir texto
     */
    private function getExpandPrompt(): string
    {
        return <<<'PROMPT'
Você é um assistente de escrita que expande textos de forma profissional.

Sua tarefa é expandir o texto fornecido seguindo estas diretrizes:
- Adicione detalhes relevantes e úteis
- Mantenha o tom profissional
- Amplie ideias sem desviar do tema original
- Torne o texto mais completo e informativo
- Preserve variáveis no formato {nome_variavel}
- Mantenha a formatação HTML se houver (tags <p>, <strong>, etc)
- Retorne APENAS o texto expandido, sem explicações

Responda APENAS com o texto expandido, nada mais.
PROMPT;
    }

    /**
     * Prompt para resumir texto
     */
    private function getSummarizePrompt(): string
    {
        return <<<'PROMPT'
Você é um assistente de escrita que resume textos de forma concisa.

Sua tarefa é resumir o texto fornecido seguindo estas diretrizes:
- Mantenha apenas as informações essenciais
- Seja conciso e direto
- Preserve o tom profissional
- Mantenha o significado original
- Preserve variáveis no formato {nome_variavel}
- Mantenha a formatação HTML se houver (tags <p>, <strong>, etc)
- Retorne APENAS o texto resumido, sem explicações

Responda APENAS com o texto resumido, nada mais.
PROMPT;
    }
}
