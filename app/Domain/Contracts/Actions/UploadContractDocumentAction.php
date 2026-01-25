<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Actions;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractDocument;
use App\Domain\Contracts\Services\DocumentStorageService;
use App\Domain\Contracts\Services\ContractHistoryService;
use Illuminate\Http\UploadedFile;

/**
 * Action para upload de documento do contrato
 */
class UploadContractDocumentAction
{
    public function __construct(
        private readonly DocumentStorageService $storageService,
        private readonly ContractHistoryService $historyService
    ) {}

    /**
     * Faz upload de documento para o contrato
     * 
     * @param Contract $contract
     * @param UploadedFile $file
     * @param int $userId ID do usuário fazendo upload
     * @return ContractDocument
     */
    public function handle(Contract $contract, UploadedFile $file, int $userId): ContractDocument
    {
        // Armazenar documento
        $document = $this->storageService->store($contract, $file);

        // Registrar histórico
        $this->historyService->logDocumentUpload($contract, $file->getClientOriginalName(), $userId);

        return $document;
    }
}
