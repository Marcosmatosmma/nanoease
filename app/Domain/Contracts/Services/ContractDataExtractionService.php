<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\AI\Services\PrismClient;
use App\Domain\AI\Prompts\Contracts\ContractDataExtractionPrompt;
use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractDocument;

/**
 * Service para extração automática de dados do contrato usando IA
 * 
 * Identifica datas, valores, cláusulas importantes e riscos
 */
class ContractDataExtractionService
{
    public function __construct(
        private readonly PrismClient $prism,
        private readonly ContractDataExtractionPrompt $prompt
    ) {}

    /**
     * Extrai dados estruturados do documento do contrato
     * 
     * @param ContractDocument $document
     * @param string|null $teamName Nome do team do usuário para detectar papel
     * @return array Dados extraídos
     */
    public function extract(ContractDocument $document, ?string $teamName = null): array
    {
        // Validar se tem texto extraído
        if (empty($document->extracted_text)) {
            throw new \RuntimeException('Documento não possui texto extraído');
        }

        // Limitar tamanho do texto (primeiros 8000 caracteres)
        $text = mb_substr($document->extracted_text, 0, 8000);

        // Gerar prompt
        $promptText = $this->prompt->render([
            'contract_text' => $text,
            'team_name' => $teamName ?? '',
        ]);

        // Chamar IA
        $response = $this->prism->ask($promptText);

        // Parse JSON
        $data = $this->parseResponse($response);

        return $data;
    }

    /**
     * Atualiza contrato com dados extraídos pela IA
     * 
     * @param Contract $contract
     * @param array $extractedData
     * @return Contract
     */
    public function applyToContract(Contract $contract, array $extractedData): Contract
    {
        // Atualizar campos se vazios ou se IA trouxe informação melhor
        if (empty($contract->contract_type) && !empty($extractedData['contract_type'])) {
            $contract->contract_type = $extractedData['contract_type'];
        }

        if (empty($contract->parties_involved) && !empty($extractedData['parties_involved'])) {
            $contract->parties_involved = $extractedData['parties_involved'];
        }

        if (empty($contract->start_date) && !empty($extractedData['start_date'])) {
            $contract->start_date = $extractedData['start_date'];
        }

        if (empty($contract->end_date) && !empty($extractedData['end_date'])) {
            $contract->end_date = $extractedData['end_date'];
        }

        if ($contract->auto_renewal === null && isset($extractedData['auto_renewal'])) {
            $contract->auto_renewal = $extractedData['auto_renewal'];
        }

        if (empty($contract->amount) && !empty($extractedData['amount'])) {
            $contract->amount = $extractedData['amount'];
        }

        if (!empty($extractedData['currency'])) {
            $contract->currency = $extractedData['currency'];
        }

        // Gerar resumo executivo
        $contract->ai_summary = $this->generateSummary($extractedData);

        $contract->save();

        return $contract->fresh();
    }

    /**
     * Faz parse da resposta da IA
     */
    private function parseResponse(string $response): array
    {
        // Extrair JSON da resposta (pode vir com texto antes/depois)
        preg_match('/\{.*\}/s', $response, $matches);

        if (empty($matches[0])) {
            throw new \RuntimeException('IA não retornou JSON válido');
        }

        $data = json_decode($matches[0], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Erro ao decodificar JSON: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Gera resumo executivo a partir dos dados extraídos
     */
    private function generateSummary(array $data): string
    {
        $parts = [];

        if (!empty($data['contract_type'])) {
            $parts[] = "Tipo: {$data['contract_type']}";
        }

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $parts[] = "Vigência: {$data['start_date']} a {$data['end_date']}";
        }

        if (isset($data['auto_renewal'])) {
            $renewal = $data['auto_renewal'] ? 'Sim' : 'Não';
            $parts[] = "Renovação automática: {$renewal}";
        }

        if (!empty($data['amount']) && !empty($data['currency'])) {
            $parts[] = "Valor: {$data['currency']} {$data['amount']}";
        }

        if (!empty($data['key_clauses'])) {
            $parts[] = "\n\nCláusulas importantes:\n- " . implode("\n- ", $data['key_clauses']);
        }

        if (!empty($data['risks'])) {
            $parts[] = "\n\nRiscos identificados:\n- " . implode("\n- ", $data['risks']);
        }

        return implode("\n", $parts);
    }
}
