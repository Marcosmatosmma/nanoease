<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractAlert;

/**
 * Service para gerenciamento de alertas de vencimento de contratos
 */
class ContractAlertService
{
    /**
     * Cria alertas padrão para um contrato
     * 
     * @param Contract $contract
     * @return void
     */
    public function createDefaultAlerts(Contract $contract): void
    {
        if (!$contract->end_date) {
            return;
        }

        // Alertas padrão: 30, 15 e 7 dias antes
        $defaultDays = [30, 15, 7];

        foreach ($defaultDays as $days) {
            ContractAlert::create([
                'contract_id' => $contract->id,
                'team_id' => $contract->team_id,
                'alert_type' => 'before_expiration',
                'days_before' => $days,
                'is_active' => true,
            ]);
        }

        // Alerta no dia do vencimento
        ContractAlert::create([
            'contract_id' => $contract->id,
            'team_id' => $contract->team_id,
            'alert_type' => 'on_expiration',
            'is_active' => true,
        ]);
    }

    /**
     * Cria alerta customizado
     * 
     * @param Contract $contract
     * @param string $alertType
     * @param int|null $daysBefore
     * @return ContractAlert
     */
    public function createAlert(Contract $contract, string $alertType, ?int $daysBefore = null): ContractAlert
    {
        return ContractAlert::create([
            'contract_id' => $contract->id,
            'team_id' => $contract->team_id,
            'alert_type' => $alertType,
            'days_before' => $daysBefore,
            'is_active' => true,
        ]);
    }

    /**
     * Verifica e dispara alertas pendentes
     * 
     * @param Contract $contract
     * @return array Alertas disparados
     */
    public function checkAndFireAlerts(Contract $contract): array
    {
        $firedAlerts = [];

        $pendingAlerts = $contract->alerts()
            ->active()
            ->notTriggered()
            ->get();

        foreach ($pendingAlerts as $alert) {
            if ($alert->shouldTrigger()) {
                $firedAlerts[] = $this->fireAlert($alert);
            }
        }

        return $firedAlerts;
    }

    /**
     * Dispara um alerta específico
     * 
     * @param ContractAlert $alert
     * @return ContractAlert
     */
    public function fireAlert(ContractAlert $alert): ContractAlert
    {
        $alert->triggered_at = now();
        $alert->save();

        return $alert;
    }

    /**
     * Desativa alerta
     */
    public function deactivateAlert(ContractAlert $alert): bool
    {
        $alert->is_active = false;
        return $alert->save();
    }

    /**
     * Reativa alerta
     */
    public function reactivateAlert(ContractAlert $alert): bool
    {
        $alert->is_active = true;
        $alert->triggered_at = null;
        return $alert->save();
    }
}
