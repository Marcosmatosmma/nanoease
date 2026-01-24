<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Actions;

use App\Domain\Tasks\Models\Task;
use App\Domain\Tasks\Models\BoardList;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action para criar uma nova tarefa
 * 
 * Cria tarefa em uma lista específica
 * Suporta múltiplas origens: manual, automação, formulário
 */
class StoreTaskAction
{
    /**
     * Cria uma nova tarefa
     * 
     * @param User $user Usuário autenticado (criador)
     * @param BoardList $boardList Lista onde a tarefa será criada
     * @param array $data Dados da tarefa
     * @return Task Tarefa criada
     */
    public function handle(User $user, BoardList $boardList, array $data): Task
    {
        return DB::transaction(function () use ($user, $boardList, $data) {
            // Busca a maior position na lista
            $maxPosition = $boardList->tasks()->max('position') ?? 0;

            // Cria a tarefa
            $task = Task::create([
                'board_list_id' => $boardList->id,
                'user_id' => $user->id,
                'team_id' => $user->currentTeam->id,
                'assigned_to' => $data['assigned_to'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'position' => $maxPosition + 1, // Adiciona no final
                'source' => $data['source'] ?? 'manual',
                'source_id' => $data['source_id'] ?? null,
                'source_metadata' => $data['source_metadata'] ?? null,
            ]);

            // Adiciona labels se fornecidas
            if (!empty($data['label_ids'])) {
                $task->labels()->attach($data['label_ids']);
            }

            return $task->fresh(['assignedUser', 'labels']);
        });
    }
}
