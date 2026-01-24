<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Actions;

use App\Domain\Tasks\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Action para deletar uma tarefa
 * 
 * Remove tarefa e atualiza positions das outras
 */
class DeleteTaskAction
{
    /**
     * Deleta uma tarefa
     * Reorganiza positions na lista
     * 
     * @param Task $task Tarefa a ser deletada
     * @return void
     */
    public function handle(Task $task): void
    {
        DB::transaction(function () use ($task) {
            $listId = $task->board_list_id;
            $position = $task->position;

            // Deleta a tarefa (cascade vai deletar relationships)
            $task->delete();

            // Fecha o gap nas positions
            Task::where('board_list_id', $listId)
                ->where('position', '>', $position)
                ->decrement('position');
        });
    }
}
