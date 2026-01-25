<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Actions;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractAlert;
use App\Domain\Tasks\Models\Task;
use App\Domain\Tasks\Models\BoardList;

/**
 * Action para criar tarefa no Kanban a partir de alerta de contrato
 */
class CreateTaskFromContractAction
{
    /**
     * Cria tarefa a partir de alerta de contrato
     * 
     * @param ContractAlert $alert
     * @param int $boardListId ID da lista onde criar a tarefa
     * @return Task
     */
    public function handle(ContractAlert $alert, int $boardListId): Task
    {
        $contract = $alert->contract;

        // Validar board_list_id e permissão (multi-tenancy)
        $boardList = BoardList::where('id', $boardListId)
            ->whereHas('board', function ($query) use ($contract) {
                $query->where('team_id', $contract->team_id);
            })
            ->firstOrFail();

        // Calcular próxima posição na lista
        $maxPosition = Task::where('board_list_id', $boardList->id)
            ->where('team_id', $contract->team_id)
            ->max('position') ?? -1;

        // Montar título e descrição
        $title = $this->buildTitle($alert);
        $description = $this->buildDescription($alert);

        // Criar tarefa
        $task = Task::create([
            'user_id' => $contract->user_id,
            'team_id' => $contract->team_id,
            'board_list_id' => $boardList->id,
            'title' => $title,
            'description' => $description,
            'source' => 'contract',
            'source_id' => $contract->id,
            'source_metadata' => [
                'alert_id' => $alert->id,
                'alert_type' => $alert->alert_type,
                'contract_name' => $contract->name,
            ],
            'position' => $maxPosition + 1,
            'due_date' => $contract->end_date,
            'is_completed' => false,
        ]);

        // Vincular tarefa ao alerta
        $alert->task_id = $task->id;
        $alert->save();

        return $task;
    }

    /**
     * Monta título da tarefa
     */
    private function buildTitle(ContractAlert $alert): string
    {
        $contract = $alert->contract;
        
        switch ($alert->alert_type) {
            case 'before_expiration':
                return "Revisar contrato \"{$contract->name}\" - vence em {$alert->days_before} dias";
            case 'on_expiration':
                return "Contrato \"{$contract->name}\" vence hoje";
            case 'after_expiration':
                return "Contrato \"{$contract->name}\" está vencido";
            default:
                return "Alerta: {$contract->name}";
        }
    }

    /**
     * Monta descrição da tarefa
     */
    private function buildDescription(ContractAlert $alert): string
    {
        $contract = $alert->contract;
        $parts = [];

        $parts[] = '<p><strong>Contrato:</strong> ' . htmlspecialchars($contract->name) . '</p>';

        if ($contract->contract_type) {
            $parts[] = '<p><strong>Tipo:</strong> ' . htmlspecialchars($contract->contract_type) . '</p>';
        }

        if ($contract->end_date) {
            $parts[] = '<p><strong>Data de vencimento:</strong> ' . $contract->end_date->format('d/m/Y') . '</p>';
        }

        if ($contract->auto_renewal !== null) {
            $renewal = $contract->auto_renewal ? 'Sim' : 'Não';
            $parts[] = '<p><strong>Renovação automática:</strong> ' . $renewal . '</p>';
        }

        if ($contract->amount) {
            $parts[] = '<p><strong>Valor:</strong> ' . $contract->currency . ' ' . number_format($contract->amount, 2, ',', '.') . '</p>';
        }

        $parts[] = '<hr>';

        // Link para visualizar contrato
        $parts[] = '<p><a href="/contracts/' . $contract->id . '" target="_blank" style="color: #2563eb; text-decoration: underline;">📄 Ver detalhes do contrato</a></p>';

        // Ações recomendadas
        $parts[] = '<p><strong>Ações recomendadas:</strong></p>';
        $parts[] = '<ul>';
        $parts[] = '<li>Revisar cláusulas de renovação e cancelamento</li>';
        $parts[] = '<li>Avaliar se o contrato deve ser renovado</li>';
        $parts[] = '<li>Notificar a outra parte se necessário cancelar</li>';
        $parts[] = '</ul>';

        return implode("\n", $parts);
    }
}
