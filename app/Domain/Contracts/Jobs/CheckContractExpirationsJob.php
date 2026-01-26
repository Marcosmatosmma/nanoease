<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Jobs;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractAlert;
use App\Domain\Contracts\Services\ContractAlertService;
use App\Domain\Contracts\Services\ContractHistoryService;
use App\Domain\Contracts\Actions\CreateTaskFromContractAction;
use App\Domain\Tasks\Models\BoardList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para verificação diária de vencimentos de contratos
 * 
 * Executa diariamente para:
 * - Atualizar status de contratos vencidos
 * - Disparar alertas pendentes
 * - Criar tarefas automaticamente
 */
class CheckContractExpirationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(
        ContractAlertService $alertService,
        ContractHistoryService $historyService,
        CreateTaskFromContractAction $createTaskAction
    ): void {
        Log::info('Iniciando verificação de vencimentos de contratos');

        $this->updateExpiredContracts($historyService);
        $this->checkAndFireAlerts($alertService, $historyService, $createTaskAction);

        Log::info('Verificação de vencimentos concluída');
    }

    /**
     * Atualiza status de contratos vencidos
     */
    private function updateExpiredContracts(ContractHistoryService $historyService): void
    {
        $expiredContracts = Contract::where('status', 'ativo')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now())
            ->get();

        foreach ($expiredContracts as $contract) {
            $oldStatus = $contract->status;
            $contract->status = 'vencido';
            $contract->save();

            $historyService->logStatusChange($contract, $oldStatus, 'vencido', null);
            
            Log::info("Contrato #{$contract->id} marcado como vencido");
        }
    }

    /**
     * Verifica e dispara alertas pendentes
     */
    private function checkAndFireAlerts(
        ContractAlertService $alertService,
        ContractHistoryService $historyService,
        CreateTaskFromContractAction $createTaskAction
    ): void {
        $activeContracts = Contract::where('status', 'ativo')
            ->whereNotNull('end_date')
            ->with(['alerts' => function ($query) {
                $query->active()->notTriggered();
            }])
            ->get();

        foreach ($activeContracts as $contract) {
            $firedAlerts = $alertService->checkAndFireAlerts($contract);

            foreach ($firedAlerts as $alert) {
                // Registrar no histórico
                $historyService->logAlertFired($contract, $alert->alert_type);

                // Enviar e-mail se configurado
                $this->trySendEmail($contract, $alert);

                // Criar tarefa automaticamente se houver lista padrão
                $this->tryCreateTask($contract, $alert, $createTaskAction, $historyService);

                Log::info("Alerta #{$alert->id} disparado para contrato #{$contract->id}");
            }
        }
    }

    /**
     * Tenta enviar e-mail de alerta
     */
    private function trySendEmail(Contract $contract, ContractAlert $alert): void
    {
        if (!$alert->send_email || !$alert->email_to) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Notification::route('mail', $alert->email_to)
                ->notify(new \App\Notifications\ContractAlertTriggered($contract, $alert));

            Log::info("E-mail enviado para {$alert->email_to} - Alerta #{$alert->id}");
        } catch (\Exception $e) {
            Log::error("Falha ao enviar e-mail para alerta #{$alert->id}: {$e->getMessage()}");
        }
    }

    /**
     * Tenta criar tarefa automaticamente
     */
    private function tryCreateTask(
        Contract $contract,
        ContractAlert $alert,
        CreateTaskFromContractAction $createTaskAction,
        ContractHistoryService $historyService
    ): void {
        try {
            // Buscar primeira lista do primeiro board do team (lista "A Fazer")
            $defaultList = BoardList::whereHas('board', function ($query) use ($contract) {
                $query->where('team_id', $contract->team_id);
            })->first();

            if ($defaultList) {
                $task = $createTaskAction->handle($alert, $defaultList->id);
                
                $historyService->logAlertFired($contract, $alert->alert_type, $task->id);
                
                Log::info("Tarefa #{$task->id} criada automaticamente para alerta #{$alert->id}");
            }
        } catch (\Exception $e) {
            Log::warning("Não foi possível criar tarefa automática para alerta #{$alert->id}: {$e->getMessage()}");
        }
    }
}
