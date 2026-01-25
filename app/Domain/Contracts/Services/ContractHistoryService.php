<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractHistory;

/**
 * Service para registro automático de histórico de contratos
 * 
 * Registra eventos no ciclo de vida do contrato
 */
class ContractHistoryService
{
    /**
     * Registra evento de upload de documento
     */
    public function logDocumentUpload(Contract $contract, string $fileName, ?int $userId = null): void
    {
        $this->log($contract, 'upload', "Documento '{$fileName}' foi enviado", [
            'file_name' => $fileName,
        ], $userId);
    }

    /**
     * Registra mudança de status
     */
    public function logStatusChange(Contract $contract, string $oldStatus, string $newStatus, ?int $userId = null): void
    {
        $this->log($contract, 'status_change', "Status alterado de '{$oldStatus}' para '{$newStatus}'", [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ], $userId);
    }

    /**
     * Registra disparo de alerta
     */
    public function logAlertFired(Contract $contract, string $alertType, ?int $taskId = null): void
    {
        $this->log($contract, 'alert_fired', "Alerta disparado: {$alertType}", [
            'alert_type' => $alertType,
            'task_id' => $taskId,
        ]);
    }

    /**
     * Registra interação com IA
     */
    public function logAiInteraction(Contract $contract, string $question, ?int $userId = null): void
    {
        $this->log($contract, 'ai_interaction', "Pergunta ao Assistente Jurídico IA", [
            'question' => $question,
        ], $userId);
    }

    /**
     * Registra edição manual
     */
    public function logManualEdit(Contract $contract, array $changes, ?int $userId = null): void
    {
        $fields = implode(', ', array_keys($changes));
        
        $this->log($contract, 'manual_edit', "Campos editados: {$fields}", [
            'changes' => $changes,
        ], $userId);
    }

    /**
     * Registra criação do contrato
     */
    public function logContractCreated(Contract $contract, ?int $userId = null): void
    {
        $this->log($contract, 'created', "Contrato criado", [], $userId);
    }

    /**
     * Registra arquivamento do contrato
     */
    public function logContractArchived(Contract $contract, ?int $userId = null): void
    {
        $this->log($contract, 'archived', "Contrato arquivado", [], $userId);
    }

    /**
     * Método genérico para registrar evento
     */
    private function log(
        Contract $contract,
        string $eventType,
        string $description,
        array $metadata = [],
        ?int $userId = null
    ): void {
        ContractHistory::create([
            'contract_id' => $contract->id,
            'team_id' => $contract->team_id,
            'user_id' => $userId ?? auth()->id(),
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
