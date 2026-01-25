<?php

declare(strict_types=1);

namespace App\Domain\Tasks\Controllers;

use App\Domain\Tasks\Actions\ListUserBoardsAction;
use App\Domain\Tasks\Actions\StoreTaskAction;
use App\Domain\Tasks\Actions\UpdateTaskAction;
use App\Domain\Tasks\Actions\MoveTaskAction;
use App\Domain\Tasks\Actions\DeleteTaskAction;
use App\Domain\Tasks\Actions\ArchiveTaskAction;
use App\Domain\Tasks\Actions\UnarchiveTaskAction;
use App\Domain\Tasks\Actions\StoreBoardListAction;
use App\Domain\Tasks\Actions\UpdateBoardListAction;
use App\Domain\Tasks\Actions\ReorderBoardListsAction;
use App\Domain\Tasks\Models\Task;
use App\Domain\Tasks\Models\Board;
use App\Domain\Tasks\Models\BoardList;
use App\Domain\Tasks\Models\TaskLabel;
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

        // Busca usuários do team para dropdown de responsável
        $teamUsers = $user->currentTeam
            ->allUsers()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ]);

        return Inertia::render('Tasks/Kanban', [
            'boards' => $boards,
            'teamUsers' => $teamUsers,
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
            'board_list_id' => 'sometimes|exists:board_lists,id',
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

    /**
     * Adiciona uma etiqueta à tarefa
     */
    public function storeLabel(Task $task): RedirectResponse
    {
        $user = Auth::user();
        
        // Valida que a tarefa pertence ao team do usuário
        abort_unless($task->team_id === $user->currentTeam->id, 403);

        $data = request()->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7',
        ]);

        $label = TaskLabel::firstOrCreate([
            'team_id' => $user->currentTeam->id,
            'name' => $data['name'],
        ], [
            'color' => $data['color'],
        ]);

        $task->labels()->syncWithoutDetaching([$label->id]);

        return Redirect::back();
    }

    /**
     * Remove uma etiqueta da tarefa
     */
    public function destroyLabel(Task $task, TaskLabel $label): RedirectResponse
    {
        $user = Auth::user();
        
        // Valida que a tarefa pertence ao team do usuário
        abort_unless($task->team_id === $user->currentTeam->id, 403);
        
        // Valida que a etiqueta pertence ao team do usuário
        abort_unless($label->team_id === $user->currentTeam->id, 403);

        $task->labels()->detach($label->id);

        return Redirect::back();
    }

    /**
     * Cria uma nova lista no board
     */
    public function storeList(
        StoreBoardListAction $action,
        Board $board
    ): RedirectResponse {
        $user = Auth::user();
        
        // Valida que o board pertence ao team do usuário
        abort_unless($board->team_id === $user->currentTeam->id, 403);

        $data = request()->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
        ]);

        $action->handle($board, $data);

        return Redirect::back()->with('success', 'Lista criada com sucesso!');
    }

    /**
     * Atualiza uma lista existente
     */
    public function updateList(
        UpdateBoardListAction $action,
        BoardList $boardList
    ): RedirectResponse {
        $user = Auth::user();
        
        // Valida que a lista pertence ao team do usuário
        abort_unless($boardList->board->team_id === $user->currentTeam->id, 403);

        $data = request()->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'nullable|string|max:7',
        ]);

        $action->handle($boardList, $data);

        return Redirect::back()->with('success', 'Lista atualizada!');
    }

    /**
     * Reordena as listas do board
     */
    public function reorderLists(
        ReorderBoardListsAction $action,
        Board $board
    ): RedirectResponse {
        $user = Auth::user();
        
        // Valida que o board pertence ao team do usuário
        abort_unless($board->team_id === $user->currentTeam->id, 403);

        $data = request()->validate([
            'list_ids' => 'required|array',
            'list_ids.*' => 'exists:board_lists,id',
        ]);

        $action->handle($board, $data['list_ids']);

        return Redirect::back();
    }

    /**
     * Arquiva uma tarefa
     */
    public function archive(
        ArchiveTaskAction $action,
        Task $task
    ): RedirectResponse {
        $user = Auth::user();
        
        // Valida que a tarefa pertence ao team do usuário
        abort_unless($task->team_id === $user->currentTeam->id, 403);

        $action->handle($task);

        return Redirect::back()->with('success', 'Tarefa arquivada!');
    }

    /**
     * Desarquiva uma tarefa
     */
    public function unarchive(
        UnarchiveTaskAction $action,
        Task $task
    ): RedirectResponse {
        $user = Auth::user();
        
        // Valida que a tarefa pertence ao team do usuário
        abort_unless($task->team_id === $user->currentTeam->id, 403);

        $action->handle($task);

        return Redirect::back()->with('success', 'Tarefa restaurada!');
    }
}
