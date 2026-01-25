<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\AI\Services\PrismClient;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Service para extrair dados de Nota Fiscal em PDF usando IA
 */
class InvoicePdfExtractionService
{
    public function __construct(
        private readonly PrismClient $prismClient
    ) {}

    /**
     * Extrai dados de uma nota fiscal em PDF
     * 
     * @param string $pdfPath Caminho do arquivo PDF
     * @return array{numero: ?string, data_emissao: ?string, valor_total: ?float, data_vencimento: ?string}
     */
    public function extractFromPdf(string $pdfPath): array
    {
        // Extrair texto do PDF
        $text = $this->extractTextFromPdf($pdfPath);
        
        if (empty($text)) {
            return [
                'numero' => null,
                'data_emissao' => null,
                'valor_total' => null,
                'data_vencimento' => null,
            ];
        }

        // Limitar o texto para não enviar muito conteúdo
        $text = substr($text, 0, 8000);

        // Usar IA para extrair dados estruturados
        return $this->extractWithAI($text);
    }

    /**
     * Extrai texto do PDF
     */
    private function extractTextFromPdf(string $path): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($path);
            $text = $pdf->getText();
            
            // Limpar texto
            $text = trim($text);
            $text = preg_replace('/\s+/', ' ', $text);
            
            return $text;
        } catch (\Exception $e) {
            \Log::error('Erro ao extrair texto do PDF da NF: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Usa IA para extrair dados estruturados do texto
     */
    private function extractWithAI(string $text): array
    {
        $prompt = $this->buildPrompt($text);

        try {
            $response = $this->prismClient->ask($prompt);
            
            \Log::info('Resposta da IA para extração de NF', [
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500),
            ]);
            
            // Tentar extrair JSON se vier com texto adicional (markdown, etc)
            $jsonContent = $this->extractJsonFromResponse($response);
            
            if (!$jsonContent) {
                \Log::warning('Não foi possível extrair JSON da resposta da IA', [
                    'response' => $response
                ]);
                return $this->emptyResponse();
            }
            
            // Parse da resposta JSON
            $data = json_decode($jsonContent, true);
            
            if (!is_array($data)) {
                \Log::warning('IA retornou JSON inválido para extração de NF', [
                    'json' => $jsonContent,
                    'error' => json_last_error_msg()
                ]);
                return $this->emptyResponse();
            }

            \Log::info('Dados extraídos com sucesso da NF', $data);

            return [
                'numero' => $data['numero'] ?? null,
                'data_emissao' => $this->formatDate($data['data_emissao'] ?? null),
                'valor_total' => $this->parseValue($data['valor_total'] ?? null),
                'data_vencimento' => $this->formatDate($data['data_vencimento'] ?? null),
            ];
        } catch (\Exception $e) {
            \Log::error('Erro ao extrair dados da NF com IA: ' . $e->getMessage());
            return $this->emptyResponse();
        }
    }

    /**
     * Extrai JSON de uma resposta que pode conter markdown ou texto adicional
     */
    private function extractJsonFromResponse(string $response): ?string
    {
        // Tentar parse direto primeiro
        $trimmed = trim($response);
        if (str_starts_with($trimmed, '{')) {
            return $trimmed;
        }

        // Tentar extrair JSON de markdown code block
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/', $response, $matches)) {
            return trim($matches[1]);
        }

        // Tentar encontrar primeiro objeto JSON no texto
        if (preg_match('/(\{[\s\S]*?\})/', $response, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Constrói o prompt para a IA
     */
    private function buildPrompt(string $text): string
    {
        return <<<PROMPT
Extraia os seguintes dados desta Nota Fiscal brasileira:

TEXTO:
{$text}

Retorne SOMENTE um objeto JSON neste formato exato (sem markdown, sem explicações):
{
  "numero": "número da NF ou null",
  "data_emissao": "YYYY-MM-DD ou null",
  "valor_total": número ou null,
  "data_vencimento": "YYYY-MM-DD ou null"
}

Regras:
- Use null (sem aspas) para campos não encontrados
- Datas no formato YYYY-MM-DD
- Valor total como número sem símbolos de moeda
- Não adicione texto antes ou depois do JSON
PROMPT;
    }

    /**
     * Formata data para YYYY-MM-DD
     */
    private function formatDate(?string $date): ?string
    {
        if (empty($date) || $date === 'null') {
            return null;
        }

        try {
            $dt = new \DateTime($date);
            return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Converte valor para float
     */
    private function parseValue($value): ?float
    {
        if ($value === null || $value === 'null') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Tentar limpar string e converter
        $cleaned = preg_replace('/[^0-9.,]/', '', (string) $value);
        $cleaned = str_replace(',', '.', $cleaned);
        
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Retorna resposta vazia
     */
    private function emptyResponse(): array
    {
        return [
            'numero' => null,
            'data_emissao' => null,
            'valor_total' => null,
            'data_vencimento' => null,
        ];
    }
}
