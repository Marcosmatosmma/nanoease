<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\AI\Services\PrismClient;
use App\Domain\AI\Prompts\Contracts\ContractAssistantPrompt;
use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Services\ContractHistoryService;
use App\Domain\Contracts\Services\VectorSearchService;

/**
 * Service para Assistente Jurídico IA
 * 
 * Responde perguntas sobre contratos em linguagem natural
 */
class ContractAssistantService
{
    public function __construct(
        private readonly PrismClient $prism,
        private readonly ContractAssistantPrompt $prompt,
        private readonly ContractHistoryService $historyService,
        private readonly VectorSearchService $vectorSearch
    ) {}

    /**
     * Faz uma pergunta sobre o contrato
     * 
     * @param Contract $contract
     * @param string $question Pergunta do usuário
     * @param int|null $userId
     * @return array ['question' => string, 'answer' => string]
     */
    public function ask(Contract $contract, string $question, ?int $userId = null): array
    {
        // 1. Busca trechos relevantes com RAG
        $chunks = $this->vectorSearch->search($contract, $question, 5);
        
        $contextText = '';
        if (!empty($chunks)) {
            $contextText = implode("\n\n---\n\n", array_column($chunks, 'content'));
        } else {
            // Fallback: se não tiver embeddings (ex: contrato antigo), tenta pegar do documento direto
            $document = $contract->documents()->whereNotNull('extracted_text')->first();
            if ($document) {
                $contextText = mb_substr($document->extracted_text, 0, 15000);
            }
        }

        if (empty($contextText)) {
            throw new \RuntimeException('Não foi possível encontrar texto no contrato.');
        }

        // 2. Gerar prompt com contexto
        $promptText = $this->prompt->render([
            'contract_text' => $contextText,
            'question' => $question,
        ]);

        // 3. Chamar IA
        $answer = $this->prism->ask($promptText);

        // 4. Registrar interação no histórico
        $this->historyService->logAiInteraction($contract, $question, $userId);

        return [
            'question' => $question,
            'answer' => $answer,
        ];
    }

    /**
     * Gera resumo executivo do contrato
     * 
     * @param Contract $contract
     * @return string
     */
    public function generateSummary(Contract $contract): string
    {
        return $this->ask($contract, 'Faça um resumo executivo deste contrato, destacando: tipo, prazo, principais obrigações, riscos e datas críticas.')['answer'];
    }

    /**
     * Identifica riscos do contrato
     * 
     * @param Contract $contract
     * @return string
     */
    public function identifyRisks(Contract $contract): string
    {
        return $this->ask($contract, 'Quais são os principais riscos práticos deste contrato para o contratante?')['answer'];
    }

    /**
     * Verifica se tem renovação automática
     * 
     * @param Contract $contract
     * @return string
     */
    public function checkAutoRenewal(Contract $contract): string
    {
        return $this->ask($contract, 'Esse contrato tem renovação automática? Se sim, qual o prazo de aviso prévio para cancelamento?')['answer'];
    }
}
