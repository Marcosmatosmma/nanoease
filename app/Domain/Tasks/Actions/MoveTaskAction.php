<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Actions;

use App\Domain\Tasks\Models\Task;
use App\Domain\Tasks\Models\BoardList;
use Illuminate\Support\Facades\DB;

/**
 * Action para mover tarefa entre listas
 * 
 * Move tarefa para outra lista e atualiza positions
 * Implementa drag-and-drop do Kanban
 */
class MoveTaskAction
{
    /**
     * Move tarefa para outra lista (ou reordena na mesma)
     * 
     * @param Task $task Tarefa a ser movida
     * @param BoardList $targetList Lista de destino
     * @param int $newPosition Nova posição na lista
     * @return Task Tarefa atualizada
     */
    public function handle(Task $task, BoardList $targetList, int $newPosition): Task
    {
        return DB::transaction(function () use ($task, $targetList, $newPosition) {
            $oldListId = $task->board_list_id;
            $oldPosition = $task->position;

            // Se mudou de lista
            if ($oldListId !== $targetList->id) {
                // Atualiza positions na lista antiga (fecha o gap)
                Task::where('board_list_id', $oldListId)
                    ->where('position', '>', $oldPosition)
                    ->decrement('position');

                // Abre espaço na nova lista
                Task::where('board_list_id', $targetList->id)
                    ->where('position', '>=', $newPosition)
                    ->increment('position');

                // Move a tarefa
                $task->update([
                    'board_list_id' => $targetList->id,
                    'position' => $newPosition,
                ]);

                // Se moveu para "Concluído", marca como completa
                if ($targetList->name === 'Concluído' && !$task->is_completed) {
                    $task->update([
                        'is_completed' => true,
                        'completed_at' => now(),
                    ]);
                }

                // Se tirou de "Concluído", desmarca
                if ($targetList->name !== 'Concluído' && $task->is_completed) {
                    $task->update([
                        'is_completed' => false,
                        'completed_at' => null,
                    ]);
                }
            } else {
                // Reordenação na mesma lista
                if ($newPosition < $oldPosition) {
                    // Movendo para cima
                    Task::where('board_list_id', $targetList->id)
                        ->where('position', '>=', $newPosition)
                        ->where('position', '<', $oldPosition)
                        ->increment('position');
                } elseif ($newPosition > $oldPosition) {
                    // Movendo para baixo
                    Task::where('board_list_id', $targetList->id)
                        ->where('position', '>', $oldPosition)
                        ->where('position', '<=', $newPosition)
                        ->decrement('position');
                }

                $task->update(['position' => $newPosition]);
            }

            return $task->fresh(['boardList', 'assignedUser', 'labels']);
        });
    }
}
