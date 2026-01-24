<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Controllers;

use App\Domain\Tasks\Actions\ListUserBoardsAction;
use App\Domain\Tasks\Actions\StoreTaskAction;
use App\Domain\Tasks\Actions\UpdateTaskAction;
use App\Domain\Tasks\Actions\MoveTaskAction;
use App\Domain\Tasks\Actions\DeleteTaskAction;
use App\Domain\Tasks\Models\Task;
use App\Domain\Tasks\Models\BoardList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller para o sistema de tarefas (Kanban)
 * 
 * Gerencia visualização e manipulação de boards, listas e tarefas
 */
final class TaskController
{
    /**
     * Exibe o Kanban com boards e tarefas
     */
    public function index(ListUserBoardsAction $action): Response
    {
        $user = Auth::user();
        $boards = $action->handle($user);

        return Inertia::render('Tasks/Kanban', [
            'boards' => $boards,
        ]);
    }

    /**
     * Cria uma nova tarefa
     */
    public function store(
        StoreTaskAction $action,
        BoardList $boardList
    ): RedirectResponse {
        $user = Auth::user();
        
        // Valida que a lista pertence ao team do usuário
        abort_unless($boardList->board->team_id === $user->currentTeam->id, 403);

        $data = request()->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'label_ids' => 'nullable|array',
            'label_ids.*' => 'exists:task_labels,id',
            'source' => 'nullable|in:manual,automation,form,system',
            'source_id' => 'nullable|string',
            'source_metadata' => 'nullable|array',
        ]);

        $action->handle($user, $boardList, $data);

        return Redirect::back()->with('success', 'Tarefa criada com sucesso!');
    }

    /**
     * Atualiza uma tarefa existente
     */
    public function update(
        UpdateTaskAction $action,
        Task $task
    ): RedirectResponse {
        $user = Auth::user();
        
        // Valida que a tarefa pertence ao team do usuário
        abort_unless($task->team_id === $user->currentTeam->id, 403);

        $data = request()->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'label_ids' => 'nullable|array',
            'label_ids.*' => 'exists:task_labels,id',
            'is_completed' => 'nullable|boolean',
        ]);

        $action->handle($task, $data);

        return Redirect::back()->with('success', 'Tarefa atualizada!');
    }

    /**
     * Move tarefa entre listas (drag-and-drop)
     */
    public function move(
        MoveTaskAction $action,
        Task $task
    ): RedirectResponse {
        $user = Auth::user();
        
        // Valida que a tarefa pertence ao team do usuário
        abort_unless($task->team_id === $user->currentTeam->id, 403);

        $data = request()->validate([
            'board_list_id' => 'required|exists:board_lists,id',
            'position' => 'required|integer|min:0',
        ]);

        $targetList = BoardList::findOrFail($data['board_list_id']);
        
        // Valida que a lista pertence ao team
        abort_unless($targetList->board->team_id === $user->currentTeam->id, 403);

        $action->handle($task, $targetList, $data['position']);

        return Redirect::back();
    }

    /**
     * Deleta uma tarefa
     */
    public function destroy(
        DeleteTaskAction $action,
        Task $task
    ): RedirectResponse {
        $user = Auth::user();
        
        // Valida que a tarefa pertence ao team do usuário
        abort_unless($task->team_id === $user->currentTeam->id, 403);

        $action->handle($task);

        return Redirect::back()->with('success', 'Tarefa removida!');
    }
}
