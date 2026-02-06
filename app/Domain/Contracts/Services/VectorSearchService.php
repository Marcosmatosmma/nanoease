<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractEmbedding;

class VectorSearchService
{
    public function __construct(
        private readonly EmbeddingService $embeddingService
    ) {}

    /**
     * Busca os trechos mais relevantes do contrato para a query
     */
    public function search(Contract $contract, string $query, int $limit = 5): array
    {
        // 1. Gera embedding da query
        $queryVector = $this->embeddingService->generate($query);

        // 2. Busca todos os embeddings do contrato
        // Como o número de chunks é pequeno (< 100), podemos carregar em memória
        $embeddings = ContractEmbedding::where('contract_id', $contract->id)
            ->get();

        if ($embeddings->isEmpty()) {
            return [];
        }

        // 3. Calcula similaridade
        $results = $embeddings->map(function ($item) use ($queryVector) {
            return [
                'content' => $item->content,
                'similarity' => $this->cosineSimilarity($queryVector, $item->embedding),
            ];
        })
        ->sortByDesc('similarity')
        ->take($limit)
        ->values()
        ->toArray();

        return $results;
    }

    /**
     * Calcula Similaridade de Cosseno entre dois vetores
     */
    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        foreach ($vecA as $i => $a) {
            $b = $vecB[$i] ?? 0;
            $dotProduct += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
