<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Prompts\EmailDataExtractionPrompt;
use Illuminate\Support\Facades\Log;

final class EmailDataExtractorService
{
    public function __construct(
        private readonly PrismClient $prismClient,
    ) {}

    /**
     * Extrai dados estruturados de um e-mail usando IA
     *
     * @param string $emailFrom Remetente do e-mail
     * @param string $emailSubject Assunto do e-mail
     * @param string $emailBody Corpo do e-mail (pode ser HTML ou texto)
     * @return array Dados extraídos estruturados
     */
    public function extract(string $emailFrom, string $emailSubject, string $emailBody): array
    {
        try {
            // Limpa HTML se necessário
            $cleanBody = $this->cleanHtml($emailBody);
            
            // Limita tamanho para não exceder tokens
            $cleanBody = mb_substr($cleanBody, 0, 3000);

            // Gera prompt
            $prompt = EmailDataExtractionPrompt::build($emailSubject, $cleanBody, $emailFrom);

            // Chama IA
            $response = $this->prismClient->ask($prompt);

            // Parse JSON
            $extracted = $this->parseResponse($response);

            Log::info('Email data extracted successfully', [
                'from' => $emailFrom,
                'subject' => $emailSubject,
                'extracted' => $extracted,
            ]);

            return $extracted;
        } catch (\Exception $e) {
            Log::error('Failed to extract email data', [
                'from' => $emailFrom,
                'subject' => $emailSubject,
                'error' => $e->getMessage(),
            ]);

            return $this->emptyExtraction();
        }
    }

    /**
     * Remove HTML e retorna texto limpo
     */
    private function cleanHtml(string $html): string
    {
        // Remove tags HTML
        $text = strip_tags($html);
        
        // Remove múltiplos espaços
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove espaços no início e fim
        return trim($text);
    }

    /**
     * Parse resposta da IA (pode vir com markdown ou direto)
     */
    private function parseResponse(string $response): array
    {
        // Remove markdown code blocks se houver
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        $response = trim($response);

        // Tenta decodificar JSON
        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse JSON from AI response', [
                'response' => $response,
                'error' => json_last_error_msg(),
            ]);

            return $this->emptyExtraction();
        }

        // Garante que campos obrigatórios existem
        return array_merge($this->emptyExtraction(), $decoded);
    }

    /**
     * Retorna estrutura vazia quando extração falha
     */
    private function emptyExtraction(): array
    {
        return [
            'tipo' => null,
            'valor' => null,
            'moeda' => null,
            'vencimento' => null,
            'numero_documento' => null,
            'cnpj' => null,
            'cpf' => null,
            'empresa' => null,
            'banco' => null,
            'codigo_barras' => null,
            'status' => null,
            'observacoes' => null,
        ];
    }
}
