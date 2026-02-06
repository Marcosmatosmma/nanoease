<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class EmbeddingService
{
    /**
     * Gera embedding para um texto usando OpenAI (ou compatível)
     */
    public function generate(string $text): array
    {
        // Limpa o texto para evitar problemas no JSON
        $text = str_replace(["\n", "\r"], ' ', $text);

        $apiKey = config('prism.providers.openai.api_key') ?? env('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('OpenAI API Key não configurada para embeddings.');
        }

        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small', // Modelo rápido e eficiente
                'input' => $text,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Erro ao gerar embedding: ' . $response->body());
        }

        return $response->json('data.0.embedding');
    }
}
