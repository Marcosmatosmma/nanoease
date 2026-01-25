<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Actions;

use App\Domain\Tasks\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Action para atualizar uma tarefa existente
 * 
 * Atualiza dados da tarefa
 * Sincroniza labels
 */
class UpdateTaskAction
{
    /**
     * Atualiza uma tarefa existente
     * 
     * @param Task $task Tarefa a ser atualizada
     * @param array $data Dados para atualizar
     * @return Task Tarefa atualizada
     */
    public function handle(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            // Atualiza campos básicos
            $task->update([
                'title' => $data['title'] ?? $task->title,
                'description' => $data['description'] ?? $task->description,
                'due_date' => $data['due_date'] ?? $task->due_date,
                'assigned_to' => $data['assigned_to'] ?? $task->assigned_to,
                'board_list_id' => $data['board_list_id'] ?? $task->board_list_id,
            ]);

            // Atualiza labels se fornecidas
            if (isset($data['label_ids'])) {
                $task->labels()->sync($data['label_ids']);
            }

            // Se marcar/desmarcar como completa manualmente
            if (isset($data['is_completed'])) {
                $task->update([
                    'is_completed' => $data['is_completed'],
                    'completed_at' => $data['is_completed'] ? now() : null,
                ]);
            }

            return $task->fresh(['assignedUser', 'labels']);
        });
    }
}
