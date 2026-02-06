<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Actions;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractEmbedding;
use App\Domain\Contracts\Services\EmbeddingService;
use Illuminate\Support\Facades\Log;

class ProcessContractEmbeddingsAction
{
    public function __construct(
        private readonly EmbeddingService $embeddingService
    ) {}

    public function handle(Contract $contract): void
    {
        // Pega o primeiro documento com texto extraído
        $document = $contract->documents()->whereNotNull('extracted_text')->first();

        if (!$document || empty($document->extracted_text)) {
            Log::warning("Contrato {$contract->id} não possui texto extraído para gerar embeddings.");
            return;
        }

        // Limpa embeddings anteriores deste contrato para evitar duplicidade
        ContractEmbedding::where('contract_id', $contract->id)->delete();

        $text = $document->extracted_text;
        $chunks = $this->chunkText($text, 1000, 200);

        foreach ($chunks as $index => $chunk) {
            try {
                $vector = $this->embeddingService->generate($chunk);

                ContractEmbedding::create([
                    'contract_id' => $contract->id,
                    'chunk_index' => $index,
                    'content' => $chunk,
                    'embedding' => $vector,
                ]);
            } catch (\Exception $e) {
                Log::error("Erro ao gerar embedding para chunk {$index} do contrato {$contract->id}: " . $e->getMessage());
                // Continua para o próximo chunk
            }
        }
    }

    /**
     * Divide o texto em pedaços com sobreposição
     */
    private function chunkText(string $text, int $chunkSize, int $overlap): array
    {
        $textLength = mb_strlen($text);
        $chunks = [];
        $start = 0;

        while ($start < $textLength) {
            $chunk = mb_substr($text, $start, $chunkSize);
            $chunks[] = $chunk;
            
            $start += ($chunkSize - $overlap);
        }

        return $chunks;
    }
}
