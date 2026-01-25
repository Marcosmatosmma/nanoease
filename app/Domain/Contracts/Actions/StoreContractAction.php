<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Actions;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractDocument;
use App\Domain\Contracts\Services\ContractHistoryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Action para criar novo contrato
 */
class StoreContractAction
{
    public function __construct(
        private readonly ContractHistoryService $historyService
    ) {}

    /**
     * Cria novo contrato
     * 
     * @param array $data Dados do contrato
     * @param int $userId ID do usuário criador
     * @param int $teamId ID do team (multi-tenancy)
     * @param UploadedFile|null $document Documento do contrato (opcional)
     * @return Contract
     */
    public function handle(array $data, int $userId, int $teamId, ?UploadedFile $document = null): Contract
    {
        // Criar contrato
        $contract = Contract::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'name' => $data['name'],
            'contract_type' => $data['contract_type'] ?? null,
            'my_role' => $data['my_role'] ?? null,
            'contract_object' => $data['contract_object'] ?? null,
            'contract_number' => $data['contract_number'] ?? null,
            'contractor' => $data['contractor'] ?? null,
            'contractor_cpf_cnpj' => $data['contractor_cpf_cnpj'] ?? null,
            'contracted' => $data['contracted'] ?? null,
            'contracted_cpf_cnpj' => $data['contracted_cpf_cnpj'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'auto_renewal' => $data['auto_renewal'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? 'BRL',
            'payment_terms' => $data['payment_terms'] ?? null,
            'status' => 'ativo',
            'notes' => $data['notes'] ?? null,
            // Dados de Nota Fiscal
            'invoice_contact_email' => $data['invoice_contact_email'] ?? null,
            'invoice_contact_link' => $data['invoice_contact_link'] ?? null,
            'invoice_system' => $data['invoice_system'] ?? null,
            'invoice_description' => $data['invoice_description'] ?? null,
            'invoice_internal_notes' => $data['invoice_internal_notes'] ?? null,
            'invoice_recipient_name' => $data['invoice_recipient_name'] ?? null,
            'invoice_recipient_cnpj' => $data['invoice_recipient_cnpj'] ?? null,
            'invoice_state_registration' => $data['invoice_state_registration'] ?? null,
            'invoice_recipient_address' => $data['invoice_recipient_address'] ?? null,
            'invoice_service_code' => $data['invoice_service_code'] ?? null,
            'invoice_due_day' => $data['invoice_due_day'] ?? null,
            'invoice_cnpj_api_data' => isset($data['invoice_cnpj_api_data']) 
                ? json_encode($data['invoice_cnpj_api_data']) 
                : null,
        ]);

        // Salvar documento se fornecido
        if ($document) {
            $this->saveDocument($contract, $document, $userId, $teamId);
        }

        // Registrar histórico
        $this->historyService->logContractCreated($contract, $userId);

        return $contract->fresh(['documents', 'alerts']);
    }

    /**
     * Salva documento do contrato no storage
     */
    private function saveDocument(Contract $contract, UploadedFile $file, int $userId, int $teamId): void
    {
        // Gerar nome único para o arquivo
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = pathinfo($originalName, PATHINFO_FILENAME);
        $uniqueName = $fileName . '_' . time() . '.' . $extension;

        // Salvar no storage (contracts/team_id/)
        $path = $file->storeAs(
            "contracts/team_{$teamId}",
            $uniqueName,
            'private'
        );

        // Criar registro do documento
        ContractDocument::create([
            'contract_id' => $contract->id,
            'team_id' => $teamId,
            'uploaded_by' => $userId,
            'file_name' => $originalName,
            'file_path' => $path,
            'file_type' => strtolower($extension),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }
}
