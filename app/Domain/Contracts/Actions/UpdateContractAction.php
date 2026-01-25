<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Actions;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Services\ContractHistoryService;

/**
 * Action para atualizar contrato existente
 */
class UpdateContractAction
{
    public function __construct(
        private readonly ContractHistoryService $historyService
    ) {}

    /**
     * Atualiza contrato existente
     * 
     * @param Contract $contract
     * @param array $data Novos dados
     * @param int $userId ID do usuário que está editando
     * @return Contract
     */
    public function handle(Contract $contract, array $data, int $userId): Contract
    {
        // Rastrear mudanças para histórico
        $changes = [];
        $oldStatus = $contract->status;

        // Campos permitidos para edição
        $fillableFields = [
            'name',
            'contract_type',
            'my_role',
            'contract_object',
            'contract_number',
            'contractor',
            'contractor_cpf_cnpj',
            'contracted',
            'contracted_cpf_cnpj',
            'parties_involved',
            'start_date',
            'end_date',
            'auto_renewal',
            'amount',
            'currency',
            'payment_terms',
            'status',
            'notes',
            'ai_summary',
            // Dados de Nota Fiscal
            'invoice_contact_email',
            'invoice_contact_link',
            'invoice_system',
            'invoice_description',
            'invoice_internal_notes',
            'invoice_recipient_name',
            'invoice_recipient_cnpj',
            'invoice_state_registration',
            'invoice_recipient_address',
            'invoice_service_code',
            'invoice_due_day',
        ];

        foreach ($fillableFields as $field) {
            if (array_key_exists($field, $data) && $contract->{$field} !== $data[$field]) {
                $changes[$field] = [
                    'old' => $contract->{$field},
                    'new' => $data[$field],
                ];
                $contract->{$field} = $data[$field];
            }
        }

        if (empty($changes)) {
            return $contract;
        }

        // Salvar alterações
        $contract->save();

        // Registrar histórico
        if (isset($changes['status'])) {
            $this->historyService->logStatusChange(
                $contract,
                $oldStatus,
                $contract->status,
                $userId
            );
        }

        if (count($changes) > 0) {
            $this->historyService->logManualEdit($contract, $changes, $userId);
        }

        return $contract->fresh(['documents', 'alerts', 'history']);
    }
}
