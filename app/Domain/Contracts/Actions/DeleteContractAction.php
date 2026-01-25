<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Actions;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Services\DocumentStorageService;
use App\Domain\Contracts\Services\ContractHistoryService;

/**
 * Action para excluir contrato
 */
class DeleteContractAction
{
    public function __construct(
        private readonly DocumentStorageService $storageService,
        private readonly ContractHistoryService $historyService
    ) {}

    /**
     * Exclui contrato e todos os dados relacionados
     * 
     * @param Contract $contract
     * @param int $userId ID do usuário que está excluindo
     * @return bool
     */
    public function handle(Contract $contract, int $userId): bool
    {
        // Excluir documentos físicos
        foreach ($contract->documents as $document) {
            $this->storageService->delete($document);
        }

        // Histórico será excluído em cascata
        // Alertas serão excluídos em cascata
        
        // Excluir contrato (cascata automática)
        return $contract->delete();
    }
}
